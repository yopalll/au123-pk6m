<?php
/**
 * Audit kategori VIYGO terhadap target sub-kategori Treatwell.
 *
 * Untuk tiap sub-kategori target:
 *   - Lakukan fuzzy match terhadap kategori.name / kategori.slug.
 *   - Hitung distinct id_salon yang punya service di kategori match.
 *   - Tentukan kategori kanonik (record dengan jumlah salon tertinggi).
 *
 * Output: ditulis ke stdout sebagai JSON (dipakai oleh generator markdown).
 */

$kategori = json_decode(file_get_contents(__DIR__.'/../data/kategori.json'), true);
$service  = json_decode(file_get_contents(__DIR__.'/../data/service.json'), true);

// Index kategori by id_kategori → row, dan kumpulkan per slug.
$katById = [];
foreach ($kategori as $k) {
    $katById[$k['id_kategori']] = $k;
}

// Index service: untuk tiap id_kategori, daftar distinct id_salon.
$salonByKat = [];
foreach ($service as $s) {
    if (($s['status'] ?? 'active') !== 'active') continue;
    $kid = $s['id_kategori'];
    $sid = $s['id_salon'];
    if (!isset($salonByKat[$kid])) $salonByKat[$kid] = [];
    $salonByKat[$kid][$sid] = true;
}

/**
 * Definisi target sub-kategori per parent group.
 * Tiap target punya:
 *   - label: judul tampilan
 *   - slug : slug kanonik yang akan dipakai di /kategori/{slug}
 *   - patterns: regex yang harus match pada lowercase(name) atau slug
 *   - exclude: regex yang harus TIDAK match (filter false-positive)
 */
