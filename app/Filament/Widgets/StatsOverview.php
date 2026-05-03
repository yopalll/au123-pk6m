<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\Review;
use App\Models\Salon;
use App\Models\Service;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Salons', number_format(Salon::count()))
                ->description(number_format(Salon::active()->count()) . ' active')
                ->color('primary'),

            Stat::make('Total Users', number_format(User::count()))
                ->description(number_format(User::where('role', 'customer')->count()) . ' customers'),

            Stat::make('Total Services', number_format(Service::count()))
                ->description(number_format(Service::active()->count()) . ' active'),

            Stat::make('Total Orders', number_format(Order::count()))
                ->description(number_format(Order::pending()->count()) . ' pending')
                ->color('warning'),

            Stat::make('Total Reviews', number_format(Review::count()))
                ->description(number_format(Review::visible()->count()) . ' visible'),

            Stat::make('Revenue', '£' . number_format(Order::success()->sum('total_pembayaran'), 2))
                ->color('success'),
        ];
    }
}
