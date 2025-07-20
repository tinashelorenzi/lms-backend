<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use MongoDB\Laravel\Eloquent\SoftDeletes;

class MongoLearningMaterial extends Model
{
    use SoftDeletes;

    protected $connection = 'mongodb';
    protected $collection = 'learning_materials';

    protected $fillable = [
        'title',
        'description',
        'content',
        'type',
        'format',
        'metadata',
        'settings',
        'tags',
        'difficulty_level',
        'estimated_duration',
        'prerequisites',
        'learning_objectives',
        'assessment_criteria',
        'interactive_elements',
        'accessibility_features',
        'version',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'metadata' => 'array',
        'settings' => 'array',
        'tags' => 'array',
        'prerequisites' => 'array',
        'learning_objectives' => 'array',
        'assessment_criteria' => 'array',
        'interactive_elements' => 'array',
        'accessibility_features' => 'array',
        'estimated_duration' => 'integer',
        'version' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the user who created this material
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this material
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope to filter by type
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to filter by status
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter by difficulty level
     */
    public function scopeWithDifficulty($query, $difficulty)
    {
        return $query->where('difficulty_level', $difficulty);
    }

    /**
     * Scope to search by title or description
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%")
              ->orWhere('tags', 'like', "%{$search}%");
        });
    }

    /**
     * Get materials by tag
     */
    public function scopeWithTag($query, $tag)
    {
        return $query->where('tags', 'like', "%{$tag}%");
    }

    /**
     * Get materials within duration range
     */
    public function scopeWithinDuration($query, $minDuration, $maxDuration)
    {
        return $query->whereBetween('estimated_duration', [$minDuration, $maxDuration]);
    }

    /**
     * Get the content as HTML
     */
    public function getContentHtmlAttribute()
    {
        if ($this->format === 'markdown') {
            return \Str::markdown($this->content);
        }
        
        return $this->content;
    }

    /**
     * Get the material's difficulty level as a human-readable string
     */
    public function getDifficultyLevelTextAttribute()
    {
        $levels = [
            'beginner' => 'Beginner',
            'intermediate' => 'Intermediate',
            'advanced' => 'Advanced',
            'expert' => 'Expert'
        ];

        return $levels[$this->difficulty_level] ?? 'Not specified';
    }

    /**
     * Check if material is accessible
     */
    public function isAccessible()
    {
        return $this->status === 'published' && !$this->trashed();
    }

    /**
     * Get material statistics
     */
    public function getStatistics()
    {
        return [
            'word_count' => str_word_count(strip_tags($this->content)),
            'estimated_reading_time' => ceil(str_word_count(strip_tags($this->content)) / 200), // 200 words per minute
            'has_interactive_elements' => !empty($this->interactive_elements),
            'has_assessments' => !empty($this->assessment_criteria),
        ];
    }
} 