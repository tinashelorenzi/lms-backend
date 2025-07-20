<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LearningMaterialResource\Pages;
use App\Filament\Resources\LearningMaterialResource\RelationManagers;
use App\Models\LearningMaterial;
use App\Enums\ContentFormat;
use App\Services\ContentProcessor;
use App\Forms\Components\BlockEditor; // Add this import
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
                                        TagsInput::make('tags')
                                            ->separator(',')
                                            ->columnSpanFull(),
                                        Group::make([
                                            Toggle::make('is_active')
                                                ->label('Active')
                                                ->default(true),
                                            Toggle::make('is_featured')
                                                ->label('Featured'),
                                        ])
                                        ->columns(2),
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
                                            ->default(ContentFormat::RICH_HTML->value)
                                            ->live()
                                            ->afterStateUpdated(function (Set $set, $state) {
                                                // Clear content when switching formats
                                                $set('content_raw', '');
                                                $set('content_blocks', []);
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

                                        // BLOCK EDITOR - Replace the Textarea with the custom component
                                        BlockEditor::make('content_blocks')
                                            ->label('Content Blocks')
                                            ->allowLatex(fn (Get $get): bool => $get('allow_latex') ?? false)
                                            ->allowEmbeds(fn (Get $get): bool => $get('allow_embeds') ?? true)
                                            ->allowedBlocks([
                                                'paragraph', 'heading', 'image', 'video', 
                                                'code', 'quote', 'list', 'latex', 'embed'
                                            ])
                                            ->visible(fn (Get $get): bool => $get('content_format') === ContentFormat::BLOCK_EDITOR->value),
                                    ])
                                    ->columnSpanFull(),
                            ]),

                        // METADATA TAB
                        Tabs\Tab::make('Metadata')
                            ->icon('heroicon-o-cog-6-tooth')
                            ->schema([
                                Section::make('Content Configuration')
                                    ->schema([
                                        KeyValue::make('editor_config')
                                            ->label('Editor Configuration')
                                            ->keyLabel('Setting')
                                            ->valueLabel('Value')
                                            ->addActionLabel('Add Setting'),

                                        KeyValue::make('embedded_media')
                                            ->label('Embedded Media')
                                            ->keyLabel('Media Type')
                                            ->valueLabel('URL/Data')
                                            ->addActionLabel('Add Media'),
                                    ])
                                    ->columns(1),

                                Section::make('Preview')
                                    ->schema([
                                        Placeholder::make('content_preview')
                                            ->label('Rendered Content')
                                            ->content(function (LearningMaterial $record = null): string {
                                                if (!$record) {
                                                    return 'Save the material to see a preview.';
                                                }
                                                
                                                $processor = app(ContentProcessor::class);
                                                return $processor->compile($record);
                                            })
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    // ... rest of your table and other methods remain the same
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                BadgeColumn::make('content_format')
                    ->label('Format')
                    ->colors([
                        'primary' => ContentFormat::RICH_HTML->value,
                        'success' => ContentFormat::MARKDOWN->value,
                        'warning' => ContentFormat::PLAIN_TEXT->value,
                        'info' => ContentFormat::BLOCK_EDITOR->value,
                    ]),
                TextColumn::make('tags')
                    ->badge()
                    ->separator(','),
                BooleanColumn::make('allow_latex')
                    ->label('LaTeX'),
                BooleanColumn::make('allow_embeds')
                    ->label('Embeds'),
                BooleanColumn::make('is_active')
                    ->label('Active'),
                BooleanColumn::make('is_featured')
                    ->label('Featured'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('content_format')
                    ->options([
                        ContentFormat::RICH_HTML->value => ContentFormat::RICH_HTML->label(),
                        ContentFormat::MARKDOWN->value => ContentFormat::MARKDOWN->label(),
                        ContentFormat::PLAIN_TEXT->value => ContentFormat::PLAIN_TEXT->label(),
                        ContentFormat::BLOCK_EDITOR->value => ContentFormat::BLOCK_EDITOR->label(),
                    ]),
                TernaryFilter::make('allow_latex'),
                TernaryFilter::make('allow_embeds'),
                TernaryFilter::make('is_active'),
                TernaryFilter::make('is_featured'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    BulkAction::make('activate')
                        ->label('Activate Selected')
                        ->icon('heroicon-m-check')
                        ->action(fn (Collection $records) => 
                            $records->each->update(['is_active' => true])
                        ),
                    BulkAction::make('deactivate')
                        ->label('Deactivate Selected')
                        ->icon('heroicon-m-x-mark')
                        ->action(fn (Collection $records) => 
                            $records->each->update(['is_active' => false])
                        ),
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
        ];
    }
}