<?php

namespace App\Filament\Store\Resources\Products\Pages;

use App\Filament\Store\Resources\Products\ProductResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;
}
