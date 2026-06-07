<?php

namespace App\Filament\Store\Resources\ProductReviews\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ProductReviewForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('id_user')
                    ->required()
                    ->numeric(),
                TextInput::make('id_product')
                    ->required()
                    ->numeric(),
                TextInput::make('id_product_order')
                    ->required()
                    ->numeric(),
                TextInput::make('rating')
                    ->required()
                    ->numeric(),
                TextInput::make('judul'),
                Textarea::make('komentar')
                    ->columnSpanFull(),
                TextInput::make('foto_urls'),
                Toggle::make('is_verified_purchase')
                    ->required(),
            ]);
    }
}
