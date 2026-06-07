<?php

namespace App\Filament\Store\Resources\ProductCategories\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ProductCategoryForm
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
                TextInput::make('icon_url')
                    ->url(),
                Select::make('parent_id')
                    ->relationship('parent', 'id_product_category'),
                TextInput::make('sort_order')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }
}
