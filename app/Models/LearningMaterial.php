<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;
use App\Enums\LearningMaterialType;

class LearningMaterial extends Model
{
    protected $fillable = [
        'title',
        'description',
        'type',
        'content',
        'video_platform',
        'video_id',
        'video_url',
        'video_metadata',
        'estimated_duration',
        'tags',
        'metadata',
        'is_active',
    ];

    protected $casts = [
        'video_metadata' => 'array',
        'tags' => 'array',
        'metadata' => 'array',
        'is_active' => 'boolean',
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
     * Scope for active materials
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for materials by type
     */
    public function scopeByType(Builder $query, LearningMaterialType $type): Builder
    {
        return $query->where('type', $type->value);
    }

    /**
     * Scope for video materials
     */
    public function scopeVideos(Builder $query): Builder
    {
        return $query->where('type', LearningMaterialType::VIDEO->value);
    }

    /**
     * Scope for text materials
     */
    public function scopeTexts(Builder $query): Builder
    {
        return $query->where('type', LearningMaterialType::TEXT->value);
    }

    /**
     * Get the type as enum
     */
    public function getTypeEnumAttribute(): ?LearningMaterialType
    {
        return $this->type ? LearningMaterialType::from($this->type) : null;
    }

    /**
     * Set the type from enum
     */
    public function setTypeAttribute($value): void
    {
        $this->attributes['type'] = $value;
    }
}
