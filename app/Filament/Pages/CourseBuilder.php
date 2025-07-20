<?php

namespace App\Filament\Pages;

use App\Models\Course;
use App\Models\Section;
use App\Models\LearningMaterial;
use App\Enums\SectionStatus;
use App\Filament\Resources\CourseResource;
use Filament\Pages\Page;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Section as FormSection;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\ViewField;
use Filament\Forms\Get;

class CourseBuilder extends Page implements HasForms, HasActions
{
    use InteractsWithForms, InteractsWithActions;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationLabel = 'Course Builder';
    protected static ?string $navigationGroup = 'Learning Management';
    protected static ?int $navigationSort = 0;
    protected static string $view = 'filament.pages.course-builder';

    public ?array $data = [];
    public ?Course $course = null;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([
                    Wizard\Step::make('Course Information')
                        ->icon('heroicon-o-information-circle')
                        ->schema([
                            FormSection::make('Basic Course Details')
                                ->schema([
                                    TextInput::make('course.name')
                                        ->label('Course Name')
                                        ->required()
                                        ->maxLength(255),
                                    TextInput::make('course.code')
                                        ->label('Course Code')
                                        ->required()
                                        ->maxLength(255),
                                    Textarea::make('course.description')
                                        ->label('Course Description')
                                        ->rows(3),
                                    TextInput::make('course.credits')
                                        ->label('Credits')
                                        ->numeric()
                                        ->default(3)
                                        ->required(),
                                    TextInput::make('course.department')
                                        ->label('Department')
                                        ->maxLength(255),
                                    Toggle::make('course.is_active')
                                        ->label('Active Course')
                                        ->default(true),
                                ])
                                ->columns(2),
                        ]),

                    Wizard\Step::make('Course Structure')
                        ->icon('heroicon-o-rectangle-stack')
                        ->schema([
                            ViewField::make('course_preview')
                                ->view('filament.forms.course-structure-preview'),
                            
                            Repeater::make('sections')
                                ->label('Course Sections')
                                ->schema([
                                    Select::make('section_id')
                                        ->label('Section')
                                        ->options(Section::active()->pluck('title', 'id'))
                                        ->searchable()
                                        ->createOptionForm([
                                            TextInput::make('title')
                                                ->required()
                                                ->maxLength(255),
                                            Textarea::make('description')
                                                ->rows(2),
                                            Textarea::make('objectives')
                                                ->label('Learning Objectives')
                                                ->rows(3),
                                        ])
                                        ->createOptionUsing(function (array $data) {
                                            return Section::create($data)->id;
                                        })
                                        ->live()
                                        ->afterStateUpdated(function ($state, callable $set, Get $get) {
                                            if ($state) {
                                                $section = Section::find($state);
                                                if ($section) {
                                                    $set('section_title', $section->title);
                                                    $set('section_description', $section->description);
                                                }
                                            }
                                        }),
                                    
                                    TextInput::make('section_title')
                                        ->label('Section Title')
                                        ->disabled()
                                        ->dehydrated(false),
                                    
                                    TextInput::make('order_number')
                                        ->label('Order')
                                        ->numeric()
                                        ->default(fn(Get $get) => count($get('../../sections')) + 1)
                                        ->required(),
                                    
                                    Select::make('status')
                                        ->options([
                                            SectionStatus::DRAFT->value => SectionStatus::DRAFT->label(),
                                            SectionStatus::OPEN->value => SectionStatus::OPEN->label(),
                                            SectionStatus::AUTOMATED->value => SectionStatus::AUTOMATED->label(),
                                        ])
                                        ->default(SectionStatus::DRAFT->value)
                                        ->required(),
                                    
                                    Toggle::make('is_required')
                                        ->label('Required Section')
                                        ->default(true),
                                ])
                                ->columns(2)
                                ->reorderable('order_number')
                                ->collapsible()
                                ->cloneable()
                                ->itemLabel(fn (array $state): ?string => 
                                    $state['section_title'] ?? 'New Section'
                                ),
                        ]),

                    Wizard\Step::make('Review & Create')
                        ->icon('heroicon-o-check-circle')
                        ->schema([
                            ViewField::make('course_summary')
                                ->view('filament.forms.course-summary'),
                        ]),
                ])
                ->columnSpanFull()
                ->submitAction(new \Illuminate\Support\HtmlString('<button type="submit" class="filament-button filament-button-size-md inline-flex items-center justify-center py-1 gap-1 font-medium rounded-lg border transition-colors focus:outline-none focus:ring-offset-2 focus:ring-2 focus:ring-inset min-h-[2.25rem] px-3 text-sm text-white shadow focus:ring-white border-transparent bg-primary-600 hover:bg-primary-500 focus:bg-primary-700 focus:ring-offset-primary-700">Create Course</button>')),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [];
    }

    protected function getActions(): array
    {
        return [
            Action::make('createCourse')
                ->label('Create Course')
                ->action('create')
                ->color('primary'),
            Action::make('saveDraft')
                ->label('Save as Draft')
                ->action('saveDraft')
                ->color('gray'),
        ];
    }

    public function create(): void
    {
        $data = $this->form->getState();
        
        try {
            // Create the course
            $course = Course::create($data['course']);
            
            // Add sections if any
            if (isset($data['sections']) && is_array($data['sections'])) {
                foreach ($data['sections'] as $sectionData) {
                    $course->sections()->attach($sectionData['section_id'], [
                        'order_number' => $sectionData['order_number'],
                        'status' => $sectionData['status'],
                        'is_required' => $sectionData['is_required'] ?? true,
                    ]);
                }
            }
            
            Notification::make()
                ->title('Course created successfully!')
                ->success()
                ->send();
            
            $this->redirect(CourseResource::getUrl('edit', ['record' => $course]));
            
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error creating course')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function saveDraft(): void
    {
        $data = $this->form->getState();
        
        try {
            // Create course as inactive
            $data['course']['is_active'] = false;
            $course = Course::create($data['course']);
            
            Notification::make()
                ->title('Course draft saved!')
                ->success()
                ->send();
            
            $this->redirect(CourseResource::getUrl('edit', ['record' => $course]));
            
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error saving draft')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}