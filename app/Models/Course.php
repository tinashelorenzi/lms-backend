<?php

namespace App\Models;

use App\Enums\SectionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;

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
     * Sections in this course
     */
    public function sections(): BelongsToMany
    {
        return $this->belongsToMany(Section::class, 'course_sections')
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
     * Get all learning materials in this course through sections
     */
    public function learningMaterials()
    {
        return $this->hasManyThrough(
            LearningMaterial::class,
            Section::class,
            'id', // Foreign key on sections table
            'id', // Foreign key on learning_materials table
            'id', // Local key on courses table
            'id'  // Local key on sections table
        )->join('course_sections', 'sections.id', '=', 'course_sections.section_id')
         ->join('section_materials', 'sections.id', '=', 'section_materials.section_id')
         ->where('course_sections.course_id', $this->id)
         ->where('section_materials.learning_material_id', 'learning_materials.id')
         ->orderBy('course_sections.order_number')
         ->orderBy('section_materials.order_number');
    }

    /**
     * Get sections with a specific status
     */
    public function sectionsByStatus(SectionStatus $status): BelongsToMany
    {
        return $this->sections()->wherePivot('status', $status);
    }

    /**
     * Get open sections for students
     */
    public function openSections(): BelongsToMany
    {
        return $this->sections()
            ->wherePivot('status', SectionStatus::OPEN)
            ->orWhere(function ($query) {
                $query->wherePivot('status', SectionStatus::AUTOMATED)
                      ->where('course_sections.opens_at', '<=', now())
                      ->where(function ($q) {
                          $q->whereNull('course_sections.closes_at')
                            ->orWhere('course_sections.closes_at', '>', now());
                      });
            });
    }

    /**
     * Get the total estimated duration for the course
     */
    protected function totalEstimatedDuration(): Attribute
    {
        return Attribute::make(
            get: function () {
                return $this->sections()
                    ->with('materials')
                    ->get()
                    ->sum('total_estimated_duration');
            }
        );
    }

    /**
     * Get the total number of learning materials in this course
     */
    protected function totalMaterialsCount(): Attribute
    {
        return Attribute::make(
            get: function () {
                return $this->sections()
                    ->withCount('materials')
                    ->get()
                    ->sum('materials_count');
            }
        );
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

    /**
     * Scope for courses with sections
     */
    public function scopeWithSections(Builder $query): Builder
    {
        return $query->with(['sections' => function ($query) {
            $query->where('sections.is_active', true)
                  ->orderBy('course_sections.order_number');
        }]);
    }

    /**
     * Scope for courses with learning materials
     */
    public function scopeWithLearningMaterials(Builder $query): Builder
    {
        return $query->with(['sections.materials' => function ($query) {
            $query->where('learning_materials.is_active', true)
                  ->orderBy('section_materials.order_number');
        }]);
    }
}