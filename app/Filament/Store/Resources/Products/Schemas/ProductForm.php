<?php

namespace App\Filament\Store\Resources\Products\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('id_product_category')
                    ->required()
                    ->numeric(),
                TextInput::make('id_collection')
                    ->numeric(),
                TextInput::make('nama')
                    ->required(),
                TextInput::make('slug')
                    ->required(),
                Textarea::make('deskripsi')
                    ->required()
                    ->columnSpanFull(),
                Textarea::make('key_ingredients')
                    ->columnSpanFull(),
                Textarea::make('full_ingredients')
                    ->columnSpanFull(),
                Textarea::make('cara_pemakaian')
                    ->columnSpanFull(),
                TextInput::make('harga')
                    ->required()
                    ->numeric(),
                TextInput::make('harga_diskon')
                    ->numeric(),
                TextInput::make('stok')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('berat_gram')
                    ->required()
                    ->numeric(),
                TextInput::make('volume_ml')
                    ->numeric(),
                TextInput::make('skin_type')
                    ->required()
                    ->default('all'),
                TextInput::make('skin_concern'),
                TextInput::make('brand')
                    ->required()
                    ->default('Fresh'),
                TextInput::make('badge'),
                TextInput::make('rating')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('total_review')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('total_sold')
                    ->required()
                    ->numeric()
                    ->default(0),
                Select::make('status')
                    ->options(['active' => 'Active', 'inactive' => 'Inactive', 'out_of_stock' => 'Out of stock'])
                    ->default('active')
                    ->required(),
                Toggle::make('is_featured')
                    ->required(),
                TextInput::make('fresh_product_id'),
                TextInput::make('fresh_url')
                    ->url(),
            ]);
    }
}
