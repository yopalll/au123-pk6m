{{--
    Component: <x-leaflet-map />
    Reusable Leaflet (OpenStreetMap) minimap.

    Props:
      $id       — DOM id (default: auto-generated)
      $height   — CSS height (default: '360px')
      $center   — array [lat, lng]; required if $markers is empty
      $zoom     — int (default: 13 for single, 11 for multi)
      $markers  — iterable of arrays/objects with: lat, lng, title, url (optional)
      $single   — bool; if true and exactly one marker, centers on it
      $class    — extra wrapper classes
--}}

@props([
    'id'      => 'leaflet-map-' . uniqid(),
    'height'  => '360px',
    'center'  => null,
    'zoom'    => null,
    'markers' => [],
    'single'  => false,
    'class'   => '',
])

@php
    // Normalize markers into a clean indexed array of plain objects.
    $normalized = collect($markers)
        ->map(function ($m) {
            $lat = is_array($m) ? ($m['lat'] ?? null) : ($m->lat ?? null);
            $lng = is_array($m) ? ($m['lng'] ?? null) : ($m->lng ?? null);
            if ($lat === null || $lng === null) return null;
            return [
                'lat'   => (float) $lat,
                'lng'   => (float) $lng,
                'title' => is_array($m) ? ($m['title'] ?? '') : ($m->title ?? ''),
                'url'   => is_array($m) ? ($m['url']   ?? '') : ($m->url   ?? ''),
            ];
        })
        ->filter()
        ->values()
        ->all();

    $hasMarkers = ! empty($normalized);
    $resolvedZoom = $zoom ?? ($single || count($normalized) <= 1 ? 13 : 11);
    // Fallback center: UK roughly
    $resolvedCenter = $center ?? ($hasMarkers ? [$normalized[0]['lat'], $normalized[0]['lng']] : [54.5, -2.5]);
@endphp

<div class="rounded-2xl overflow-hidden border border-gray-100 bg-[#E8F4FB] {{ $class }}">
    @if ($hasMarkers || $center)
        <div id="{{ $id }}" style="height: {{ $height }}; width: 100%;"></div>
        <script>
            (function() {
                var init = function() {
                    if (typeof L === 'undefined') {
                        // Leaflet not yet loaded — try again shortly.
                        return setTimeout(init, 100);
                    }
                    var map = L.map(@js($id)).setView(@js($resolvedCenter), @js($resolvedZoom));
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19,
                        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
                    }).addTo(map);

                    var markers = @js($normalized);
                    var layer = L.layerGroup().addTo(map);
                    markers.forEach(function (m) {
                        var marker = L.marker([m.lat, m.lng]).addTo(layer);
                        var popup = '<strong>' + (m.title || 'Salon') + '</strong>';
                        if (m.url) popup += '<br/><a href="' + m.url + '" style="color:#1B2D6B;font-weight:600">View salon →</a>';
                        marker.bindPopup(popup);
                    });

                    if (markers.length > 1) {
                        var bounds = L.latLngBounds(markers.map(function (m) { return [m.lat, m.lng]; }));
                        map.fitBounds(bounds, { padding: [30, 30], maxZoom: 14 });
                    }

                    // Force map to recalculate size on first paint (helps inside flex/sticky containers)
                    setTimeout(function () { map.invalidateSize(); }, 200);
                };
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', init);
                } else {
                    init();
                }
            })();
        </script>
    @else
        <div style="height: {{ $height }};" class="flex items-center justify-center text-sm text-gray-500">
            No map available
        </div>
    @endif
</div>
