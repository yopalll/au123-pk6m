---
name: Serene Floral Noir
colors:
  surface: '#111316'
  surface-dim: '#111316'
  surface-bright: '#37393d'
  surface-container-lowest: '#0c0e11'
  surface-container-low: '#1a1c1f'
  surface-container: '#1e2023'
  surface-container-high: '#282a2d'
  surface-container-highest: '#333538'
  on-surface: '#e2e2e6'
  on-surface-variant: '#d7c2b8'
  inverse-surface: '#e2e2e6'
  inverse-on-surface: '#2f3034'
  outline: '#9f8d84'
  outline-variant: '#52443c'
  surface-tint: '#ffb68b'
  primary: '#ffdbc8'
  on-primary: '#502405'
  primary-container: '#ffb68b'
  on-primary-container: '#7a4523'
  inverse-primary: '#88512e'
  secondary: '#a5cbea'
  on-secondary: '#04344d'
  secondary-container: '#264d67'
  on-secondary-container: '#97bddb'
  tertiary: '#c7e9e9'
  on-tertiary: '#143536'
  tertiary-container: '#abcdcd'
  on-tertiary-container: '#385858'
  error: '#ffb4ab'
  on-error: '#690005'
  error-container: '#93000a'
  on-error-container: '#ffdad6'
  primary-fixed: '#ffdbc9'
  primary-fixed-dim: '#ffb68b'
  on-primary-fixed: '#321200'
  on-primary-fixed-variant: '#6c3a19'
  secondary-fixed: '#c9e6ff'
  secondary-fixed-dim: '#a5cbea'
  on-secondary-fixed: '#001e2f'
  on-secondary-fixed-variant: '#234a64'
  tertiary-fixed: '#c7e9e9'
  tertiary-fixed-dim: '#abcdcd'
  on-tertiary-fixed: '#002020'
  on-tertiary-fixed-variant: '#2c4c4c'
  background: '#111316'
  on-background: '#e2e2e6'
  surface-variant: '#333538'
  surface-charcoal: '#1A1D21'
  surface-glass: rgba(17, 19, 22, 0.6)
  border-glow: rgba(255, 255, 255, 0.2)
typography:
  display-lg:
    fontFamily: Playfair Display
    fontSize: 48px
    fontWeight: '700'
    lineHeight: 56px
    letterSpacing: -0.02em
  display-lg-mobile:
    fontFamily: Playfair Display
    fontSize: 36px
    fontWeight: '700'
    lineHeight: 44px
    letterSpacing: -0.01em
  headline-lg:
    fontFamily: Playfair Display
    fontSize: 32px
    fontWeight: '600'
    lineHeight: 40px
  headline-md:
    fontFamily: Playfair Display
    fontSize: 24px
    fontWeight: '600'
    lineHeight: 32px
  body-lg:
    fontFamily: Manrope
    fontSize: 18px
    fontWeight: '400'
    lineHeight: 28px
  body-md:
    fontFamily: Manrope
    fontSize: 16px
    fontWeight: '400'
    lineHeight: 24px
  label-md:
    fontFamily: Manrope
    fontSize: 14px
    fontWeight: '600'
    lineHeight: 20px
    letterSpacing: 0.05em
  label-sm:
    fontFamily: Manrope
    fontSize: 12px
    fontWeight: '500'
    lineHeight: 16px
rounded:
  sm: 0.125rem
  DEFAULT: 0.25rem
  md: 0.375rem
  lg: 0.5rem
  xl: 0.75rem
  full: 9999px
spacing:
  base: 4px
  xs: 8px
  sm: 16px
  md: 24px
  lg: 40px
  xl: 80px
  gutter: 24px
  margin-desktop: 80px
  margin-mobile: 20px
---

## Brand & Style

This design system embodies "Serene Floral Noir," a sophisticated aesthetic tailored for a high-end Beauty, Skincare, and Lifestyle platform. It balances the mysterious depth of a midnight garden with the clinical precision of modern skincare science.

The visual direction leverages **Minimalism** to ensure product photography remains the focal point, while **Glassmorphism** adds a layer of ethereal depth. The emotional response should be one of tranquility, luxury, and exclusivity. The interface feels like a premium apothecary at twilight—hushed, tactile, and luminous.

Key stylistic pillars include:
- **Midnight Foundations:** Using near-black depths to allow product colors and skin tones to pop.
- **Luminous Layers:** Semi-transparent surfaces that mimic the translucency of healthy skin.
- **Botanical Accents:** Subtle organic textures and copper-toned highlights that evoke natural warmth.

