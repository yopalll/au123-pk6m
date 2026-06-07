<?php

namespace App\Filament\Store\Resources\ProductReviews\Pages;

use App\Filament\Store\Resources\ProductReviews\ProductReviewResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProductReviews extends ListRecords
{
    protected static string $resource = ProductReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
