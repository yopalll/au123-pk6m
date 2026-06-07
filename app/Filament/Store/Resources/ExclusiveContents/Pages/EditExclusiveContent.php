<?php

namespace App\Filament\Store\Resources\ExclusiveContents\Pages;

use App\Filament\Store\Resources\ExclusiveContents\ExclusiveContentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditExclusiveContent extends EditRecord
{
    protected static string $resource = ExclusiveContentResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
