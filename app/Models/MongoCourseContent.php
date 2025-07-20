<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use MongoDB\Laravel\Eloquent\SoftDeletes;

class MongoCourseContent extends Model
{
    use SoftDeletes;

    protected $connection = 'mongodb';
    protected $collection = 'course_contents';

    protected $fillable = [
        'course_id',
        'section_id',
        'title',
        'description',
        'content_type', // text, video, audio, interactive, assessment, file
        'content',
        'format', // html, markdown, json, etc.
        'metadata',
        'settings',
        'order_number',
        'is_required',
        'completion_criteria',
        'prerequisites',
        'estimated_duration',
        'difficulty_level',
        'tags',
        'interactive_elements',
        'accessibility_features',
        'version',
        'status',
        'created_by',
        'updated_by',
        'available_from',
        'available_until',
    ];

    protected $casts = [
        'metadata' => 'array',
        'settings' => 'array',
        'completion_criteria' => 'array',
        'prerequisites' => 'array',
        'tags' => 'array',
        'interactive_elements' => 'array',
        'accessibility_features' => 'array',
        'order_number' => 'integer',
        'is_required' => 'boolean',
        'estimated_duration' => 'integer',
        'version' => 'integer',
        'available_from' => 'datetime',
        'available_until' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the course this content belongs to
     */
    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Get the section this content belongs to
     */
    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    /**
     * Get the user who created this content
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this content
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope to filter by course
     */
    public function scopeForCourse($query, $courseId)
    {
        return $query->where('course_id', $courseId);
    }

    /**
     * Scope to filter by section
     */
    public function scopeForSection($query, $sectionId)
    {
        return $query->where('section_id', $sectionId);
    }

    /**
     * Scope to filter by content type
     */
    public function scopeOfType($query, $contentType)
    {
        return $query->where('content_type', $contentType);
    }

    /**
     * Scope to filter by status
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get required content
     */
    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    /**
     * Scope to get optional content
     */
    public function scopeOptional($query)
    {
        return $query->where('is_required', false);
    }

    /**
     * Scope to get currently available content
     */
    public function scopeAvailable($query)
    {
        $now = now();
        return $query->where(function ($q) use ($now) {
            $q->whereNull('available_from')
              ->orWhere('available_from', '<=', $now);
        })->where(function ($q) use ($now) {
            $q->whereNull('available_until')
              ->orWhere('available_until', '>=', $now);
        });
    }

    /**
     * Get the content as HTML
     */
    public function getContentHtmlAttribute()
    {
        if ($this->format === 'markdown') {
            return \Str::markdown($this->content);
        }
        
        if ($this->format === 'json' && is_array($this->content)) {
            // Handle structured content (like interactive elements)
            return $this->renderStructuredContent();
        }
        
        return $this->content;
    }

    /**
     * Render structured content (for interactive elements)
     */
    protected function renderStructuredContent()
    {
        if (!is_array($this->content)) {
            return $this->content;
        }

        $html = '';
        
        foreach ($this->content as $element) {
            switch ($element['type'] ?? 'text') {
                case 'text':
                    $html .= '<div class="content-text">' . ($element['content'] ?? '') . '</div>';
                    break;
                case 'image':
                    $html .= '<div class="content-image"><img src="' . ($element['src'] ?? '') . '" alt="' . ($element['alt'] ?? '') . '"></div>';
                    break;
                case 'video':
                    $html .= '<div class="content-video"><video controls src="' . ($element['src'] ?? '') . '"></video></div>';
                    break;
                case 'quiz':
                    $html .= '<div class="content-quiz" data-quiz-id="' . ($element['id'] ?? '') . '">Quiz content</div>';
                    break;
                default:
                    $html .= '<div class="content-element">' . ($element['content'] ?? '') . '</div>';
            }
        }
        
        return $html;
    }

    /**
     * Check if content is currently available
     */
    public function isCurrentlyAvailable()
    {
        $now = now();
        
        if ($this->available_from && $now < $this->available_from) {
            return false;
        }
        
        if ($this->available_until && $now > $this->available_until) {
            return false;
        }
        
        return $this->status === 'published' && !$this->trashed();
    }

    /**
     * Get content statistics
     */
    public function getStatistics()
    {
        $stats = [
            'word_count' => 0,
            'estimated_reading_time' => 0,
            'has_interactive_elements' => !empty($this->interactive_elements),
            'has_media' => false,
            'media_count' => 0,
        ];

        if ($this->content_type === 'text') {
            $stats['word_count'] = str_word_count(strip_tags($this->content));
            $stats['estimated_reading_time'] = ceil($stats['word_count'] / 200); // 200 words per minute
        }

        if ($this->content_type === 'video') {
            $stats['has_media'] = true;
            $stats['media_count'] = 1;
            $stats['estimated_reading_time'] = $this->estimated_duration ?? 0;
        }

        return $stats;
    }

    /**
     * Get the next content in sequence
     */
    public function getNextContent()
    {
        return static::where('course_id', $this->course_id)
                    ->where('section_id', $this->section_id)
                    ->where('order_number', '>', $this->order_number)
                    ->orderBy('order_number')
                    ->first();
    }

    /**
     * Get the previous content in sequence
     */
    public function getPreviousContent()
    {
        return static::where('course_id', $this->course_id)
                    ->where('section_id', $this->section_id)
                    ->where('order_number', '<', $this->order_number)
                    ->orderBy('order_number', 'desc')
                    ->first();
    }
} 