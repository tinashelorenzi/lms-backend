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
                            ->options(function () {
                                // Fixed: Ensure proper handling of null values
                                return Section::active()
                                    ->whereNotNull('title')
                                    ->where('title', '!=', '')
                                    ->get()
                                    ->pluck('title', 'id')
                                    ->filter(); // Remove any remaining null values
                            })
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
                            }),
                    ])
                    ->columnSpan(2),

                FormSection::make('Section Configuration')
                    ->schema([
                        TextInput::make('order_number')
                            ->label('Order')
                            ->numeric()
                            ->required()
                            ->default(function () {
                                // Get the next order number for this course
                                $maxOrder = $this->getOwnerRecord()
                                    ->sections()
                                    ->max('course_sections.order_number');
                                return ($maxOrder ?? 0) + 1;
                            }),
                        
                        Select::make('status')
                            ->options([
                                SectionStatus::DRAFT->value => SectionStatus::DRAFT->label(),
                                SectionStatus::OPEN->value => SectionStatus::OPEN->label(),
                                SectionStatus::CLOSED->value => SectionStatus::CLOSED->label(),
                                SectionStatus::AUTOMATED->value => SectionStatus::AUTOMATED->label(),
                            ])
                            ->default(SectionStatus::OPEN->value)
                            ->required(),
                        
                        Toggle::make('is_required')
                            ->label('Required Section')
                            ->default(true),
                        
                        DateTimePicker::make('opens_at')
                            ->label('Opens At'),
                        
                        DateTimePicker::make('closes_at')
                            ->label('Closes At'),
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
                    ->sortable()
                    ->description(fn($record) => $record->description),
                BadgeColumn::make('status')
                    ->colors([
                        'gray' => SectionStatus::DRAFT->value,
                        'success' => SectionStatus::OPEN->value,
                        'danger' => SectionStatus::CLOSED->value,
                        'warning' => SectionStatus::AUTOMATED->value,
                    ]),
                BooleanColumn::make('is_required')
                    ->label('Required'),
                // Fixed: Use a different approach for counting materials
                TextColumn::make('materials_count')
                    ->label('Materials')
                    ->getStateUsing(function ($record) {
                        try {
                            // Get materials count from section_materials table
                            return \DB::table('section_materials')
                                ->where('section_id', $record->id)
                                ->count();
                        } catch (\Exception $e) {
                            return 0;
                        }
                    })
                    ->badge()
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
                Tables\Actions\CreateAction::make(),
                Tables\Actions\AttachAction::make()
                    ->form(fn (Tables\Actions\AttachAction $action): array => [
                        Select::make('recordId')
                            ->label('Section')
                            ->options(function () {
                                // Only show sections not already attached to this course
                                $attachedSectionIds = $this->getOwnerRecord()
                                    ->sections()
                                    ->pluck('sections.id')
                                    ->toArray();
                                
                                return Section::active()
                                    ->whereNotIn('id', $attachedSectionIds)
                                    ->whereNotNull('title')
                                    ->where('title', '!=', '')
                                    ->pluck('title', 'id')
                                    ->filter();
                            })
                            ->searchable()
                            ->required(),
                        TextInput::make('order_number')
                            ->label('Order')
                            ->numeric()
                            ->required()
                            ->default(function () {
                                $maxOrder = $this->getOwnerRecord()
                                    ->sections()
                                    ->max('course_sections.order_number');
                                return ($maxOrder ?? 0) + 1;
                            }),
                        Select::make('status')
                            ->options([
                                SectionStatus::DRAFT->value => SectionStatus::DRAFT->label(),
                                SectionStatus::OPEN->value => SectionStatus::OPEN->label(),
                                SectionStatus::CLOSED->value => SectionStatus::CLOSED->label(),
                                SectionStatus::AUTOMATED->value => SectionStatus::AUTOMATED->label(),
                            ])
                            ->default(SectionStatus::OPEN->value)
                            ->required(),
                        Toggle::make('is_required')
                            ->label('Required Section')
                            ->default(true),
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
            ->reorderable('order_number');
    }
}