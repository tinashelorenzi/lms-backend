<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class SectionMaterial extends Model
{
    protected $fillable = [
        'section_id',
        'learning_material_id',
        'order_number',
        'is_required',
        'completion_criteria',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'completion_criteria' => 'array',
    ];

    /**
     * Get the section that owns this material assignment
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    /**
     * Get the learning material for this assignment
     */
    public function learningMaterial(): BelongsTo
    {
        return $this->belongsTo(LearningMaterial::class);
    }

    /**
     * Check if this material meets completion criteria for a student
     */
    public function isCompletedBy(?User $student = null): bool
    {
        if (!$student) {
            return false;
        }

        // TODO: Implement when we add student progress tracking
        // This would check against student_material_progress table
        return false;
    }

    /**
     * Get completion criteria as a readable format
     */
    public function getCompletionCriteriaDescription(): string
    {
        $criteria = $this->completion_criteria;

        if (empty($criteria)) {
            return 'View the material';
        }

        $descriptions = [];

        if (isset($criteria['minimum_time'])) {
            $descriptions[] = "Spend at least {$criteria['minimum_time']} minutes";
        }

        if (isset($criteria['quiz_score'])) {
            $descriptions[] = "Score at least {$criteria['quiz_score']}% on quiz";
        }

        if (isset($criteria['interaction_required'])) {
            $descriptions[] = "Complete all interactions";
        }

        return implode(', ', $descriptions) ?: 'View the material';
    }

    /**
     * Scope for required materials
     */
    public function scopeRequired(Builder $query): Builder
    {
        return $query->where('is_required', true);
    }

    /**
     * Scope for materials ordered by their sequence
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('order_number');
    }

    /**
     * Scope for materials with specific completion criteria
     */
    public function scopeWithCriteria(Builder $query, string $criteriaType): Builder
    {
        return $query->whereJsonContains('completion_criteria', [$criteriaType => true]);
    }
}