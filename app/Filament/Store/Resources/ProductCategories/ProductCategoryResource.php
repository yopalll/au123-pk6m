<?php

namespace App\Filament\Store\Resources\ProductCategories;

use App\Filament\Store\Resources\ProductCategories\Pages\CreateProductCategory;
use App\Filament\Store\Resources\ProductCategories\Pages\EditProductCategory;
use App\Filament\Store\Resources\ProductCategories\Pages\ListProductCategories;
use App\Filament\Store\Resources\ProductCategories\Schemas\ProductCategoryForm;
use App\Filament\Store\Resources\ProductCategories\Tables\ProductCategoriesTable;
use App\Models\ProductCategory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ProductCategoryResource extends Resource
{
    protected static ?string $model = ProductCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static string|\UnitEnum|null $navigationGroup = 'Katalog';

    protected static ?string $recordTitleAttribute = 'nama';

    public static function form(Schema $schema): Schema
    {
        return ProductCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductCategoriesTable::configure($table);
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
            'index' => ListProductCategories::route('/'),
            'create' => CreateProductCategory::route('/create'),
            'edit' => EditProductCategory::route('/{record}/edit'),
        ];
    }
}
