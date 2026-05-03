<?php

namespace App\Http\Controllers;

use App\Models\Kota;
use App\Models\MitraApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class MitraController extends Controller
{
    public function index()
    {
        $kotas = Kota::orderBy('nama_kota')->get();

        return view('mitra.index', compact('kotas'));
    }

    public function apply(Request $request)
    {
        $data = $request->validate([
            'nama_salon'   => 'required|string|max:200',
            'nama_pemilik' => 'required|string|max:200',
            'email'        => 'required|email|max:200',
            'phone'        => 'required|string|max:50',
            'kota'         => 'nullable|integer|exists:kota,id_kota',
            'catatan'      => 'nullable|string|max:5000',
        ]);

        $application = MitraApplication::create([
            'nama_salon'   => $data['nama_salon'],
            'nama_pemilik' => $data['nama_pemilik'],
            'email'        => $data['email'],
            'phone'        => $data['phone'],
            'id_kota'      => $data['kota'] ?? null,
            'catatan'      => $data['catatan'] ?? null,
            'status'       => 'new',
        ]);

        // Notify VIYGO partnerships team. We send via the configured mailer
        // (default `log` in dev — check storage/logs/laravel.log).
        try {
            Mail::raw(
                "New salon application:\n\n"
                . "Salon: {$application->nama_salon}\n"
                . "Owner: {$application->nama_pemilik}\n"
                . "Email: {$application->email}\n"
                . "Phone: {$application->phone}\n"
                . "City id: " . ($application->id_kota ?? '—') . "\n\n"
                . "Notes:\n" . ($application->catatan ?? '(none)'),
                function ($msg) use ($application) {
                    $msg->to(config('viygo.help_email', 'partners@viygo.com'))
                        ->replyTo($application->email, $application->nama_pemilik)
                        ->subject('New VIYGO salon application: ' . $application->nama_salon);
                },
            );
        } catch (\Throwable $e) {
            // Don't fail the user's submission if mail is misconfigured.
            // The DB row is the source of truth; notification is best-effort.
            Log::warning('mitra application mail failed', [
                'application_id' => $application->id_application,
                'error'          => $e->getMessage(),
            ]);
        }

        return back()
            ->with('success', "Thanks {$application->nama_pemilik} — we'll review your application and get back to you within 24 hours.")
            ->with('mitra_applied', true);
    }
}
