<?php

namespace App\Filament\Store\Resources\Lookbooks\Pages;

use App\Filament\Store\Resources\Lookbooks\LookbookResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLookbooks extends ListRecords
{
    protected static string $resource = LookbookResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
