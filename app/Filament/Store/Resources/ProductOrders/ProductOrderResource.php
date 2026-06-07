<?php

namespace App\Filament\Store\Resources\ProductOrders;

use App\Filament\Store\Resources\ProductOrders\Pages\EditProductOrder;
use App\Filament\Store\Resources\ProductOrders\Pages\ListProductOrders;
use App\Filament\Store\Resources\ProductOrders\Schemas\ProductOrderForm;
use App\Filament\Store\Resources\ProductOrders\Tables\ProductOrdersTable;
use App\Models\ProductOrder;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ProductOrderResource extends Resource
{
    protected static ?string $model = ProductOrder::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Pesanan';

    protected static ?string $recordTitleAttribute = 'kode_order';

    // PRD 10.3 — Admin Store hanya Read + Update (status, resi), bukan Create.
    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return ProductOrderForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductOrdersTable::configure($table);
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
            'index' => ListProductOrders::route('/'),
            'edit' => EditProductOrder::route('/{record}/edit'),
        ];
    }
}
