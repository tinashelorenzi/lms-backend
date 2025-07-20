<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CourseResource\Pages;
use App\Filament\Resources\CourseResource\RelationManagers;
use App\Models\Course;
use App\Models\Section;
use App\Models\User;
use App\Enums\UserType;
use App\Enums\SectionStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Section as FormSection;
use Filament\Forms\Components\Tabs;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Forms\Get;
use Filament\Forms\Set;

class CourseResource extends Resource
{
    protected static ?string $model = Course::class;
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationLabel = 'Courses';
    protected static ?string $navigationGroup = 'Learning Management';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('course_tabs')
                    ->tabs([
                        // BASIC INFORMATION TAB
                        Tabs\Tab::make('Basic Information')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                FormSection::make('Course Details')
                                    ->schema([
                                        TextInput::make('name')
                                            ->required()
                                            ->maxLength(255),
                                        TextInput::make('code')
                                            ->required()
                                            ->maxLength(50)
                                            ->unique(ignoreRecord: true),
                                        Textarea::make('description')
                                            ->rows(3),
                                        TextInput::make('credits')
                                            ->numeric()
                                            ->required(),
                                        TextInput::make('department')
                                            ->required()
                                            ->maxLength(255),
                                        Toggle::make('is_active')
                                            ->default(true),
                                    ])
                                    ->columns(2),
                            ]),

                        // SECTIONS TAB - FIXED VERSION
                        Tabs\Tab::make('Sections')
                            ->icon('heroicon-o-squares-2x2')
                            ->schema([
                                FormSection::make('Course Sections')
                                    ->description('Configure sections for this course')
                                    ->schema([
                                        Repeater::make('sections')
                                            ->relationship('sections')
                                            ->schema([
                                                Select::make('section_id')
                                                    ->label('Section')
                                                    ->options(function () {
                                                        // Fixed: Ensure we get proper key-value pairs with non-null labels
                                                        return Section::active()
                                                            ->whereNotNull('title')
                                                            ->where('title', '!=', '')
                                                            ->pluck('title', 'id')
                                                            ->filter(); // Remove any null values
                                                    })
                                                    ->required()
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
                                                        TextInput::make('estimated_duration')
                                                            ->numeric()
                                                            ->suffix('minutes'),
                                                        Toggle::make('is_active')
                                                            ->default(true),
                                                    ])
                                                    ->createOptionUsing(function (array $data) {
                                                        return Section::create($data)->id;
                                                    }),
                                                
                                                TextInput::make('order_number')
                                                    ->label('Order')
                                                    ->numeric()
                                                    ->required()
                                                    ->default(1),
                                                
                                                Select::make('status')
                                                    ->options([
                                                        SectionStatus::DRAFT->value => SectionStatus::DRAFT->label(),
                                                        SectionStatus::OPEN->value => SectionStatus::OPEN->label(),
                                                        SectionStatus::CLOSED->value => SectionStatus::CLOSED->label(),
                                                        SectionStatus::AUTOMATED->value => SectionStatus::AUTOMATED->label(),
                                                    ])
                                                    ->default(SectionStatus::OPEN->value)
                                                    ->required(),
                                                
                                                Toggle::make('is_required')
                                                    ->label('Required Section')
                                                    ->default(true),
                                                
                                                DateTimePicker::make('opens_at')
                                                    ->label('Opens At'),
                                                
                                                DateTimePicker::make('closes_at')
                                                    ->label('Closes At'),
                                            ])
                                            ->columnSpan(2)
                                            ->orderColumn('order_number')
                                            ->reorderable(true)
                                            ->collapsible()
                                            ->itemLabel(fn (array $state): ?string => $state['title'] ?? 'New Section')
                                            ->addActionLabel('Add Section'),
                                    ])
                                    ->columnSpan(2),
                            ]),

                        // ENROLLMENT TAB
                        Tabs\Tab::make('Enrollment')
                            ->icon('heroicon-o-users')
                            ->schema([
                                FormSection::make('Teachers')
                                    ->schema([
                                        Repeater::make('teachers')
                                            ->relationship('teachers')
                                            ->schema([
                                                Select::make('teacher_id')
                                                    ->label('Teacher')
                                                    ->options(function () {
                                                        return User::where('user_type', UserType::TEACHER)
                                                            ->whereNotNull('name')
                                                            ->where('name', '!=', '')
                                                            ->pluck('name', 'id')
                                                            ->filter();
                                                    })
                                                    ->required()
                                                    ->searchable(),
                                                
                                                TextInput::make('academic_year')
                                                    ->default(now()->year)
                                                    ->required(),
                                                
                                                Select::make('semester')
                                                    ->options([
                                                        'fall' => 'Fall',
                                                        'spring' => 'Spring',
                                                        'summer' => 'Summer',
                                                    ])
                                                    ->default('fall')
                                                    ->required(),
                                                
                                                Toggle::make('is_primary')
                                                    ->label('Primary Teacher')
                                                    ->default(false),
                                            ])
                                            ->columnSpan(2)
                                            ->addActionLabel('Add Teacher'),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('department')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('credits')
                    ->sortable()
                    ->alignCenter(),
                BadgeColumn::make('sections_count')
                    ->label('Sections')
                    ->counts('sections')
                    ->color('primary'),
                BadgeColumn::make('teachers_count')
                    ->label('Teachers')
                    ->counts('teachers')
                    ->color('success'),
                BooleanColumn::make('is_active')
                    ->label('Active'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Active Courses'),
                SelectFilter::make('department')
                    ->options(function () {
                        return Course::distinct('department')
                            ->whereNotNull('department')
                            ->where('department', '!=', '')
                            ->pluck('department', 'department')
                            ->filter();
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\SectionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCourses::route('/'),
            'create' => Pages\CreateCourse::route('/create'),
            'view' => Pages\ViewCourse::route('/{record}'),
            'edit' => Pages\EditCourse::route('/{record}/edit'),
        ];
    }
}