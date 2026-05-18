<?php

namespace App\Observers;

use App\Models\Review;
use App\Models\Salon;

class ReviewObserver
{
    /**
     * After any review write that could affect the visible aggregate,
     * recompute salon.rating + salon.total_review.
     *
     * Why saveQuietly: we don't want to bump salon.updated_at on every
     * review change; that would invalidate caches keyed on that column.
     */
    public function saved(Review $review): void
    {
        $this->recompute($review->id_salon);
    }

    public function deleted(Review $review): void
    {
        $this->recompute($review->id_salon);
    }

    protected function recompute(int $idSalon): void
    {
        // BUG-A13: Use withTrashed() so soft-deleted salons are still recomputed
        // on review edit/delete; rating is preserved correctly on restore.
        $salon = Salon::withTrashed()->find($idSalon);

        if (! $salon) {
            return;
        }

        $stats = Review::where('id_salon', $idSalon)
            ->where('is_visible', true)
            ->selectRaw('AVG(rating) as avg_rating, COUNT(*) as cnt')
            ->first();

        $salon->rating       = $stats?->cnt ? round((float) $stats->avg_rating, 2) : 0;
        $salon->total_review = (int) ($stats?->cnt ?? 0);

        $salon->saveQuietly();
    }
}
