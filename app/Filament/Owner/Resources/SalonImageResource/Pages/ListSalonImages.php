<?php

namespace App\Filament\Owner\Resources\SalonImageResource\Pages;

use App\Filament\Owner\Resources\SalonImageResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSalonImages extends ListRecords
{
    protected static string $resource = SalonImageResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
