<?php

namespace App\Filament\Store\Resources\ProductCollections;

use App\Filament\Store\Resources\ProductCollections\Pages\CreateProductCollection;
use App\Filament\Store\Resources\ProductCollections\Pages\EditProductCollection;
use App\Filament\Store\Resources\ProductCollections\Pages\ListProductCollections;
use App\Filament\Store\Resources\ProductCollections\Schemas\ProductCollectionForm;
use App\Filament\Store\Resources\ProductCollections\Tables\ProductCollectionsTable;
use App\Models\ProductCollection;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ProductCollectionResource extends Resource
{
    protected static ?string $model = ProductCollection::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static string|\UnitEnum|null $navigationGroup = 'Katalog';

    protected static ?string $recordTitleAttribute = 'nama';

    public static function form(Schema $schema): Schema
    {
        return ProductCollectionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductCollectionsTable::configure($table);
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
            'index' => ListProductCollections::route('/'),
            'create' => CreateProductCollection::route('/create'),
            'edit' => EditProductCollection::route('/{record}/edit'),
        ];
    }
}
