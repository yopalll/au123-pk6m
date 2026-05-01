<?php

namespace App\Http\Controllers;

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

    private function supportEmail(): string
    {
        return config('viygo.support_email', 'support@viygo.com');
    }
}
