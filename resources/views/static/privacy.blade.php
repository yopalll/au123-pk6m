<x-layouts.public title="Privacy Policy">

<section class="bg-[#1B2D6B] py-16 text-center">
    <div class="text-xs font-bold text-[#4BA3CC] uppercase tracking-widest mb-3">Legal</div>
    <h1 class="text-4xl text-white mb-2">Privacy Policy</h1>
    <p class="text-white/60 text-sm">Last updated: 1 May 2026</p>
</section>

<article class="max-w-3xl mx-auto px-6 py-12 space-y-6 text-gray-700 leading-relaxed">

    <p class="text-sm">This Privacy Policy explains how <strong>VIYGO Ltd.</strong> ("VIYGO", "we", "us") collects, uses and protects information about you when you use our website and services.</p>

    <h2 class="text-xl text-[#1B2D6B] mt-8">1. Information we collect</h2>
    <p class="text-sm">When you create an account, book a treatment, or contact us, we collect information including your name, email address, phone number, booking history and payment status. We also automatically log technical data such as IP address, device type and browser version for security and analytics.</p>

    <h2 class="text-xl text-[#1B2D6B] mt-8">2. How we use your information</h2>
    <p class="text-sm">We use your information to deliver bookings, contact you about appointments, improve our service, prevent fraud, and meet our legal obligations. We never sell your data.</p>

    <h2 class="text-xl text-[#1B2D6B] mt-8">3. Sharing with salons</h2>
    <p class="text-sm">When you book a treatment, your name, contact details and the service you booked are shared with the salon so they can deliver the appointment.</p>

    <h2 class="text-xl text-[#1B2D6B] mt-8">4. Cookies</h2>
    <p class="text-sm">We use essential and analytics cookies. See our <a href="{{ route('static.cookies') }}" class="text-[#4BA3CC] hover:underline">Cookie Policy</a> for details.</p>

    <h2 class="text-xl text-[#1B2D6B] mt-8">5. Your rights (UK GDPR)</h2>
    <p class="text-sm">You have the right to access, correct, delete or export the personal data we hold about you. To exercise these rights, email <a href="mailto:{{ config('viygo.support_email') }}" class="text-[#4BA3CC] hover:underline">{{ config('viygo.support_email') }}</a>.</p>

    <h2 class="text-xl text-[#1B2D6B] mt-8">6. Data retention</h2>
    <p class="text-sm">We keep account data for as long as your account is active and for up to 7 years after closure (legally required for financial records).</p>

    <h2 class="text-xl text-[#1B2D6B] mt-8">7. Contact</h2>
    <p class="text-sm">For privacy questions, contact <a href="mailto:{{ config('viygo.support_email') }}" class="text-[#4BA3CC] hover:underline">{{ config('viygo.support_email') }}</a>.</p>
</article>

</x-layouts.public>
