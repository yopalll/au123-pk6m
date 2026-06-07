<?php

namespace App\Filament\Store\Resources\ProductCategories\Pages;

use App\Filament\Store\Resources\ProductCategories\ProductCategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProductCategory extends EditRecord
{
    protected static string $resource = ProductCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
