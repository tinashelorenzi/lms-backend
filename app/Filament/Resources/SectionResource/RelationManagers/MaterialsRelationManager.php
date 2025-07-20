<?php

namespace App\Filament\Resources\SectionResource\RelationManagers;

use App\Models\LearningMaterial;
use App\Services\CourseContentService;
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
use Illuminate\Support\Facades\DB;

class MaterialsRelationManager extends RelationManager
{
    protected static string $relationship = 'materials';
    protected static ?string $recordTitleAttribute = 'title';

    protected CourseContentService $courseContentService;

    public function boot()
    {
        $this->courseContentService = app(CourseContentService::class);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('learning_material_id')
                    ->label('Learning Material')
                    ->options(function () {
                        // Fixed: Get materials from MongoDB with proper error handling
                        try {
                            return LearningMaterial::where('is_active', true)
                                ->whereNotNull('title')
                                ->get()
                                ->pluck('title', '_id')
                                ->filter(); // Remove any null values
                        } catch (\Exception $e) {
                            return [];
                        }
                    })
                    ->required()
                    ->searchable()
                    ->createOptionForm([
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255),
                        Textarea::make('description')
                            ->rows(2),
                        Select::make('content_type')
                            ->label('Type')
                            ->options([
                                'text' => 'Text/HTML',
                                'video' => 'Video',
                                'document' => 'Document',
                                'quiz' => 'Quiz',
                                'assignment' => 'Assignment',
                                'interactive' => 'Interactive',
                            ])
                            ->required(),
                        TextInput::make('estimated_duration')
                            ->label('Duration (minutes)')
                            ->numeric(),
                        Select::make('difficulty_level')
                            ->options([
                                'beginner' => 'Beginner',
                                'intermediate' => 'Intermediate',
                                'advanced' => 'Advanced',
                            ])
                            ->default('beginner'),
                        Toggle::make('is_active')
                            ->default(true),
                    ])
                    ->createOptionUsing(function (array $data) {
                        // Create new material in MongoDB
                        $material = LearningMaterial::createMaterial($data);
                        return $material->_id;
                    }),
                
