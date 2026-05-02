<?php

namespace App\Filament\Owner\Resources\SalonImageResource\Pages;

use App\Filament\Owner\Resources\SalonImageResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSalonImage extends EditRecord
{
    protected static string $resource = SalonImageResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
