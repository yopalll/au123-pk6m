<?php

namespace App\Filament\Store\Resources\ProductReviews\Pages;

use App\Filament\Store\Resources\ProductReviews\ProductReviewResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProductReview extends CreateRecord
{
    protected static string $resource = ProductReviewResource::class;
}
