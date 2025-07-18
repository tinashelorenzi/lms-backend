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
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MaterialsRelationManager extends RelationManager
{
    protected static string $relationship = 'materials';

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
                    ->addActionLabel('Add criteria'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                BadgeColumn::make('type')
                    ->colors([
                        'primary' => LearningMaterialType::TEXT->value,
                        'success' => LearningMaterialType::VIDEO->value,
                    ])
                    ->icons([
                        'heroicon-o-document-text' => LearningMaterialType::TEXT->value,
                        'heroicon-o-play-circle' => LearningMaterialType::VIDEO->value,
                    ])
                    ->formatStateUsing(fn ($state) => $state ? LearningMaterialType::from($state)->label() : 'Unknown'),
                TextColumn::make('video_platform')
                    ->label('Platform')
                    ->badge()
                    ->colors([
                        'danger' => 'youtube',
                        'primary' => 'vimeo',
                        'warning' => 'dailymotion',
                        'success' => 'loom',
                        'info' => 'google_drive',
                    ])
                    ->visible(fn ($record) => $record && $record->type === LearningMaterialType::VIDEO),
                TextColumn::make('pivot.order_number')
                    ->label('Order')
                    ->sortable(),
                TextColumn::make('estimated_duration')
                    ->label('Duration (min)')
                    ->sortable(),
                BooleanColumn::make('pivot.is_required')
                    ->label('Required')
                    ->sortable(),
                BooleanColumn::make('is_active')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        LearningMaterialType::TEXT->value => LearningMaterialType::TEXT->label(),
                        LearningMaterialType::VIDEO->value => LearningMaterialType::VIDEO->label(),
                    ]),
                Tables\Filters\TernaryFilter::make('pivot.is_required')
                    ->label('Required Status'),
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->form(fn (Tables\Actions\AttachAction $action): array => [
                        $action->getRecordSelect(),
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
                            ->addActionLabel('Add criteria'),
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->form(fn (Tables\Actions\EditAction $action): array => [
                        TextInput::make('order_number')
                            ->label('Order')
                            ->numeric()
                            ->required(),
                        Toggle::make('is_required')
                            ->label('Required'),
                        KeyValue::make('completion_criteria')
                            ->label('Completion Criteria')
                            ->keyLabel('Criteria')
                            ->valueLabel('Value')
                            ->addActionLabel('Add criteria'),
                    ]),
                Tables\Actions\DetachAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make(),
                ]),
            ])
            ->defaultSort('pivot.order_number', 'asc');
    }
}
