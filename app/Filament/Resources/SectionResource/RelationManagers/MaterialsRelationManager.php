<?php

namespace App\Filament\Resources\SectionResource\RelationManagers;

use App\Models\LearningMaterial;
use App\Enums\LearningMaterialType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\KeyValue;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

class MaterialsRelationManager extends RelationManager
{
    protected static string $relationship = 'materials';
    protected static ?string $recordTitleAttribute = 'title';

    public function form(Form $form): Form
    {
        return $form
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
                                LearningMaterialType::QUIZ->value => LearningMaterialType::QUIZ->label(),
                                LearningMaterialType::FILE->value => LearningMaterialType::FILE->label(),
                                LearningMaterialType::LINK->value => LearningMaterialType::LINK->label(),
                            ])
                            ->required(),
                        Toggle::make('is_active')
                            ->default(true),
                    ])
                    ->createOptionUsing(function (array $data) {
                        return LearningMaterial::create($data)->id;
                    }),
                
                TextInput::make('order_number')
                    ->label('Order')
                    ->numeric()
                    ->default(fn() => $this->getOwnerRecord()->materials()->count() + 1)
                    ->required(),
                
                Toggle::make('is_required')
                    ->label('Required Material')
                    ->default(true),
                
                KeyValue::make('completion_criteria')
                    ->label('Completion Criteria')
                    ->keyLabel('Criterion')
                    ->valueLabel('Value')
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                TextColumn::make('order_number')
                    ->label('#')
                    ->sortable()
                    ->alignCenter(),
                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                BadgeColumn::make('type')
                    ->colors([
                        'primary' => LearningMaterialType::TEXT->value,
                        'success' => LearningMaterialType::VIDEO->value,
                        'warning' => LearningMaterialType::QUIZ->value,
                        'info' => LearningMaterialType::FILE->value,
                        'secondary' => LearningMaterialType::LINK->value,
                    ]),
                BooleanColumn::make('is_required')
                    ->label('Required'),
                TextColumn::make('estimated_duration')
                    ->label('Duration (min)')
                    ->alignCenter()
                    ->toggleable(),
                BooleanColumn::make('is_active')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        LearningMaterialType::TEXT->value => LearningMaterialType::TEXT->label(),
                        LearningMaterialType::VIDEO->value => LearningMaterialType::VIDEO->label(),
                        LearningMaterialType::QUIZ->value => LearningMaterialType::QUIZ->label(),
                        LearningMaterialType::FILE->value => LearningMaterialType::FILE->label(),
                        LearningMaterialType::LINK->value => LearningMaterialType::LINK->label(),
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add Material'),
                Tables\Actions\AttachAction::make()
                    ->preloadRecordSelect()
                    ->recordSelectOptionsQuery(fn (Builder $query) => $query->active())
                    ->form(fn (Tables\Actions\AttachAction $action): array => [
                        $action->getRecordSelect(),
                        TextInput::make('order_number')
                            ->label('Order')
                            ->numeric()
                            ->default(fn() => $this->getOwnerRecord()->materials()->count() + 1)
                            ->required(),
                        Toggle::make('is_required')
                            ->label('Required Material')
                            ->default(true),
                        KeyValue::make('completion_criteria')
                            ->label('Completion Criteria')
                            ->keyLabel('Criterion')
                            ->valueLabel('Value'),
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DetachAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make(),
                ]),
            ])
            ->reorderable('order_number')
            ->defaultSort('order_number');
    }
}