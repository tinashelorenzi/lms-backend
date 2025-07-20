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
use Filament\Forms\Components\Section as FormSection;
use Filament\Forms\Components\Tabs;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Columns\BadgeColumn;
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
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('section_tabs')
                    ->tabs([
                        // SECTION INFORMATION TAB
                        Tabs\Tab::make('Section Information')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                FormSection::make('Basic Information')
                                    ->schema([
                                        TextInput::make('title')
                                            ->required()
                                            ->maxLength(255)
                                            ->columnSpanFull(),
                                        Textarea::make('description')
                                            ->rows(3)
                                            ->columnSpanFull(),
                                        Textarea::make('objectives')
                                            ->label('Learning Objectives')
                                            ->rows(4)
                                            ->placeholder('What will students learn in this section?')
                                            ->columnSpanFull(),
                                        TextInput::make('estimated_duration')
                                            ->label('Estimated Duration (minutes)')
                                            ->numeric()
                                            ->columnSpan(1),
                                        Toggle::make('is_active')
                                            ->default(true)
                                            ->columnSpan(1),
                                    ])
                                    ->columns(2),
                            ]),

                        // LEARNING MATERIALS TAB
                        Tabs\Tab::make('Learning Materials')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Repeater::make('sectionMaterials')
                                    ->relationship('materials')
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
                                            })
                                            ->columnSpan(2),
                                        
                                        TextInput::make('order_number')
                                            ->label('Order')
                                            ->numeric()
                                            ->default(1)
                                            ->required()
                                            ->columnSpan(1),
                                        
                                        Toggle::make('is_required')
                                            ->default(true)
                                            ->columnSpan(1),
                                        
                                        KeyValue::make('completion_criteria')
                                            ->label('Completion Criteria')
                                            ->keyLabel('Criterion')
                                            ->valueLabel('Value')
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(2)
                                    ->defaultItems(0)
                                    ->reorderable('order_number')
                                    ->collapsible()
                                    ->itemLabel(fn (array $state): ?string => 
                                        LearningMaterial::find($state['learning_material_id'])?->title ?? 'New Material'
                                    ),
                            ]),

                        // METADATA TAB
                        Tabs\Tab::make('Advanced Settings')
                            ->icon('heroicon-o-cog-6-tooth')
                            ->schema([
                                FormSection::make('Section Metadata')
                                    ->schema([
                                        KeyValue::make('metadata')
                                            ->keyLabel('Property')
                                            ->valueLabel('Value')
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
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
                    ->toggleable(),
                BadgeColumn::make('materials_count')
                    ->label('Materials')
                    ->counts('materials')
                    ->color('primary'),
                BadgeColumn::make('courses_count')
                    ->label('Used in Courses')
                    ->counts('courses')
                    ->color('success'),
                TextColumn::make('estimated_duration')
                    ->label('Duration (min)')
                    ->alignCenter()
                    ->toggleable(),
                BooleanColumn::make('is_active')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Status'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\ReplicateAction::make()
                    ->label('Duplicate')
                    ->excludeAttributes(['created_at', 'updated_at']),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    BulkAction::make('activate')
                        ->label('Activate Selected')
                        ->icon('heroicon-o-check')
                        ->action(fn (Collection $records) => $records->each->update(['is_active' => true]))
                        ->requiresConfirmation(),
                    BulkAction::make('deactivate')
                        ->label('Deactivate Selected')
                        ->icon('heroicon-o-x-mark')
                        ->action(fn (Collection $records) => $records->each->update(['is_active' => false]))
                        ->requiresConfirmation(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\MaterialsRelationManager::class,
            RelationManagers\CoursesRelationManager::class,
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