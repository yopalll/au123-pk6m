<?php

namespace App\Filament\Store\Resources\ProductCollections\Pages;

use App\Filament\Store\Resources\ProductCollections\ProductCollectionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProductCollections extends ListRecords
{
    protected static string $resource = ProductCollectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
