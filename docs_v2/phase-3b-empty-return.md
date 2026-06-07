# Phase 3B — Modul 3: Empty Return + Poin + Konten Eksklusif
## Step 3.2

> **Prerequisite:** Phase 2B selesai (poin harus bisa digunakan di checkout)  
> **Design Reference:**  
> - `docs_v2/design/l1_sustainability_landing/` — landing page  
> - `docs_v2/design/l2_return_request_flow/` — form pengajuan  
> - `docs_v2/design/l4_points_dashboard/` — dashboard poin & tier  
> - `docs_v2/design/l1.1_detailed_sustainability_report/` — impact report  
> - `docs_v2/design/m_l1_sustainability_landing/` — mobile landing  
> - `docs_v2/design/m_l2_return_request_flow/` — mobile form  
> **Verifikasi:** Submit → Admin approve → poin bertambah → tier naik → konten eksklusif terbuka

---

## SUB-STEP 3.2.1 — Routes

```php
// Empty Return
Route::prefix('empty-return')->name('emptyReturn.')->group(function () {
    Route::get('/',        [EmptyReturnController::class, 'index'])->name('index');
    Route::middleware('auth')->group(function () {
        Route::get('/submit',  [EmptyReturnController::class, 'create'])->name('create');
        Route::post('/submit', [EmptyReturnController::class, 'store'])->name('store');
        Route::get('/riwayat', [EmptyReturnController::class, 'history'])->name('history');
    });
});

// Poin Dashboard (dalam group akun)
Route::prefix('akun')->name('akun.')->middleware('auth')->group(function () {
    Route::get('/poin',           [PointController::class, 'index'])->name('poin');
    Route::get('/poin/riwayat',   [PointController::class, 'history'])->name('poin.history');
});

// Konten Eksklusif
Route::prefix('eksklusif')->name('exclusive.')->middleware('auth')->group(function () {
    Route::get('/',        [ExclusiveContentController::class, 'index'])->name('index');
    Route::get('/{slug}',  [ExclusiveContentController::class, 'show'])->name('show');
});
```

---

## SUB-STEP 3.2.2 — EmptyReturnController

**File:** `app/Http/Controllers/EmptyReturnController.php`

```php
<?php
namespace App\Http\Controllers;

use App\Models\{EmptyReturn, EmptyReturnPhoto, Product, UserPoint, PointTransaction};
use Illuminate\Http\Request;

class EmptyReturnController extends Controller
{
    public function index()
    {
        // Counter global (semua approved returns)
        $totalBotol     = EmptyReturn::where('status', 'approved')->sum('jumlah');
        $estimasiKg     = round($totalBotol * 0.05, 1); // asumsi 50g per botol

        return view('empty-return.index', compact('totalBotol', 'estimasiKg'));
    }

    public function create()
    {
        // Produk dari riwayat pembelian user untuk auto-fill
        $purchasedProducts = \App\Models\ProductOrderItem::whereHas('order', fn($q) =>
                $q->where('id_user', auth()->user()->id_user)
                  ->whereIn('status', ['delivered', 'completed']))
            ->with('product')
            ->get()
            ->pluck('product')
            ->unique('id_product');

        $salons = \App\Models\Salon::where('status', 'active')
                                   ->orderBy('nama')
                                   ->get(['id_salon', 'nama', 'alamat']);

        // Estimasi poin: 5 poin per botol kecil, 10 per botol besar
        return view('empty-return.create', compact('purchasedProducts', 'salons'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_produk'     => 'required|string|max:255',
            'jumlah'          => 'required|integer|min:1|max:50',
            'metode'          => 'required|in:dropoff,pickup',
            'id_salon'        => 'required_if:metode,dropoff|nullable|exists:salon,id_salon',
            'alamat_pickup'   => 'required_if:metode,pickup|nullable|string',
            'foto'            => 'nullable|array|max:3',
            'foto.*'          => 'image|max:2048',
        ]);

        $emptyReturn = EmptyReturn::create([
            'id_user'       => auth()->user()->id_user,
            'id_product'    => $request->id_product ?? null,
            'id_salon'      => $request->metode === 'dropoff' ? $request->id_salon : null,
            'nama_produk'   => $request->nama_produk,
            'jumlah'        => $request->jumlah,
            'metode'        => $request->metode,
            'alamat_pickup' => $request->alamat_pickup,
            'status'        => 'pending',
        ]);

        // Handle foto upload
        if ($request->hasFile('foto')) {
            foreach ($request->file('foto') as $foto) {
                $path = $foto->store('empty-returns', 'public');
                EmptyReturnPhoto::create(['id_return' => $emptyReturn->id_return, 'photo_url' => $path]);
            }
        }

        return redirect()->route('emptyReturn.history')
                         ->with('success', 'Pengajuan pengembalian berhasil dikirim! Kami akan memverifikasi dalam 1-3 hari kerja.');
    }

    public function history()
    {
        $returns = EmptyReturn::where('id_user', auth()->user()->id_user)
                              ->with('photos')
                              ->latest()->paginate(10);

        return view('empty-return.history', compact('returns'));
    }
}
```

