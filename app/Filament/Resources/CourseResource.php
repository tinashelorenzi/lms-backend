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
                                            ->maxLength(255)
                                            ->columnSpanFull(),
                                        TextInput::make('code')
                                            ->required()
                                            ->unique(ignoreRecord: true)
                                            ->maxLength(255)
                                            ->columnSpan(1),
                                        TextInput::make('credits')
                                            ->required()
                                            ->numeric()
                                            ->default(3)
                                            ->minValue(1)
                                            ->maxValue(10)
                                            ->columnSpan(1),
                                        Textarea::make('description')
                                            ->rows(3)
                                            ->columnSpanFull(),
                                        TextInput::make('department')
                                            ->maxLength(255)
                                            ->columnSpan(1),
                                        Toggle::make('is_active')
                                            ->default(true)
                                            ->columnSpan(1),
                                    ])
                                    ->columns(2),
                            ]),

                        // SECTIONS TAB
                        Tabs\Tab::make('Course Sections')
                            ->icon('heroicon-o-rectangle-stack')
                            ->schema([
                                Repeater::make('courseSections')
                                    ->relationship('sections')
                                    ->schema([
                                        Select::make('section_id')
                                            ->label('Section')
                                            ->options(Section::active()->pluck('title', 'id'))
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
                                            ->default(1)
                                            ->required()
                                            ->columnSpan(1),
                                        
                                        Select::make('status')
                                            ->options(collect(SectionStatus::cases())
                                                ->mapWithKeys(fn($case) => [$case->value => $case->label()]))
                                            ->default(SectionStatus::DRAFT->value)
                                            ->required()
                                            ->columnSpan(1),
                                        
                                        Toggle::make('is_required')
                                            ->default(true)
                                            ->columnSpan(1),
                                        
                                        DateTimePicker::make('opens_at')
                                            ->label('Opens At')
                                            ->columnSpan(1),
                                        
                                        DateTimePicker::make('closes_at')
                                            ->label('Closes At')
                                            ->columnSpan(1),
                                    ])
                                    ->columns(3)
                                    ->defaultItems(1)
                                    ->reorderable('order_number')
                                    ->collapsible()
                                    ->itemLabel(fn (array $state): ?string => 
                                        Section::find($state['section_id'])?->title ?? 'New Section'
                                    ),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('code')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('department')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('credits')
                    ->sortable()
                    ->alignCenter(),
                BadgeColumn::make('sections_count')
                    ->label('Sections')
                    ->counts('sections')
                    ->color('primary'),
                BadgeColumn::make('total_materials_count')
                    ->label('Materials')
                    ->color('success'),
                BooleanColumn::make('is_active')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Status'),
                SelectFilter::make('department')
                    ->options(Course::distinct('department')->pluck('department', 'department')),
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
            RelationManagers\TeachersRelationManager::class,
            RelationManagers\StudentsRelationManager::class,
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