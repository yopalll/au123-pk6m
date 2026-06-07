<?php

namespace App\Support;

use Illuminate\Support\HtmlString;

/**
 * Brand wordmark "VIYGÖ" gaya Serene Floral Noir (Playfair copper gradient)
 * untuk dipakai sebagai brandName di panel Filament.
 */
class FilamentBrand
{
    public static function name(string $suffix): HtmlString
    {
        $word = '<span style="font-family:\'Playfair Display\',serif;font-weight:700;font-size:21px;letter-spacing:.02em;'
            .'background:linear-gradient(135deg,#ffdbc8,#ffb68b 60%,#e09a6a);-webkit-background-clip:text;background-clip:text;'
            .'-webkit-text-fill-color:transparent;color:transparent;">VIYG&Ouml;</span>';

        $tag = '<span style="font-family:Manrope,sans-serif;font-size:11px;color:#a5cbea;text-transform:uppercase;'
            .'letter-spacing:.18em;margin-left:7px;">'.e($suffix).'</span>';

        return new HtmlString('<span style="display:inline-flex;align-items:baseline;">'.$word.$tag.'</span>');
    }

    /** Link Google Font Playfair untuk di-inject ke <head> panel. */
    public static function fontLink(): HtmlString
    {
        return new HtmlString(
            '<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">'
        );
    }
}
