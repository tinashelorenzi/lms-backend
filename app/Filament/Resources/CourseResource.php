<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CourseResource\Pages;
use App\Models\Course;
use App\Models\User;
use App\Enums\UserType;
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
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;

class CourseResource extends Resource
{
    protected static ?string $model = Course::class;
    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    protected static ?string $navigationLabel = 'Courses';
    protected static ?string $navigationGroup = 'Academic Management';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Course Information')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('code')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Textarea::make('description')
                            ->rows(3),
                        TextInput::make('credits')
                            ->required()
                            ->numeric()
                            ->default(3)
                            ->minValue(1)
                            ->maxValue(10),
                        TextInput::make('department')
                            ->maxLength(255),
                        Toggle::make('is_active')
                            ->default(true),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Teacher Assignments')
                    ->schema([
                        Repeater::make('teachers')
                            ->relationship()
                            ->schema([
                                Select::make('teacher_id')
                                    ->label('Teacher')
                                    ->options(
                                        User::byType(UserType::TEACHER)
                                            ->pluck('name', 'id')
                                    )
                                    ->required()
                                    ->searchable(),
                                TextInput::make('academic_year')
                                    ->required()
                                    ->default(now()->year . '-' . (now()->year + 1)),
                                Select::make('semester')
                                    ->options([
                                        'Fall' => 'Fall',
                                        'Spring' => 'Spring',
                                        'Summer' => 'Summer',
                                    ])
                                    ->required(),
                                Toggle::make('is_primary')
                                    ->label('Primary Teacher')
                                    ->default(false),
                            ])
                            ->columns(4)
                            ->collapsible(),
                    ]),

                Forms\Components\Section::make('Student Enrollments')
                    ->schema([
                        Repeater::make('students')
                            ->relationship()
                            ->schema([
                                Select::make('student_id')
                                    ->label('Student')
                                    ->options(
                                        User::byType(UserType::STUDENT)
                                            ->with('studentProfile')
                                            ->get()
                                            ->mapWithKeys(function ($user) {
                                                $studentId = $user->studentProfile?->student_id ?? 'No ID';
                                                return [$user->id => "{$user->name} ({$studentId})"];
                                            })
                                    )
                                    ->required()
                                    ->searchable()
                                    ->placeholder('Search by name or student ID...')
                                    ->helperText('You can search by student name or student ID'),
                                TextInput::make('academic_year')
                                    ->required()
                                    ->default(now()->year . '-' . (now()->year + 1)),
                                Select::make('semester')
                                    ->options([
                                        'Fall' => 'Fall',
                                        'Spring' => 'Spring',
                                        'Summer' => 'Summer',
                                    ])
                                    ->required(),
                                Forms\Components\DatePicker::make('enrollment_date')
                                    ->required()
                                    ->default(now()),
                                Select::make('status')
                                    ->options([
                                        'active' => 'Active',
                                        'completed' => 'Completed',
                                        'dropped' => 'Dropped',
                                    ])
                                    ->default('active')
                                    ->required(),
                                TextInput::make('grade')
                                    ->numeric()
                                    ->step(0.01)
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->suffix('%'),
                            ])
                            ->columns(3)
                            ->collapsible(),
                    ]),
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
                    ->sortable(),
                TextColumn::make('credits')
                    ->sortable(),
                TextColumn::make('teachers_count')
                    ->counts('teachers')
                    ->label('Teachers'),
                TextColumn::make('students_count')
                    ->counts('students')
                    ->label('Students'),
                BooleanColumn::make('is_active')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('department')
                    ->options(
                        Course::distinct()
                            ->pluck('department', 'department')
                            ->filter()
                            ->toArray()
                    ),
                TernaryFilter::make('is_active')
                    ->label('Active Status'),
            ])
            ->actions([
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCourses::route('/'),
            'create' => Pages\CreateCourse::route('/create'),
            'edit' => Pages\EditCourse::route('/{record}/edit'),
        ];
    }
}