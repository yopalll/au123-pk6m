<?php

namespace App\Filament\Store\Resources\Products;

use App\Filament\Store\Resources\Products\Pages\CreateProduct;
use App\Filament\Store\Resources\Products\Pages\EditProduct;
use App\Filament\Store\Resources\Products\Pages\ListProducts;
use App\Filament\Store\Resources\Products\Schemas\ProductForm;
use App\Filament\Store\Resources\Products\Tables\ProductsTable;
use App\Models\Product;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static string|\UnitEnum|null $navigationGroup = 'Katalog';

    protected static ?string $recordTitleAttribute = 'nama';

    public static function form(Schema $schema): Schema
    {
        return ProductForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }
}
