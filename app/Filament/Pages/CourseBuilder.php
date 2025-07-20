<?php

namespace App\Filament\Pages;

use App\Models\Course;
use App\Models\Section;
use App\Models\LearningMaterial;
use App\Services\CourseContentService;
use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section as FormSection;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Builder\Block;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CourseBuilder extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationLabel = 'Course Builder';
    protected static ?string $navigationGroup = 'Course Management';
    protected static string $view = 'filament.pages.course-builder';

    public ?array $data = [];
    public ?int $selectedCourseId = null;
    public ?array $courseStructure = [];

    protected CourseContentService $courseContentService;

    public function boot(CourseContentService $courseContentService)
    {
        $this->courseContentService = $courseContentService;
    }

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                FormSection::make('Course Selection')
                    ->schema([
                        Select::make('selectedCourseId')
                            ->label('Select Course to Edit')
                            ->options(function () {
                                try {
                                    return Course::where('is_active', true)
                                        ->whereNotNull('name')
                                        ->where('name', '!=', '')
                                        ->pluck('name', 'id')
                                        ->filter();
                                } catch (\Exception $e) {
                                    Log::error('Error loading courses for builder', ['error' => $e->getMessage()]);
                                    return [];
                                }
                            })
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(function ($state) {
                                if ($state) {
                                    $this->loadCourseStructure($state);
                                }
                            }),
                    ]),

                FormSection::make('Course Structure')
                    ->schema([
                        Repeater::make('sections')
                            ->label('Course Sections')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('title')
                                            ->label('Section Title')
                                            ->required()
                                            ->maxLength(255),
                                        
                                        Select::make('section_id')
                                            ->label('Existing Section')
                                            ->options(function () {
                                                try {
                                                    return Section::where('is_active', true)
                                                        ->whereNotNull('title')
                                                        ->where('title', '!=', '')
                                                        ->pluck('title', 'id')
                                                        ->filter();
                                                } catch (\Exception $e) {
                                                    return [];
                                                }
                                            })
                                            ->searchable()
                                            ->placeholder('Create new or select existing'),
                                    ]),
                                
                                Textarea::make('description')
                                    ->label('Section Description')
                                    ->rows(2),
                                
                                Grid::make(3)
                                    ->schema([
                                        Toggle::make('is_required')
                                            ->label('Required Section')
                                            ->default(true),
                                        
                                        DateTimePicker::make('opens_at')
                                            ->label('Opens At'),
                                        
                                        DateTimePicker::make('closes_at')
                                            ->label('Closes At'),
                                    ]),
                                
                                Repeater::make('materials')
                                    ->label('Learning Materials')
                                    ->schema([
                                        Select::make('type')
                                            ->label('Material Type')
                                            ->options([
                                                'text' => 'Text/HTML Content',
                                                'video' => 'Video',
                                                'document' => 'Document/File',
                                                'quiz' => 'Quiz/Assessment',
                                                'assignment' => 'Assignment',
                                                'interactive' => 'Interactive Content',
                                            ])
                                            ->required()
                                            ->live(),
                                        
                                        TextInput::make('title')
                                            ->label('Material Title')
                                            ->required()
                                            ->maxLength(255),
                                        
                                        Textarea::make('description')
                                            ->label('Description')
                                            ->rows(2),
                                        
                                        // Dynamic fields based on material type
                                        TextInput::make('video_url')
                                            ->label('Video URL')
                                            ->url()
                                            ->visible(fn ($get) => $get('type') === 'video'),
                                        
                                        TextInput::make('duration')
                                            ->label('Duration (minutes)')
                                            ->numeric()
                                            ->visible(fn ($get) => in_array($get('type'), ['video', 'quiz'])),
                                        
                                        TextInput::make('file_url')
                                            ->label('File URL')
                                            ->url()
                                            ->visible(fn ($get) => $get('type') === 'document'),
                                        
                                        Select::make('file_type')
                                            ->label('File Type')
                                            ->options([
                                                'pdf' => 'PDF',
                                                'doc' => 'Word Document',
                                                'ppt' => 'PowerPoint',
                                                'xlsx' => 'Excel',
                                            ])
                                            ->visible(fn ($get) => $get('type') === 'document'),
                                        
                                        Toggle::make('download_allowed')
                                            ->label('Allow Download')
                                            ->default(true)
                                            ->visible(fn ($get) => $get('type') === 'document'),
                                        
                                        TextInput::make('time_limit')
                                            ->label('Time Limit (minutes)')
                                            ->numeric()
                                            ->visible(fn ($get) => $get('type') === 'quiz'),
                                        
                                        TextInput::make('attempts_allowed')
                                            ->label('Attempts Allowed')
                                            ->numeric()
                                            ->default(1)
                                            ->visible(fn ($get) => $get('type') === 'quiz'),
                                        
                                        TextInput::make('passing_score')
                                            ->label('Passing Score (%)')
                                            ->numeric()
                                            ->default(70)
                                            ->visible(fn ($get) => $get('type') === 'quiz'),
                                        
                                        Textarea::make('instructions')
                                            ->label('Instructions')
                                            ->rows(3)
                                            ->visible(fn ($get) => $get('type') === 'assignment'),
                                        
                                        DateTimePicker::make('due_date')
                                            ->label('Due Date')
                                            ->visible(fn ($get) => $get('type') === 'assignment'),
                                        
                                        TextInput::make('max_points')
                                            ->label('Maximum Points')
                                            ->numeric()
                                            ->default(100)
                                            ->visible(fn ($get) => $get('type') === 'assignment'),
                                        
                                        Toggle::make('is_required')
                                            ->label('Required Material')
                                            ->default(true),
                                    ])
                                    ->reorderable()
                                    ->itemLabel(fn (array $state): ?string => $state['title'] ?? 'New Material')
                                    ->addActionLabel('Add Learning Material')
                                    ->defaultItems(0)
                                    ->collapsed(),
                            ])
                            ->reorderable()
                            ->itemLabel(fn (array $state): ?string => $state['title'] ?? 'New Section')
                            ->addActionLabel('Add Section')
                            ->defaultItems(0)
                            ->collapsed(),
                    ])
                    ->visible(fn () => $this->selectedCourseId !== null),
            ])
            ->statePath('data');
    }

    public function loadCourseStructure(?int $courseId): void
    {
        if (!$courseId) {
            $this->courseStructure = [];
            return;
        }

        try {
            $this->selectedCourseId = $courseId;
            
            // Get course structure using the service
            $structure = $this->courseContentService->getCourseStructure($courseId);
            
            if (!$structure['course']) {
                throw new \Exception('Course not found');
            }

            $sectionsData = [];
            foreach ($structure['sections'] as $section) {
                $materialsData = [];
                
                // Safely handle materials - they might not exist yet
                if (isset($section->materials) && $section->materials) {
                    foreach ($section->materials as $material) {
                        $mongoMaterial = null;
                        
                        // Safely get mongo material
                        if (isset($material->mongo_content)) {
                            $mongoMaterial = $material->mongo_content;
                        } elseif (isset($material->learning_material_id)) {
                            try {
                                $mongoMaterial = LearningMaterial::find($material->learning_material_id);
                            } catch (\Exception $e) {
                                Log::warning('Could not load mongo material', [
                                    'material_id' => $material->learning_material_id,
                                    'error' => $e->getMessage()
                                ]);
                            }
                        }
                        
                        $materialData = [
                            'type' => $mongoMaterial->content_type ?? 'text',
                            'title' => $mongoMaterial->title ?? $material->title ?? 'Untitled Material',
                            'description' => $mongoMaterial->description ?? $material->description ?? '',
                            'is_required' => $material->pivot->is_required ?? true,
                        ];
                        
                        // Add type-specific data safely
                        if ($mongoMaterial && isset($mongoMaterial->content_data) && is_array($mongoMaterial->content_data)) {
                            $contentData = $mongoMaterial->content_data;
                            
                            switch ($mongoMaterial->content_type) {
                                case 'video':
                                    $materialData['video_url'] = $contentData['url'] ?? '';
                                    $materialData['duration'] = $contentData['duration'] ?? 0;
                                    break;
                                case 'document':
                                    $materialData['file_url'] = $contentData['file_url'] ?? '';
                                    $materialData['file_type'] = $contentData['file_type'] ?? 'pdf';
                                    $materialData['download_allowed'] = $contentData['download_allowed'] ?? true;
                                    break;
                                case 'quiz':
                                    $materialData['time_limit'] = $contentData['time_limit'] ?? null;
                                    $materialData['attempts_allowed'] = $contentData['attempts_allowed'] ?? 1;
                                    $materialData['passing_score'] = $contentData['passing_score'] ?? 70;
                                    break;
                                case 'assignment':
                                    $materialData['instructions'] = $contentData['instructions'] ?? '';
                                    $materialData['due_date'] = $contentData['due_date'] ?? null;
                                    $materialData['max_points'] = $contentData['max_points'] ?? 100;
                                    break;
                            }
                        }
                        
                        $materialsData[] = $materialData;
                    }
                }
                
                $sectionsData[] = [
                    'section_id' => $section->id,
                    'title' => $section->title ?? 'Untitled Section',
                    'description' => $section->description ?? '',
                    'is_required' => $section->pivot->is_required ?? true,
                    'opens_at' => $section->pivot->opens_at ?? null,
                    'closes_at' => $section->pivot->closes_at ?? null,
                    'materials' => $materialsData,
                ];
            }

            $this->form->fill([
                'selectedCourseId' => $courseId,
                'sections' => $sectionsData,
            ]);

            $this->courseStructure = $structure;

        } catch (\Exception $e) {
            Log::error('Error loading course structure', [
                'course_id' => $courseId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            Notification::make()
                ->title('Error loading course structure')
                ->body($e->getMessage())
                ->danger()
                ->send();
                
            // Set empty structure to prevent further errors
            $this->courseStructure = ['course' => null, 'sections' => collect()];
        }
    }

    public function save(): void
    {
        try {
            $data = $this->form->getState();
            
            if (!$data['selectedCourseId']) {
                throw new \Exception('Please select a course first.');
            }

            DB::beginTransaction();

            // Clear existing course sections
            DB::table('course_sections')
                ->where('course_id', $data['selectedCourseId'])
                ->delete();

            $sectionOrder = 1;
            foreach ($data['sections'] ?? [] as $sectionData) {
                // Handle the new structure (no longer wrapped in type/data)
                $section = $sectionData;
                
                // Create or get section
                $sectionId = $section['section_id'] ?? null;
                if (!$sectionId) {
                    $newSection = Section::create([
                        'title' => $section['title'],
                        'description' => $section['description'] ?? '',
                        'is_active' => true,
                    ]);
                    $sectionId = $newSection->id;
                } else {
                    // Update existing section
                    Section::where('id', $sectionId)->update([
                        'title' => $section['title'],
                        'description' => $section['description'] ?? '',
                    ]);
                }

                // Add section to course
                DB::table('course_sections')->insert([
                    'course_id' => $data['selectedCourseId'],
                    'section_id' => $sectionId,
                    'order_number' => $sectionOrder++,
                    'status' => 'open',
                    'is_required' => $section['is_required'] ?? true,
                    'opens_at' => $section['opens_at'] ?? null,
                    'closes_at' => $section['closes_at'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Clear existing materials for this section
                DB::table('section_materials')
                    ->where('section_id', $sectionId)
                    ->delete();

                // Add materials
                $materialOrder = 1;
                foreach ($section['materials'] ?? [] as $materialData) {
                    // Create material in MongoDB
                    $material = LearningMaterial::createMaterial([
                        'title' => $materialData['title'],
                        'description' => $materialData['description'] ?? '',
                        'content_type' => $materialData['type'],
                        'content_data' => $this->formatContentData($materialData['type'], $materialData),
                        'is_active' => true,
                        'created_by' => auth()->id(),
                    ]);

                    // Link material to section
                    DB::table('section_materials')->insert([
                        'section_id' => $sectionId,
                        'learning_material_id' => $material->_id,
                        'order_number' => $materialOrder++,
                        'is_required' => $materialData['is_required'] ?? true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            DB::commit();

            Notification::make()
                ->title('Course structure saved successfully!')
                ->success()
                ->send();

            $this->loadCourseStructure($data['selectedCourseId']);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error saving course structure', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            Notification::make()
                ->title('Error saving course structure')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function previewCourse(): void
    {
        if (!$this->selectedCourseId) {
            Notification::make()
                ->title('Please select a course first')
                ->warning()
                ->send();
            return;
        }

        // Redirect to course preview page
        $this->redirect(route('filament.admin.resources.courses.view', $this->selectedCourseId));
    }

    public function duplicateCourse(): void
    {
        if (!$this->selectedCourseId) {
            Notification::make()
                ->title('Please select a course first')
                ->warning()
                ->send();
            return;
        }

        try {
            $originalCourse = Course::findOrFail($this->selectedCourseId);
            
            // Create duplicate course
            $duplicateCourse = Course::create([
                'name' => $originalCourse->name . ' (Copy)',
                'code' => $originalCourse->code . '_COPY',
                'description' => $originalCourse->description,
                'credits' => $originalCourse->credits,
                'department' => $originalCourse->department,
                'is_active' => false, // Start as inactive
            ]);

            // Copy course structure
            $structure = $this->courseContentService->getCourseStructure($this->selectedCourseId);
            
            foreach ($structure['sections'] as $section) {
                // Create section assignment
                DB::table('course_sections')->insert([
                    'course_id' => $duplicateCourse->id,
                    'section_id' => $section->id,
                    'order_number' => $section->pivot->order_number,
                    'status' => $section->pivot->status,
                    'is_required' => $section->pivot->is_required,
                    'opens_at' => $section->pivot->opens_at,
                    'closes_at' => $section->pivot->closes_at,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            Notification::make()
                ->title('Course duplicated successfully!')
                ->body("New course '{$duplicateCourse->name}' created")
                ->success()
                ->send();

        } catch (\Exception $e) {
            Log::error('Error duplicating course', [
                'course_id' => $this->selectedCourseId,
                'error' => $e->getMessage()
            ]);
            
            Notification::make()
                ->title('Error duplicating course')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    private function formatContentData(string $type, array $data): array
    {
        return match ($type) {
            'video' => [
                'url' => $data['video_url'] ?? '',
                'duration' => $data['duration'] ?? 0,
            ],
            'document' => [
                'file_url' => $data['file_url'] ?? '',
                'file_type' => $data['file_type'] ?? 'pdf',
                'download_allowed' => $data['download_allowed'] ?? true,
            ],
            'quiz' => [
                'questions' => [], // Would be populated separately
                'time_limit' => $data['time_limit'] ?? null,
                'attempts_allowed' => $data['attempts_allowed'] ?? 1,
                'passing_score' => $data['passing_score'] ?? 70,
            ],
            'assignment' => [
                'instructions' => $data['instructions'] ?? '',
                'due_date' => $data['due_date'] ?? null,
                'max_points' => $data['max_points'] ?? 100,
                'submission_types' => ['text', 'file'],
            ],
            'interactive' => [
                'html_content' => '',
                'interactive_elements' => [],
            ],
            default => []
        };
    }
}