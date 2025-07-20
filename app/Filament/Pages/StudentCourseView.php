<?php

namespace App\Filament\Pages;

use App\Models\Course;
use App\Models\Section;
use App\Enums\SectionStatus;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;

class StudentCourseView extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    protected static ?string $navigationLabel = 'My Courses';
    protected static ?string $navigationGroup = 'Student Portal';
    protected static string $view = 'filament.pages.student-course-view';

    public function getCourses()
    {
        // This would typically filter by the authenticated student
        // For now, we'll show all active courses with their sections
        return Course::active()
            ->with(['sections' => function ($query) {
                $query->where('sections.is_active', true)
                      ->wherePivot('status', '!=', SectionStatus::DRAFT->value)
                      ->orderBy('course_sections.order_number');
            }, 'sections.materials' => function ($query) {
                $query->where('learning_materials.is_active', true)
                      ->orderBy('section_materials.order_number');
            }])
            ->get();
    }

    public function getSectionProgress(Section $section): array
    {
        // Mock progress data - in real implementation, you'd check user progress
        $totalMaterials = $section->materials->count();
        $completedMaterials = rand(0, $totalMaterials);
        
        return [
            'completed' => $completedMaterials,
            'total' => $totalMaterials,
            'percentage' => $totalMaterials > 0 ? round(($completedMaterials / $totalMaterials) * 100) : 0
        ];
    }
}