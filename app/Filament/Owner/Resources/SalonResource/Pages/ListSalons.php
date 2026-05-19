<?php

namespace App\Filament\Owner\Resources\SalonResource\Pages;

use App\Filament\Owner\Resources\SalonResource;
use App\Models\Salon;
use Filament\Resources\Pages\ListRecords;

class ListSalons extends ListRecords
{
    protected static string $resource = SalonResource::class;

    public function mount(): void
    {
        /** @var Salon|null $salon */
        $salon = Salon::where('id_user', auth()->id())->first();

        if ($salon) {
            $this->redirect(SalonResource::getUrl('view', ['record' => $salon->id_salon]));
            return;
        }

        parent::mount();
    }
}
