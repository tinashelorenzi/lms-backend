<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;

class SchoolClass extends Model
{
    protected $fillable = [
        'name',
        'code',
        'description',
        'grade_level',
        'max_students',
        'academic_year',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Students assigned to this class
     */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'class_student', 'school_class_id', 'student_id')
            ->withPivot(['enrollment_date', 'status'])
            ->withTimestamps();
    }

    /**
     * Get active students
     */
    public function activeStudents()
    {
        return $this->students()->wherePivot('status', 'active');
    }

    /**
     * Get student count
     */
    public function getStudentCountAttribute(): int
    {
        return $this->students()->count();
    }

    /**
     * Get active student count
     */
    public function getActiveStudentCountAttribute(): int
    {
        return $this->activeStudents()->count();
    }

    /**
     * Check if class is at capacity
     */
    public function isAtCapacity(): bool
    {
        return $this->active_student_count >= $this->max_students;
    }

    /**
     * Get available spots
     */
    public function getAvailableSpotsAttribute(): int
    {
        return max(0, $this->max_students - $this->active_student_count);
    }

    /**
     * Scope for active classes
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for classes by academic year
     */
    public function scopeByAcademicYear(Builder $query, string $academicYear): Builder
    {
        return $query->where('academic_year', $academicYear);
    }

    /**
     * Scope for classes by grade level
     */
    public function scopeByGradeLevel(Builder $query, string $gradeLevel): Builder
    {
        return $query->where('grade_level', $gradeLevel);
    }
}