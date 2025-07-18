<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Section extends Model
{
    protected $fillable = [
        'title',
        'description',
        'objectives',
        'estimated_duration',
        'metadata',
        'is_active',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Courses that use this section
     */
    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'course_sections')
            ->withPivot([
                'order_number',
                'status',
                'automation_rules',
                'opens_at',
                'closes_at',
                'is_required'
            ])
            ->withTimestamps()
            ->orderBy('course_sections.order_number');
    }

    /**
     * Learning materials in this section
     */
    public function materials(): BelongsToMany
    {
        return $this->belongsToMany(LearningMaterial::class, 'section_materials')
            ->withPivot([
                'order_number',
                'is_required',
                'completion_criteria'
            ])
            ->withTimestamps()
            ->orderBy('section_materials.order_number');
    }

    /**
     * Get the total estimated duration including all materials
     */
    protected function totalEstimatedDuration(): Attribute
    {
        return Attribute::make(
            get: function () {
                $sectionDuration = $this->estimated_duration ?? 0;
                $materialsDuration = $this->materials->sum('estimated_duration') ?? 0;
                return $sectionDuration + $materialsDuration;
            }
        );
    }

    /**
     * Scope for active sections
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for sections with materials
     */
    public function scopeWithMaterials(Builder $query): Builder
    {
        return $query->with(['materials' => function ($query) {
            $query->where('learning_materials.is_active', true)
                  ->orderBy('section_materials.order_number');
        }]);
    }
}