<?php

namespace App\Filament\Resources\LearningMaterialResource\RelationManagers;

use App\Models\Section;
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
use Filament\Tables\Filters\TernaryFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SectionsRelationManager extends RelationManager
{
    protected static string $relationship = 'sections';

    public function form(Form $form): Form
    {
        return $form
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
                        TextInput::make('estimated_duration')
                            ->label('Estimated Duration (minutes)')
                            ->numeric()
                            ->minValue(1),
                    ])
                    ->createOptionUsing(function (array $data) {
                        return Section::create($data)->id;
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
                TextColumn::make('description')
                    ->limit(50)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }
                        return $state;
                    }),
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
                TernaryFilter::make('pivot.is_required')
                    ->label('Required Status'),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),
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
