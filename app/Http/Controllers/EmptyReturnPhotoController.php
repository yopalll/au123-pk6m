<?php

namespace App\Http\Controllers;

use App\Constants\UserRole;
use App\Models\EmptyReturnPhoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EmptyReturnPhotoController extends Controller
{
    /**
     * Stream foto empty-return langsung lewat PHP.
     *
     * Kenapa tidak pakai URL /storage langsung? Di produksi (Docker: container
     * app PHP-FPM + web Nginx terpisah) symlink public/storage sering tidak ada
     * atau tidak ter-share antar container, sehingga gambar gagal dibuka.
     * Men-stream lewat route ini selalu berhasil selama PHP bisa membaca file,
     * sekaligus memproteksi foto (privat) hanya untuk admin store / pemiliknya.
     */
    public function show(Request $request, EmptyReturnPhoto $photo)
    {
        $user = $request->user();

        $isStaff = in_array($user->role, [UserRole::ADMIN, UserRole::ADMIN_STORE], true);
        $isOwner = $photo->emptyReturn && $photo->emptyReturn->id_user === $user->id_user;

        abort_unless($isStaff || $isOwner, 403);
        abort_unless(Storage::disk('public')->exists($photo->photo_url), 404);

        // Streamed response dengan Content-Type yang benar; cache privat.
        return Storage::disk('public')->response(
            $photo->photo_url,
            null,
            ['Cache-Control' => 'private, max-age=86400']
        );
    }
}
