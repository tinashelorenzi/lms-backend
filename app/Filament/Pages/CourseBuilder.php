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

class EnhancedCourseBuilder extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationLabel = 'Course Builder';
    protected static ?string $navigationGroup = 'Course Management';
    protected static string $view = 'filament.pages.enhanced-course-builder';

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
                            ->options(Course::active()->pluck('name', 'id'))
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(function ($state) {
                                $this->loadCourseStructure($state);
                            }),
                    ]),

                FormSection::make('Course Structure')
                    ->schema([
                        Builder::make('sections')
                            ->label('Course Sections')
                            ->blocks([
                                Block::make('section')
                                    ->label('Course Section')
                                    ->icon('heroicon-m-document-text')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                Select::make('section_id')
                                                    ->label('Existing Section')
                                                    ->options(Section::active()->pluck('title', 'id'))
                                                    ->searchable()
                                                    ->nullable()
                                                    ->live()
                                                    ->afterStateUpdated(function ($state, callable $set) {
                                                        if ($state) {
                                                            $section = Section::find($state);
                                                            if ($section) {
                                                                $set('title', $section->title);
                                                                $set('description', $section->description);
                                                            }
                                                        }
                                                    }),
                                                
                                                TextInput::make('title')
                                                    ->label('Section Title')
                                                    ->required()
                                                    ->maxLength(255),
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

                                        Builder::make('materials')
                                            ->label('Learning Materials')
                                            ->blocks([
                                                Block::make('video')
                                                    ->label('Video Content')
                                                    ->icon('heroicon-m-play-circle')
                                                    ->schema([
                                                        TextInput::make('title')
                                                            ->required(),
                                                        Textarea::make('description')
                                                            ->rows(2),
                                                        TextInput::make('video_url')
                                                            ->label('Video URL')
                                                            ->url()
                                                            ->required(),
                                                        TextInput::make('duration')
                                                            ->label('Duration (minutes)')
                                                            ->numeric(),
                                                    ]),

                                                Block::make('document')
                                                    ->label('Document')
                                                    ->icon('heroicon-m-document')
                                                    ->schema([
                                                        TextInput::make('title')
                                                            ->required(),
                                                        Textarea::make('description')
                                                            ->rows(2),
                                                        TextInput::make('file_url')
                                                            ->label('Document URL')
                                                            ->url()
                                                            ->required(),
                                                        Select::make('file_type')
                                                            ->options([
                                                                'pdf' => 'PDF',
                                                                'doc' => 'Word Document',
                                                                'ppt' => 'PowerPoint',
                                                                'txt' => 'Text File',
                                                            ])
                                                            ->default('pdf'),
                                                        Toggle::make('download_allowed')
                                                            ->label('Allow Download')
                                                            ->default(true),
                                                    ]),

                                                Block::make('quiz')
                                                    ->label('Quiz/Assessment')
                                                    ->icon('heroicon-m-question-mark-circle')
                                                    ->schema([
                                                        TextInput::make('title')
                                                            ->required(),
                                                        Textarea::make('description')
                                                            ->rows(2),
                                                        TextInput::make('time_limit')
                                                            ->label('Time Limit (minutes)')
                                                            ->numeric(),
                                                        TextInput::make('attempts_allowed')
                                                            ->label('Attempts Allowed')
                                                            ->numeric()
                                                            ->default(1),
                                                        TextInput::make('passing_score')
                                                            ->label('Passing Score (%)')
                                                            ->numeric()
                                                            ->default(70),
                                                        Repeater::make('questions')
                                                            ->schema([
                                                                TextInput::make('question')
                                                                    ->required(),
                                                                Select::make('type')
                                                                    ->options([
                                                                        'multiple_choice' => 'Multiple Choice',
                                                                        'true_false' => 'True/False',
                                                                        'short_answer' => 'Short Answer',
                                                                        'essay' => 'Essay',
                                                                    ])
                                                                    ->required(),
                                                                Repeater::make('options')
                                                                    ->schema([
                                                                        TextInput::make('text')
                                                                            ->required(),
                                                                        Toggle::make('is_correct')
                                                                            ->default(false),
                                                                    ])
                                                                    ->visible(fn ($get) => in_array($get('type'), ['multiple_choice']))
                                                                    ->columnSpanFull(),
                                                            ])
                                                            ->columnSpanFull(),
                                                    ]),

                                                Block::make('assignment')
                                                    ->label('Assignment')
                                                    ->icon('heroicon-m-clipboard-document-list')
                                                    ->schema([
                                                        TextInput::make('title')
                                                            ->required(),
                                                        Textarea::make('instructions')
                                                            ->rows(4)
                                                            ->required(),
                                                        DateTimePicker::make('due_date')
                                                            ->label('Due Date'),
                                                        TextInput::make('max_points')
                                                            ->label('Maximum Points')
                                                            ->numeric()
                                                            ->default(100),
                                                        Select::make('submission_types')
                                                            ->label('Submission Types')
                                                            ->multiple()
                                                            ->options([
                                                                'file' => 'File Upload',
                                                                'text' => 'Text Entry',
                                                                'url' => 'Website URL',
                                                            ])
                                                            ->default(['file']),
                                                    ]),
                                            ])
                                            ->columnSpanFull(),
                                    ])
                                    ->columnSpanFull(),
                            ])
                            ->columnSpanFull()
                            ->visible(fn ($get) => $get('selectedCourseId')),
                    ])
                    ->visible(fn ($get) => $get('selectedCourseId')),
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
            $this->courseStructure = $this->courseContentService->getCourseStructure($courseId);
            
            // Convert to form format
            $sectionsData = [];
            foreach ($this->courseStructure['sections'] as $section) {
                $materialsData = [];
                foreach ($section->materials as $material) {
                    $materialsData[] = [
                        'type' => $material->content_type,
                        'data' => array_merge(
                            $material->toArray(),
                            $material->content_data
                        ),
                    ];
                }

                $sectionsData[] = [
                    'type' => 'section',
                    'data' => [
                        'section_id' => $section->id,
                        'title' => $section->title,
                        'description' => $section->description,
                        'is_required' => $section->pivot->is_required ?? true,
                        'opens_at' => $section->pivot->opens_at,
                        'closes_at' => $section->pivot->closes_at,
                        'materials' => $materialsData,
                    ],
                ];
            }

            $this->form->fill([
                'selectedCourseId' => $courseId,
                'sections' => $sectionsData,
            ]);

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error loading course structure')
                ->body($e->getMessage())
                ->danger()
                ->send();
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
                if ($sectionData['type'] !== 'section') continue;

                $section = $sectionData['data'];
                
                // Create or get section
                $sectionId = $section['section_id'] ?? null;
                if (!$sectionId) {
                    $newSection = Section::create([
                        'title' => $section['title'],
                        'description' => $section['description'],
                        'is_active' => true,
                    ]);
                    $sectionId = $newSection->id;
                }

                // Add section to course
                DB::table('course_sections')->insert([
                    'course_id' => $data['selectedCourseId'],
                    'section_id' => $sectionId,
                    'order_number' => $sectionOrder++,
                    'status' => 'open',
                    'is_required' => $section['is_required'] ?? true,
                    'opens_at' => $section['opens_at'],
                    'closes_at' => $section['closes_at'],
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
                    $material = LearningMaterial::create([
                        'title' => $materialData['data']['title'],
                        'description' => $materialData['data']['description'] ?? '',
                        'content_type' => $materialData['type'],
                        'content_data' => $this->formatContentData($materialData['type'], $materialData['data']),
                        'is_active' => true,
                        'created_by' => auth()->id(),
                    ]);

                    // Link material to section
                    DB::table('section_materials')->insert([
                        'section_id' => $sectionId,
                        'learning_material_id' => $material->_id,
                        'order_number' => $materialOrder++,
                        'is_required' => true,
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
            
            Notification::make()
                ->title('Error saving course structure')
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
                'questions' => $data['questions'] ?? [],
                'time_limit' => $data['time_limit'],
                'attempts_allowed' => $data['attempts_allowed'] ?? 1,
                'passing_score' => $data['passing_score'] ?? 70,
            ],
            'assignment' => [
                'instructions' => $data['instructions'] ?? '',
                'due_date' => $data['due_date'],
                'max_points' => $data['max_points'] ?? 100,
                'submission_types' => $data['submission_types'] ?? ['file'],
            ],
            default => $data,
        };
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

        // Redirect to student view or open in new tab
        $this->redirect(route('filament.admin.pages.student-course-view', ['course' => $this->selectedCourseId]));
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

        // This would open a modal or redirect to course duplication
        Notification::make()
            ->title('Course duplication feature coming soon!')
            ->info()
            ->send();
    }
}