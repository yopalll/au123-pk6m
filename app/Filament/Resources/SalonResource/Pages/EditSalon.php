<?php

namespace App\Filament\Resources\SalonResource\Pages;

use App\Filament\Resources\SalonResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSalon extends EditRecord
{
    protected static string $resource = SalonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}
