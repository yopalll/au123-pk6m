<?php
/**
 * Audit kategori v2 — target diselaraskan dengan navbar Treatwell.co.uk
 * (hasil scrape dari /hairdressers-and-hair-salons/, /hair-removal-salons/,
 *  /massage-salons-and-therapists/, /nail-salons-and-nail-bars/,
 *  /beauty-salons-face-treatments/, /beauty-salons-body-treatments/).
 *
 * Output: JSON ke stdout berisi mapping label Treatwell → slug DB VIYGO + jumlah salon.
 */

$kategori = json_decode(file_get_contents(__DIR__.'/../data/kategori.json'), true);
$service  = json_decode(file_get_contents(__DIR__.'/../data/service.json'), true);

$katById   = [];
$salonByKat= [];
foreach ($kategori as $k) $katById[$k['id_kategori']] = $k;
foreach ($service as $s) {
    if (($s['status'] ?? 'active') !== 'active') continue;
    $salonByKat[$s['id_kategori']][$s['id_salon']] = true;
}

// Target sub-kategori berdasarkan listing Treatwell.
$targets = [
    'HAIR' => [
        ['label' => "Ladies' Haircuts",                'tw_slug' => 'ladies-haircuts-1',
            'patterns' => ['/ladies.*haircut/', '/wash.*haircut.*finish/'], 'exclude' => ['/men/']],
        ['label' => 'Blow Dry',                        'tw_slug' => 'blow-dry',
            'patterns' => ['/^blow.?dry/', '/^blowdry/', '/\bblow.?dry\b/'],
            'exclude'  => ['/brazilian/', '/keratin/']],
        ['label' => 'Hair Colouring',                  'tw_slug' => 'hair-colouring',
            'patterns' => ['/hair.*colour/', '/hair.*color/', '/highlight/', '/colour.*service/']],
        ['label' => "Ladies' Brazilian Blow Dry",      'tw_slug' => 'ladies-brazilian-blow-dry',
            'patterns' => ['/brazilian.*blow/', '/keratin.*treatment/', '/keratin.*smooth/']],
        ['label' => 'Balayage',                        'tw_slug' => 'balayage',
            'patterns' => ['/balayag/']],
        ['label' => 'Hair Extensions',                 'tw_slug' => 'hair-extensions',
            'patterns' => ['/hair.*extension/']],
        ['label' => "Men's Haircut",                   'tw_slug' => 'men-s-haircut',
            'patterns' => ['/men.?s.*haircut/', '/men.*haircut/', '/gent.*haircut/'],
            'exclude'  => ['/ladies/']],
    ],
    'HAIR REMOVAL' => [
        ['label' => "Ladies' Waxing",                  'tw_slug' => 'ladies-waxing',
            'patterns' => ['/ladies.*wax/', '/female.*wax/'],
            'exclude'  => ['/men/']],
        ['label' => 'Hollywood Waxing',                'tw_slug' => 'hollywood-waxing',
            'patterns' => ['/hollywood.*wax/', '/^hollywood/'],
            'exclude'  => ['/dont book/', "/don.t book/"]],
        ['label' => 'Brazilian Waxing',                'tw_slug' => 'brazilian-waxing',
            'patterns' => ['/brazilian.*wax/']],
        ['label' => 'Facial Threading',                'tw_slug' => 'facial-threading',
            'patterns' => ['/facial.*thread/', '/face.*thread/', '/^thread/']],
        ['label' => "Men's Waxing",                    'tw_slug' => 'men-s-waxing',
            'patterns' => ['/men.?s.*wax/', '/gent.*wax/'],
            'exclude'  => ['/ladies/']],
        ['label' => "Ladies' Leg Waxing",              'tw_slug' => 'ladies-leg-waxing',
            'patterns' => ['/ladies.*leg.*wax/', '/leg.*wax/'],
            'exclude'  => ['/men/']],
        ['label' => 'Sugaring',                        'tw_slug' => 'sugaring',
            'patterns' => ['/sugaring/', '/sugar.*wax/', '/^sugar/']],
    ],
    'MASSAGE' => [
        ['label' => 'Deep Tissue Massage',             'tw_slug' => 'deep-tissue-massage',
            'patterns' => ['/deep.?tissue/']],
        ['label' => 'Swedish Massage',                 'tw_slug' => 'swedish-massage',
            'patterns' => ['/swedish/']],
        ['label' => 'Therapeutic Massage',             'tw_slug' => 'therapeutic-massage',
            'patterns' => ['/therapeutic/', '/holistic.*massage/']],
        ['label' => 'Thai Massage',                    'tw_slug' => 'thai-massage',
            'patterns' => ['/thai/']],
        ['label' => 'Aromatherapy Massage',            'tw_slug' => 'aromatherapy-massage',
            'patterns' => ['/aromatherap/', '/aroma.*massage/']],
        ['label' => 'Sports Massage',                  'tw_slug' => 'sports-massage',
            'patterns' => ['/sports.*massage/']],
        ['label' => 'Hot Stone Massage',               'tw_slug' => 'hot-stone-massage',
            'patterns' => ['/hot.?stone/']],
    ],
    'NAILS' => [
        ['label' => 'Pedicure',                        'tw_slug' => 'pedicure',
            'patterns' => ['/pedicure/'],
            'exclude'  => ['/gel/', '/acrylic/', '/extensions/']],
        ['label' => 'Manicure',                        'tw_slug' => 'manicure',
            'patterns' => ['/manicure/'],
            'exclude'  => ['/gel/', '/acrylic/', '/extensions/']],
        ['label' => 'Gel Nails Manicure',              'tw_slug' => 'gel-nails-manicure',
            'patterns' => ['/gel.*manicure/', '/shellac.*manicure/']],
        ['label' => 'Hard Gel Extensions & Overlays',  'tw_slug' => 'hard-gel-extensions-overlays',
            'patterns' => ['/acrylic/', '/hard.?gel/', '/nail.*extension/', '/nail.*overlay/', '/builder.?gel/']],
        ['label' => 'Gel Nails Pedicure',              'tw_slug' => 'gel-nails-pedicure',
            'patterns' => ['/gel.*pedicure/', '/shellac.*pedicure/']],
        ['label' => 'Nail or Gel Polish Removal',      'tw_slug' => 'nail-or-gel-polish-removal',
            'patterns' => ['/polish.*remov/', '/gel.*remov/', '/soak.?off/']],
        ['label' => 'Nail Art',                        'tw_slug' => 'nail-art',
            'patterns' => ['/nail.?art/']],
    ],
    'FACE' => [
        ['label' => 'Classic Facials',                 'tw_slug' => 'classic-facials',
            'patterns' => ['/classic.*facial/', '/express.*facial/', '/signature.*facial/'],
            'exclude'  => ['/men/']],
        ['label' => 'Eyelash Extensions',              'tw_slug' => 'eyelash-extensions',
            'patterns' => ['/eyelash.*extension/', '/lash.*extension/', '/individual.*lash/', '/volume.*lash/']],
        ['label' => 'Eyebrow & Eyelash Tinting',       'tw_slug' => 'eyebrow',
            'patterns' => ['/lash.*tint/', '/brow.*tint/', '/eyelash.*tint/', '/eyebrow.*tint/']],
        ['label' => 'Eyebrow Threading',               'tw_slug' => 'eyebrow-threading',
            'patterns' => ['/eyebrow.*thread/', '/brow.*thread/']],
        ['label' => 'Eyebrow Waxing',                  'tw_slug' => 'eyebrow-waxing',
            'patterns' => ['/eyebrow.*wax/', '/brow.*wax/']],
        ['label' => 'Brow Definition',                 'tw_slug' => 'brow-definition',
            'patterns' => ['/brow.*definition/', '/brow.*shape/', '/brow.*lamination/', '/hd.?brow/']],
        ['label' => 'Lash Lift',                       'tw_slug' => 'lash-lift',
            'patterns' => ['/lash.*lift/']],
    ],
    'BODY' => [
        ['label' => 'Spray Tanning and Sunless Tanning','tw_slug' => 'spray-tanning-and-sunless-tanning',
            'patterns' => ['/spray.?tan/', '/sunless/', '/^tan(ning)?$/', '/tanning/']],
        ['label' => 'Colonic Hydrotherapy',            'tw_slug' => 'colonic-hydrotherapy',
            'patterns' => ['/colonic/', '/colon.*hydro/']],
        ['label' => 'Body Wraps',                      'tw_slug' => 'body-wraps',
            'patterns' => ['/body.?wrap/']],
        ['label' => 'Cryolipolysis',                   'tw_slug' => 'cryolipolysis',
            'patterns' => ['/cryolip/', '/coolsculpt/', '/fat.*freez/']],
        ['label' => 'Body Exfoliation Treatments',     'tw_slug' => 'body-exfoliation-treatments',
            'patterns' => ['/body.*exfoliat/', '/body.*scrub/', '/body.*polish/']],
        ['label' => 'Cellulite Treatments',            'tw_slug' => 'cellulite-treatments',
            'patterns' => ['/cellulite/']],
        ['label' => 'Weight Loss Treatments',          'tw_slug' => 'weight-loss-treatments',
            'patterns' => ['/weight.?loss/', '/slimming/']],
    ],
    "MEN'S" => [
        ['label' => "Men's Haircut",                   'tw_slug' => 'men-s-haircut',
            'patterns' => ['/men.?s.*haircut/', '/men.*haircut/', '/gent.*haircut/'],
            'exclude'  => ['/ladies/']],
        ['label' => 'Beard Trimming & Shaving',        'tw_slug' => 'beard-trimming',
            'patterns' => ['/beard/', '/^shave/', '/wet.*shave/', '/hot.*towel.*shave/']],
        ['label' => "Men's Hair Colouring",            'tw_slug' => 'men-s-hair-colouring',
            'patterns' => ['/men.?s.*colour/', '/men.?s.*color/', '/gent.*colour/'],
            'exclude'  => ['/ladies/']],
        ['label' => "Men's Facials",                   'tw_slug' => 'men-s-facials',
            'patterns' => ['/men.?s.*facial/', '/men.*facial/'],
            'exclude'  => ['/ladies/']],
        ['label' => "Men's Waxing",                    'tw_slug' => 'men-s-waxing',
            'patterns' => ['/men.?s.*wax/', '/gent.*wax/'],
            'exclude'  => ['/ladies/']],
        ['label' => 'Barbers',                         'tw_slug' => 'barbers',
            'patterns' => ['/barber/']],
    ],
];