$targets = [
    'HAIR' => [
        ['label' => "Ladies' Haircuts",                    'slug' => 'ladies-haircuts',
            'patterns' => ['/ladies.*haircut/', '/haircut.*ladies/', '/wash.*haircut.*finish/']],
        ['label' => 'Blow Dry',                            'slug' => 'blow-dry',
            'patterns' => ['/blow.?dry/', '/blow-?dry/'], 'exclude' => ['/brazilian/', '/keratin/']],
        ['label' => "Ladies' Hair Colouring & Highlights", 'slug' => 'ladies-hair-colouring-highlights',
            'patterns' => ['/ladies.*colour/', '/ladies.*color/', '/ladies.*highlight/', '/highlight.*service/', '/colour.*service/']],
        ['label' => "Ladies' Brazilian Blow Dry",          'slug' => 'ladies-brazilian-blow-dry',
            'patterns' => ['/brazilian.*blow/', '/keratin.*blow/', '/keratin.*treatment/']],
        ['label' => 'Balayage & Ombre',                    'slug' => 'balayage-ombre',
            'patterns' => ['/balayag/', '/ombre/', '/babylight/']],
        ['label' => "Men's Haircut",                       'slug' => 'mens-haircut',
            'patterns' => ['/men.?s.*haircut/', '/men.*haircut/', '/gent.*haircut/', '/barber.*cut/'],
            'exclude'  => ['/ladies/']],
    ],
    'HAIR REMOVAL' => [
        ['label' => 'Facial Threading',                    'slug' => 'facial-threading',
            'patterns' => ['/facial.*thread/', '/face.*thread/', '/eyebrow.*thread/', '/lip.*thread/', '/chin.*thread/', '/thread/']],
        ['label' => "Ladies' Waxing",                      'slug' => 'ladies-waxing',
            'patterns' => ['/ladies.*wax/', '/female.*wax/', '/women.*wax/'],
            'exclude'  => ['/men/']],
        ['label' => 'Sugaring',                            'slug' => 'sugaring',
            'patterns' => ['/sugar/']],
        ['label' => 'Hollywood Waxing',                    'slug' => 'hollywood-waxing',
            'patterns' => ['/^hollywood/', '/hollywood.*wax/', '/hollywood$/'],
            'exclude'  => ['/dont book/', "/don.t book/"]],
        ['label' => "Men's Waxing",                        'slug' => 'mens-waxing',
            'patterns' => ['/men.?s.*wax/', '/gent.*wax/', '/male.*wax/'],
            'exclude'  => ['/ladies/']],
        ['label' => "Ladies' Leg Waxing",                  'slug' => 'ladies-leg-waxing',
            'patterns' => ['/leg.*wax/', '/half.?leg/', '/full.?leg.*wax/'],
            'exclude'  => ['/men/']],
    ],
    'MASSAGE' => [
        ['label' => 'Deep Tissue Massage',                 'slug' => 'deep-tissue-massage',
            'patterns' => ['/deep.?tissue/']],
        ['label' => 'Swedish Massage',                     'slug' => 'swedish-massage',
            'patterns' => ['/swedish/']],
        ['label' => 'Therapeutic Massage',                 'slug' => 'therapeutic-massage',
            'patterns' => ['/therapeutic/', '/holistic.*massage/', '/relaxing.*massage/']],
        ['label' => 'Thai Massage',                        'slug' => 'thai-massage',
            'patterns' => ['/thai/']],
        ['label' => 'Aromatherapy Massage',                'slug' => 'aromatherapy-massage',
            'patterns' => ['/aromatherap/', '/aroma.*massage/']],
        ['label' => 'Hot Stone Massage',                   'slug' => 'hot-stone-massage',
            'patterns' => ['/hot.?stone/']],
    ],
    'NAILS' => [
        ['label' => 'Pedicure',                            'slug' => 'pedicure',
            'patterns' => ['/pedicure/'],
            'exclude'  => ['/gel/', '/acrylic/', '/extensions/']],
        ['label' => 'Manicure',                            'slug' => 'manicure',
            'patterns' => ['/manicure/'],
            'exclude'  => ['/gel/', '/acrylic/', '/extensions/']],
        ['label' => 'Nail or Gel Polish Removal',          'slug' => 'nail-or-gel-polish-removal',
            'patterns' => ['/polish.*remov/', '/gel.*remov/', '/soak.?off/']],
        ['label' => 'Gel Nails Manicure',                  'slug' => 'gel-nails-manicure',
            'patterns' => ['/gel.*manicure/', '/gel.*nail.*hand/', '/shellac.*manicure/']],
        ['label' => 'Gel Nails Pedicure',                  'slug' => 'gel-nails-pedicure',
            'patterns' => ['/gel.*pedicure/', '/shellac.*pedicure/']],
        ['label' => 'Acrylic, Hard Gel & Nail Extensions', 'slug' => 'acrylic-hard-gel-nail-extensions',
            'patterns' => ['/acrylic/', '/hard.?gel/', '/nail.*extension/', '/builder.?gel/']],
    ],
    'FACE' => [
        ['label' => 'Classic Facials',                     'slug' => 'classic-facials',
            'patterns' => ['/classic.*facial/', '/express.*facial/', '/signature.*facial/', '/^facial$/', '/standard.*facial/'],
            'exclude'  => ['/men/']],
        ['label' => 'Eyelash Extensions',                  'slug' => 'eyelash-extensions',
            'patterns' => ['/eyelash.*extension/', '/lash.*extension/', '/individual.*lash/', '/classic.*lash/', '/volume.*lash/']],
        ['label' => 'Eyebrow and Eyelash Tinting',         'slug' => 'eyebrow-and-eyelash-tinting',
            'patterns' => ['/lash.*tint/', '/brow.*tint/', '/eyelash.*tint/', '/eyebrow.*tint/']],
        ['label' => 'Eyebrow Threading',                   'slug' => 'eyebrow-threading',
            'patterns' => ['/eyebrow.*thread/', '/brow.*thread/']],
        ['label' => 'Eyebrow Waxing',                      'slug' => 'eyebrow-waxing',
            'patterns' => ['/eyebrow.*wax/', '/brow.*wax/']],
        ['label' => 'Definition Brows',                    'slug' => 'definition-brows',
            'patterns' => ['/brow.*shape/', '/brow.*definition/', '/brow.*lamination/', '/brow.*sculpt/', '/hd.?brow/', '/henna.?brow/']],
    ],
    'BODY' => [
        ['label' => 'Spray Tanning and Sunless Tanning',   'slug' => 'spray-tanning-and-sunless-tanning',
            'patterns' => ['/spray.?tan/', '/sunless/', '/self.?tan/', '/^tan$/', '/tanning/']],
        ['label' => 'Body Exfoliation Treatments',         'slug' => 'body-exfoliation-treatments',
            'patterns' => ['/body.*exfoliat/', '/body.*scrub/', '/body.*polish/']],
        ['label' => 'Body Wraps',                          'slug' => 'body-wraps',
            'patterns' => ['/body.?wrap/']],
        ['label' => 'Colonic Hydrotherapy',                'slug' => 'colonic-hydrotherapy',
            'patterns' => ['/colonic/', '/colon.*hydro/']],
        ['label' => 'Cryolipolysis',                       'slug' => 'cryolipolysis',
            'patterns' => ['/cryolip/', '/coolsculpt/', '/fat.*freez/']],
        ['label' => 'Cellulite Treatments',                'slug' => 'cellulite-treatments',
            'patterns' => ['/cellulite/']],
    ],
    "MEN'S" => [
        ['label' => "Men's Haircut",                       'slug' => 'mens-haircut',
            'patterns' => ['/men.?s.*haircut/', '/men.*haircut/', '/gent.*haircut/', '/barber.*cut/'],
            'exclude'  => ['/ladies/']],
        ['label' => 'Beard Trims and Shaves',              'slug' => 'beard-trims-and-shaves',
            'patterns' => ['/beard/', '/^shave/', '/wet.*shave/', '/hot.*towel.*shave/']],
        ['label' => "Men's Hair Colouring",                'slug' => 'mens-hair-colouring',
            'patterns' => ['/men.?s.*colour/', '/men.?s.*color/', '/gent.*colour/'],
            'exclude'  => ['/ladies/']],
        ['label' => "Men's Brazilian Blow Dry",            'slug' => 'mens-brazilian-blow-dry',
            'patterns' => ['/men.?s.*brazilian/', '/men.?s.*keratin/'],
            'exclude'  => ['/ladies/']],
        ['label' => "Men's Facials",                       'slug' => 'mens-facials',
            'patterns' => ['/men.?s.*facial/', '/men.*facial/'],
            'exclude'  => ['/ladies/']],
        ['label' => "Men's Waxing",                        'slug' => 'mens-waxing',
            'patterns' => ['/men.?s.*wax/', '/gent.*wax/', '/male.*wax/'],
            'exclude'  => ['/ladies/']],
        ['label' => 'Barbers',                             'slug' => 'barbers',
            'patterns' => ['/barber/', '/barbershop/']],
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

        // Pilih kategori kanonik = kategori match dengan jumlah salon tertinggi
        $bestKid     = null;
        $bestCount   = -1;
        foreach ($matchKatIds as $kid) {
            $c = isset($salonByKat[$kid]) ? count($salonByKat[$kid]) : 0;
            if ($c > $bestCount) { $bestCount = $c; $bestKid = $kid; }
        }

        $status = '❌';
        if ($salonCount >= 50)      $status = '✅';
        elseif ($salonCount >= 1)   $status = '⚠️';

        $report[$group][] = [
            'label'             => $sub['label'],
            'slug_target'       => $sub['slug'],
            'matched_kats'      => count($matchKatIds),
            'sample_names'      => $sampleNames,
            'canonical_kid'     => $bestKid,
            'canonical_name'    => $bestKid ? $katById[$bestKid]['name'] : null,
            'canonical_slug'    => $bestKid ? $katById[$bestKid]['slug'] : null,
            'canonical_salons'  => $bestCount > 0 ? $bestCount : 0,
            'salon_count'       => $salonCount,
            'status'            => $status,
        ];
    }
}

echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
