<?php

namespace App\Filament\Resources\LearningMaterialResource\Pages;

use App\Filament\Resources\LearningMaterialResource;
use Filament\Resources\Pages\Page;

class StudentCourseView extends Page
{
    protected static string $resource = LearningMaterialResource::class;

    protected static string $view = 'filament.resources.learning-material-resource.pages.student-course-view';
}
