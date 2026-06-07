<?php

namespace App\Filament\Store\Resources\ExclusiveContents;

use App\Filament\Store\Resources\ExclusiveContents\Pages\CreateExclusiveContent;
use App\Filament\Store\Resources\ExclusiveContents\Pages\EditExclusiveContent;
use App\Filament\Store\Resources\ExclusiveContents\Pages\ListExclusiveContents;
use App\Models\ExclusiveContent;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ExclusiveContentResource extends Resource
{
    protected static ?string $model = ExclusiveContent::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLockClosed;

    protected static string|\UnitEnum|null $navigationGroup = 'Empty Return';

    protected static ?string $recordTitleAttribute = 'judul';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('judul')
                ->required()
                ->live(onBlur: true)
                ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug((string) $state))),
            TextInput::make('slug')->required()->unique(ignoreRecord: true),
            Select::make('tipe')->options(['article' => 'Article', 'video' => 'Video', 'tip' => 'Tip'])->default('article')->required(),
            Select::make('min_tier')->options(['bronze' => 'Bronze', 'silver' => 'Silver', 'gold' => 'Gold'])->default('bronze')->required(),
            TextInput::make('video_url')->url()->label('Video URL (jika video)'),
            TextInput::make('thumbnail_url')->label('Thumbnail URL'),
            Textarea::make('konten')->rows(8)->columnSpanFull()->label('Konten (article/tip)'),
            Toggle::make('is_published')->label('Publish')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('judul')->searchable(),
                TextColumn::make('tipe')->badge(),
                TextColumn::make('min_tier')->badge()->color('warning'),
                IconColumn::make('is_published')->boolean(),
                TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('id_content', 'desc')
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListExclusiveContents::route('/'),
            'create' => CreateExclusiveContent::route('/create'),
            'edit' => EditExclusiveContent::route('/{record}/edit'),
        ];
    }
}
