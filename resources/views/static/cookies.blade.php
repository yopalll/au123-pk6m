<x-layouts.public title="Cookie Policy">

<section class="bg-[#1B2D6B] py-16 text-center">
    <div class="text-xs font-bold text-[#4BA3CC] uppercase tracking-widest mb-3">Legal</div>
    <h1 class="text-4xl text-white mb-2">Cookie Policy</h1>
    <p class="text-white/60 text-sm">Last updated: 1 May 2026</p>
</section>

<article class="max-w-3xl mx-auto px-6 py-12 space-y-6 text-gray-700 leading-relaxed">

    <p class="text-sm">VIYGO uses cookies and similar technologies to make the site work, to remember your preferences, and to understand how the site is used. This page explains what cookies we use and why.</p>

    <h2 class="text-xl text-[#1B2D6B] mt-8">1. What is a cookie?</h2>
    <p class="text-sm">A cookie is a small text file stored by your browser when you visit a website. It allows the site to remember information across pages and visits.</p>

    <h2 class="text-xl text-[#1B2D6B] mt-8">2. Cookies we use</h2>
    <div class="overflow-hidden rounded-xl border border-gray-100 my-4">
        <table class="w-full text-sm">
            <thead class="bg-[#E8F4FB] text-[#1B2D6B]">
                <tr>
                    <th class="text-left px-4 py-2">Name</th>
                    <th class="text-left px-4 py-2">Purpose</th>
                    <th class="text-left px-4 py-2">Type</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <tr><td class="px-4 py-2"><code>viygo_session</code></td><td class="px-4 py-2">Keeps you signed in</td><td class="px-4 py-2">Essential</td></tr>
                <tr><td class="px-4 py-2"><code>XSRF-TOKEN</code></td><td class="px-4 py-2">Cross-site request forgery protection</td><td class="px-4 py-2">Essential</td></tr>
                <tr><td class="px-4 py-2"><code>_ga</code></td><td class="px-4 py-2">Google Analytics traffic measurement</td><td class="px-4 py-2">Analytics</td></tr>
            </tbody>
        </table>
    </div>

    <h2 class="text-xl text-[#1B2D6B] mt-8">3. Managing cookies</h2>
    <p class="text-sm">You can control or disable cookies through your browser settings. Note that disabling essential cookies may break parts of the site (notably login).</p>

    <h2 class="text-xl text-[#1B2D6B] mt-8">4. Contact</h2>
    <p class="text-sm">Questions? Email <a href="mailto:{{ config('viygo.support_email') }}" class="text-[#4BA3CC] hover:underline">{{ config('viygo.support_email') }}</a>.</p>
</article>

</x-layouts.public>
