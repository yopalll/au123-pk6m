# BUG-FIX-PLAN.md

This document serves as the step-by-step execution plan for resolving the 12 findings identified in the Bug Audit. It strictly adheres to the project's standardization rules:
- **American English Spelling:** Enforcing `canceled` across the entire project.
- **Single Source of Truth:** Implementing the `App\Constants\OrderStatus` constant class.
- **Strict Casing:** PascalCase for Classes, camelCase for variables/methods, snake_case for DB schemas.
- **Language Consistency:** English for all code/schemas, Indonesian exclusively for UI labels.

---

## Phase 1: Critical Fixes & Foundation

### 1. Create `OrderStatus` Constant
**Objective:** Stop using hardcoded status strings.

- [ ] Create **`app/Constants/OrderStatus.php`**:

```php
<?php

namespace App\Constants;

class OrderStatus
{
    public const PENDING = 'pending';
    public const CONFIRMED = 'confirmed';
    public const SUCCESS = 'success';
    public const CANCELED = 'canceled'; // Enforcing American English

    public static function all(): array
    {
        return [
            self::PENDING,
            self::CONFIRMED,
            self::SUCCESS,
            self::CANCELED,
        ];
    }
}
```

### 2. Resolve BUG-01: Extend `order.status` Enum
**Objective:** Allow Midtrans webhooks to successfully update paid orders to `confirmed`.

- [ ] Run the Artisan command:
```bash
php artisan make:migration extend_status_enum_in_order_table
```

- [ ] Add the following code to the generated migration file:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE `order` MODIFY `status` ENUM('pending', 'confirmed', 'success', 'canceled') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE `order` MODIFY `status` ENUM('pending', 'success', 'canceled') NOT NULL DEFAULT 'pending'");
    }
};
```

### 3. Resolve BUG-05: Cancel-spelling inconsistency
**Objective:** Standardize the spelling of `canceled` (single 'L') in the `order_detail` table to match the core `order` table.

- [ ] Run the Artisan command:
```bash
php artisan make:migration standardize_canceled_status_in_order_detail_table
```

- [ ] Add the following code to the generated migration file:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Update existing rows first to prevent data truncation errors
        DB::statement("UPDATE `order_detail` SET `status` = 'canceled' WHERE `status` = 'cancelled'");
        DB::statement("ALTER TABLE `order_detail` MODIFY `status` ENUM('pending', 'in_progress', 'completed', 'canceled') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE `order_detail` MODIFY `status` ENUM('pending', 'in_progress', 'completed', 'cancelled') NOT NULL DEFAULT 'pending'");
        DB::statement("UPDATE `order_detail` SET `status` = 'cancelled' WHERE `status` = 'canceled'");
    }
};
```

---

## Phase 2: High Priority Logic & UI Fixes

### 1. Resolve BUG-03: Paid bookings missing from Upcoming Tab
**Objective:** Ensure customers can view their `confirmed` orders in the `/akun/bookings` panel.

- [ ] Update **`app/Http/Controllers/AkunController.php`**:
```php
use App\Constants\OrderStatus;

// Inside the relevant method
public function bookings($tab = 'mendatang')
{
    $statusMap = [
        'mendatang'  => [OrderStatus::PENDING, OrderStatus::CONFIRMED],
        'selesai'    => [OrderStatus::SUCCESS],
        'dibatalkan' => [OrderStatus::CANCELED],
    ];

    $orders = Order::where('id_user', auth()->id())
        ->when(isset($statusMap[$tab]), fn ($query) => $query->whereIn('status', (array) $statusMap[$tab]))
        ->get();

    return view('akun.bookings', compact('orders', 'tab'));
}
```

- [ ] Update **`resources/views/akun/bookings.blade.php`** logic:
```php
@php
    use App\Constants\OrderStatus;
    
    $badgeLabels = [
        OrderStatus::PENDING   => 'Menunggu Pembayaran',
        OrderStatus::CONFIRMED => 'Mendatang',
        OrderStatus::SUCCESS   => 'Selesai',
        OrderStatus::CANCELED  => 'Dibatalkan',
    ];
@endphp
```

### 2. Resolve BUG-04: Owner monthly revenue widget hides paid bookings
**Objective:** Include `confirmed` statuses when calculating collected cash.

- [ ] Update **`app/Filament/Owner/Widgets/OwnerStatsOverview.php`**:
```php
use App\Constants\OrderStatus;
use App\Models\Order;

// Inside the relevant method
$thisMonthRevenue = Order::whereIn('id_salon', $salonIds)
    ->whereIn('status', [OrderStatus::CONFIRMED, OrderStatus::SUCCESS])
    ->whereYear('date_order', now()->year)
    ->whereMonth('date_order', now()->month)
    ->sum('total_pembayaran');
```

