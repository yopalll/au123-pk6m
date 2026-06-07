<?php

namespace App\Filament\Store\Resources\ProductOrders\Pages;

use App\Filament\Store\Resources\ProductOrders\ProductOrderResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProductOrder extends CreateRecord
{
    protected static string $resource = ProductOrderResource::class;
}
