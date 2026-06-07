<?php

namespace App\Services;

use App\Models\EmptyReturn;
use App\Models\PointTransaction;
use App\Models\UserPoint;
use Illuminate\Support\Facades\DB;

class PointService
{
    /** Ambang tier berdasarkan total_earned. */
    public const TIERS = ['starter' => 0, 'bronze' => 50, 'silver' => 150, 'gold' => 300];

    public static function tierFor(int $totalEarned): string
    {
        return match (true) {
            $totalEarned >= 300 => 'gold',
            $totalEarned >= 150 => 'silver',
            $totalEarned >= 50 => 'bronze',
            default => 'starter',
        };
    }

    public static function getOrCreate(int $idUser): UserPoint
    {
        return UserPoint::firstOrCreate(
            ['id_user' => $idUser],
            ['saldo' => 0, 'total_earned' => 0, 'total_spent' => 0, 'tier' => 'starter']
        );
    }

    /** Kredit poin saat empty return di-approve admin. */
    public static function creditFromEmptyReturn(EmptyReturn $return): void
    {
        $poin = (int) $return->poin_earned;
        if ($poin <= 0) {
            return;
        }

        DB::transaction(function () use ($return, $poin) {
            $up = self::getOrCreate($return->id_user);
            $newSaldo = $up->saldo + $poin;
            $newEarned = $up->total_earned + $poin;

            $up->update([
                'saldo' => $newSaldo,
                'total_earned' => $newEarned,
                'tier' => self::tierFor($newEarned),
            ]);

            PointTransaction::create([
                'id_user' => $return->id_user,
                'type' => 'earn',
                'amount' => $poin,
                'source' => 'empty_return',
                'reference_id' => $return->id_return,
                'reference_type' => 'empty_return',
                'description' => "Poin dari pengembalian botol: {$return->nama_produk} ({$return->jumlah} botol)",
                'saldo_after' => $newSaldo,
            ]);
        });

        BadgeService::check($return->id_user);
    }

    /** Pakai poin sebagai potongan saat checkout. Kembalikan rupiah potongan. */
    public static function spend(int $idUser, int $poin, int $idProductOrder): float
    {
        $up = UserPoint::where('id_user', $idUser)->first();
        if (! $up || $poin <= 0 || $up->saldo < $poin) {
            return 0.0;
        }

        $potongan = $poin * 1000; // 1 poin = Rp 1.000
        $newSaldo = $up->saldo - $poin;

        DB::transaction(function () use ($up, $poin, $newSaldo, $idUser, $idProductOrder) {
            $up->update(['saldo' => $newSaldo, 'total_spent' => $up->total_spent + $poin]);

            PointTransaction::create([
                'id_user' => $idUser,
                'type' => 'spend',
                'amount' => $poin,
                'source' => 'purchase_discount',
                'reference_id' => $idProductOrder,
                'reference_type' => 'product_order',
                'description' => 'Poin digunakan untuk diskon pembelian',
                'saldo_after' => $newSaldo,
            ]);
        });

        return (float) $potongan;
    }
}
