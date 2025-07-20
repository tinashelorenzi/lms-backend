<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Section;
use App\Models\SectionMaterial;
use App\Models\LearningMaterial;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CourseContentService
{
    /**
     * Get course structure with materials
     */
    public function getCourseStructure(int $courseId): array
    {
        try {
            // Get course and sections from MySQL
            $course = Course::with(['sections' => function ($query) {
                $query->orderBy('course_sections.order_number');
            }])->findOrFail($courseId);

            // Get material IDs from pivot table
            $materialIds = DB::table('section_materials')
                ->join('course_sections', 'section_materials.section_id', '=', 'course_sections.section_id')
                ->where('course_sections.course_id', $courseId)
                ->pluck('section_materials.learning_material_id')
                ->toArray();

            // Get materials from MongoDB - FIXED to handle MongoDB properly
            $materials = collect();
            if (!empty($materialIds)) {
                try {
                    $mongoMaterials = LearningMaterial::whereIn('_id', $materialIds)->get();
                    $materials = $mongoMaterials->keyBy('_id');
                } catch (\Exception $e) {
                    Log::warning('Failed to fetch MongoDB materials', [
                        'material_ids' => $materialIds,
                        'error' => $e->getMessage()
                    ]);
                    $materials = collect();
                }
            }

            // Structure the data
            $sections = $course->sections->map(function ($section) use ($materials) {
                $sectionMaterials = DB::table('section_materials')
                    ->where('section_id', $section->id)
                    ->orderBy('order_number')
                    ->get();

                $section->materials = $sectionMaterials->map(function ($pivot) use ($materials) {
                    $material = $materials->get($pivot->learning_material_id);
                    if ($material) {
                        $material->pivot = $pivot;
                        return $material;
                    }
                    return null;
                })->filter();

                return $section;
            });

            return [
                'course' => $course,
                'sections' => $sections,
            ];
        } catch (\Exception $e) {
            Log::error('Error getting course structure', [
                'course_id' => $courseId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'course' => null,
                'sections' => collect(),
            ];
        }
    }

    /**
     * Add material to section - FIXED
     */
    public function addMaterialToSection(int $sectionId, string $materialId, int $orderNumber = null, array $pivotData = []): bool
    {
        try {
            // Check if material exists in MongoDB
            $material = LearningMaterial::find($materialId);
            if (!$material) {
                Log::warning('Material not found in MongoDB', ['material_id' => $materialId]);
                return false;
            }

            // Check if section exists in MySQL
            $section = Section::find($sectionId);
            if (!$section) {
                Log::warning('Section not found in MySQL', ['section_id' => $sectionId]);
                return false;
            }

            // Determine order number if not provided
            if ($orderNumber === null) {
                $orderNumber = DB::table('section_materials')
                    ->where('section_id', $sectionId)
                    ->max('order_number') ?? 0;
                $orderNumber++;
            }

            // Insert into pivot table
            $inserted = DB::table('section_materials')->insert([
                'section_id' => $sectionId,
                'learning_material_id' => $materialId,
                'order_number' => $orderNumber,
                'is_required' => $pivotData['is_required'] ?? true,
                'completion_criteria' => json_encode($pivotData['completion_criteria'] ?? []),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($inserted) {
                Log::info('Material added to section', [
                    'section_id' => $sectionId,
                    'material_id' => $materialId,
                    'order_number' => $orderNumber
                ]);
            }

            return $inserted;
        } catch (\Exception $e) {
            Log::error('Error adding material to section', [
                'section_id' => $sectionId,
                'material_id' => $materialId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get all materials for a course with MongoDB content - FIXED
     */
    public function getCourseMaterials(Course $course): Collection
    {
        try {
            $sections = $course->sections()->with(['sections' => function($query) {
                // Load section details but handle the relationship carefully
            }])->get();
            
            $materials = collect();
            
            foreach ($sections as $section) {
                // Get section materials from pivot table
                $sectionMaterials = DB::table('section_materials')
                    ->where('section_id', $section->id)
                    ->orderBy('order_number')
                    ->get();
                
                foreach ($sectionMaterials as $sectionMaterial) {
                    // Get the MongoDB material content
                    try {
                        $mongoMaterial = LearningMaterial::find($sectionMaterial->learning_material_id);
                        
                        if ($mongoMaterial) {
                            $material = (object) array_merge(
                                (array) $sectionMaterial,
                                [
                                    'mongo_content' => $mongoMaterial,
                                    'title' => $mongoMaterial->title,
                                    'description' => $mongoMaterial->description,
                                    'content_type' => $mongoMaterial->content_type,
                                    'content_data' => $mongoMaterial->content_data,
                                    'statistics' => $mongoMaterial->getStatistics(),
                                ]
                            );
                            
                            $materials->push($material);
                        }
                    } catch (\Exception $e) {
                        Log::warning('Failed to fetch material from MongoDB', [
                            'material_id' => $sectionMaterial->learning_material_id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }
            
            return $materials;
        } catch (\Exception $e) {
            Log::error('Error getting course materials', [
                'course_id' => $course->id,
                'error' => $e->getMessage()
            ]);
            return collect();
        }
    }

    /**
     * Get materials for a specific section - FIXED
     */
    public function getSectionMaterials(Section $section): Collection
    {
        try {
            // Get section materials from pivot table
            $sectionMaterials = DB::table('section_materials')
                ->where('section_id', $section->id)
                ->orderBy('order_number')
                ->get();
            
            $materials = collect();
            
            foreach ($sectionMaterials as $sectionMaterial) {
                try {
                    $mongoMaterial = LearningMaterial::find($sectionMaterial->learning_material_id);
                    
                    if ($mongoMaterial) {
                        $material = (object) array_merge(
                            (array) $sectionMaterial,
                            [
                                'mongo_content' => $mongoMaterial,
                                'title' => $mongoMaterial->title,
                                'description' => $mongoMaterial->description,
                                'content_type' => $mongoMaterial->content_type,
                                'content_data' => $mongoMaterial->content_data,
                                'statistics' => $mongoMaterial->getStatistics(),
                            ]
                        );
                        
                        $materials->push($material);
                    }
                } catch (\Exception $e) {
                    Log::warning('Failed to fetch material from MongoDB', [
                        'material_id' => $sectionMaterial->learning_material_id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            return $materials->sortBy('order_number');
        } catch (\Exception $e) {
            Log::error('Error getting section materials', [
                'section_id' => $section->id,
                'error' => $e->getMessage()
            ]);
            return collect();
        }
    }

    /**
     * Get sections that use a specific material - FIXED
     */
    public function getSectionsForMaterial(string $materialId): Collection
    {
        try {
            $sectionIds = DB::table('section_materials')
                ->where('learning_material_id', $materialId)
                ->pluck('section_id')
                ->toArray();

            if (empty($sectionIds)) {
                return collect();
            }

            return Section::whereIn('id', $sectionIds)->get();
        } catch (\Exception $e) {
            Log::error('Error getting sections for material', [
                'material_id' => $materialId,
                'error' => $e->getMessage()
            ]);
            return collect();
        }
    }

    /**
     * Create a new learning material with MongoDB content - FIXED
     */
    public function createLearningMaterial(array $data): ?LearningMaterial
    {
        DB::beginTransaction();
        
        try {
            // Create the MongoDB material using the model's method
            $mongoMaterial = LearningMaterial::createMaterial($data);

            // If section_id is provided, create the section material relationship
            if (isset($data['section_id'])) {
                $this->addMaterialToSection(
                    $data['section_id'],
                    $mongoMaterial->_id,
                    $data['order_number'] ?? null,
                    [
                        'is_required' => $data['is_required'] ?? true,
                        'completion_criteria' => $data['completion_criteria'] ?? [],
                    ]
                );
            }

            DB::commit();
            
            Log::info('Learning material created', [
                'material_id' => $mongoMaterial->_id,
                'title' => $mongoMaterial->title,
                'type' => $mongoMaterial->content_type
            ]);

            return $mongoMaterial;
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error creating learning material', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            
            return null;
        }
    }

    /**
     * Update learning material - FIXED
     */
    public function updateLearningMaterial(string $materialId, array $data): bool
    {
        try {
            $material = LearningMaterial::find($materialId);
            
            if (!$material) {
                Log::warning('Material not found for update', ['material_id' => $materialId]);
                return false;
            }

            // Update content data if provided
            if (isset($data['content_data'])) {
                $material->updateContent($data['content_data']);
                unset($data['content_data']);
            }

            // Update other fields
            $material->update($data);
            
            Log::info('Learning material updated', [
                'material_id' => $materialId,
                'title' => $material->title
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Error updating learning material', [
                'material_id' => $materialId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Delete learning material - FIXED
     */
    public function deleteLearningMaterial(string $materialId): bool
    {
        DB::beginTransaction();
        
        try {
            // Remove all section material relationships
            DB::table('section_materials')
                ->where('learning_material_id', $materialId)
                ->delete();

            // Archive the MongoDB material
            $material = LearningMaterial::find($materialId);
            if ($material) {
                $material->archive();
            }

            DB::commit();
            
            Log::info('Learning material deleted', ['material_id' => $materialId]);
            
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error deleting learning material', [
                'material_id' => $materialId,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * Reorder materials in a section
     */
    public function reorderSectionMaterials(int $sectionId, array $materialOrder): bool
    {
        DB::beginTransaction();
        
        try {
            foreach ($materialOrder as $index => $materialId) {
                DB::table('section_materials')
                    ->where('section_id', $sectionId)
                    ->where('learning_material_id', $materialId)
                    ->update(['order_number' => $index + 1]);
            }

            DB::commit();
            
            Log::info('Section materials reordered', [
                'section_id' => $sectionId,
                'new_order' => $materialOrder
            ]);
            
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error reordering section materials', [
                'section_id' => $sectionId,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * Sync course data between MySQL and MongoDB
     */
    public function syncCourseData(int $courseId): array
    {
        $results = [
            'synced' => 0,
            'errors' => 0,
            'orphaned_removed' => 0,
        ];

        try {
            // Get all material IDs referenced in section_materials for this course
            $referencedMaterialIds = DB::table('section_materials')
                ->join('course_sections', 'section_materials.section_id', '=', 'course_sections.section_id')
                ->where('course_sections.course_id', $courseId)
                ->pluck('section_materials.learning_material_id')
                ->unique()
                ->toArray();

            // Check which materials actually exist in MongoDB
            $existingMaterials = LearningMaterial::whereIn('_id', $referencedMaterialIds)->pluck('_id')->toArray();
            
            // Find orphaned references
            $orphanedRefs = array_diff($referencedMaterialIds, $existingMaterials);
            
            if (!empty($orphanedRefs)) {
                // Remove orphaned references
                $removed = DB::table('section_materials')
                    ->whereIn('learning_material_id', $orphanedRefs)
                    ->delete();
                
                $results['orphaned_removed'] = $removed;
            }

            $results['synced'] = count($existingMaterials);
            
            Log::info('Course data synced', [
                'course_id' => $courseId,
                'results' => $results
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error syncing course data', [
                'course_id' => $courseId,
                'error' => $e->getMessage()
            ]);
            $results['errors']++;
        }

        return $results;
    }
}