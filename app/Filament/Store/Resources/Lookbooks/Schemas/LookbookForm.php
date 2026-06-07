<?php

namespace App\Filament\Store\Resources\Lookbooks\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class LookbookForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('judul')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug((string) $state))),
                TextInput::make('slug')
                    ->required()
                    ->unique(ignoreRecord: true),
                TextInput::make('tema')
                    ->required()
                    ->datalist(['Morning Routine', 'Night Care', 'Anti-Aging', 'Acne Care', 'Hydration']),
                TextInput::make('cover_url')
                    ->label('Cover image URL')
                    ->required(),
                Textarea::make('deskripsi')
                    ->required()
                    ->columnSpanFull(),
                Toggle::make('is_published')
                    ->label('Publish')
                    ->default(true),

                Repeater::make('slides')
                    ->relationship('slides')
                    ->orderColumn('sort_order')
                    ->columnSpanFull()
                    ->collapsible()
                    ->itemLabel(fn (array $state): ?string => $state['judul'] ?? 'Slide')
                    ->schema([
                        TextInput::make('judul'),
                        TextInput::make('image_url')
                            ->label('Slide image URL')
                            ->required(),
                        Textarea::make('deskripsi')
                            ->columnSpanFull(),
                        Textarea::make('tips')
                            ->columnSpanFull(),
                        Repeater::make('items')
                            ->relationship('items')
                            ->label('Produk yang di-tag')
                            ->columnSpanFull()
                            ->schema([
                                Select::make('id_product')
                                    ->relationship('product', 'nama')
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                TextInput::make('position_x')
                                    ->label('Posisi X (%)')
                                    ->numeric()
                                    ->default(50),
                                TextInput::make('position_y')
                                    ->label('Posisi Y (%)')
                                    ->numeric()
                                    ->default(50),
                            ])
                            ->columns(3),
                    ]),
            ]);
    }
}
