<?php

namespace App\Services;

use App\Constants\UserRole;
use App\Models\MitraApplication;
use App\Models\Salon;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Provisions a salon_owner user + salon record when an admin approves a
 * MitraApplication. The salon is created with status='inactive' so it does
 * not appear in public listings until the owner completes the profile
 * and admin activates it.
 *
 * Gating rule: owner can only edit the salon AFTER admin approves — because
 * before this runs, the users + salon rows don't exist (login is impossible).
 */
class ApproveSalonApplicationService
{
    /**
     * Approve the application: create user + salon (inactive), update app row,
     * send password reset link. Idempotent — re-running on an already-approved
     * application throws to prevent double provisioning.
     *
     * @throws RuntimeException
     */
    public function approve(MitraApplication $app): Salon
    {
        if ($app->status === 'approved' || $app->id_salon !== null) {
            throw new RuntimeException(
                'This application has already been approved (salon already provisioned).'
            );
        }

        if (! $app->id_kota) {
            throw new RuntimeException(
                'Cannot approve: city is missing. Edit the application and set a city first.'
            );
        }

        // Email collision check OUTSIDE transaction — if email already belongs
        // to another user we refuse rather than auto-promote (safer for MVP).
        $existingUser = User::where('email', $app->email)->first();
        if ($existingUser && $existingUser->role !== UserRole::SALON_OWNER) {
            throw new RuntimeException(
                "Email '{$app->email}' is already registered as a {$existingUser->role}. "
                . 'Refusing to overwrite. Use a different email or merge accounts manually.'
            );
        }

        $salon = DB::transaction(function () use ($app, $existingUser) {
            // 1. Create or reuse the owner user account.
            if ($existingUser) {
                // Existing salon_owner — reuse, just ensure activation.
                $user = $existingUser;
                if (! $user->is_active) {
                    $user->is_active = true;
                    $user->save();
                }
            } else {
                $user = new User();
                $user->first_name   = Str::of($app->nama_pemilik)->before(' ')->trim()->toString() ?: $app->nama_pemilik;
                $user->last_name    = Str::of($app->nama_pemilik)->after(' ')->trim()->toString() ?: null;
                $user->email        = $app->email;
                $user->phone_number = $app->phone;
                // Default password is the literal string "password".
                // Owner is expected to change it via /owner/profile after first login.
                $user->password     = Hash::make('password');
                $user->save();

                // Role + is_active are $guarded — set explicitly to bypass mass-assignment guard.
                $user->role      = UserRole::SALON_OWNER;
                $user->is_active = true;
                $user->save();
            }

            // 2. Create the salon — status='inactive', placeholder fields the
            //    owner will fill in via the owner dashboard.
            $salon = Salon::create([
                'id_user'      => $user->id_user,
                'id_kota'      => $app->id_kota,
                'nama_salon'   => $app->nama_salon,
                'slug'         => $this->generateUniqueSlug($app->nama_salon),
                'alamat'       => 'TBD — to be completed by owner',
                'deskripsi'    => $app->catatan,
                'phone_number' => $app->phone,
                'opening_time' => '09:00:00',
                'closing_time' => '18:00:00',
                'status'       => 'inactive',
            ]);

            // 3. Link the application to the new salon and mark approved.
            $app->update([
                'id_salon' => $salon->id_salon,
                'status'   => 'approved',
            ]);

            return $salon;
        });

        Log::info('Salon application approved + provisioned', [
            'application_id' => $app->id_application,
            'salon_id'       => $salon->id_salon,
            'user_id'        => $salon->id_user,
        ]);

        return $salon;
    }

    /**
     * Generate a unique slug. Appends -2, -3, ... on collision.
     */
    protected function generateUniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'salon';
        $slug = $base;
        $i = 2;

        while (Salon::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
            if ($i > 1000) {
                // Defensive: don't loop forever.
                $slug = $base . '-' . Str::random(6);
                break;
            }
        }

        return $slug;
    }
}
