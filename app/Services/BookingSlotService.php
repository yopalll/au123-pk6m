<?php

namespace App\Services;

use App\Models\OrderDetail;
use App\Models\Salon;
use App\Models\Service;
use App\Models\Staff;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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
        return $this->availableSlotsForDuration(
            $salon,
            (int) ($service->durasi ?? 30),
            $date,
            $preferredStaffId,
            [$service->id_service],
        );
    }

    /**
     * Versi multi-service: hitung slot untuk total durasi (sum semua service yang dipilih).
     *
     * @param  array<int>  $serviceIds  ID service yang dipilih (untuk filter staff_service pivot bila ada)
     * @return Collection<int, array{time: string, staff: array<int, array{id:int,name:string}>}>
     */
    public function availableSlotsForDuration(
        Salon $salon,
        int $totalDurationMinutes,
        CarbonImmutable $date,
        ?int $preferredStaffId = null,
        array $serviceIds = [],
    ): Collection {
        $duration = max(15, $totalDurationMinutes);

        $opening = $salon->opening_time
            ? CarbonImmutable::parse($salon->opening_time)
            : CarbonImmutable::createFromTime(9, 0);

        $closing = $salon->closing_time
            ? CarbonImmutable::parse($salon->closing_time)
            : CarbonImmutable::createFromTime(20, 0);

        $weekday = ucfirst(strtolower($date->englishDayOfWeek));

        $staffQuery = Staff::query()
            ->where('id_salon', $salon->id_salon)
            ->where('status', 'active')
            ->with(['schedules' => fn ($q) => $q
                ->where('hari', $weekday)
                ->where('is_available', true),
            ]);

        // Filter staff yang bisa kerjakan SEMUA service yang dipilih (intersect).
        // Hanya berlaku bila ada baris staff_service untuk service-service ini;
        // selain itu semua active staff dianggap bookable.
        $serviceIdsWithPivot = array_values(array_filter(
            $serviceIds,
            fn ($id) => $this->serviceHasStaffPivot((int) $id),
        ));
        foreach ($serviceIdsWithPivot as $sid) {
            $staffQuery->whereHas('services', fn (Builder $q) => $q
                ->where('service.id_service', $sid)
            );
        }

        if ($preferredStaffId) {
            $staffQuery->where('id_staff', $preferredStaffId);
        }

        $staffList = $staffQuery->get();

        // Salon hasn't onboarded any staff (or none qualified for this
        // service via staff_service). Returning an empty collection lets
        // the wizard show a clean "no slots" empty-state instead of
        // booking against id_staff = null in a confusing way.
        if ($staffList->isEmpty()) {
            return collect();
        }

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
        return $this->isSlotAvailableForDuration(
            $salon,
            (int) ($service->durasi ?? 30),
            $date,
            $time,
            $staffId,
            [$service->id_service],
        );
    }

    /**
     * Versi multi-service: cek slot tersedia untuk total durasi gabungan.
     *
     * @param  array<int>  $serviceIds
     */
    public function isSlotAvailableForDuration(
        Salon $salon,
        int $totalDurationMinutes,
        CarbonImmutable $date,
        string $time,
        ?int $staffId = null,
        array $serviceIds = [],
    ): bool {
        $available = $this->availableSlotsForDuration(
            $salon,
            $totalDurationMinutes,
            $date,
            $staffId,
            $serviceIds,
        );

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
     */
    public function pickStaffForSlot(
        Salon $salon,
        Service $service,
        CarbonImmutable $date,
        string $time,
    ): ?int {
        return $this->pickStaffForSlotForDuration(
            $salon,
            (int) ($service->durasi ?? 30),
            $date,
            $time,
            [$service->id_service],
        );
    }

    /**
     * Versi multi-service: pilih staff untuk slot dengan total durasi gabungan.
     *
     * @param  array<int>  $serviceIds
     */
    public function pickStaffForSlotForDuration(
        Salon $salon,
        int $totalDurationMinutes,
        CarbonImmutable $date,
        string $time,
        array $serviceIds = [],
    ): ?int {
        $slot = $this->availableSlotsForDuration($salon, $totalDurationMinutes, $date, null, $serviceIds)
            ->firstWhere('time', $time);

        if (! $slot) {
            return null;
        }

        $first = $slot['staff'][0] ?? null;

        return $first && $first['id'] !== 0 ? (int) $first['id'] : null;
    }

    /**
     * Cheap check (cached per request) for whether this service has any
     * staff_service rows. If it does, we narrow the staff list; otherwise
     * we treat every active staff as bookable.
     */
    protected function serviceHasStaffPivot(int $idService): bool
    {
        static $cache = [];

        if (! array_key_exists($idService, $cache)) {
            $cache[$idService] = DB::table('staff_service')
                ->where('id_service', $idService)
                ->exists();
        }

        return $cache[$idService];
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
            if (! $row->start_time || ! $row->end_time) {
                continue;
            }
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
