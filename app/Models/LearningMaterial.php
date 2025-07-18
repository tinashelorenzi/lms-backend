<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Enums\ContentFormat;
use App\Services\ContentProcessor;

class LearningMaterial extends Model
{
    protected $fillable = [
        'title',
        'description',
        'content',  // Keep for backward compatibility
        'content_format',
        'content_raw',
        'content_compiled',
        'embedded_media',
        'editor_config',
        'allow_latex',
        'allow_embeds',
        'content_blocks',
        'estimated_duration',
        'tags',
        'metadata',
        'is_active',
    ];

    protected $casts = [
        'embedded_media' => 'array',
        'editor_config' => 'array',
        'content_blocks' => 'array',
        'tags' => 'array',
        'metadata' => 'array',
        'is_active' => 'boolean',
        'allow_latex' => 'boolean',
        'allow_embeds' => 'boolean',
        'content_format' => ContentFormat::class,
    ];

    /**
     * Sections that use this learning material
     */
    public function sections(): BelongsToMany
    {
        return $this->belongsToMany(Section::class, 'section_materials')
            ->withPivot([
                'order_number',
                'is_required',
                'completion_criteria'
            ])
            ->withTimestamps()
            ->orderBy('section_materials.order_number');
    }

    /**
     * Get compiled content with all processing applied
     */
    protected function compiledContent(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->content_compiled) {
                    return $this->content_compiled;
                }
                
                // Auto-compile from raw content if needed
                return app(ContentProcessor::class)->compile($this);
            }
        );
    }

    /**
     * Get content type based on analysis
     */
    public function getContentTypeAttribute(): string
    {
        $media = $this->embedded_media ?? [];
        $hasVideo = collect($media)->where('type', 'video')->isNotEmpty();
        $hasText = !empty($this->content_raw) || !empty($this->content);
        
        if ($hasVideo && $hasText) return 'mixed';
        if ($hasVideo) return 'video';
        if ($hasText) return 'text';
        return 'empty';
    }

    /**
     * Get estimated reading/viewing time
     */
    public function getEstimatedTimeAttribute(): int
    {
        if ($this->estimated_duration) {
            return $this->estimated_duration;
        }

        // Auto-calculate based on content
        $time = 0;
        
        // Text reading time (200 words per minute)
        $wordCount = str_word_count(strip_tags($this->content_raw ?? $this->content ?? ''));
        $time += ceil($wordCount / 200);
        
        // Video time
        $media = $this->embedded_media ?? [];
        foreach ($media as $item) {
            if ($item['type'] === 'video' && isset($item['duration'])) {
                $time += ceil($item['duration'] / 60); // Convert seconds to minutes
            }
        }
        
        return max(1, $time); // Minimum 1 minute
    }

    /**
     * Scope for active materials
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for materials with specific content format
     */
    public function scopeByFormat(Builder $query, ContentFormat $format): Builder
    {
        return $query->where('content_format', $format);
    }

    /**
     * Scope for materials containing video
     */
    public function scopeWithVideo(Builder $query): Builder
    {
        return $query->whereJsonContains('embedded_media', ['type' => 'video']);
    }

    /**
     * Scope for materials with LaTeX
     */
    public function scopeWithLatex(Builder $query): Builder
    {
        return $query->where('allow_latex', true);
    }
}