<?php

namespace App\Models;

use App\Enums\SectionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class CourseSection extends Model
{
    protected $fillable = [
        'course_id',
        'section_id',
        'order_number',
        'status',
        'automation_rules',
        'opens_at',
        'closes_at',
        'is_required',
    ];

    protected $casts = [
        'status' => SectionStatus::class,
        'automation_rules' => 'array',
        'opens_at' => 'datetime',
        'closes_at' => 'datetime',
        'is_required' => 'boolean',
    ];

    /**
     * Get the course that owns this section assignment
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Get the section for this assignment
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    /**
     * Check if this section is currently accessible
     */
    public function isAccessible(): bool
    {
        return match ($this->status) {
            SectionStatus::OPEN => true,
            SectionStatus::CLOSED => false,
            SectionStatus::AUTOMATED => $this->isAutomationConditionMet(),
        };
    }

    /**
     * Check if automation conditions are met
     */
    protected function isAutomationConditionMet(): bool
    {
        // Check time-based conditions
        if ($this->opens_at && $this->opens_at > now()) {
            return false;
        }

        if ($this->closes_at && $this->closes_at < now()) {
            return false;
        }

        // Check custom automation rules
        if (empty($this->automation_rules)) {
            return true;
        }

        // You can implement custom logic here based on automation_rules
        // For example: prerequisite sections, completion requirements, etc.
        return $this->evaluateAutomationRules();
    }

    /**
     * Evaluate custom automation rules
     */
    protected function evaluateAutomationRules(): bool
    {
        $rules = $this->automation_rules;

        if (empty($rules)) {
            return true;
        }

        // Example automation rules evaluation
        if (isset($rules['prerequisite_sections'])) {
            // Check if prerequisite sections are completed
            // This would require student progress tracking
            return $this->checkPrerequisiteSections($rules['prerequisite_sections']);
        }

        if (isset($rules['minimum_score'])) {
            // Check if student has minimum score in previous sections
            return $this->checkMinimumScore($rules['minimum_score']);
        }

        return true;
    }

    /**
     * Check prerequisite sections (placeholder for future implementation)
     */
    protected function checkPrerequisiteSections(array $prerequisiteSections): bool
    {
        // TODO: Implement when we add student progress tracking
        return true;
    }

    /**
     * Check minimum score requirement (placeholder for future implementation)
     */
    protected function checkMinimumScore(int $minimumScore): bool
    {
        // TODO: Implement when we add grading system
        return true;
    }

    /**
     * Scope for accessible sections
     */
    public function scopeAccessible(Builder $query): Builder
    {
        return $query->where('status', SectionStatus::OPEN)
            ->orWhere(function ($query) {
                $query->where('status', SectionStatus::AUTOMATED)
                      ->where('opens_at', '<=', now())
                      ->where(function ($q) {
                          $q->whereNull('closes_at')
                            ->orWhere('closes_at', '>', now());
                      });
            });
    }

    /**
     * Scope for required sections
     */
    public function scopeRequired(Builder $query): Builder
    {
        return $query->where('is_required', true);
    }

    /**
     * Scope for sections ordered by their sequence
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('order_number');
    }
}