$report = [];
foreach ($targets as $group => $subs) {
    $report[$group] = [];
    foreach ($subs as $sub) {
        $matchKatIds = [];
        $sampleNames = [];
        foreach ($kategori as $k) {
            $haystack = strtolower($k['name']) . ' ' . strtolower($k['slug']);
            $hit = false;
            foreach ($sub['patterns'] as $p) {
                if (preg_match($p, $haystack)) { $hit = true; break; }
            }
            if (!$hit) continue;
            if (!empty($sub['exclude'])) {
                foreach ($sub['exclude'] as $ex) {
                    if (preg_match($ex, $haystack)) { $hit = false; break; }
                }
            }
            if (!$hit) continue;
            $matchKatIds[] = $k['id_kategori'];
            if (count($sampleNames) < 3) $sampleNames[] = $k['name'];
        }

        $salonSet = [];
        foreach ($matchKatIds as $kid) {
            if (!isset($salonByKat[$kid])) continue;
            foreach ($salonByKat[$kid] as $sid => $_) $salonSet[$sid] = true;
        }
        $salonCount = count($salonSet);

        $bestKid = null; $bestCount = -1;
        foreach ($matchKatIds as $kid) {
            $c = isset($salonByKat[$kid]) ? count($salonByKat[$kid]) : 0;
            if ($c > $bestCount) { $bestCount = $c; $bestKid = $kid; }
        }

        $status = '❌';
        if ($salonCount >= 50)    $status = '✅';
        elseif ($salonCount >= 1) $status = '⚠️';

        $report[$group][] = [
            'label'           => $sub['label'],
            'tw_slug'         => $sub['tw_slug'],
            'matched_kats'    => count($matchKatIds),
            'sample_names'    => $sampleNames,
            'canonical_kid'   => $bestKid,
            'canonical_name'  => $bestKid ? $katById[$bestKid]['name'] : null,
            'canonical_slug'  => $bestKid ? $katById[$bestKid]['slug'] : null,
            'canonical_salons'=> max($bestCount, 0),
            'salon_count'     => $salonCount,
            'status'          => $status,
        ];
    }
}

echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
