<?php

namespace App\Filament\Store\Resources\ExclusiveContents\Pages;

use App\Filament\Store\Resources\ExclusiveContents\ExclusiveContentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateExclusiveContent extends CreateRecord
{
    protected static string $resource = ExclusiveContentResource::class;
}