                TextInput::make('order_number')
                    ->label('Order')
                    ->numeric()
                    ->default(function () {
                        // Get next order number for this section
                        return DB::table('section_materials')
                            ->where('section_id', $this->getOwnerRecord()->id)
                            ->max('order_number') + 1 ?? 1;
                    })
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
            ->query(function () {
                // Custom query to handle the MongoDB relationship
                $sectionId = $this->getOwnerRecord()->id;
                
                // Get materials for this section from the pivot table
                $materialData = DB::table('section_materials')
                    ->where('section_id', $sectionId)
                    ->orderBy('order_number')
                    ->get();

                // Create a collection with the material information
                $materials = collect();
                
                foreach ($materialData as $pivotData) {
                    try {
                        $mongoMaterial = LearningMaterial::find($pivotData->learning_material_id);
                        
                        if ($mongoMaterial) {
                            // Create a combined object with both pivot and material data
                            $combinedData = (object) array_merge(
                                (array) $pivotData,
                                [
                                    'id' => $pivotData->id, // Use pivot table ID for Filament
                                    'title' => $mongoMaterial->title,
                                    'description' => $mongoMaterial->description,
                                    'content_type' => $mongoMaterial->content_type,
                                    'estimated_duration' => $mongoMaterial->estimated_duration,
                                    'difficulty_level' => $mongoMaterial->difficulty_level,
                                    'is_active' => $mongoMaterial->is_active,
                                    'mongo_id' => $mongoMaterial->_id,
                                ]
                            );
                            
                            $materials->push($combinedData);
                        }
                    } catch (\Exception $e) {
                        // Skip materials that can't be loaded from MongoDB
                        continue;
                    }
                }

                // Return a query builder-like object that Filament can work with
                return $materials;
            })
            ->columns([
                TextColumn::make('order_number')
                    ->label('#')
                    ->sortable()
                    ->alignCenter(),
                TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->description(fn($record) => $record->description ?? ''),
                BadgeColumn::make('content_type')
                    ->label('Type')
                    ->colors([
                        'primary' => 'text',
                        'success' => 'video',
                        'warning' => 'quiz',
                        'info' => 'document',
                        'secondary' => 'assignment',
                        'danger' => 'interactive',
                    ]),
                BooleanColumn::make('is_required')
                    ->label('Required'),
                TextColumn::make('estimated_duration')
                    ->label('Duration (min)')
                    ->alignCenter()
                    ->toggleable(),
                BadgeColumn::make('difficulty_level')
                    ->label('Difficulty')
                    ->colors([
                        'success' => 'beginner',
                        'warning' => 'intermediate',
                        'danger' => 'advanced',
                    ]),
                BooleanColumn::make('is_active')
                    ->label('Active')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('content_type')
                    ->label('Type')
                    ->options([
                        'text' => 'Text/HTML',
                        'video' => 'Video',
                        'document' => 'Document',
                        'quiz' => 'Quiz',
                        'assignment' => 'Assignment',
                        'interactive' => 'Interactive',
                    ]),
                SelectFilter::make('difficulty_level')
                    ->label('Difficulty')
                    ->options([
                        'beginner' => 'Beginner',
                        'intermediate' => 'Intermediate',
                        'advanced' => 'Advanced',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add Material')
                    ->using(function (array $data) {
                        // Custom creation logic
                        return $this->courseContentService->addMaterialToSection(
                            $this->getOwnerRecord()->id,
                            $data['learning_material_id'],
                            $data['order_number'],
                            [
                                'is_required' => $data['is_required'],
                                'completion_criteria' => $data['completion_criteria'] ?? [],
                            ]
                        );
                    }),
                Tables\Actions\Action::make('createNewMaterial')
                    ->label('Create New Material')
                    ->icon('heroicon-o-plus')
                    ->form([
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255),
                        Textarea::make('description')
                            ->rows(3),
                        Select::make('content_type')
                            ->label('Type')
                            ->options([
                                'text' => 'Text/HTML',
                                'video' => 'Video',
                                'document' => 'Document',
                                'quiz' => 'Quiz',
                                'assignment' => 'Assignment',
                                'interactive' => 'Interactive',
                            ])
                            ->required(),
                        TextInput::make('estimated_duration')
                            ->label('Duration (minutes)')
                            ->numeric(),
                        Select::make('difficulty_level')
                            ->options([
                                'beginner' => 'Beginner',
                                'intermediate' => 'Intermediate',
                                'advanced' => 'Advanced',
                            ])
                            ->default('beginner'),
                        TextInput::make('order_number')
                            ->label('Order')
                            ->numeric()
                            ->default(function () {
                                return DB::table('section_materials')
                                    ->where('section_id', $this->getOwnerRecord()->id)
                                    ->max('order_number') + 1 ?? 1;
                            })
                            ->required(),
                        Toggle::make('is_required')
                            ->label('Required Material')
                            ->default(true),
                    ])
                    ->action(function (array $data) {
                        // Create the material and add it to the section
                        $material = $this->courseContentService->createLearningMaterial(
                            array_merge($data, [
                                'section_id' => $this->getOwnerRecord()->id,
                            ])
                        );
                        
                        if ($material) {
                            \Filament\Notifications\Notification::make()
                                ->title('Material created successfully!')
                                ->success()
                                ->send();
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title('Failed to create material')
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->form([
                        TextInput::make('order_number')
                            ->label('Order')
                            ->numeric()
                            ->required(),
                        Toggle::make('is_required')
                            ->label('Required Material'),
                        KeyValue::make('completion_criteria')
                            ->label('Completion Criteria')
                            ->keyLabel('Criterion')
                            ->valueLabel('Value'),
                    ])
                    ->using(function ($record, array $data) {
                        // Update the pivot table data
                        DB::table('section_materials')
                            ->where('id', $record->id)
                            ->update([
                                'order_number' => $data['order_number'],
                                'is_required' => $data['is_required'],
                                'completion_criteria' => json_encode($data['completion_criteria'] ?? []),
                                'updated_at' => now(),
                            ]);
                        
                        return $record;
                    }),
                Tables\Actions\DeleteAction::make()
                    ->using(function ($record) {
                        // Remove from section_materials pivot table
                        DB::table('section_materials')
                            ->where('id', $record->id)
                            ->delete();
                        
                        return $record;
                    }),
                Tables\Actions\Action::make('editContent')
                    ->label('Edit Content')
                    ->icon('heroicon-o-pencil-square')
                    ->url(function ($record) {
                        // This would redirect to a dedicated material editor
                        return route('filament.admin.resources.learning-materials.edit', $record->mongo_id);
                    })
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->using(function ($records) {
                            $ids = $records->pluck('id')->toArray();
                            DB::table('section_materials')
                                ->whereIn('id', $ids)
                                ->delete();
                        }),
                ]),
            ])
            ->reorderable('order_number')
            ->defaultSort('order_number');
    }
}