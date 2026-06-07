<?php

namespace App\Filament\Store\Widgets;

use App\Models\Product;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LowStockProducts extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public function getTableHeading(): string
    {
        return 'Stok Menipis (< 10)';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Product::query()->where('stok', '<', 10)->orderBy('stok'))
            ->columns([
                TextColumn::make('nama')->label('Produk')->limit(40)->searchable(),
                TextColumn::make('category.nama')->label('Kategori')->badge(),
                TextColumn::make('stok')->badge()->color(fn (int $state) => $state === 0 ? 'danger' : 'warning'),
                TextColumn::make('harga')->money('IDR'),
                TextColumn::make('total_sold')->label('Terjual'),
            ])
            ->paginated([5, 10]);
    }
}
