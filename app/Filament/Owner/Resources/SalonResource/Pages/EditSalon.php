<?php

namespace App\Filament\Owner\Resources\SalonResource\Pages;

use App\Filament\Owner\Resources\SalonResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSalon extends EditRecord
{
    protected static string $resource = SalonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
        ];
    }
}
