<?php

namespace App\Services;

use App\Models\User;
use App\Models\Course;
use App\Models\Section;
use App\Models\LearningMaterial;
use App\Models\SectionMaterial;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CompleteStudentProgressService
{
    /**
     * Track material interaction with comprehensive data
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
                    'attempts' => DB::raw('attempts + 1'),
                    'updated_at' => now(),
                ]);
        }
    }

    /**
     * Update material progress with validation
     */
    public function updateMaterialProgress(
        int $studentId,
        string $materialId,
        float $progressPercentage,
        int $timeSpent = 0,
        ?float $score = null
    ): bool {
        $updateData = [
            'progress_percentage' => min(100, max(0, $progressPercentage)),
            'time_spent' => DB::raw("time_spent + {$timeSpent}"),
            'last_accessed_at' => now(),
            'updated_at' => now(),
        ];

        if ($score !== null) {
            $updateData['score'] = max(0, min(100, $score));
        }

        // Determine status based on progress and material type
        $material = LearningMaterial::find($materialId);
        if ($material) {
            $isCompleted = $this->determineCompletionStatus($material, $progressPercentage, $score);
            
            if ($isCompleted) {
                $updateData['status'] = 'completed';
                $updateData['completed_at'] = now();
            } elseif ($progressPercentage > 0) {
                $updateData['status'] = 'in_progress';
            }
        }

        $updated = DB::table('student_progress')
            ->where('student_id', $studentId)
            ->where('learning_material_id', $materialId)
            ->update($updateData);

        if ($updated && isset($updateData['status']) && $updateData['status'] === 'completed') {
            // Check if section is completed
            $this->checkSectionCompletion($studentId, $materialId);
            
            // Check if course is completed
            $progress = DB::table('student_progress')
                ->where('student_id', $studentId)
                ->where('learning_material_id', $materialId)
                ->first();
                
            if ($progress) {
                $this->checkCourseCompletion($studentId, $progress->course_id);
            }
        }

        return $updated > 0;
    }

    /**
     * Determine if material is completed based on type and criteria
     */
    private function determineCompletionStatus(
        LearningMaterial $material, 
        float $progressPercentage, 
        ?float $score
    ): bool {
        switch ($material->content_type) {
            case 'quiz':
                $passingScore = $material->content_data['passing_score'] ?? 70;
                return $score !== null && $score >= $passingScore;
                
            case 'assignment':
                // Assignment is complete when submitted (100% progress)
                return $progressPercentage >= 100;
                
            case 'video':
                // Video complete when watched at least 90%
                return $progressPercentage >= 90;
                
            case 'text':
            case 'document':
                // Text/document complete when fully viewed
                return $progressPercentage >= 100;
                
            default:
                return $progressPercentage >= 100;
        }
    }

    /**
     * Check if section is completed with proper logic
     */
    private function checkSectionCompletion(int $studentId, string $materialId): void
    {
        $progress = DB::table('student_progress')
            ->where('student_id', $studentId)
            ->where('learning_material_id', $materialId)
            ->first();

        if (!$progress) return;

        // Get all required materials in this section
        $requiredMaterials = DB::table('section_materials')
            ->where('section_id', $progress->section_id)
            ->where('is_required', true)
            ->pluck('learning_material_id');

        // Check progress for all required materials
        $completedRequired = DB::table('student_progress')
            ->where('student_id', $studentId)
            ->whereIn('learning_material_id', $requiredMaterials)
            ->where('status', 'completed')
            ->count();

        $totalRequired = $requiredMaterials->count();

        // If all required materials are completed, mark section as completed
        if ($completedRequired >= $totalRequired && $totalRequired > 0) {
            $this->markSectionCompleted($studentId, $progress->section_id);
        }
    }

    /**
     * Mark section as completed
     */
    private function markSectionCompleted(int $studentId, int $sectionId): void
    {
        // Create or update section completion record
        DB::table('student_section_progress')->updateOrInsert(
            [
                'student_id' => $studentId,
                'section_id' => $sectionId,
            ],
            [
                'status' => 'completed',
                'completed_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    /**
     * Check if course is completed
     */
    private function checkCourseCompletion(int $studentId, int $courseId): void
    {
        // Get all required sections in the course
        $requiredSections = DB::table('sections')
            ->where('course_id', $courseId)
            ->where('is_required', true)
            ->pluck('id');

        // Check completed sections
        $completedSections = DB::table('student_section_progress')
            ->where('student_id', $studentId)
            ->whereIn('section_id', $requiredSections)
            ->where('status', 'completed')
            ->count();

        $totalRequired = $requiredSections->count();

        // If all required sections are completed, mark course as completed
        if ($completedSections >= $totalRequired && $totalRequired > 0) {
            $this->markCourseCompleted($studentId, $courseId);
        }
    }

    /**
     * Mark course as completed
     */
    private function markCourseCompleted(int $studentId, int $courseId): void
    {
        DB::table('course_student')->updateOrInsert(
            [
                'student_id' => $studentId,
                'course_id' => $courseId,
            ],
            [
                'status' => 'completed',
                'completed_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    /**
     * Get comprehensive student progress for a course
     */
    public function getStudentCourseProgress(int $studentId, int $courseId): array
    {
        $course = Course::with(['sections.materials'])->find($courseId);
        
        if (!$course) {
            return ['error' => 'Course not found'];
        }

        $sections = [];
        $totalMaterials = 0;
        $completedMaterials = 0;
        $totalTimeSpent = 0;
        $overallScore = 0;
        $scoredMaterials = 0;

        foreach ($course->sections as $section) {
            $sectionProgress = $this->getSectionProgress($studentId, $section->id);
            $materialProgresses = [];

            foreach ($section->materials as $sectionMaterial) {
                $material = $sectionMaterial->learningMaterial;
                if (!$material) continue;

                $materialProgress = DB::table('student_progress')
                    ->where('student_id', $studentId)
                    ->where('learning_material_id', $material->id)
                    ->first();

                $progress = [
                    'material_id' => $material->id,
                    'title' => $material->title,
                    'type' => $material->content_type,
                    'is_required' => $sectionMaterial->is_required,
                    'status' => $materialProgress->status ?? 'not_started',
                    'progress_percentage' => $materialProgress->progress_percentage ?? 0,
                    'time_spent' => $materialProgress->time_spent ?? 0,
                    'score' => $materialProgress->score,
                    'attempts' => $materialProgress->attempts ?? 0,
                    'last_accessed_at' => $materialProgress->last_accessed_at,
                    'completed_at' => $materialProgress->completed_at,
                ];

                $materialProgresses[] = $progress;
                $totalMaterials++;
                
                if ($progress['status'] === 'completed') {
                    $completedMaterials++;
                }
                
                $totalTimeSpent += $progress['time_spent'];
                
                if ($progress['score'] !== null) {
                    $overallScore += $progress['score'];
                    $scoredMaterials++;
                }
            }

            $sections[] = [
                'section_id' => $section->id,
                'title' => $section->title,
                'is_required' => $section->is_required,
                'status' => $sectionProgress['status'],
                'completed_at' => $sectionProgress['completed_at'],
                'materials' => $materialProgresses,
            ];
        }

        return [
            'course_id' => $courseId,
            'course_title' => $course->title,
            'overall_progress_percentage' => $totalMaterials > 0 ? round(($completedMaterials / $totalMaterials) * 100, 2) : 0,
            'total_materials' => $totalMaterials,
            'completed_materials' => $completedMaterials,
            'total_time_spent_minutes' => round($totalTimeSpent / 60, 2),
            'average_score' => $scoredMaterials > 0 ? round($overallScore / $scoredMaterials, 2) : null,
            'sections' => $sections,
        ];
    }

    /**
     * Get section progress
     */
    private function getSectionProgress(int $studentId, int $sectionId): array
    {
        $sectionProgress = DB::table('student_section_progress')
            ->where('student_id', $studentId)
            ->where('section_id', $sectionId)
            ->first();

        return [
            'status' => $sectionProgress->status ?? 'not_started',
            'completed_at' => $sectionProgress->completed_at ?? null,
        ];
    }

    /**
     * Submit quiz answers and calculate score
     */
    public function submitQuiz(
        int $studentId,
        string $materialId,
        array $answers,
        int $timeTaken
    ): array {
        $material = LearningMaterial::find($materialId);
        
        if (!$material || $material->content_type !== 'quiz') {
            return ['error' => 'Invalid quiz material'];
        }

        $quizData = $material->content_data;
        $questions = $quizData['questions'] ?? [];
        
        if (empty($questions)) {
            return ['error' => 'Quiz has no questions'];
        }

        // Calculate score
        $totalQuestions = count($questions);
        $correctAnswers = 0;
        $results = [];

        foreach ($questions as $index => $question) {
            $userAnswer = $answers[$index] ?? null;
            $isCorrect = $this->checkAnswer($question, $userAnswer);
            
            if ($isCorrect) {
                $correctAnswers++;
            }

            $results[] = [
                'question_index' => $index,
                'user_answer' => $userAnswer,
                'is_correct' => $isCorrect,
                'correct_answer' => $this->getCorrectAnswer($question),
                'explanation' => $question['explanation'] ?? null,
            ];
        }

        $score = ($correctAnswers / $totalQuestions) * 100;
        $passingScore = $quizData['passing_score'] ?? 70;
        $passed = $score >= $passingScore;

        // Update progress
        $this->updateMaterialProgress(
            $studentId,
            $materialId,
            $passed ? 100 : 0,
            $timeTaken,
            $score
        );

        // Track submission
        $this->trackMaterialInteraction(
            $studentId,
            $material->course_id ?? 0, // You might need to get this from section
            $material->section_id ?? 0,
            $materialId,
            [
                'action' => 'quiz_submitted',
                'score' => $score,
                'time_taken' => $timeTaken,
                'answers' => $answers,
                'timestamp' => now()->toISOString(),
            ]
        );

        return [
            'score' => $score,
            'passed' => $passed,
            'passing_score' => $passingScore,
            'correct_answers' => $correctAnswers,
            'total_questions' => $totalQuestions,
            'results' => $results,
            'time_taken' => $timeTaken,
        ];
    }

    /**
     * Check if answer is correct for a question
     */
    private function checkAnswer(array $question, $userAnswer): bool
    {
        switch ($question['question_type']) {
            case 'multiple_choice':
                $options = $question['options'] ?? [];
                foreach ($options as $index => $option) {
                    if ($option['is_correct'] && $userAnswer == $index) {
                        return true;
                    }
                }
                return false;

            case 'true_false':
                return $question['correct_answer'] === $userAnswer;

            case 'fill_blank':
            case 'short_answer':
                $correctAnswer = strtolower(trim($question['correct_answer']));
                $userAnswerClean = strtolower(trim($userAnswer));
                return $correctAnswer === $userAnswerClean;

            case 'essay':
                // Essay questions require manual grading
                return false;

            default:
                return false;
        }
    }

    /**
     * Get correct answer for display
     */
    private function getCorrectAnswer(array $question)
    {
        switch ($question['question_type']) {
            case 'multiple_choice':
                $options = $question['options'] ?? [];
                foreach ($options as $index => $option) {
                    if ($option['is_correct']) {
                        return $option['text'];
                    }
                }
                return null;

            case 'true_false':
            case 'fill_blank':
            case 'short_answer':
                return $question['correct_answer'];

            case 'essay':
                return $question['sample_answer'] ?? 'Manual grading required';

            default:
                return null;
        }
    }

    /**
     * Submit assignment
     */
    public function submitAssignment(
        int $studentId,
        string $materialId,
        array $submissionData
    ): array {
        $material = LearningMaterial::find($materialId);
        
        if (!$material || $material->content_type !== 'assignment') {
            return ['error' => 'Invalid assignment material'];
        }

        // Store submission in separate table
        $submissionId = DB::table('assignment_submissions')->insertGetId([
            'student_id' => $studentId,
            'material_id' => $materialId,
            'submission_type' => $submissionData['type'],
            'content' => $submissionData['content'] ?? null,
            'file_path' => $submissionData['file_path'] ?? null,
            'url' => $submissionData['url'] ?? null,
            'submitted_at' => now(),
            'status' => 'submitted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Update progress to show submission
        $this->updateMaterialProgress(
            $studentId,
            $materialId,
            100, // Mark as complete upon submission
            0
        );

        // Track submission
        $this->trackMaterialInteraction(
            $studentId,
            0, // You might need to get course_id from material
            0, // You might need to get section_id from material
            $materialId,
            [
                'action' => 'assignment_submitted',
                'submission_id' => $submissionId,
                'submission_type' => $submissionData['type'],
                'timestamp' => now()->toISOString(),
            ]
        );

        return [
            'success' => true,
            'submission_id' => $submissionId,
            'message' => 'Assignment submitted successfully',
        ];
    }

    /**
     * Get student analytics
     */
    public function getStudentAnalytics(int $studentId, ?int $courseId = null): array
    {
        $query = DB::table('student_progress')
            ->where('student_id', $studentId);
            
        if ($courseId) {
            $query->where('course_id', $courseId);
        }

        $progress = $query->get();

        $analytics = [
            'total_materials' => $progress->count(),
            'completed_materials' => $progress->where('status', 'completed')->count(),
            'in_progress_materials' => $progress->where('status', 'in_progress')->count(),
            'total_time_spent_hours' => round($progress->sum('time_spent') / 3600, 2),
            'average_score' => $progress->whereNotNull('score')->avg('score'),
            'total_attempts' => $progress->sum('attempts'),
        ];

        $analytics['completion_rate'] = $analytics['total_materials'] > 0 
            ? round(($analytics['completed_materials'] / $analytics['total_materials']) * 100, 2)
            : 0;

        return $analytics;
    }
}