---

## SUB-STEP 3.2.3 — PointController

**File:** `app/Http/Controllers/PointController.php`

```php
<?php
namespace App\Http\Controllers;

use App\Models\{UserPoint, PointTransaction, ExclusiveContent};

class PointController extends Controller
{
    public function index()
    {
        $user       = auth()->user();
        $userPoint  = UserPoint::firstOrCreate(
            ['id_user' => $user->id_user],
            ['saldo' => 0, 'total_earned' => 0, 'total_spent' => 0, 'tier' => 'starter']
        );

        // Progress ke tier berikutnya
        $tierThresholds = ['starter' => 0, 'bronze' => 50, 'silver' => 150, 'gold' => 300];
        $tiers          = array_keys($tierThresholds);
        $currentIdx     = array_search($userPoint->tier, $tiers);
        $nextTier       = $tiers[$currentIdx + 1] ?? null;
        $nextThreshold  = $nextTier ? $tierThresholds[$nextTier] : null;
        $progress       = $nextThreshold
            ? min(100, ($userPoint->total_earned / $nextThreshold) * 100)
            : 100;

        // Konten yang bisa diakses berdasarkan tier
        $tierOrder          = ['starter' => 0, 'bronze' => 1, 'silver' => 2, 'gold' => 3];
        $accessibleTiers    = array_slice($tiers, 0, $currentIdx + 1);
        $exclusiveContents  = ExclusiveContent::where('is_published', true)
                                              ->whereIn('min_tier', $accessibleTiers)
                                              ->limit(6)->get();

        // Riwayat transaksi (5 terbaru)
        $recentTransactions = PointTransaction::where('id_user', $user->id_user)
                                              ->latest()->limit(5)->get();

        return view('akun.poin', compact(
            'userPoint', 'nextTier', 'nextThreshold', 'progress',
            'exclusiveContents', 'recentTransactions', 'tierThresholds'
        ));
    }

    public function history()
    {
        $transactions = PointTransaction::where('id_user', auth()->user()->id_user)
                                        ->latest()->paginate(20);
        return view('akun.poin-history', compact('transactions'));
    }
}
```

---

## SUB-STEP 3.2.4 — ExclusiveContentController

**File:** `app/Http/Controllers/ExclusiveContentController.php`