### 3. Resolve BUG-02: Vite manifest missing
**Objective:** Ensure the frontend layout compiles successfully on a fresh clone.

- [ ] Execute the following terminal commands to fix the 500 error:
```bash
npm install && npm run build
```

---

## Phase 3: Medium Priority Logic Adjustments

### 1. Resolve BUG-06: Owner staff schedule form casing
**Objective:** Prevent database collation mismatch by writing capitalized day keys.

- [ ] Update **`app/Filament/Owner/Resources/StaffResource/RelationManagers/SchedulesRelationManager.php`**:
```php
->options([
    'Monday'    => 'Senin',
    'Tuesday'   => 'Selasa',
    'Wednesday' => 'Rabu',
    'Thursday'  => 'Kamis',
    'Friday'    => 'Jumat',
    'Saturday'  => 'Sabtu',
    'Sunday'    => 'Minggu',
])
```

### 2. Resolve BUG-08: AlpineJS Calendar selection bug
**Objective:** Stop the calendar selection from persisting incorrectly across month navigation.

- [ ] Update **`resources/views/booking/create.blade.php`**:
```html
<!-- Adjust AlpineJS component to use a full YYYY-MM-DD string format -->
<div x-data="{ 
    selectedDate: null, 
    // ...
}">
    <!-- Inside the calendar grid loop -->
    <template x-for="cell in calendarCells" :key="cell.date">
        <button 
            :class="{ 'bg-primary text-white': selectedDate === cell.date }"
            @click="selectedDate = cell.date"
            x-text="cell.day">
        </button>
    </template>
</div>
```

### 3. Resolve BUG-09 & BUG-10: BookingSlotService fixes
**Objective:** Handle empty rosters dynamically and respect the `staff_service` pivot.

- [ ] Update **`app/Services/BookingSlotService.php`**:
```php
use App\Models\Staff;
use Illuminate\Support\Facades\DB;

class BookingSlotService
{
    public function availableSlots($salon, $service, $date)
    {
        $staffQuery = Staff::query()
            ->where('id_salon', $salon->id_salon)
            ->where('status', 'active');

        // BUG-10: Filter by staff_service pivot if it exists for this service
        if (DB::table('staff_service')->where('id_service', $service->id_service)->exists()) {
            $staffQuery->whereHas('services', fn ($query) => $query->where('service.id_service', $service->id_service));
        }

        $staffList = $staffQuery->get();

        // BUG-09: Deterministically catch empty staff rosters
        if ($staffList->isEmpty()) {
            return collect(); // Salon has no eligible staff
        }

        // ... slot generation logic continues here ...
    }
}
```

---

## Phase 4: Low Priority & Edge Cases

### 1. Resolve BUG-07: Enable refunds for `confirmed` cancellation
**Objective:** Allow cancellation of paid orders and initiate an automatic Midtrans refund.

- [ ] Update **`app/Http/Controllers/BookingController.php`**:
```php
use App\Constants\OrderStatus;
use App\Models\Order;
use Midtrans\Transaction;

// Inside the relevant controller class
public function cancelBooking($orderId)
{
    $order = Order::where('id', $orderId)
        ->whereIn('status', [OrderStatus::PENDING, OrderStatus::CONFIRMED])
        ->firstOrFail();

    // Trigger Midtrans refund if the order was already paid
    if ($order->status === OrderStatus::CONFIRMED) {
        $payment = $order->pembayaran()->first();
        if ($payment) {
            Transaction::refund($payment->id_transaksi, [
                'refund_key' => 'refund_' . $order->id,
                'amount'     => $order->total_pembayaran,
                'reason'     => 'Customer requested cancellation'
            ]);
            $payment->update(['status_pembayaran' => 'refunded']);
        }
    }

    $order->update(['status' => OrderStatus::CANCELED]);

    return redirect()->back()->with('success', 'Booking berhasil dibatalkan.');
}
```

### 2. Resolve BUG-12: Document Vite manifest dependency
**Objective:** Guide future contributors to avoid BUG-02.

- [ ] Add the following snippet to the Local Setup section in **`README.md`**:
```markdown
- **Build Frontend Assets:**
  After installing dependencies with `composer install` and `npm install`, you MUST compile the frontend assets. If skipped, you will encounter a `ViteManifestNotFoundException` 500 error.
  ```bash
  npm run build
  ```
```
