<?php

namespace App\Filament\Store\Resources\ProductCollections\Pages;

use App\Filament\Store\Resources\ProductCollections\ProductCollectionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProductCollection extends CreateRecord
{
    protected static string $resource = ProductCollectionResource::class;
}
