<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SchoolClassResource\Pages;
use App\Models\SchoolClass;
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

class SchoolClassResource extends Resource
{
    protected static ?string $model = SchoolClass::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-library';
    protected static ?string $navigationLabel = 'Classes';
    protected static ?string $navigationGroup = 'Academic Management';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Class Information')
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
                        TextInput::make('grade_level')
                            ->maxLength(255),
                        TextInput::make('max_students')
                            ->required()
                            ->numeric()
                            ->default(30)
                            ->minValue(1),
                        TextInput::make('academic_year')
                            ->required()
                            ->default(now()->year . '-' . (now()->year + 1)),
                        Toggle::make('is_active')
                            ->default(true),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Student Assignments')
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
                                Forms\Components\DatePicker::make('enrollment_date')
                                    ->required()
                                    ->default(now()),
                                Select::make('status')
                                    ->options([
                                        'active' => 'Active',
                                        'transferred' => 'Transferred',
                                        'graduated' => 'Graduated',
                                    ])
                                    ->default('active')
                                    ->required(),
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
                TextColumn::make('grade_level')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('academic_year')
                    ->sortable(),
                TextColumn::make('students_count')
                    ->counts('students')
                    ->label('Total Students'),
                TextColumn::make('max_students')
                    ->label('Max Students'),
                BooleanColumn::make('is_active')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('grade_level')
                    ->options(
                        SchoolClass::distinct()
                            ->pluck('grade_level', 'grade_level')
                            ->filter()
                            ->toArray()
                    ),
                SelectFilter::make('academic_year')
                    ->options(
                        SchoolClass::distinct()
                            ->pluck('academic_year', 'academic_year')
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
            'index' => Pages\ListSchoolClasses::route('/'),
            'create' => Pages\CreateSchoolClass::route('/create'),
            'edit' => Pages\EditSchoolClass::route('/{record}/edit'),
        ];
    }
}