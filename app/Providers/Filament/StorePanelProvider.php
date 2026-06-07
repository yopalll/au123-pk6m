<?php

namespace App\Providers\Filament;

use App\Filament\Store\Widgets\LowStockProducts;
use App\Filament\Store\Widgets\SalesTrendChart;
use App\Filament\Store\Widgets\StoreStatsOverview;
use App\Http\Middleware\EnsureUserIsActive;
use App\Support\FilamentBrand;
use Filament\View\PanelsRenderHook;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class StorePanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('store')
            ->path('admin/store')
            ->login()
            ->profile()
            ->brandName(FilamentBrand::name('Store'))
            ->renderHook(PanelsRenderHook::HEAD_END, fn () => FilamentBrand::fontLink())
            ->favicon(asset('favicon.ico'))
            ->darkMode(true, isForced: true)
            ->font('Manrope')
            ->colors([
                'primary' => Color::hex('#ffb68b'),
                'gray'    => Color::Zinc,
            ])
            ->discoverResources(in: app_path('Filament/Store/Resources'), for: 'App\\Filament\\Store\\Resources')
            ->discoverPages(in: app_path('Filament/Store/Pages'), for: 'App\\Filament\\Store\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Store/Widgets'), for: 'App\\Filament\\Store\\Widgets')
            ->widgets([
                AccountWidget::class,
                StoreStatsOverview::class,
                SalesTrendChart::class,
                LowStockProducts::class,
            ])
            ->navigationGroups([
                'Katalog',
                'Pesanan',
                'Konten',
                'Komunitas',
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                ValidateCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                EnsureUserIsActive::class,
            ]);
    }
}
