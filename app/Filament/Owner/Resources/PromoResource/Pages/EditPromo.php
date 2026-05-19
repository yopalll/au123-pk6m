<?php

namespace App\Filament\Owner\Resources\PromoResource\Pages;

use App\Filament\Owner\Resources\PromoResource;
use App\Models\Salon;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPromo extends EditRecord
{
    protected static string $resource = PromoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    /**
     * Defense in depth: same ownership check on update.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $ownedIds = Salon::query()
            ->where('id_user', auth()->id())
            ->pluck('id_salon')
            ->all();

        if (! in_array((int) ($data['id_salon'] ?? 0), $ownedIds, true)) {
            abort(403, 'You can only assign discounts to your own salon.');
        }

        return $data;
    }
}
