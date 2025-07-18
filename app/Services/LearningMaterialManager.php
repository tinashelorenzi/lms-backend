<?php

namespace App\Services;

use App\Models\LearningMaterial;
use App\Models\Section;
use App\Models\Course;
use App\Enums\LearningMaterialType;
use App\Services\VideoService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LearningMaterialManager
{
    protected VideoService $videoService;

    public function __construct(VideoService $videoService)
    {
        $this->videoService = $videoService;
    }

    /**
     * Create a new learning material
     */
    public function createMaterial(array $data): LearningMaterial
    {
        return DB::transaction(function () use ($data) {
            // Handle enum conversion if needed
            if (isset($data['type']) && is_string($data['type'])) {
                $data['type'] = LearningMaterialType::from($data['type']);
            }

            $material = new LearningMaterial($data);

            // Handle video-specific data
            if ($material->type === LearningMaterialType::VIDEO && !empty($data['video_url'])) {
                $this->processVideoData($material, $data['video_url']);
            }

            $material->save();

            Log::info('Learning material created', [
                'id' => $material->id,
                'title' => $material->title,
                'type' => $material->type->value,
            ]);

            return $material;
        });
    }

    /**
     * Update an existing learning material
     */
    public function updateMaterial(LearningMaterial $material, array $data): LearningMaterial
    {
        return DB::transaction(function () use ($material, $data) {
            // Handle enum conversion if needed
            if (isset($data['type']) && is_string($data['type'])) {
                $data['type'] = LearningMaterialType::from($data['type']);
            }

            $material->fill($data);

            // Handle video-specific data if URL changed
            if ($material->type === LearningMaterialType::VIDEO && 
                !empty($data['video_url']) && 
                $material->video_url !== $data['video_url']) {
                $this->processVideoData($material, $data['video_url']);
            }

            $material->save();

            Log::info('Learning material updated', [
                'id' => $material->id,
                'title' => $material->title,
            ]);

            return $material;
        });
    }

    /**
     * Process video data and extract metadata
     */
    protected function processVideoData(LearningMaterial $material, string $videoUrl): void
    {
        $videoData = $this->videoService->parseVideoUrl($videoUrl);
        
        $material->video_platform = $videoData['platform'];
        $material->video_id = $videoData['id'];
        $material->video_url = $videoData['url'];

        // Get metadata if possible
        if ($videoData['platform'] !== 'generic' && $videoData['id']) {
            $metadata = $this->videoService->getVideoMetadata($videoData['platform'], $videoData['id']);
            $material->video_metadata = array_merge($material->video_metadata ?? [], $metadata);
        }
    }

    /**
     * Add material to a section
     */
    public function addMaterialToSection(LearningMaterial $material, Section $section, array $options = []): void
    {
        $defaultOptions = [
            'order_number' => $section->materials()->count() + 1,
            'is_required' => true,
            'completion_criteria' => [],
        ];

        $options = array_merge($defaultOptions, $options);

        $section->materials()->attach($material->id, $options);

        Log::info('Material added to section', [
            'material_id' => $material->id,
            'section_id' => $section->id,
            'order' => $options['order_number'],
        ]);
    }

    /**
     * Remove material from a section
     */
    public function removeMaterialFromSection(LearningMaterial $material, Section $section): void
    {
        $section->materials()->detach($material->id);

        Log::info('Material removed from section', [
            'material_id' => $material->id,
            'section_id' => $section->id,
        ]);
    }

    /**
     * Reorder materials within a section
     */
    public function reorderMaterials(Section $section, array $materialOrder): void
    {
        DB::transaction(function () use ($section, $materialOrder) {
            foreach ($materialOrder as $order => $materialId) {
                $section->materials()->updateExistingPivot($materialId, [
                    'order_number' => $order + 1
                ]);
            }
        });

        Log::info('Materials reordered in section', [
            'section_id' => $section->id,
            'order' => $materialOrder,
        ]);
    }

    /**
     * Duplicate a learning material
     */
    public function duplicateMaterial(LearningMaterial $material, string $newTitle = null): LearningMaterial
    {
        $newMaterial = $material->replicate();
        $newMaterial->title = $newTitle ?? $material->title . ' (Copy)';
        $newMaterial->save();

        Log::info('Learning material duplicated', [
            'original_id' => $material->id,
            'new_id' => $newMaterial->id,
        ]);

        return $newMaterial;
    }

    /**
     * Get materials usage statistics
     */
    public function getMaterialUsageStats(LearningMaterial $material): array
    {
        return [
            'sections_count' => $material->sections()->count(),
            'courses_count' => $this->getCoursesUsingMaterial($material)->count(),
            'total_students' => $this->getStudentsAccessingMaterial($material)->count(),
        ];
    }

    /**
     * Get courses that use this material
     */
    public function getCoursesUsingMaterial(LearningMaterial $material)
    {
        return Course::whereHas('sections.materials', function ($query) use ($material) {
            $query->where('learning_materials.id', $material->id);
        });
    }

    /**
     * Get students who have access to this material
     */
    public function getStudentsAccessingMaterial(LearningMaterial $material)
    {
        // This would be implemented when we add student progress tracking
        // For now, return empty collection
        return collect();
    }

    /**
     * Search for materials by various criteria
     */
    public function searchMaterials(array $filters = [])
    {
        $query = LearningMaterial::query();

        if (!empty($filters['type'])) {
            $type = is_string($filters['type']) ? LearningMaterialType::from($filters['type']) : $filters['type'];
            $query->where('type', $type);
        }

        if (!empty($filters['platform'])) {
            $query->where('video_platform', $filters['platform']);
        }

        if (!empty($filters['tags'])) {
            $query->whereJsonContains('tags', $filters['tags']);
        }

        if (!empty($filters['title'])) {
            $query->where('title', 'like', '%' . $filters['title'] . '%');
        }

        if (!empty($filters['active_only'])) {
            $query->where('is_active', true);
        }

        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Get materials that are not used in any section
     */
    public function getUnusedMaterials()
    {
        return LearningMaterial::whereDoesntHave('sections')->get();
    }

    /**
     * Bulk update materials
     */
    public function bulkUpdateMaterials(array $materialIds, array $updateData): int
    {
        return LearningMaterial::whereIn('id', $materialIds)
            ->update($updateData);
    }

    /**
 * Import content from various sources
 */
public function importFromUrl(string $url): ?LearningMaterial
{
    try {
        // Detect content type from URL
        $contentType = $this->detectContentType($url);
        
        switch ($contentType) {
            case 'youtube':
                return $this->importYouTubeVideo($url);
            case 'vimeo':
                return $this->importVimeoVideo($url);
            case 'webpage':
                return $this->importWebpage($url);
            default:
                throw new \Exception('Unsupported content type');
        }
    } catch (\Exception $e) {
        Log::error('Content import failed', [
            'url' => $url,
            'error' => $e->getMessage(),
        ]);
        return null;
    }
}

/**
 * Export learning material to various formats
 */
public function exportMaterial(LearningMaterial $material, string $format): string
{
    $processor = app(ContentProcessor::class);
    
    switch ($format) {
        case 'pdf':
            return $this->exportToPdf($material, $processor);
        case 'word':
            return $this->exportToWord($material, $processor);
        case 'html':
            return $this->exportToHtml($material, $processor);
        case 'scorm':
            return $this->exportToScorm($material, $processor);
        default:
            throw new \Exception('Unsupported export format');
    }
}

/**
 * Create material from template
 */
public function createFromTemplate(string $templateType, array $data = []): LearningMaterial
{
    $templates = [
        'lecture' => [
            'content_format' => ContentFormat::RICH_HTML,
            'allow_latex' => true,
            'allow_embeds' => true,
            'content_blocks' => [
                ['type' => 'heading', 'content' => 'Lecture Title', 'attributes' => ['level' => 1]],
                ['type' => 'paragraph', 'content' => 'Learning objectives...'],
                ['type' => 'heading', 'content' => 'Introduction', 'attributes' => ['level' => 2]],
                ['type' => 'paragraph', 'content' => 'Content goes here...'],
            ]
        ],
        'tutorial' => [
            'content_format' => ContentFormat::BLOCK_EDITOR,
            'allow_latex' => false,
            'allow_embeds' => true,
            'content_blocks' => [
                ['type' => 'heading', 'content' => 'Tutorial: ', 'attributes' => ['level' => 1]],
                ['type' => 'paragraph', 'content' => 'In this tutorial, you will learn...'],
                ['type' => 'heading', 'content' => 'Step 1', 'attributes' => ['level' => 2]],
                ['type' => 'paragraph', 'content' => 'First, we need to...'],
            ]
        ],
        'assessment' => [
            'content_format' => ContentFormat::RICH_HTML,
            'allow_latex' => true,
            'allow_embeds' => false,
            'content_blocks' => [
                ['type' => 'heading', 'content' => 'Assessment', 'attributes' => ['level' => 1]],
                ['type' => 'paragraph', 'content' => 'Instructions...'],
            ]
        ]
    ];
    
    $template = $templates[$templateType] ?? $templates['lecture'];
    $materialData = array_merge($template, $data);
    
    return $this->createMaterial($materialData);
}
}