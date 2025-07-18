<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StudentResource\Pages;
use App\Models\User;
use App\Enums\UserType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BooleanColumn;
use Illuminate\Database\Eloquent\Builder;

class StudentResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationLabel = 'Students';
    protected static ?string $navigationGroup = 'User Management';
    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->byType(UserType::STUDENT);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255),
                        TextInput::make('phone')
                            ->tel()
                            ->maxLength(255),
                        Toggle::make('is_active')
                            ->default(true),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Student Information')
                    ->schema([
                        TextInput::make('studentProfile.student_id')
                            ->label('Student ID')
                            ->required()
                            ->unique(table: 'student_profiles', column: 'student_id', ignoreRecord: true)
                            ->suffixAction(
                                \Filament\Forms\Components\Actions\Action::make('generate_student_id')
                                    ->icon('heroicon-m-sparkles')
                                    ->tooltip('Generate Student ID')
                                    ->action(function ($state, $set) {
                                        $lastStudent = \App\Models\StudentProfile::orderBy('id', 'desc')->first();
                                        $lastNumber = $lastStudent ? (int) substr($lastStudent->student_id, 3) : 0;
                                        $newNumber = $lastNumber + 1;
                                        $newStudentId = 'STU' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
                                        $set('studentProfile.student_id', $newStudentId);
                                    })
                            ),
                        TextInput::make('studentProfile.class_year')
                            ->label('Class Year'),
                        TextInput::make('studentProfile.major')
                            ->label('Major'),
                        TextInput::make('studentProfile.gpa')
                            ->label('GPA')
                            ->numeric()
                            ->step(0.01)
                            ->minValue(0)
                            ->maxValue(4),
                        DatePicker::make('studentProfile.enrollment_date')
                            ->label('Enrollment Date')
                            ->required()
                            ->default(now()),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Parent Information')
                    ->schema([
                        TextInput::make('studentProfile.parent_name')
                            ->label('Parent Name'),
                        TextInput::make('studentProfile.parent_phone')
                            ->label('Parent Phone')
                            ->tel(),
                        TextInput::make('studentProfile.parent_email')
                            ->label('Parent Email')
                            ->email(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Textarea::make('studentProfile.medical_conditions')
                            ->label('Medical Conditions')
                            ->rows(3),
                        Textarea::make('studentProfile.emergency_contact')
                            ->label('Emergency Contact')
                            ->rows(3),
                        DatePicker::make('date_of_birth'),
                        Select::make('gender')
                            ->options([
                                'male' => 'Male',
                                'female' => 'Female',
                                'other' => 'Other',
                            ]),
                        Textarea::make('address')
                            ->rows(3),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Authentication')
                    ->schema([
                        TextInput::make('password')
                            ->password()
                            ->required()
                            ->minLength(8)
                            ->hiddenOn('edit'),
                        TextInput::make('password_confirmation')
                            ->password()
                            ->required()
                            ->same('password')
                            ->hiddenOn('edit'),
                    ])
                    ->columns(2)
                    ->visibleOn('create'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('studentProfile.student_id')
                    ->label('Student ID')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('studentProfile.class_year')
                    ->label('Class Year')
                    ->sortable(),
                TextColumn::make('studentProfile.major')
                    ->label('Major')
                    ->sortable(),
                TextColumn::make('studentProfile.gpa')
                    ->label('GPA')
                    ->sortable(),
                BooleanColumn::make('is_active')
                    ->sortable(),
                TextColumn::make('studentProfile.enrollment_date')
                    ->label('Enrolled')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('is_active')
                    ->options([
                        '1' => 'Active',
                        '0' => 'Inactive',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('toggle_status')
                    ->label(fn (User $record): string => $record->is_active ? 'Suspend' : 'Activate')
                    ->icon(fn (User $record): string => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn (User $record): string => $record->is_active ? 'danger' : 'success')
                    ->action(function (User $record) {
                        $record->is_active = !$record->is_active;
                        $record->save();
                    })
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudents::route('/'),
            'create' => Pages\CreateStudent::route('/create'),
            'edit' => Pages\EditStudent::route('/{record}/edit'),
        ];
    }
}