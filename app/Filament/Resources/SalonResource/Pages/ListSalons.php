<?php

namespace App\Filament\Resources\SalonResource\Pages;

use App\Filament\Resources\SalonResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSalons extends ListRecords
{
    protected static string $resource = SalonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