```php
<?php
namespace App\Http\Controllers;

use App\Models\{ExclusiveContent, UserPoint};

class ExclusiveContentController extends Controller
{
    public function index()
    {
        $userPoint  = UserPoint::where('id_user', auth()->user()->id_user)->first();
        $userTier   = $userPoint?->tier ?? 'starter';

        $tierOrder  = ['starter' => 0, 'bronze' => 1, 'silver' => 2, 'gold' => 3];
        $userLevel  = $tierOrder[$userTier];

        $contents = ExclusiveContent::where('is_published', true)->get()->map(function ($c) use ($tierOrder, $userLevel) {
            $c->is_accessible = $userLevel >= $tierOrder[$c->min_tier];
            return $c;
        });

        return view('exclusive.index', compact('contents', 'userTier'));
    }

    public function show(string $slug)
    {
        $content    = ExclusiveContent::where('slug', $slug)->where('is_published', true)->firstOrFail();
        $userPoint  = UserPoint::where('id_user', auth()->user()->id_user)->first();
        $userTier   = $userPoint?->tier ?? 'starter';

        $tierOrder  = ['starter' => 0, 'bronze' => 1, 'silver' => 2, 'gold' => 3];
        $accessible = ($tierOrder[$userTier] ?? 0) >= ($tierOrder[$content->min_tier] ?? 99);

        if (!$accessible) {
            return redirect()->route('exclusive.index')
                             ->with('error', "Konten ini membutuhkan tier {$content->min_tier} atau lebih tinggi.");
        }

        return view('exclusive.show', compact('content'));
    }
}
```

---

## SUB-STEP 3.2.5 — Service: Approve Empty Return + Credit Poin

Buat helper service `app/Services/PointService.php`:

```php
<?php
namespace App\Services;

use App\Models\{UserPoint, PointTransaction, EmptyReturn};

class PointService
{
    // Panggil ini saat Admin approve empty return
    public static function creditFromEmptyReturn(EmptyReturn $emptyReturn): void
    {
        $poin = $emptyReturn->poin_earned;
        if ($poin <= 0) return;

        $userPoint = UserPoint::firstOrCreate(
            ['id_user' => $emptyReturn->id_user],
            ['saldo' => 0, 'total_earned' => 0, 'total_spent' => 0, 'tier' => 'starter']
        );

        $newSaldo = $userPoint->saldo + $poin;
        $newTotal = $userPoint->total_earned + $poin;

        // Hitung tier baru
        $tier = match(true) {
            $newTotal >= 300 => 'gold',
            $newTotal >= 150 => 'silver',
            $newTotal >= 50  => 'bronze',
            default          => 'starter',
        };

        $userPoint->update([
            'saldo'        => $newSaldo,
            'total_earned' => $newTotal,
            'tier'         => $tier,
        ]);

        PointTransaction::create([
            'id_user'        => $emptyReturn->id_user,
            'type'           => 'earn',
            'amount'         => $poin,
            'source'         => 'empty_return',
            'reference_id'   => $emptyReturn->id_return,
            'reference_type' => 'empty_return',
            'description'    => "Poin dari pengembalian botol: {$emptyReturn->nama_produk} ({$emptyReturn->jumlah} botol)",
            'saldo_after'    => $newSaldo,
            'created_at'     => now(),
        ]);
    }

    // Panggil saat checkout menggunakan poin
    public static function spendPoints(int $idUser, int $poin, int $idProductOrder): float
    {
        $userPoint = UserPoint::where('id_user', $idUser)->first();
        if (!$userPoint || $userPoint->saldo < $poin) return 0;

        $potongan  = $poin * 1000; // 1 poin = Rp 1.000
        $newSaldo  = $userPoint->saldo - $poin;

        $userPoint->update(['saldo' => $newSaldo, 'total_spent' => $userPoint->total_spent + $poin]);

        PointTransaction::create([
            'id_user'        => $idUser,
            'type'           => 'spend',
            'amount'         => $poin,
            'source'         => 'purchase_discount',
            'reference_id'   => $idProductOrder,
            'reference_type' => 'product_order',
            'description'    => "Poin digunakan untuk diskon pembelian",
            'saldo_after'    => $newSaldo,
            'created_at'     => now(),
        ]);

        return $potongan;
    }
}
```

---

## SUB-STEP 3.2.6 — Update Filament: EmptyReturn Approval

> 🔴 **Filament v5** (lihat [CATATAN-LINGKUNGAN §5](CATATAN-LINGKUNGAN.md)): custom action ada di
> namespace `Filament\Actions\Action` (BUKAN `Filament\Tables\Actions\Action`). Resource pakai
> `form(Schema $form): Schema`. Tiru `app/Filament/Resources/OrderResource.php` (pola read+update).

Di `EmptyReturnResource` (Filament Admin Store), tambahkan action Approve/Reject:

