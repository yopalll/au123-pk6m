<?php

namespace App\Filament\Owner\Resources\PromoResource\Pages;

use App\Filament\Owner\Resources\PromoResource;
use App\Models\Salon;
use Filament\Resources\Pages\CreateRecord;

class CreatePromo extends CreateRecord
{
    protected static string $resource = PromoResource::class;

    /**
     * Defense in depth: ensure the chosen id_salon really belongs to the
     * current owner. Filament's Select already restricts the option list,
     * but tampered POST payloads should also be blocked.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $ownedIds = Salon::query()
            ->where('id_user', auth()->id())
            ->pluck('id_salon')
            ->all();

        if (! in_array((int) ($data['id_salon'] ?? 0), $ownedIds, true)) {
            abort(403, 'You can only create discounts for your own salon.');
        }

        return $data;
    }
}
