<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;

class Course extends Model
{
    protected $fillable = [
        'name',
        'code',
        'description',
        'credits',
        'department',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Teachers assigned to this course
     */
    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'course_teacher', 'course_id', 'teacher_id')
            ->withPivot(['academic_year', 'semester', 'is_primary'])
            ->withTimestamps();
    }

    /**
     * Students enrolled in this course
     */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'course_student', 'course_id', 'student_id')
            ->withPivot(['academic_year', 'semester', 'enrollment_date', 'status', 'grade'])
            ->withTimestamps();
    }

    /**
     * Get primary teacher for a specific academic year and semester
     */
    public function primaryTeacher(?string $academicYear = null, ?string $semester = null)
    {
        $query = $this->teachers()->wherePivot('is_primary', true);
        
        if ($academicYear) {
            $query->wherePivot('academic_year', $academicYear);
        }
        
        if ($semester) {
            $query->wherePivot('semester', $semester);
        }
        
        return $query->first();
    }

    /**
     * Get active students for a specific academic year and semester
     */
    public function activeStudents(?string $academicYear = null, ?string $semester = null)
    {
        $query = $this->students()->wherePivot('status', 'active');
        
        if ($academicYear) {
            $query->wherePivot('academic_year', $academicYear);
        }
        
        if ($semester) {
            $query->wherePivot('semester', $semester);
        }
        
        return $query->get();
    }

    /**
     * Scope for active courses
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for courses by department
     */
    public function scopeByDepartment(Builder $query, string $department): Builder
    {
        return $query->where('department', $department);
    }
}