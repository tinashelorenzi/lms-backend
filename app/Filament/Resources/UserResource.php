<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
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
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'All Users';
    protected static ?string $navigationGroup = 'User Management';
    protected static ?int $navigationSort = 1;

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
                        Select::make('user_type')
                            ->options([
                                UserType::ADMIN->value => UserType::ADMIN->label(),
                                UserType::TEACHER->value => UserType::TEACHER->label(),
                                UserType::STUDENT->value => UserType::STUDENT->label(),
                            ])
                            ->required()
                            ->default(UserType::STUDENT->value),
                        Toggle::make('is_active')
                            ->default(true)
                            ->helperText('Inactive users cannot login'),
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
                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                BadgeColumn::make('user_type')
                    ->colors([
                        'danger' => UserType::ADMIN->value,
                        'warning' => UserType::TEACHER->value,
                        'success' => UserType::STUDENT->value,
                    ])
                    ->formatStateUsing(fn (UserType $state): string => $state->label()),
                TextColumn::make('phone')
                    ->searchable(),
                BooleanColumn::make('is_active')
                    ->sortable(),
                TextColumn::make('last_login_at')
                    ->dateTime()
                    ->sortable()
                    ->since(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('user_type')
                    ->options([
                        UserType::ADMIN->value => UserType::ADMIN->label(),
                        UserType::TEACHER->value => UserType::TEACHER->label(),
                        UserType::STUDENT->value => UserType::STUDENT->label(),
                    ]),
                SelectFilter::make('is_active')
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
                    ->requiresConfirmation()
                    ->modalDescription(fn (User $record): string => 
                        $record->is_active 
                            ? 'Are you sure you want to suspend this user?' 
                            : 'Are you sure you want to activate this user?'
                    ),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}