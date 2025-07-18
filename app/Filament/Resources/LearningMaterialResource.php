<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LearningMaterialResource\Pages;
use App\Filament\Resources\LearningMaterialResource\RelationManagers;
use App\Models\LearningMaterial;
use App\Enums\ContentFormat;
use App\Services\ContentProcessor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Placeholder;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Columns\BadgeColumn;
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
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Learning Materials';
    protected static ?string $navigationGroup = 'Learning Management';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('content')
                    ->tabs([
                        // CONTENT TAB
                        Tabs\Tab::make('Content')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Section::make('Basic Information')
                                    ->schema([
                                        TextInput::make('title')
                                            ->required()
                                            ->maxLength(255)
                                            ->columnSpanFull(),
                                        Textarea::make('description')
                                            ->rows(2)
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(2),

                                Section::make('Content Editor')
                                    ->schema([
                                        Select::make('content_format')
                                            ->options([
                                                ContentFormat::RICH_HTML->value => ContentFormat::RICH_HTML->label(),
                                                ContentFormat::MARKDOWN->value => ContentFormat::MARKDOWN->label(),
                                                ContentFormat::PLAIN_TEXT->value => ContentFormat::PLAIN_TEXT->label(),
                                                ContentFormat::BLOCK_EDITOR->value => ContentFormat::BLOCK_EDITOR->label(),
                                            ])
                                            ->default(ContentFormat::RICH_HTML)
                                            ->live()
                                            ->afterStateUpdated(function (Set $set, $state) {
                                                // Clear content when switching formats
                                                $set('content_raw', '');
                                            }),

                                        Group::make([
                                            Toggle::make('allow_latex')
                                                ->label('Enable LaTeX')
                                                ->helperText('Allow mathematical expressions using LaTeX syntax'),
                                            Toggle::make('allow_embeds')
                                                ->label('Enable Web Embeds')
                                                ->helperText('Auto-convert URLs to embedded content')
                                                ->default(true),
                                        ])
                                        ->columns(2),

                                                                                 // RICH HTML EDITOR
                                         Forms\Components\RichEditor::make('content_raw')
                                             ->label('Content')
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
                                             ->visible(fn (Get $get): bool => $get('content_format') === ContentFormat::RICH_HTML->value),

                                                                                 // MARKDOWN EDITOR
                                         Forms\Components\Textarea::make('content_raw')
                                             ->label('Markdown Content')
                                             ->rows(20)
                                             ->helperText('Write content using Markdown syntax')
                                             ->visible(fn (Get $get): bool => $get('content_format') === ContentFormat::MARKDOWN->value),

                                         // PLAIN TEXT EDITOR
                                         Textarea::make('content_raw')
                                             ->label('Content')
                                             ->rows(20)
                                             ->visible(fn (Get $get): bool => $get('content_format') === ContentFormat::PLAIN_TEXT->value),

                                         // BLOCK EDITOR
                                         Forms\Components\Textarea::make('content_blocks')
                                             ->label('Content Blocks (JSON)')
                                             ->rows(20)
                                             ->helperText('Advanced block editor - JSON format')
                                             ->visible(fn (Get $get): bool => $get('content_format') === ContentFormat::BLOCK_EDITOR->value),
                                    ])
                                    ->columnSpanFull(),
                            ]),

                        // MEDIA TAB
                        Tabs\Tab::make('Embedded Media')
                            ->icon('heroicon-o-photo')
                            ->schema([
                                Section::make('Media Library')
                                    ->schema([
                                        KeyValue::make('embedded_media')
                                            ->label('Embedded Media')
                                            ->keyLabel('Type')
                                            ->valueLabel('URL/Data')
                                            ->addActionLabel('Add Media')
                                            ->helperText('Add images, videos, audio, or files'),
                                    ])
                                    ->columnSpanFull(),

                                Section::make('Video Sources')
                                    ->schema([
                                        KeyValue::make('video_sources')
                                            ->label('External Video URLs')
                                            ->keyLabel('Platform')
                                            ->valueLabel('URL')
                                            ->addActionLabel('Add Video')
                                            ->helperText('YouTube, Vimeo, Dailymotion, Loom, Google Drive'),
                                    ])
                                    ->columnSpanFull(),
                            ]),

                        // SETTINGS TAB
                        Tabs\Tab::make('Settings')
                            ->icon('heroicon-o-cog-6-tooth')
                            ->schema([
                                Section::make('Content Settings')
                                    ->schema([
                                        TextInput::make('estimated_duration')
                                            ->label('Estimated Duration (minutes)')
                                            ->numeric()
                                            ->minValue(1)
                                            ->maxValue(999)
                                            ->helperText('Leave empty for auto-calculation'),
                                        Toggle::make('is_active')
                                            ->label('Active')
                                            ->default(true),
                                    ])
                                    ->columns(2),

                                Section::make('Editor Configuration')
                                    ->schema([
                                        KeyValue::make('editor_config')
                                            ->label('Editor Settings')
                                            ->keyLabel('Setting')
                                            ->valueLabel('Value')
                                            ->addActionLabel('Add Setting')
                                            ->helperText('Custom editor configuration options'),
                                    ])
                                    ->collapsible()
                                    ->collapsed(),

                                Section::make('Classification')
                                    ->schema([
                                        TagsInput::make('tags')
                                            ->label('Tags')
                                            ->placeholder('Add tags for categorization')
                                            ->suggestions([
                                                'programming', 'database', 'web-development', 'mobile', 'design',
                                                'beginner', 'intermediate', 'advanced', 'tutorial', 'theory',
                                                'practical', 'assignment', 'quiz', 'exercise', 'interactive',
                                                'video', 'text', 'mixed-media', 'latex', 'mathematics'
                                            ]),
                                        KeyValue::make('metadata')
                                            ->label('Additional Metadata')
                                            ->keyLabel('Key')
                                            ->valueLabel('Value')
                                            ->addActionLabel('Add metadata'),
                                    ]),
                            ]),

                        // PREVIEW TAB
                        Tabs\Tab::make('Preview')
                            ->icon('heroicon-o-eye')
                            ->schema([
                                Section::make('Content Preview')
                                    ->schema([
                                        Placeholder::make('content_preview')
                                            ->label('Content Preview')
                                            ->content('Content preview will be available here')
                                            ->formatStateUsing(fn ($state) => new \Illuminate\Support\HtmlString($state)),
                                    ])
                                    ->columnSpanFull(),

                                Section::make('Statistics')
                                    ->schema([
                                        Placeholder::make('content_stats')
                                            ->label('Content Statistics')
                                            ->content('Content statistics will be displayed here'),
                                    ])
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->columnSpanFull()
                    ->persistTabInQueryString(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
                
                BadgeColumn::make('content_type')
                    ->label('Type')
                    ->colors([
                        'primary' => 'text',
                        'success' => 'video',
                        'warning' => 'mixed',
                        'gray' => 'empty',
                    ])
                    ->icons([
                        'heroicon-o-document-text' => 'text',
                        'heroicon-o-play-circle' => 'video',
                        'heroicon-o-squares-plus' => 'mixed',
                        'heroicon-o-exclamation-triangle' => 'empty',
                    ]),

                BadgeColumn::make('content_format')
                    ->label('Format')
                    ->colors([
                        'primary' => ContentFormat::RICH_HTML->value,
                        'info' => ContentFormat::MARKDOWN->value,
                        'gray' => ContentFormat::PLAIN_TEXT->value,
                        'warning' => ContentFormat::BLOCK_EDITOR->value,
                    ])
                    ->formatStateUsing(fn ($state) => $state instanceof ContentFormat ? $state->label() : ContentFormat::from($state)->label()),

                TextColumn::make('estimated_time')
                    ->label('Duration')
                    ->suffix(' min')
                    ->sortable(),

                TextColumn::make('sections_count')
                    ->counts('sections')
                    ->label('Used in Sections')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('tags')
                    ->badge()
                    ->separator(',')
                    ->limit(3)
                    ->toggleable(),

                BooleanColumn::make('allow_latex')
                    ->label('LaTeX')
                    ->toggleable(isToggledHiddenByDefault: true),

                BooleanColumn::make('allow_embeds')
                    ->label('Embeds')
                    ->toggleable(isToggledHiddenByDefault: true),

                BooleanColumn::make('is_active')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('content_format')
                    ->label('Format')
                    ->options([
                        ContentFormat::RICH_HTML->value => ContentFormat::RICH_HTML->label(),
                        ContentFormat::MARKDOWN->value => ContentFormat::MARKDOWN->label(),
                        ContentFormat::PLAIN_TEXT->value => ContentFormat::PLAIN_TEXT->label(),
                        ContentFormat::BLOCK_EDITOR->value => ContentFormat::BLOCK_EDITOR->label(),
                    ]),

                SelectFilter::make('content_type')
                    ->label('Content Type')
                    ->options([
                        'text' => 'Text Only',
                        'video' => 'Video Only',
                        'mixed' => 'Mixed Content',
                        'empty' => 'Empty',
                    ]),

                TernaryFilter::make('allow_latex')
                    ->label('LaTeX Enabled'),

                TernaryFilter::make('allow_embeds')
                    ->label('Embeds Enabled'),

                TernaryFilter::make('is_active')
                    ->label('Active Status'),

                Tables\Filters\Filter::make('unused')
                    ->label('Unused Materials')
                    ->query(fn (Builder $query): Builder => $query->doesntHave('sections')),

                Tables\Filters\Filter::make('has_video')
                    ->label('Contains Video')
                    ->query(fn (Builder $query): Builder => $query->withVideo()),
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
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    
                    BulkAction::make('activate')
                        ->label('Activate')
                        ->icon('heroicon-o-check')
                        ->action(fn (Collection $records) => $records->each(fn ($record) => $record->update(['is_active' => true]))),
                    
                    BulkAction::make('deactivate')
                        ->label('Deactivate')
                        ->icon('heroicon-o-x-mark')
                        ->action(fn (Collection $records) => $records->each(fn ($record) => $record->update(['is_active' => false]))),

                    BulkAction::make('enable_latex')
                        ->label('Enable LaTeX')
                        ->icon('heroicon-o-variable')
                        ->action(fn (Collection $records) => $records->each(fn ($record) => $record->update(['allow_latex' => true]))),

                    BulkAction::make('convert_format')
                        ->label('Convert Format')
                        ->icon('heroicon-o-arrow-path')
                        ->form([
                            Select::make('target_format')
                                ->label('Target Format')
                                ->options([
                                    ContentFormat::RICH_HTML->value => ContentFormat::RICH_HTML->label(),
                                    ContentFormat::MARKDOWN->value => ContentFormat::MARKDOWN->label(),
                                    ContentFormat::PLAIN_TEXT->value => ContentFormat::PLAIN_TEXT->label(),
                                ])
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data) {
                            $processor = app(ContentProcessor::class);
                            $records->each(function ($record) use ($processor, $data) {
                                $processor->convertFormat($record, ContentFormat::from($data['target_format']));
                            });
                        }),
                ]),
            ]);
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
            'edit' => Pages\EditLearningMaterial::route('/{record}/edit'),
            'view' => Pages\ViewLearningMaterial::route('/{record}'),
        ];
    }
}