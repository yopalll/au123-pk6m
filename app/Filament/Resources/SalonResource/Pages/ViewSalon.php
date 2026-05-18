<?php

namespace App\Filament\Resources\SalonResource\Pages;

use App\Filament\Resources\SalonResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewSalon extends ViewRecord
{
    protected static string $resource = SalonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->url(fn () => SalonResource::getUrl('edit', ['record' => $this->record->id_salon])),
        ];
    }

    public function getBreadcrumbs(): array
    {
        return [
            SalonResource::getUrl() => SalonResource::getBreadcrumb(),
            SalonResource::getUrl('view', ['record' => $this->record->id_salon]) => $this->getRecordTitle(),
        ];
    }
}
