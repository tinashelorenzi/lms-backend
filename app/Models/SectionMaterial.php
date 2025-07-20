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

        $progress = DB::table('student_progress')
            ->where('student_id', $student->id)
            ->where('learning_material_id', $this->learning_material_id)
            ->first();

        if (!$progress) {
            return false;
        }

        // Check if marked as completed
        if ($progress->status === 'completed') {
            return true;
        }

        // Check completion criteria if specified
        $criteria = $this->completion_criteria;
        if (empty($criteria)) {
            // Default criteria: just need to view/interact
            return $progress->progress_percentage >= 100;
        }

        $criteriasMet = true;

        // Check minimum time requirement
        if (isset($criteria['minimum_time'])) {
            $requiredMinutes = $criteria['minimum_time'];
            $timeSpentMinutes = ($progress->time_spent ?? 0) / 60;
            if ($timeSpentMinutes < $requiredMinutes) {
                $criteriasMet = false;
            }
        }

        // Check quiz score requirement
        if (isset($criteria['quiz_score'])) {
            $requiredScore = $criteria['quiz_score'];
            if (($progress->score ?? 0) < $requiredScore) {
                $criteriasMet = false;
            }
        }

        // Check interaction requirement
        if (isset($criteria['interaction_required']) && $criteria['interaction_required']) {
            $interactionData = json_decode($progress->interaction_data, true) ?? [];
            if (empty($interactionData)) {
                $criteriasMet = false;
            }
        }

        return $criteriasMet;
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