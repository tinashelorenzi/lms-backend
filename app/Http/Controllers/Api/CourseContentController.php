<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Section;
use App\Models\MongoLearningMaterial;
use App\Services\CourseContentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CourseContentController extends Controller
{
    protected $courseContentService;

    public function __construct(CourseContentService $courseContentService)
    {
        $this->courseContentService = $courseContentService;
    }

    /**
     * Get all materials for a course
     */
    public function getCourseMaterials(Course $course): JsonResponse
    {
        try {
            $materials = $this->courseContentService->getCourseMaterials($course);
            
            return response()->json([
                'success' => true,
                'data' => $materials,
                'message' => 'Course materials retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve course materials: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get materials for a specific section
     */
    public function getSectionMaterials(Section $section): JsonResponse
    {
        try {
            $materials = $this->courseContentService->getSectionMaterials($section);
            
            return response()->json([
                'success' => true,
                'data' => $materials,
                'message' => 'Section materials retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve section materials: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new learning material
     */
    public function createMaterial(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'content' => 'required|string',
            'type' => 'required|string|in:text,video,audio,interactive,assessment,file',
            'format' => 'nullable|string|in:html,markdown,json',
            'section_id' => 'nullable|exists:sections,id',
            'order_number' => 'nullable|integer|min:1',
            'is_required' => 'nullable|boolean',
            'difficulty_level' => 'nullable|string|in:beginner,intermediate,advanced,expert',
            'estimated_duration' => 'nullable|integer|min:0',
            'tags' => 'nullable|array',
            'status' => 'nullable|string|in:draft,published,archived',
        ]);

        try {
            $material = $this->courseContentService->createLearningMaterial(array_merge(
                $request->all(),
                ['created_by' => auth()->id()]
            ));
            
            return response()->json([
                'success' => true,
                'data' => $material,
                'message' => 'Learning material created successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create learning material: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a specific learning material
     */
    public function getMaterial(string $materialId): JsonResponse
    {
        try {
            $material = MongoLearningMaterial::findOrFail($materialId);
            
            return response()->json([
                'success' => true,
                'data' => $material,
                'message' => 'Learning material retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve learning material: ' . $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update a learning material
     */
    public function updateMaterial(Request $request, string $materialId): JsonResponse
    {
        $request->validate([
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'content' => 'nullable|string',
            'type' => 'nullable|string|in:text,video,audio,interactive,assessment,file',
            'format' => 'nullable|string|in:html,markdown,json',
            'difficulty_level' => 'nullable|string|in:beginner,intermediate,advanced,expert',
            'estimated_duration' => 'nullable|integer|min:0',
            'tags' => 'nullable|array',
            'status' => 'nullable|string|in:draft,published,archived',
        ]);

        try {
            $material = $this->courseContentService->updateLearningMaterial($materialId, array_merge(
                $request->all(),
                ['updated_by' => auth()->id()]
            ));
            
            return response()->json([
                'success' => true,
                'data' => $material,
                'message' => 'Learning material updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update learning material: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a learning material
     */
    public function deleteMaterial(string $materialId): JsonResponse
    {
        try {
            $this->courseContentService->deleteLearningMaterial($materialId);
            
            return response()->json([
                'success' => true,
                'message' => 'Learning material deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete learning material: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search learning materials
     */
    public function searchMaterials(Request $request): JsonResponse
    {
        $request->validate([
            'search' => 'nullable|string|max:255',
            'type' => 'nullable|string|in:text,video,audio,interactive,assessment,file',
            'status' => 'nullable|string|in:draft,published,archived',
            'difficulty' => 'nullable|string|in:beginner,intermediate,advanced,expert',
            'tag' => 'nullable|string',
        ]);

        try {
            $materials = $this->courseContentService->searchMaterials(
                $request->get('search', ''),
                $request->only(['type', 'status', 'difficulty', 'tag'])
            );
            
            return response()->json([
                'success' => true,
                'data' => $materials,
                'message' => 'Materials search completed successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to search materials: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get course analytics
     */
    public function getCourseAnalytics(Course $course): JsonResponse
    {
        try {
            $analytics = $this->courseContentService->getCourseAnalytics($course);
            
            return response()->json([
                'success' => true,
                'data' => $analytics,
                'message' => 'Course analytics retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve course analytics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get material recommendations for a student
     */
    public function getRecommendations(Course $course): JsonResponse
    {
        try {
            $recommendations = $this->courseContentService->getMaterialRecommendations(
                auth()->id(),
                $course
            );
            
            return response()->json([
                'success' => true,
                'data' => $recommendations,
                'message' => 'Material recommendations retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve recommendations: ' . $e->getMessage()
            ], 500);
        }
    }
} 