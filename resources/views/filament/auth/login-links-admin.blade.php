{{-- Injected via AUTH_LOGIN_FORM_AFTER render hook in AdminPanelProvider.
     Uses inline width/height + style on SVG so icons render small regardless
     of whether Tailwind utility classes are present in Filament's CSS bundle. --}}
<div style="margin-top: 1.5rem; display: flex; flex-direction: column; gap: 0.6rem;">
    <div style="display: flex; align-items: center; gap: 0.75rem; color: #9ca3af; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em;">
        <div style="flex-grow: 1; height: 1px; background: #e5e7eb;"></div>
        <span>or sign in as</span>
        <div style="flex-grow: 1; height: 1px; background: #e5e7eb;"></div>
    </div>

    <a href="{{ url('/owner/login') }}"
       style="display: flex; align-items: center; justify-content: space-between; padding: 0.625rem 1rem; border: 1px solid #e5e7eb; border-radius: 0.5rem; color: #374151; text-decoration: none; font-size: 0.875rem; font-weight: 500; transition: all 0.15s;"
       onmouseover="this.style.borderColor='#1B2D6B'; this.style.background='#f9fafb';"
       onmouseout="this.style.borderColor='#e5e7eb'; this.style.background='transparent';">
        <span>Login as Salon Owner</span>
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0;">
            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
            <polyline points="9 22 9 12 15 12 15 22"/>
        </svg>
    </a>

    <a href="{{ route('login') }}"
       style="display: flex; align-items: center; justify-content: space-between; padding: 0.625rem 1rem; border: 1px solid #e5e7eb; border-radius: 0.5rem; color: #374151; text-decoration: none; font-size: 0.875rem; font-weight: 500; transition: all 0.15s;"
       onmouseover="this.style.borderColor='#1B2D6B'; this.style.background='#f9fafb';"
       onmouseout="this.style.borderColor='#e5e7eb'; this.style.background='transparent';">
        <span>Login as Customer</span>
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0;">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
            <circle cx="12" cy="7" r="4"/>
        </svg>
    </a>
</div>
