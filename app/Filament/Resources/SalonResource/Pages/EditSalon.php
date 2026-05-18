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
            Actions\ViewAction::make()
                ->url(fn () => SalonResource::getUrl('view', ['record' => $this->record->id_salon])),
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    public function getBreadcrumbs(): array
    {
        return [
            SalonResource::getUrl() => SalonResource::getBreadcrumb(),
            SalonResource::getUrl('view', ['record' => $this->record->id_salon]) => $this->getRecordTitle(),
            $this->getBreadcrumb(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return SalonResource::getUrl('view', ['record' => $this->record->id_salon]);
    }
}
