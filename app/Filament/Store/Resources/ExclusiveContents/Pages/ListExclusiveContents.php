<?php

namespace App\Filament\Store\Resources\ExclusiveContents\Pages;

use App\Filament\Store\Resources\ExclusiveContents\ExclusiveContentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListExclusiveContents extends ListRecords
{
    protected static string $resource = ExclusiveContentResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
