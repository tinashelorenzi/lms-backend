<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LearningMaterialResource\Pages;
use App\Filament\Resources\LearningMaterialResource\RelationManagers;
use App\Models\LearningMaterial;
use App\Enums\LearningMaterialType;
use App\Services\VideoService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Placeholder;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Filament\Forms\Get;
use Filament\Forms\Set;

class LearningMaterialResource extends Resource
{
    protected static ?string $model = LearningMaterial::class;
    protected static ?string $navigationIcon = 'heroicon-o-play-circle';
    protected static ?string $navigationLabel = 'Learning Materials';
    protected static ?string $navigationGroup = 'Learning Management';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255),
                        Textarea::make('description')
                            ->rows(3),
                        Select::make('type')
                            ->options([
                                LearningMaterialType::TEXT->value => LearningMaterialType::TEXT->label(),
                                LearningMaterialType::VIDEO->value => LearningMaterialType::VIDEO->label(),
                            ])
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Set $set, $state) {
                                if ($state === LearningMaterialType::TEXT->value) {
                                    $set('video_platform', null);
                                    $set('video_id', null);
                                    $set('video_url', null);
                                }
                            }),
                        TextInput::make('estimated_duration')
                            ->label('Estimated Duration (minutes)')
                            ->numeric()
                            ->minValue(1)
                            ->placeholder('e.g., 15'),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Content')
                    ->schema([
                        RichEditor::make('content')
                            ->label('Text Content')
                            ->toolbarButtons([
                                'attachFiles',
                                'blockquote',
                                'bold',
                                'bulletList',
                                'codeBlock',
                                'h2',
                                'h3',
                                'italic',
                                'link',
                                'orderedList',
                                'redo',
                                'strike',
                                'underline',
                                'undo',
                            ])
                            ->visible(fn (Get $get): bool => $get('type') === LearningMaterialType::TEXT->value),
                    ]),

                Forms\Components\Section::make('Video Information')
                    ->schema([
                        TextInput::make('video_url')
                            ->label('Video URL')
                            ->url()
                            ->placeholder('https://www.youtube.com/watch?v=...')
                            ->helperText('Supported platforms: YouTube, Vimeo, Dailymotion, Loom, Google Drive'),
                        TextInput::make('video_platform')
                            ->label('Platform')
                            ->disabled()
                            ->dehydrated(),
                        TextInput::make('video_id')
                            ->label('Video ID')
                            ->disabled()
                            ->dehydrated(),
                        Placeholder::make('video_preview')
                            ->label('Video Preview')
                            ->content('Enter video URL and platform details to see preview')
                            ->formatStateUsing(fn ($state) => new \Illuminate\Support\HtmlString($state)),
                        KeyValue::make('video_metadata')
                            ->label('Video Metadata')
                            ->keyLabel('Property')
                            ->valueLabel('Value')
                            ->addActionLabel('Add metadata'),
                    ])
                    ->visible(fn (Get $get): bool => $get('type') === LearningMaterialType::VIDEO->value),

                Forms\Components\Section::make('Classification')
                    ->schema([
                        TagsInput::make('tags')
                            ->label('Tags')
                            ->placeholder('Add tags for categorization')
                            ->suggestions([
                                'programming', 'database', 'web-development', 'mobile', 'design',
                                'beginner', 'intermediate', 'advanced', 'tutorial', 'theory',
                                'practical', 'assignment', 'quiz', 'exercise'
                            ]),
                        KeyValue::make('metadata')
                            ->label('Additional Metadata')
                            ->keyLabel('Key')
                            ->valueLabel('Value')
                            ->addActionLabel('Add metadata'),
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
                TextColumn::make('estimated_duration')
                    ->label('Duration (min)')
                    ->sortable(),
                TextColumn::make('sections_count')
                    ->counts('sections')
                    ->label('Used in Sections'),
                TextColumn::make('tags')
                    ->badge()
                    ->separator(',')
                    ->limit(3),
                BooleanColumn::make('is_active')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        LearningMaterialType::TEXT->value => LearningMaterialType::TEXT->label(),
                        LearningMaterialType::VIDEO->value => LearningMaterialType::VIDEO->label(),
                    ]),
                SelectFilter::make('video_platform')
                    ->options([
                        'youtube' => 'YouTube',
                        'vimeo' => 'Vimeo',
                        'dailymotion' => 'Dailymotion',
                        'loom' => 'Loom',
                        'google_drive' => 'Google Drive',
                    ])
                    ->visible(fn () => LearningMaterial::where('type', LearningMaterialType::VIDEO)->exists()),
                TernaryFilter::make('is_active')
                    ->label('Active Status'),
                Tables\Filters\Filter::make('unused')
                    ->label('Unused Materials')
                    ->query(fn (Builder $query): Builder => $query->doesntHave('sections')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('duplicate')
                    ->label('Duplicate')
                    ->icon('heroicon-o-document-duplicate')
                    ->action(function (LearningMaterial $record) {
                        $newMaterial = $record->replicate();
                        $newMaterial->title = $record->title . ' (Copy)';
                        $newMaterial->save();
                    })
                    ->successNotificationTitle('Material duplicated successfully'),
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
            RelationManagers\SectionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLearningMaterials::route('/'),
            'create' => Pages\CreateLearningMaterial::route('/create'),
            'view' => Pages\ViewLearningMaterial::route('/{record}'),
            'edit' => Pages\EditLearningMaterial::route('/{record}/edit'),
        ];
    }
}