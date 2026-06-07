<?php

namespace App\Filament\Store\Resources\ProductOrders\Pages;

use App\Filament\Store\Resources\ProductOrders\ProductOrderResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProductOrders extends ListRecords
{
    protected static string $resource = ProductOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