## Colors

The palette is rooted in a deep charcoal base, providing a high-contrast stage for the warm copper primary and cool botanical secondary tones.

- **Primary (Copper):** Reserved for high-priority calls to action, focus states, and brand-critical highlights. It provides a human, earthy warmth against the dark background.
- **Secondary (Dusty Blue):** Used for supportive UI elements, informational badges, and subtle decorative accents.
- **Tertiary (Teal):** Applied to success states and wellness-related iconography, reinforcing a sense of health and vitality.
- **Neutral/Surface:** The background is a solid `#111316`. Container levels are defined by slight increases in lightness (e.g., `#1A1D21`) or via frosted glass overlays.

## Typography

The typography system uses a classic Serif/Sans-Serif pairing to convey both heritage and modern efficiency.

- **Playfair Display:** Used for display titles and headlines. Its high-contrast strokes evoke fashion editorial aesthetics and luxury branding.
- **Manrope:** A modern, geometric sans-serif used for all functional text. It ensures maximum readability for ingredient lists, product descriptions, and navigational elements.

**Usage Notes:**
- All labels use an increased letter spacing and uppercase styling to create a "breathable" feel in dense UI areas.
- Maintain generous line heights to preserve the minimalist, "airy" quality of the design.

## Layout & Spacing

This design system employs a **12-column fluid grid** for desktop, transitioning to a 4-column grid for mobile devices. 

- **Grid Philosophy:** Large margins (80px) on desktop create a "boutique" feel, centering the content and preventing it from feeling like a standard utility-based e-commerce site.
- **Spacing Rhythm:** Based on a 4px scale. Components should primarily utilize the `md` (24px) unit for internal padding and gutters to maintain a consistent visual tempo.
- **Reflow Rules:** On mobile, margins reduce significantly to 20px, and horizontal scrolls are preferred for product carousels to maximize screen real estate while keeping the high-end imagery large.

## Elevation & Depth

Hierarchy is established through **Glassmorphism** and tonal layering rather than traditional drop shadows.

- **The Background Texture:** A subtle midnight floral tapestry is fixed to the background with a 10% opacity overlay, ensuring it doesn't compete with the content.
- **Frosted UI Panels:** Active surfaces use a `backdrop-blur` of 12px to 20px. 
- **The Inner Glow:** Every glass panel must feature a 1px solid border at 20% white opacity. This creates a "sharp edge" effect that mimics high-end glass packaging.
- **Tonal Tiers:** For non-transparent containers, use `#1A1D21` to sit slightly "above" the base background.

## Shapes

The shape language is **Soft (0.25rem / 4px base)**. 

While the platform is feminine and lifestyle-oriented, the "Noir" aspect requires a level of architectural structure. Rounded corners are kept minimal to maintain a clean, sophisticated edge. 
- **Standard Radius:** 4px for buttons, inputs, and small components.
- **Large Radius:** 8px (rounded-lg) for main content cards and modals.
- **Exceptions:** Pill shapes (32px+) are strictly reserved for status chips and tags to differentiate them from functional buttons.

## Components

### Buttons
- **Primary:** Solid Copper (`#FFB68B`) with dark text (`#111316`). No shadow. High-contrast and impactful.
- **Secondary:** Transparent background with a 1px Dusty Blue (`#A5CBEA`) border. Text color matches the border.
- **Ghost:** No border or background, used for low-priority actions like "Cancel" or "Learn More."

### Cards
- **Product Cards:** Image-heavy with a "Frosted Footer." The bottom 30% of the card uses the glassmorphism style (backdrop-blur + white 20% border) to house the product name and price.
- **Article Cards:** Full-bleed imagery with a centered glassmorphic overlay for the category and title.

### Inputs & Form Elements
- **Fields:** Deep charcoal background (`#1A1D21`) with a fine 1px border (`rgba(255, 255, 255, 0.1)`).
- **Focus State:** The border transitions to Copper (`#FFB68B`) with a subtle 2px outer glow of the same color at 20% opacity.
- **Checkboxes/Radios:** Square profile (4px radius) using the Primary color for the checked state.

### Navigation
- **Header:** Always a frosted glass blur to allow the floral background texture to peek through as the user scrolls.
- **Navigation Links:** Manrope Medium, 14px, uppercase with 0.1em letter spacing. Active state indicated by a subtle Copper underline.