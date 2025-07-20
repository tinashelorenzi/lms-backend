<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Section;
use App\Models\LearningMaterial;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CourseContentService
{
    /**
     * Get course structure with materials
     */
    public function getCourseStructure(int $courseId): array
    {
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

        // Get materials from MongoDB
        $materials = LearningMaterial::whereIn('_id', $materialIds)->get()->keyBy('_id');

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
    }

    /**
     * Add material to section
     */
    public function addMaterialToSection(int $sectionId, string $materialId, int $orderNumber = null, array $pivotData = []): bool
    {
        // Check if material exists in MongoDB
        $material = LearningMaterial::find($materialId);
        if (!$material) {
            return false;
        }

        // Check if section exists in MySQL
        $section = Section::find($sectionId);
        if (!$section) {
            return false;
        }

        // Determine order number if not provided
        if ($orderNumber === null) {
            $orderNumber = DB::table('section_materials')
                ->where('section_id', $sectionId)
                ->max('order_number') + 1;
        }

        // Insert into pivot table
        return DB::table('section_materials')->insert([
            'section_id' => $sectionId,
            'learning_material_id' => $materialId,
            'order_number' => $orderNumber,
            'is_required' => $pivotData['is_required'] ?? true,
            'completion_criteria' => json_encode($pivotData['completion_criteria'] ?? []),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Update material order in section
     */
    public function updateMaterialOrder(int $sectionId, array $materialOrders): bool
    {
        try {
            DB::beginTransaction();

            foreach ($materialOrders as $order => $materialId) {
                DB::table('section_materials')
                    ->where('section_id', $sectionId)
                    ->where('learning_material_id', $materialId)
                    ->update(['order_number' => $order + 1]);
            }

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            return false;
        }
    }

    /**
     * Remove material from section
     */
    public function removeMaterialFromSection(int $sectionId, string $materialId): bool
    {
        return DB::table('section_materials')
            ->where('section_id', $sectionId)
            ->where('learning_material_id', $materialId)
            ->delete();
    }

    /**
     * Get sections for a material
     */
    public function getSectionsForMaterial(string $materialId): Collection
    {
        $sectionIds = DB::table('section_materials')
            ->where('learning_material_id', $materialId)
            ->pluck('section_id');

        return Section::whereIn('id', $sectionIds)->get();
    }

    /**
     * Duplicate course structure
     */
    public function duplicateCourseStructure(int $sourceCourseId, int $targetCourseId): bool
    {
        try {
            DB::beginTransaction();

            // Get source course sections
            $sourceSections = DB::table('course_sections')
                ->where('course_id', $sourceCourseId)
                ->orderBy('order_number')
                ->get();

            foreach ($sourceSections as $sourceSection) {
                // Add section to target course
                DB::table('course_sections')->insert([
                    'course_id' => $targetCourseId,
                    'section_id' => $sourceSection->section_id,
                    'order_number' => $sourceSection->order_number,
                    'status' => $sourceSection->status,
                    'automation_rules' => $sourceSection->automation_rules,
                    'opens_at' => $sourceSection->opens_at,
                    'closes_at' => $sourceSection->closes_at,
                    'is_required' => $sourceSection->is_required,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Copy section materials
                $materials = DB::table('section_materials')
                    ->where('section_id', $sourceSection->section_id)
                    ->get();

                foreach ($materials as $material) {
                    DB::table('section_materials')->insert([
                        'section_id' => $sourceSection->section_id,
                        'learning_material_id' => $material->learning_material_id,
                        'order_number' => $material->order_number,
                        'is_required' => $material->is_required,
                        'completion_criteria' => $material->completion_criteria,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            return false;
        }
    }

    /**
     * Get material usage statistics
     */
    public function getMaterialUsageStats(string $materialId): array
    {
        $usage = DB::table('section_materials')
            ->join('course_sections', 'section_materials.section_id', '=', 'course_sections.section_id')
            ->join('courses', 'course_sections.course_id', '=', 'courses.id')
            ->join('sections', 'section_materials.section_id', '=', 'sections.id')
            ->where('section_materials.learning_material_id', $materialId)
            ->select('courses.name as course_name', 'sections.title as section_title')
            ->get();

        return [
            'total_usage' => $usage->count(),
            'courses' => $usage->groupBy('course_name')->keys()->toArray(),
            'detailed_usage' => $usage->toArray(),
        ];
    }
}