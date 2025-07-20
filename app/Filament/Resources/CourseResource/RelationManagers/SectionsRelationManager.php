<?php

namespace App\Filament\Resources\CourseResource\RelationManagers;

use App\Models\Section;
use App\Enums\SectionStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Section as FormSection;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

class SectionsRelationManager extends RelationManager
{
    protected static string $relationship = 'sections';
    protected static ?string $recordTitleAttribute = 'title';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                FormSection::make('Section Selection')
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
                            })
                            ->columnSpanFull(),
                    ]),

                FormSection::make('Section Configuration')
                    ->schema([
                        TextInput::make('order_number')
                            ->label('Order')
                            ->numeric()
                            ->default(fn() => $this->getOwnerRecord()->sections()->count() + 1)
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
                            ->columnSpan(2),
                        
                        DateTimePicker::make('opens_at')
                            ->label('Opens At')
                            ->columnSpan(1),
                        
                        DateTimePicker::make('closes_at')
                            ->label('Closes At')
                            ->columnSpan(1),
                    ])
                    ->columns(2),
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
                TextColumn::make('description')
                    ->limit(50)
                    ->toggleable(),
                BadgeColumn::make('status')
                    ->colors([
                        'gray' => SectionStatus::DRAFT->value,
                        'success' => SectionStatus::OPEN->value,
                        'danger' => SectionStatus::CLOSED->value,
                        'warning' => SectionStatus::AUTOMATED->value,
                        'secondary' => SectionStatus::ARCHIVED->value,
                    ]),
                BooleanColumn::make('is_required')
                    ->label('Required'),
                BadgeColumn::make('materials_count')
                    ->label('Materials')
                    ->counts('materials')
                    ->color('primary'),
                TextColumn::make('opens_at')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('closes_at')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(collect(SectionStatus::cases())
                        ->mapWithKeys(fn($case) => [$case->value => $case->label()])),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add Section'),
                Tables\Actions\AttachAction::make()
                    ->preloadRecordSelect()
                    ->recordSelectOptionsQuery(fn (Builder $query) => $query->active())
                    ->form(fn (AttachAction $action): array => [
                        $action->getRecordSelect(),
                        TextInput::make('order_number')
                            ->label('Order')
                            ->numeric()
                            ->default(fn() => $this->getOwnerRecord()->sections()->count() + 1)
                            ->required(),
                        Select::make('status')
                            ->options(collect(SectionStatus::cases())
                                ->mapWithKeys(fn($case) => [$case->value => $case->label()]))
                            ->default(SectionStatus::DRAFT->value)
                            ->required(),
                        Toggle::make('is_required')
                            ->default(true),
                        DateTimePicker::make('opens_at')
                            ->label('Opens At'),
                        DateTimePicker::make('closes_at')
                            ->label('Closes At'),
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