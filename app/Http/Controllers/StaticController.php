<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Renders the static informational pages linked from the footer
 * (About, Careers, Press, Help, Contact, Privacy, Terms, Cookie).
 *
 * Each method maps 1:1 to a Blade view under resources/views/static/.
 * Help and Contact pages surface the support email defined in
 * config('viygo.support_email').
 */
class StaticController extends Controller
{
    public function about()      { return view('static.about'); }
    public function careers()    { return view('static.careers'); }
    public function press()      { return view('static.press'); }
    public function help()       { return view('static.help',    ['supportEmail' => $this->supportEmail()]); }
    public function contact()    { return view('static.contact', ['supportEmail' => $this->supportEmail()]); }
    public function privacy()    { return view('static.privacy'); }
    public function terms()      { return view('static.terms'); }
    public function cookies()    { return view('static.cookies'); }

    /**
     * Handle the contact form submission. Sends a plain-text email to the
     * support inbox. We don't persist the message — keeps the schema small.
     */
    public function submitContact(Request $request)
    {
        $data = $request->validate([
            'name'    => 'required|string|max:200',
            'email'   => 'required|email|max:200',
            'subject' => 'nullable|string|max:200',
            'message' => 'required|string|max:5000',
        ]);

        try {
            Mail::raw(
                "From: {$data['name']} <{$data['email']}>\n\n"
                . ($data['subject'] ? "Subject: {$data['subject']}\n\n" : '')
                . $data['message'],
                function ($msg) use ($data) {
                    $msg->to($this->supportEmail())
                        ->replyTo($data['email'], $data['name'])
                        ->subject('VIYGO contact form: ' . ($data['subject'] ?? 'New message'));
                },
            );
        } catch (\Throwable $e) {
            Log::warning('contact form mail failed', [
                'email' => $data['email'],
                'error' => $e->getMessage(),
            ]);

            return back()->withInput()->withErrors([
                'mail' => "Couldn't send your message just now. Please email us at {$this->supportEmail()} directly.",
            ]);
        }

        return back()->with('success', "Thanks {$data['name']} — we've received your message and will reply soon.");
    }

    /**
     * Newsletter subscription used by /treatment-files. We log it for now;
     * a real list would post to Mailchimp / Resend / Buttondown.
     */
    public function subscribeNewsletter(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email|max:200',
        ]);

        Log::info('newsletter signup', ['email' => $data['email']]);

        return back()->with('success', 'You\'re on the list. Check your inbox to confirm.');
    }

    private function supportEmail(): string
    {
        return config('viygo.support_email', 'support@viygo.com');
    }
}
