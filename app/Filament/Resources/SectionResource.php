<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SectionResource\Pages;
use App\Filament\Resources\SectionResource\RelationManagers;
use App\Models\Section;
use App\Models\LearningMaterial;
use App\Enums\LearningMaterialType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\KeyValue;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class SectionResource extends Resource
{
    protected static ?string $model = Section::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Sections';
    protected static ?string $navigationGroup = 'Learning Management';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Section Information')
                    ->schema([
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255),
                        Textarea::make('description')
                            ->rows(3),
                        Textarea::make('objectives')
                            ->label('Learning Objectives')
                            ->rows(4)
                            ->placeholder('What will students learn in this section?'),
                        TextInput::make('estimated_duration')
                            ->label('Estimated Duration (minutes)')
                            ->numeric()
                            ->minValue(1)
                            ->placeholder('e.g., 30'),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        KeyValue::make('metadata')
                            ->label('Additional Metadata')
                            ->keyLabel('Key')
                            ->valueLabel('Value')
                            ->addActionLabel('Add metadata')
                            ->collapsible(),
                    ]),

                Forms\Components\Section::make('Learning Materials')
                    ->schema([
                        Repeater::make('materials')
                            ->relationship()
                            ->schema([
                                Select::make('learning_material_id')
                                    ->label('Learning Material')
                                    ->options(LearningMaterial::active()->pluck('title', 'id'))
                                    ->required()
                                    ->searchable()
                                    ->createOptionForm([
                                        TextInput::make('title')
                                            ->required()
                                            ->maxLength(255),
                                        Textarea::make('description')
                                            ->rows(2),
                                        Select::make('type')
                                            ->options([
                                                LearningMaterialType::TEXT->value => LearningMaterialType::TEXT->label(),
                                                LearningMaterialType::VIDEO->value => LearningMaterialType::VIDEO->label(),
                                            ])
                                            ->required(),
                                    ])
                                    ->createOptionUsing(function (array $data) {
                                        return LearningMaterial::create($data)->id;
                                    }),
                                TextInput::make('order_number')
                                    ->label('Order')
                                    ->numeric()
                                    ->default(1)
                                    ->required(),
                                Toggle::make('is_required')
                                    ->label('Required')
                                    ->default(true),
                                KeyValue::make('completion_criteria')
                                    ->label('Completion Criteria')
                                    ->keyLabel('Criteria')
                                    ->valueLabel('Value')
                                    ->addActionLabel('Add criteria')
                                    ->collapsible(),
                            ])
                            ->columns(2)
                            ->orderColumn('order_number')
                            ->collapsible()
                            ->cloneable(),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('description')
                    ->limit(50)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }
                        return $state;
                    }),
                TextColumn::make('materials_count')
                    ->counts('materials')
                    ->label('Materials'),
                TextColumn::make('courses_count')
                    ->counts('courses')
                    ->label('Used in Courses'),
                TextColumn::make('estimated_duration')
                    ->label('Duration (min)')
                    ->sortable(),
                BooleanColumn::make('is_active')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Active Status'),
                Tables\Filters\Filter::make('has_materials')
                    ->label('Has Materials')
                    ->query(fn (Builder $query): Builder => $query->has('materials')),
                Tables\Filters\Filter::make('unused')
                    ->label('Unused Sections')
                    ->query(fn (Builder $query): Builder => $query->doesntHave('courses')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    BulkAction::make('activate')
                        ->label('Activate')
                        ->icon('heroicon-o-check')
                        ->action(fn (Collection $records) => $records->each->update(['is_active' => true]))
                        ->requiresConfirmation()
                        ->color('success'),
                    BulkAction::make('deactivate')
                        ->label('Deactivate')
                        ->icon('heroicon-o-x-mark')
                        ->action(fn (Collection $records) => $records->each->update(['is_active' => false]))
                        ->requiresConfirmation()
                        ->color('danger'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\MaterialsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSections::route('/'),
            'create' => Pages\CreateSection::route('/create'),
            'view' => Pages\ViewSection::route('/{record}'),
            'edit' => Pages\EditSection::route('/{record}/edit'),
        ];
    }
}