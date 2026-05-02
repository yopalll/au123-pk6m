<?php

namespace App\Filament\Owner\Resources\SalonResource\Pages;

use App\Filament\Owner\Resources\SalonResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewSalon extends ViewRecord
{
    protected static string $resource = SalonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
