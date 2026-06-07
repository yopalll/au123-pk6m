<?php

namespace App\Filament\Store\Resources\ProductCollections\Pages;

use App\Filament\Store\Resources\ProductCollections\ProductCollectionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProductCollection extends EditRecord
{
    protected static string $resource = ProductCollectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