```php
// Dalam EmptyReturnResource ->recordActions([ ... ]) :
\Filament\Actions\Action::make('approve')
    ->label('Approve')
    ->icon('heroicon-o-check')
    ->color('success')
    ->form([
        Forms\Components\TextInput::make('poin_earned')
            ->label('Poin yang diberikan')
            ->numeric()
            ->default(fn ($record) => $record->jumlah * 5) // 5 poin per botol default
            ->required(),
        Forms\Components\Textarea::make('catatan_admin')
            ->label('Catatan (opsional)'),
    ])
    ->action(function (EmptyReturn $record, array $data) {
        $record->update([
            'status'       => 'approved',
            'poin_earned'  => $data['poin_earned'],
            'catatan_admin'=> $data['catatan_admin'] ?? null,
            'verified_by'  => auth()->user()->id_user,
            'verified_at'  => now(),
        ]);

        // Credit poin ke user
        \App\Services\PointService::creditFromEmptyReturn($record);
    })
    ->visible(fn ($record) => $record->status === 'pending'),

\Filament\Actions\Action::make('reject')
    ->label('Reject')
    ->icon('heroicon-o-x-mark')
    ->color('danger')
    ->form([
        Forms\Components\Textarea::make('catatan_admin')
            ->label('Alasan penolakan')
            ->required(),
    ])
    ->action(fn (EmptyReturn $record, array $data) => $record->update([
        'status'       => 'rejected',
        'catatan_admin'=> $data['catatan_admin'],
        'verified_by'  => auth()->user()->id_user,
        'verified_at'  => now(),
    ]))
    ->visible(fn ($record) => $record->status === 'pending'),
```

---

## SUB-STEP 3.2.7 — Update Checkout: Apply Poin

Update `ProductCheckoutController::store()` — tambahkan logika pemakaian poin:

```php
// Di dalam store(), setelah validasi:
$poinDigunakan = 0;
$potonganPoin  = 0;

if ($request->poin_digunakan && $request->poin_digunakan > 0) {
    $userPoint = \App\Models\UserPoint::where('id_user', $user->id_user)->first();
    if ($userPoint && $userPoint->saldo >= $request->poin_digunakan) {
        $poinDigunakan = (int) $request->poin_digunakan;
        $potonganPoin  = $poinDigunakan * 1000; // 1 poin = Rp 1.000
    }
}

// Lanjut buat order, setelah order dibuat:
if ($poinDigunakan > 0) {
    \App\Services\PointService::spendPoints($user->id_user, $poinDigunakan, $order->id_product_order);
}
```

---

## SUB-STEP 3.2.8 — Views

| View | Design Reference |
|------|-----------------|
| `resources/views/empty-return/index.blade.php` | `l1_sustainability_landing/code.html` |
| `resources/views/empty-return/create.blade.php` | `l2_return_request_flow/code.html` |
| `resources/views/empty-return/history.blade.php` | (list sederhana) |
| `resources/views/akun/poin.blade.php` | `l4_points_dashboard/code.html` |
| `resources/views/akun/poin-history.blade.php` | (table transaksi) |
| `resources/views/exclusive/index.blade.php` | (grid konten dengan lock untuk tier tidak cukup) |
| `resources/views/exclusive/show.blade.php` | (article/video/tip content) |

---

## VERIFIKASI

```
1. Buka /empty-return → landing page tampil dengan counter botol + impact meter
2. Login → /empty-return/submit → isi form, upload foto → submit
3. Login sebagai admin.store → /admin/store/empty-returns → lihat pengajuan
4. Approve pengajuan → set poin_earned = 10
5. Buka /akun/poin → saldo bertambah 10 poin, tier progress update
6. Jika total_earned >= 50 → tier naik ke Bronze → konten Bronze terbuka
7. Buka /eksklusif → konten Bronze tampil, konten Silver/Gold terkunci
8. Checkout → input poin_digunakan = 5 → potongan Rp 5.000 di ringkasan
```

Lanjutkan ke **[phase-3c-community.md](phase-3c-community.md)**.
