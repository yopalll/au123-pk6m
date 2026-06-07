<?php

namespace App\Filament\Store\Widgets;

use App\Models\EmptyReturn;
use App\Models\Product;
use App\Models\ProductOrder;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StoreStatsOverview extends StatsOverviewWidget
{
    protected ?string $heading = 'Ringkasan Toko';

    protected function getStats(): array
    {
        $today = now()->toDateString();

        $revenueToday = ProductOrder::whereDate('created_at', $today)
            ->whereIn('status', ['paid', 'processing', 'shipped', 'delivered', 'completed'])
            ->sum('grand_total');

        return [
            Stat::make('Produk Aktif', number_format(Product::where('status', 'active')->count()))
                ->description(number_format(Product::where('stok', '<', 10)->count()).' stok menipis')
                ->descriptionColor('warning')
                ->color('primary'),

            Stat::make('Pesanan Hari Ini', number_format(ProductOrder::whereDate('created_at', $today)->count()))
                ->description('Rp '.number_format($revenueToday, 0, ',', '.'))
                ->color('success'),

            Stat::make('Perlu Diproses', number_format(ProductOrder::whereIn('status', ['pending', 'paid'])->count()))
                ->description('Pesanan menunggu')
                ->color('warning'),

            Stat::make('Empty Return Pending', number_format(EmptyReturn::where('status', 'pending')->count()))
                ->description('Perlu verifikasi')
                ->color('danger'),
        ];
    }
}
