<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use MongoDB\Laravel\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;

class LearningMaterial extends Model
{
    use SoftDeletes;

    protected $connection = 'mongodb';
    protected $collection = 'learning_materials';

    protected $fillable = [
        'title',
        'description',
        'content_type', // video, document, quiz, assignment, etc.
        'content_data', // flexible content storage
        'metadata',
        'settings',
        'estimated_duration',
        'difficulty_level',
        'prerequisites',
        'learning_objectives',
        'tags',
        'is_active',
        'version',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'content_data' => 'array',
        'metadata' => 'array',
        'settings' => 'array',
        'prerequisites' => 'array',
        'learning_objectives' => 'array',
        'tags' => 'array',
        'is_active' => 'boolean',
        'estimated_duration' => 'integer',
        'version' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * FIXED: Get sections that use this material (using service instead of direct relationship)
     */
    public function sections()
    {
        // Use a service to handle the cross-database relationship
        return app(\App\Services\CourseContentService::class)->getSectionsForMaterial($this->_id);
    }

    /**
     * FIXED: Active scope for MongoDB
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * FIXED: Scope for materials by type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('content_type', $type);
    }

    /**
     * Get the content based on type
     */
    protected function formattedContent(): Attribute
    {
        return Attribute::make(
            get: function () {
                return match ($this->content_type) {
                    'video' => $this->formatVideoContent(),
                    'document' => $this->formatDocumentContent(),
                    'quiz' => $this->formatQuizContent(),
                    'assignment' => $this->formatAssignmentContent(),
                    'interactive' => $this->formatInteractiveContent(),
                    default => $this->content_data
                };
            }
        );
    }

    /**
     * Get statistics for this material
     */
    public function getStatistics(): array
    {
        try {
            // Get usage statistics via service
            $service = app(\App\Services\CourseContentService::class);
            $sectionsCount = $service->getSectionsForMaterial($this->_id)->count();
            
            return [
                'sections_using' => $sectionsCount,
                'total_views' => $this->metadata['total_views'] ?? 0,
                'average_completion_time' => $this->metadata['avg_completion_time'] ?? 0,
                'difficulty_rating' => $this->difficulty_level,
                'estimated_duration' => $this->estimated_duration,
            ];
        } catch (\Exception $e) {
            return [
                'sections_using' => 0,
                'total_views' => 0,
                'average_completion_time' => 0,
                'difficulty_rating' => $this->difficulty_level,
                'estimated_duration' => $this->estimated_duration,
            ];
        }
    }

    /**
     * Format video content
     */
    private function formatVideoContent(): array
    {
        return [
            'type' => 'video',
            'url' => $this->content_data['url'] ?? '',
            'duration' => $this->content_data['duration'] ?? 0,
            'subtitles' => $this->content_data['subtitles'] ?? [],
            'chapters' => $this->content_data['chapters'] ?? [],
            'quality_options' => $this->content_data['quality_options'] ?? [],
        ];
    }

    /**
     * Format document content
     */
    private function formatDocumentContent(): array
    {
        return [
            'type' => 'document',
            'file_url' => $this->content_data['file_url'] ?? '',
            'file_type' => $this->content_data['file_type'] ?? 'pdf',
            'pages' => $this->content_data['pages'] ?? 1,
            'download_allowed' => $this->content_data['download_allowed'] ?? true,
        ];
    }

    /**
     * Format quiz content
     */
    private function formatQuizContent(): array
    {
        return [
            'type' => 'quiz',
            'questions' => $this->content_data['questions'] ?? [],
            'time_limit' => $this->content_data['time_limit'] ?? null,
            'attempts_allowed' => $this->content_data['attempts_allowed'] ?? 1,
            'passing_score' => $this->content_data['passing_score'] ?? 70,
            'randomize_questions' => $this->content_data['randomize_questions'] ?? false,
            'show_results' => $this->content_data['show_results'] ?? true,
        ];
    }

    /**
     * Format assignment content
     */
    private function formatAssignmentContent(): array
    {
        return [
            'type' => 'assignment',
            'instructions' => $this->content_data['instructions'] ?? '',
            'due_date' => $this->content_data['due_date'] ?? null,
            'max_points' => $this->content_data['max_points'] ?? 100,
            'submission_types' => $this->content_data['submission_types'] ?? ['text'],
            'rubric' => $this->content_data['rubric'] ?? [],
            'late_penalty' => $this->content_data['late_penalty'] ?? 0,
        ];
    }

    /**
     * Format interactive content
     */
    private function formatInteractiveContent(): array
    {
        return [
            'type' => 'interactive',
            'html_content' => $this->content_data['html_content'] ?? '',
            'interactive_elements' => $this->content_data['interactive_elements'] ?? [],
            'completion_criteria' => $this->content_data['completion_criteria'] ?? [],
            'tracking_enabled' => $this->content_data['tracking_enabled'] ?? true,
        ];
    }

    /**
     * Create a new learning material with validation
     */
    public static function createMaterial(array $data): self
    {
        // Validate required fields
        if (empty($data['title'])) {
            throw new \InvalidArgumentException('Title is required');
        }

        if (empty($data['content_type'])) {
            throw new \InvalidArgumentException('Content type is required');
        }

        // Set defaults
        $data = array_merge([
            'is_active' => true,
            'version' => 1,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
            'metadata' => [],
            'settings' => [],
            'tags' => [],
            'prerequisites' => [],
            'learning_objectives' => [],
            'estimated_duration' => 0,
            'difficulty_level' => 'beginner',
        ], $data);

        return static::create($data);
    }

    /**
     * Update material content
     */
    public function updateContent(array $contentData): bool
    {
        $this->content_data = $contentData;
        $this->version = $this->version + 1;
        $this->updated_by = auth()->id();
        
        return $this->save();
    }

    /**
     * Archive this material (soft delete)
     */
    public function archive(): bool
    {
        $this->is_active = false;
        $this->save();
        
        return $this->delete();
    }

    /**
     * Duplicate this material
     */
    public function duplicate(array $overrides = []): self
    {
        $data = $this->toArray();
        
        // Remove unique identifiers
        unset($data['_id'], $data['created_at'], $data['updated_at']);
        
        // Apply overrides
        $data = array_merge($data, $overrides, [
            'title' => $overrides['title'] ?? $this->title . ' (Copy)',
            'version' => 1,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);
        
        return static::create($data);
    }
}