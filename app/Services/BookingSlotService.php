<?php

namespace App\Services;

use App\Models\OrderDetail;
use App\Models\Salon;
use App\Models\Service;
use App\Models\Staff;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Computes available booking slots for a given (salon, service, date, staff?) tuple.
 *
 * Inputs come from three sources:
 *   1. Salon.opening_time / closing_time (the outer window)
 *   2. Staff.staff_schedule rows for that weekday (the inner window per staff)
 *   3. Existing OrderDetail rows for staff on that date (the busy intervals)
 *
 * The output is a list of {time:'HH:MM', staff:[{id,name}, ...]} entries — one
 * per concrete bookable start time. The frontend can then render them as a grid
 * of buttons.
 */
class BookingSlotService
{
    /** Step size when generating candidate slots, in minutes. */
    public const STEP_MINUTES = 30;

    /**
     * @return Collection<int, array{time: string, staff: array<int, array{id:int,name:string}>}>
     */
    public function availableSlots(
        Salon $salon,
        Service $service,
        CarbonImmutable $date,
        ?int $preferredStaffId = null,
    ): Collection {
        $duration = max(15, (int) ($service->durasi ?? 30));

        $opening = $salon->opening_time
            ? CarbonImmutable::parse($salon->opening_time)
            : CarbonImmutable::createFromTime(9, 0);

        $closing = $salon->closing_time
            ? CarbonImmutable::parse($salon->closing_time)
            : CarbonImmutable::createFromTime(20, 0);

        // Schedules for active staff working at this salon on this weekday.
        // We accept staff that are bookable for any service (no staff_service
        // constraint here — most salons don't pivot specific services to staff
        // in this dataset). preferredStaffId narrows further.
        $weekday = ucfirst(strtolower($date->englishDayOfWeek));

        $staffQuery = Staff::query()
            ->where('id_salon', $salon->id_salon)
            ->where('status', 'active')
            ->with(['schedules' => fn ($q) => $q
                ->where('hari', $weekday)
                ->where('is_available', true),
            ]);

        if ($preferredStaffId) {
            $staffQuery->where('id_staff', $preferredStaffId);
        }

        $staffList = $staffQuery->get();

        // Pull all OrderDetail rows for those staff on the target date in one query.
        $busyByStaff = $this->busyByStaff(
            $staffList->pluck('id_staff')->all(),
            $date,
        );

        // Walk the salon's open window in STEP_MINUTES increments. For each step,
        // collect the staff that can deliver `duration` minutes starting there.
        $slots = collect();
        $cursor = $opening;
        $latestStart = $closing->subMinutes($duration);

        while ($cursor->lessThanOrEqualTo($latestStart)) {
            $availableStaff = [];
            $proposedEnd = $cursor->addMinutes($duration);

            foreach ($staffList as $staff) {
                if (! $this->staffWorking($staff, $cursor, $proposedEnd)) {
                    continue;
                }

                if ($this->staffBusy(
                    $busyByStaff[$staff->id_staff] ?? [],
                    $cursor,
                    $proposedEnd,
                )) {
                    continue;
                }

                $availableStaff[] = [
                    'id'   => (int) $staff->id_staff,
                    'name' => $staff->name,
                ];
            }

            // No staff = closed slot, but a salon without staff-records seeded yet
            // should still be usable — fall back to "any staff" if no schedules
            // exist for the entire roster on this day.
            if (empty($availableStaff) && $staffList->every(fn ($s) => $s->schedules->isEmpty())) {
                $availableStaff = [['id' => 0, 'name' => 'Any staff']];
            }

            if (! empty($availableStaff)) {
                $slots->push([
                    'time'  => $cursor->format('H:i'),
                    'staff' => $availableStaff,
                ]);
            }

            $cursor = $cursor->addMinutes(self::STEP_MINUTES);
        }

        // If the date is today, drop slots whose start time is already in the past.
        if ($date->isSameDay(now())) {
            $now = CarbonImmutable::now()->format('H:i');
            $slots = $slots->filter(fn ($s) => $s['time'] >= $now)->values();
        }

        return $slots;
    }

    /**
     * Re-verify (server-side, post-form) that a {date, time, staff} tuple is
     * still bookable. Used by BookingController::store right before insert
     * so two customers racing on the same slot can't both win.
     */
    public function isSlotAvailable(
        Salon $salon,
        Service $service,
        CarbonImmutable $date,
        string $time,
        ?int $staffId = null,
    ): bool {
        $available = $this->availableSlots($salon, $service, $date, $staffId);

        return $available->contains(function (array $slot) use ($time, $staffId) {
            if ($slot['time'] !== $time) {
                return false;
            }

            if ($staffId === null || $staffId === 0) {
                return true;
            }

            return collect($slot['staff'])->contains('id', $staffId);
        });
    }

    /**
     * Pick a concrete staff id for a given slot when the user said "Any staff".
     * Returns null if no staff is available (caller should fail validation).
     */
    public function pickStaffForSlot(
        Salon $salon,
        Service $service,
        CarbonImmutable $date,
        string $time,
    ): ?int {
        $slot = $this->availableSlots($salon, $service, $date)
            ->firstWhere('time', $time);

        if (! $slot) {
            return null;
        }

        $first = $slot['staff'][0] ?? null;

        return $first && $first['id'] !== 0 ? (int) $first['id'] : null;
    }

    /**
     * @param  list<int>  $staffIds
     * @return array<int, list<array{start: CarbonImmutable, end: CarbonImmutable}>>
     */
    protected function busyByStaff(array $staffIds, CarbonImmutable $date): array
    {
        if (empty($staffIds)) {
            return [];
        }

        $rows = OrderDetail::query()
            ->whereIn('id_staff', $staffIds)
            ->whereHas('order', fn ($q) => $q
                ->whereDate('date_order', $date->toDateString())
                ->whereNotIn('status', ['canceled']),
            )
            ->get(['id_staff', 'start_time', 'end_time']);

        $by = [];

        foreach ($rows as $row) {
            $by[$row->id_staff][] = [
                'start' => CarbonImmutable::parse($row->start_time),
                'end'   => CarbonImmutable::parse($row->end_time),
            ];
        }

        return $by;
    }

    protected function staffWorking(
        Staff $staff,
        CarbonImmutable $proposedStart,
        CarbonImmutable $proposedEnd,
    ): bool {
        $schedules = $staff->schedules;

        // No schedule rows at all = treat as "follows salon hours" so seeded data
        // without explicit staff_schedule rows still books.
        if ($schedules->isEmpty()) {
            return true;
        }

        return $schedules->contains(function ($sched) use ($proposedStart, $proposedEnd) {
            $schedStart = CarbonImmutable::parse($sched->start_time);
            $schedEnd   = CarbonImmutable::parse($sched->end_time);

            return $schedStart->lessThanOrEqualTo($proposedStart)
                && $schedEnd->greaterThanOrEqualTo($proposedEnd);
        });
    }

    /**
     * @param  list<array{start: CarbonImmutable, end: CarbonImmutable}>  $busy
     */
    protected function staffBusy(
        array $busy,
        CarbonImmutable $proposedStart,
        CarbonImmutable $proposedEnd,
    ): bool {
        foreach ($busy as $window) {
            // Overlap = NOT (proposedEnd <= start OR proposedStart >= end)
            $disjoint = $proposedEnd->lessThanOrEqualTo($window['start'])
                || $proposedStart->greaterThanOrEqualTo($window['end']);

            if (! $disjoint) {
                return true;
            }
        }

        return false;
    }
}
