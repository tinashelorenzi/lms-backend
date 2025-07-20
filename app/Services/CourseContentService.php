<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Section;
use App\Models\SectionMaterial;
use App\Models\MongoLearningMaterial;
use App\Models\MongoCourseContent;
use App\Models\StudentProgress;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CourseContentService
{
    /**
     * Get all materials for a course with MongoDB content
     */
    public function getCourseMaterials(Course $course): Collection
    {
        $sections = $course->sections()->with('materials')->get();
        
        $materials = collect();
        
        foreach ($sections as $section) {
            $sectionMaterials = $section->materials;
            
            foreach ($sectionMaterials as $sectionMaterial) {
                // Get the MongoDB material content
                $mongoMaterial = MongoLearningMaterial::find($sectionMaterial->learning_material_id);
                
                if ($mongoMaterial) {
                    $material = (object) array_merge(
                        $sectionMaterial->toArray(),
                        [
                            'mongo_content' => $mongoMaterial,
                            'content_html' => $mongoMaterial->content_html ?? '',
                            'statistics' => $mongoMaterial->getStatistics(),
                        ]
                    );
                    
                    $materials->push($material);
                }
            }
        }
        
        return $materials;
    }

    /**
     * Get materials for a specific section
     */
    public function getSectionMaterials(Section $section): Collection
    {
        $sectionMaterials = $section->materials;
        $materials = collect();
        
        foreach ($sectionMaterials as $sectionMaterial) {
            $mongoMaterial = MongoLearningMaterial::find($sectionMaterial->learning_material_id);
            
            if ($mongoMaterial) {
                $material = (object) array_merge(
                    $sectionMaterial->toArray(),
                    [
                        'mongo_content' => $mongoMaterial,
                        'content_html' => $mongoMaterial->content_html ?? '',
                        'statistics' => $mongoMaterial->getStatistics(),
                    ]
                );
                
                $materials->push($material);
            }
        }
        
        return $materials->sortBy('order_number');
    }

    /**
     * Get sections that use a specific material
     */
    public function getSectionsForMaterial(string $materialId): Collection
    {
        return SectionMaterial::where('learning_material_id', $materialId)
            ->with('section')
            ->get()
            ->pluck('section');
    }

    /**
     * Create a new learning material with MongoDB content
     */
    public function createLearningMaterial(array $data): MongoLearningMaterial
    {
        DB::beginTransaction();
        
        try {
            // Create the MongoDB material
            $mongoMaterial = MongoLearningMaterial::create([
                'title' => $data['title'],
                'description' => $data['description'] ?? '',
                'content' => $data['content'] ?? '',
                'type' => $data['type'] ?? 'text',
                'format' => $data['format'] ?? 'html',
                'metadata' => $data['metadata'] ?? [],
                'settings' => $data['settings'] ?? [],
                'tags' => $data['tags'] ?? [],
                'difficulty_level' => $data['difficulty_level'] ?? 'beginner',
                'estimated_duration' => $data['estimated_duration'] ?? 0,
                'prerequisites' => $data['prerequisites'] ?? [],
                'learning_objectives' => $data['learning_objectives'] ?? [],
                'assessment_criteria' => $data['assessment_criteria'] ?? [],
                'interactive_elements' => $data['interactive_elements'] ?? [],
                'accessibility_features' => $data['accessibility_features'] ?? [],
                'version' => 1,
                'status' => $data['status'] ?? 'draft',
                'created_by' => $data['created_by'] ?? auth()->id(),
                'updated_by' => $data['updated_by'] ?? auth()->id(),
            ]);

            // If section_id is provided, create the section material relationship
            if (isset($data['section_id'])) {
                SectionMaterial::create([
                    'section_id' => $data['section_id'],
                    'learning_material_id' => $mongoMaterial->_id,
                    'order_number' => $data['order_number'] ?? 1,
                    'is_required' => $data['is_required'] ?? true,
                    'completion_criteria' => $data['completion_criteria'] ?? null,
                    'settings' => $data['settings'] ?? null,
                    'available_from' => $data['available_from'] ?? null,
                    'available_until' => $data['available_until'] ?? null,
                ]);
            }

            DB::commit();
            return $mongoMaterial;
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update a learning material
     */
    public function updateLearningMaterial(string $materialId, array $data): MongoLearningMaterial
    {
        $mongoMaterial = MongoLearningMaterial::findOrFail($materialId);
        
        $mongoMaterial->update(array_merge($data, [
            'updated_by' => auth()->id(),
            'version' => $mongoMaterial->version + 1,
        ]));
        
        return $mongoMaterial;
    }

    /**
     * Delete a learning material
     */
    public function deleteLearningMaterial(string $materialId): bool
    {
        DB::beginTransaction();
        
        try {
            // Delete the MongoDB material
            $mongoMaterial = MongoLearningMaterial::findOrFail($materialId);
            $mongoMaterial->delete();
            
            // Delete the section material relationships
            SectionMaterial::where('learning_material_id', $materialId)->delete();
            
            // Delete student progress records
            StudentProgress::where('learning_material_id', $materialId)->delete();
            
            DB::commit();
            return true;
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get student progress for a material
     */
    public function getStudentProgress(string $materialId, int $studentId): ?StudentProgress
    {
        return StudentProgress::where('learning_material_id', $materialId)
            ->where('student_id', $studentId)
            ->first();
    }

    /**
     * Update student progress for a material
     */
    public function updateStudentProgress(string $materialId, int $studentId, array $progressData): StudentProgress
    {
        $progress = StudentProgress::updateOrCreate(
            [
                'learning_material_id' => $materialId,
                'student_id' => $studentId,
            ],
            array_merge($progressData, [
                'last_accessed_at' => now(),
            ])
        );
        
        return $progress;
    }

    /**
     * Get course analytics
     */
    public function getCourseAnalytics(Course $course): array
    {
        $sections = $course->sections;
        $totalMaterials = 0;
        $totalDuration = 0;
        $materialTypes = [];
        
        foreach ($sections as $section) {
            $materials = $this->getSectionMaterials($section);
            $totalMaterials += $materials->count();
            
            foreach ($materials as $material) {
                $totalDuration += $material->mongo_content->estimated_duration ?? 0;
                $type = $material->mongo_content->type ?? 'unknown';
                $materialTypes[$type] = ($materialTypes[$type] ?? 0) + 1;
            }
        }
        
        return [
            'total_sections' => $sections->count(),
            'total_materials' => $totalMaterials,
            'total_duration' => $totalDuration,
            'material_types' => $materialTypes,
            'average_duration_per_material' => $totalMaterials > 0 ? round($totalDuration / $totalMaterials, 2) : 0,
        ];
    }

    /**
     * Search learning materials
     */
    public function searchMaterials(string $searchTerm, array $filters = []): Collection
    {
        $query = MongoLearningMaterial::query();
        
        // Apply search
        if ($searchTerm) {
            $query->search($searchTerm);
        }
        
        // Apply filters
        if (isset($filters['type'])) {
            $query->ofType($filters['type']);
        }
        
        if (isset($filters['status'])) {
            $query->withStatus($filters['status']);
        }
        
        if (isset($filters['difficulty'])) {
            $query->withDifficulty($filters['difficulty']);
        }
        
        if (isset($filters['tag'])) {
            $query->withTag($filters['tag']);
        }
        
        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Get material recommendations for a student
     */
    public function getMaterialRecommendations(int $studentId, Course $course): Collection
    {
        // Get student's completed materials
        $completedMaterials = StudentProgress::where('student_id', $studentId)
            ->where('status', 'completed')
            ->pluck('learning_material_id');
        
        // Get all course materials
        $courseMaterials = $this->getCourseMaterials($course);
        
        // Filter out completed materials and return recommendations
        return $courseMaterials->filter(function ($material) use ($completedMaterials) {
            return !$completedMaterials->contains($material->learning_material_id);
        })->take(5);
    }
} 