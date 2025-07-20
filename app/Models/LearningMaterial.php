<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class LearningMaterial extends Model
{
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
    ];

    /**
     * Get sections that use this material (MySQL relationship)
     */
    public function sections()
    {
        // We'll handle this through a service since we can't directly relate MongoDB to MySQL
        return app(CourseContentService::class)->getSectionsForMaterial($this->_id);
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

    private function formatQuizContent(): array
    {
        return [
            'type' => 'quiz',
            'questions' => $this->content_data['questions'] ?? [],
            'time_limit' => $this->content_data['time_limit'] ?? null,
            'attempts_allowed' => $this->content_data['attempts_allowed'] ?? 1,
            'randomize_questions' => $this->content_data['randomize_questions'] ?? false,
            'passing_score' => $this->content_data['passing_score'] ?? 70,
        ];
    }

    private function formatAssignmentContent(): array
    {
        return [
            'type' => 'assignment',
            'instructions' => $this->content_data['instructions'] ?? '',
            'due_date' => $this->content_data['due_date'] ?? null,
            'max_points' => $this->content_data['max_points'] ?? 100,
            'submission_types' => $this->content_data['submission_types'] ?? ['file', 'text'],
            'rubric' => $this->content_data['rubric'] ?? [],
        ];
    }

    private function formatInteractiveContent(): array
    {
        return [
            'type' => 'interactive',
            'html_content' => $this->content_data['html_content'] ?? '',
            'css_styles' => $this->content_data['css_styles'] ?? '',
            'javascript' => $this->content_data['javascript'] ?? '',
            'resources' => $this->content_data['resources'] ?? [],
        ];
    }

    /**
     * Scope for active materials
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope by content type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('content_type', $type);
    }

    /**
     * Full text search
     */
    public function scopeSearch($query, string $searchTerm)
    {
        return $query->where(function ($q) use ($searchTerm) {
            $q->where('title', 'like', "%{$searchTerm}%")
              ->orWhere('description', 'like', "%{$searchTerm}%")
              ->orWhere('tags', 'in', [$searchTerm]);
        });
    }
}