<?php

namespace App\Services;

use App\Models\User;
use App\Models\Course;
use App\Models\Section;
use App\Models\LearningMaterial;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StudentProgressService
{
    /**
     * Track material interaction
     */
    public function trackMaterialInteraction(
        int $studentId,
        int $courseId,
        int $sectionId,
        string $materialId,
        array $interactionData = []
    ): void {
        $progress = DB::table('student_progress')
            ->where('student_id', $studentId)
            ->where('learning_material_id', $materialId)
            ->first();

        if (!$progress) {
            // Create new progress record
            DB::table('student_progress')->insert([
                'student_id' => $studentId,
                'course_id' => $courseId,
                'section_id' => $sectionId,
                'learning_material_id' => $materialId,
                'status' => 'in_progress',
                'started_at' => now(),
                'last_accessed_at' => now(),
                'interaction_data' => json_encode($interactionData),
                'attempts' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            // Update existing progress
            $existingData = json_decode($progress->interaction_data, true) ?? [];
            $mergedData = array_merge($existingData, $interactionData);

            DB::table('student_progress')
                ->where('id', $progress->id)
                ->update([
                    'last_accessed_at' => now(),
                    'interaction_data' => json_encode($mergedData),
                    'updated_at' => now(),
                ]);
        }
    }

    /**
     * Update material progress
     */
    public function updateMaterialProgress(
        int $studentId,
        string $materialId,
        float $progressPercentage,
        int $timeSpent = 0,
        ?float $score = null
    ): void {
        $updateData = [
            'progress_percentage' => min(100, max(0, $progressPercentage)),
            'time_spent' => DB::raw("time_spent + {$timeSpent}"),
            'last_accessed_at' => now(),
            'updated_at' => now(),
        ];

        if ($score !== null) {
            $updateData['score'] = $score;
        }

        if ($progressPercentage >= 100) {
            $updateData['status'] = 'completed';
            $updateData['completed_at'] = now();
        } elseif ($progressPercentage > 0) {
            $updateData['status'] = 'in_progress';
        }

        DB::table('student_progress')
            ->where('student_id', $studentId)
            ->where('learning_material_id', $materialId)
            ->update($updateData);

        // Check if section is completed
        $this->checkSectionCompletion($studentId, $materialId);
    }

    /**
     * Check if section is completed
     */
    private function checkSectionCompletion(int $studentId, string $materialId): void
    {
        // Get section for this material
        $sectionId = DB::table('student_progress')
            ->where('student_id', $studentId)
            ->where('learning_material_id', $materialId)
            ->value('section_id');

        if (!$sectionId) return;

        // Get all materials in this section
        $sectionMaterials = DB::table('section_materials')
            ->where('section_id', $sectionId)
            ->pluck('learning_material_id');

        // Check progress for all required materials
        $completedMaterials = DB::table('student_progress')
            ->where('student_id', $studentId)
            ->whereIn('learning_material_id', $sectionMaterials)
            ->where('status', 'completed')
            ->count();

        $requiredMaterials = DB::table('section_materials')
            ->where('section_id', $sectionId)
            ->where('is_required', true)
            ->count();

        // If all required materials are completed, mark section as completed
        if ($completedMaterials >= $requiredMaterials) {
            $this->markSectionCompleted($studentId, $sectionId);
        }
    }

    /**
     * Mark section as completed
     */
    private function markSectionCompleted(int $studentId, int $sectionId): void
    {
        // Update section completion in course_student pivot or separate table
        // This is a simplified approach - you might want a separate section_progress table
        
        $courseId = DB::table('course_sections')
            ->where('section_id', $sectionId)
            ->value('course_id');

        if ($courseId) {
            $this->checkCourseCompletion($studentId, $courseId);
        }
    }

    /**
     * Check if course is completed
     */
    private function checkCourseCompletion(int $studentId, int $courseId): void
    {
        // Get all required sections in course
        $requiredSections = DB::table('course_sections')
            ->where('course_id', $courseId)
            ->where('is_required', true)
            ->pluck('section_id');

        $completedSections = 0;
        foreach ($requiredSections as $sectionId) {
            $sectionMaterials = DB::table('section_materials')
                ->where('section_id', $sectionId)
                ->where('is_required', true)
                ->pluck('learning_material_id');

            $completedMaterials = DB::table('student_progress')
                ->where('student_id', $studentId)
                ->whereIn('learning_material_id', $sectionMaterials)
                ->where('status', 'completed')
                ->count();

            if ($completedMaterials >= $sectionMaterials->count()) {
                $completedSections++;
            }
        }

        // Update course completion status
        if ($completedSections >= $requiredSections->count()) {
            DB::table('course_student')
                ->where('student_id', $studentId)
                ->where('course_id', $courseId)
                ->update([
                    'status' => 'completed',
                    'completion_date' => now(),
                ]);
        }
    }

    /**
     * Get student progress for course
     */
    public function getStudentCourseProgress(int $studentId, int $courseId): array
    {
        $courseStructure = app(CourseContentService::class)->getCourseStructure($courseId);
        
        $progressData = [];
        $totalMaterials = 0;
        $completedMaterials = 0;
        $totalTimeSpent = 0;

        foreach ($courseStructure['sections'] as $section) {
            $sectionProgress = [
                'section_id' => $section->id,
                'title' => $section->title,
                'materials' => [],
                'completed_materials' => 0,
                'total_materials' => $section->materials->count(),
                'progress_percentage' => 0,
            ];

            foreach ($section->materials as $material) {
                $materialProgress = DB::table('student_progress')
                    ->where('student_id', $studentId)
                    ->where('learning_material_id', $material->_id)
                    ->first();

                $materialData = [
                    'material_id' => $material->_id,
                    'title' => $material->title,
                    'type' => $material->content_type,
                    'status' => $materialProgress->status ?? 'not_started',
                    'progress_percentage' => $materialProgress->progress_percentage ?? 0,
                    'time_spent' => $materialProgress->time_spent ?? 0,
                    'score' => $materialProgress->score ?? null,
                    'last_accessed' => $materialProgress->last_accessed_at ?? null,
                ];

                $sectionProgress['materials'][] = $materialData;
                
                if ($materialData['status'] === 'completed') {
                    $sectionProgress['completed_materials']++;
                    $completedMaterials++;
                }
                
                $totalMaterials++;
                $totalTimeSpent += $materialData['time_spent'];
            }

            if ($sectionProgress['total_materials'] > 0) {
                $sectionProgress['progress_percentage'] = 
                    ($sectionProgress['completed_materials'] / $sectionProgress['total_materials']) * 100;
            }

            $progressData[] = $sectionProgress;
        }

        return [
            'course' => $courseStructure['course'],
            'sections' => $progressData,
            'overall_progress' => [
                'total_materials' => $totalMaterials,
                'completed_materials' => $completedMaterials,
                'progress_percentage' => $totalMaterials > 0 ? ($completedMaterials / $totalMaterials) * 100 : 0,
                'total_time_spent' => $totalTimeSpent,
            ],
        ];
    }

    /**
     * Get course analytics
     */
    public function getCourseAnalytics(int $courseId, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?? Carbon::now()->subDays(30);
        $endDate = $endDate ?? Carbon::now();

        $analytics = DB::table('course_analytics')
            ->where('course_id', $courseId)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date')
            ->get();

        $studentProgress = DB::table('student_progress')
            ->join('course_student', 'student_progress.student_id', '=', 'course_student.student_id')
            ->where('course_student.course_id', $courseId)
            ->selectRaw('
                COUNT(DISTINCT student_progress.student_id) as total_students,
                COUNT(CASE WHEN student_progress.status = "completed" THEN 1 END) as completed_materials,
                COUNT(*) as total_material_interactions,
                AVG(student_progress.progress_percentage) as avg_progress,
                SUM(student_progress.time_spent) as total_time_spent
            ')
            ->first();

        return [
            'daily_analytics' => $analytics,
            'summary' => [
                'total_students' => $studentProgress->total_students ?? 0,
                'completed_materials' => $studentProgress->completed_materials ?? 0,
                'total_interactions' => $studentProgress->total_material_interactions ?? 0,
                'average_progress' => round($studentProgress->avg_progress ?? 0, 2),
                'total_time_spent' => $studentProgress->total_time_spent ?? 0,
            ],
        ];
    }

    /**
     * Generate daily analytics
     */
    public function generateDailyAnalytics(int $courseId, Carbon $date): void
    {
        $analytics = DB::table('course_student')
            ->leftJoin('student_progress', function ($join) use ($courseId) {
                $join->on('course_student.student_id', '=', 'student_progress.student_id')
                     ->where('student_progress.course_id', $courseId);
            })
            ->where('course_student.course_id', $courseId)
            ->selectRaw('
                COUNT(DISTINCT course_student.student_id) as total_enrollments,
                COUNT(DISTINCT CASE WHEN student_progress.last_accessed_at >= ? THEN student_progress.student_id END) as active_students,
                COUNT(DISTINCT CASE WHEN course_student.status = "completed" THEN course_student.student_id END) as completed_students,
                AVG(CASE WHEN student_progress.progress_percentage > 0 THEN student_progress.progress_percentage END) as avg_completion_rate,
                SUM(student_progress.time_spent) / 60 as total_time_minutes,
                AVG(student_progress.score) as average_score
            ', [$date->startOfDay()])
            ->first();

        DB::table('course_analytics')->updateOrInsert(
            ['course_id' => $courseId, 'date' => $date->toDateString()],
            [
                'total_enrollments' => $analytics->total_enrollments ?? 0,
                'active_students' => $analytics->active_students ?? 0,
                'completed_students' => $analytics->completed_students ?? 0,
                'average_completion_rate' => $analytics->avg_completion_rate ?? 0,
                'total_time_spent' => $analytics->total_time_minutes ?? 0,
                'average_score' => $analytics->average_score ?? null,
                'updated_at' => now(),
            ]
        );
    }
}