<?php

namespace App\Filament\Store\Resources\Lookbooks\Pages;

use App\Filament\Store\Resources\Lookbooks\LookbookResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLookbook extends EditRecord
{
    protected static string $resource = LookbookResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
