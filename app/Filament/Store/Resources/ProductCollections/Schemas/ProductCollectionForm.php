<?php

namespace App\Filament\Store\Resources\ProductCollections\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ProductCollectionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nama')
                    ->required(),
                TextInput::make('slug')
                    ->required(),
                Textarea::make('deskripsi')
                    ->columnSpanFull(),
                TextInput::make('banner_url')
                    ->url(),
                TextInput::make('tagline'),
                TextInput::make('sort_order')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }
}
