<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TeacherResource\Pages;
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

class TeacherResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Teachers';
    protected static ?string $navigationGroup = 'User Management';
    protected static ?int $navigationSort = 3;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->byType(UserType::TEACHER);
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

                Forms\Components\Section::make('Teacher Information')
                    ->schema([
                        TextInput::make('teacherProfile.employee_id')
                            ->label('Employee ID')
                            ->required()
                            ->unique(ignoreRecord: true),
                        TextInput::make('teacherProfile.department')
                            ->label('Department'),
                        TextInput::make('teacherProfile.qualification')
                            ->label('Qualification'),
                        TextInput::make('teacherProfile.specialization')
                            ->label('Specialization'),
                        TextInput::make('teacherProfile.years_of_experience')
                            ->label('Years of Experience')
                            ->numeric()
                            ->minValue(0),
                        DatePicker::make('teacherProfile.hire_date')
                            ->label('Hire Date')
                            ->required()
                            ->default(now()),
                        TextInput::make('teacherProfile.salary')
                            ->label('Salary')
                            ->numeric()
                            ->prefix('$'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Personal Information')
                    ->schema([
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
                TextColumn::make('teacherProfile.employee_id')
                    ->label('Employee ID')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('teacherProfile.department')
                    ->label('Department')
                    ->sortable(),
                TextColumn::make('teacherProfile.qualification')
                    ->label('Qualification')
                    ->sortable(),
                TextColumn::make('teacherProfile.specialization')
                    ->label('Specialization')
                    ->sortable(),
                TextColumn::make('teacherProfile.salary')
                    ->label('Salary')
                    ->money('USD')
                    ->sortable(),
                BooleanColumn::make('is_active')
                    ->sortable(),
                TextColumn::make('teacherProfile.hire_date')
                    ->label('Hired')
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
            'index' => Pages\ListTeachers::route('/'),
            'create' => Pages\CreateTeacher::route('/create'),
            'edit' => Pages\EditTeacher::route('/{record}/edit'),
        ];
    }
}