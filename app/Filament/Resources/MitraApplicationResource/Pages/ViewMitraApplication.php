<?php

namespace App\Filament\Resources\MitraApplicationResource\Pages;

use App\Filament\Resources\MitraApplicationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class ViewMitraApplication extends EditRecord
{
    protected static string $resource = MitraApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('← All Applications')
                ->url(MitraApplicationResource::getUrl('index'))
                ->color('gray'),
        ];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Application status updated.';
    }
}
