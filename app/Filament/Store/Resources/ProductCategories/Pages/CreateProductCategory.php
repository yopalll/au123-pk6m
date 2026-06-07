<?php

namespace App\Filament\Store\Resources\ProductCategories\Pages;

use App\Filament\Store\Resources\ProductCategories\ProductCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProductCategory extends CreateRecord
{
    protected static string $resource = ProductCategoryResource::class;
}
