---
name: VIYGÖ Premium Wellness
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
  on-surface-variant: '#d8c2b7'
  inverse-surface: '#e2e2e6'
  inverse-on-surface: '#2f3034'
  outline: '#a08d83'
  outline-variant: '#53443b'
  surface-tint: '#ffb68b'
  primary: '#ffb68b'
  on-primary: '#502405'
  primary-container: '#d39068'
  on-primary-container: '#582a0a'
  inverse-primary: '#88512e'
  secondary: '#a5cbea'
  on-secondary: '#04344d'
  secondary-container: '#264d67'
  on-secondary-container: '#97bddb'
  tertiary: '#abcdcd'
  on-tertiary: '#143536'
  tertiary-container: '#85a6a6'
  on-tertiary-container: '#1b3c3c'
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
  glass-surface: rgba(30, 32, 35, 0.4)
typography:
  display-lg:
    fontFamily: Playfair Display
    fontSize: 56px
    fontWeight: '700'
    lineHeight: '1.1'
    letterSpacing: -0.02em
  headline-lg:
    fontFamily: Playfair Display
    fontSize: 40px
    fontWeight: '600'
    lineHeight: '1.2'
  headline-lg-mobile:
    fontFamily: Playfair Display
    fontSize: 32px
    fontWeight: '600'
    lineHeight: '1.2'
  headline-md:
    fontFamily: Playfair Display
    fontSize: 28px
    fontWeight: '500'
    lineHeight: '1.3'
  body-lg:
    fontFamily: Manrope
    fontSize: 18px
    fontWeight: '400'
    lineHeight: '1.6'
  body-md:
    fontFamily: Manrope
    fontSize: 16px
    fontWeight: '400'
    lineHeight: '1.5'
  label-md:
    fontFamily: Manrope
    fontSize: 14px
    fontWeight: '600'
    lineHeight: '1.2'
    letterSpacing: 0.05em
  label-sm:
    fontFamily: Manrope
    fontSize: 12px
    fontWeight: '500'
    lineHeight: '1.2'
rounded:
  sm: 0.25rem
  DEFAULT: 0.5rem
  md: 0.75rem
  lg: 1rem
  xl: 1.5rem
  full: 9999px
spacing:
  unit: 4px
  gutter: 24px
  section-gap: 120px
  container-max-width: 1200px
  margin-mobile: 20px
  margin-desktop: 80px
---

## Brand & Style

VIYGÖ is a premium beauty and wellness marketplace that positions itself at the intersection of luxury and technology. The brand personality is sophisticated, serene, and modern, targeting a discerning audience seeking high-end self-care services. 

The design style is **Glassmorphism mixed with Modern Editorial**. It utilizes deep tonal layering and translucent "glass" surfaces to create depth without clutter. The aesthetic relies on high-contrast serif typography for a boutique editorial feel, paired with functional sans-serif for utility. Atmospheric background blurs and organic "blob" masks create a sense of movement and relaxation, evoking the feeling of a high-end spa environment.

## Colors
The palette is rooted in a "Deep Night" foundation (`#111316`), accented by warm, skin-toned primaries and calming botanical secondaries. 

- **Primary (`#ffb68b`):** A soft apricot/peach used for CTAs and highlights. It feels energetic yet approachable.
- **Secondary (`#a5cbea`):** A dusty sky blue used for subtle iconography and accents.
- **Tertiary (`#abcdcd`):** A muted sage/teal used to distinguish specific categories like skincare and wellness.
- **Neutral:** The background remains dark to let imagery and glass effects shine. Text primarily uses a high-contrast off-white (`#e2e2e6`) for maximum legibility.

## Typography
The system uses a high-contrast pairing strategy:
- **Playfair Display** is the primary "voice" of the brand. It is used for all major headings and brand identifiers to convey luxury and timelessness.
- **Manrope** serves as the functional workhorse. It is utilized for body copy, input labels, and button text to ensure modern clarity and high readability at small sizes.

On mobile, display sizes scale down aggressively to maintain the editorial hierarchy without overwhelming the smaller viewport.

## Layout & Spacing
The design follows a **Fixed-Width Grid** on desktop, centered within a 1200px container. On mobile, it transitions to a fluid model with 20px safe-area margins.

- **Vertical Rhythm:** Large 120px gaps between sections create a sense of breathing room and premium quality.
- **Grid:** A standard 12-column system is used on desktop. Cards typically span 3 or 4 columns depending on the content density (e.g., 4-column for treatment tiles, 3-column for venue cards).
- **Interactive Spacing:** Components use a tight 4px base unit, ensuring buttons and inputs feel compact and well-defined within larger, airy layouts.

## Elevation & Depth
Depth is created through transparency and blur rather than traditional drop shadows.

- **Glassmorphism:** The primary container style for interactive components (search bars, cards) uses `glass-surface` (40% opacity background with a 16px backdrop blur).
- **Tonal Layering:** Surfaces "closest" to the user are lighter (e.g., `surface-bright`), while the background remains the darkest (`surface`).
- **Shadows:** When used (like on the search bar), shadows are large and diffused (`shadow-2xl`), acting more as an ambient glow to lift the element off the background imagery.
- **Atmospheric Depth:** Background images are treated with a `mix-blend-screen` and low opacity (20%) to sit "behind" the UI layers without competing for attention.

## Shapes
The shape language is smooth and approachable.
- **Base Components:** Standard buttons and inputs use a 12px (`rounded-xl`) radius.
- **Container Elements:** Larger glass cards and venue cards use a 24px - 32px (`rounded-2xl` to `rounded-3xl`) radius to soften the overall interface.
- **Action Items:** Secondary "pill" buttons (like the Login button) use a `full` radius for distinct visual hierarchy.

## Components

- **Buttons:**
  - *Primary:* High-contrast apricot (`primary`) background with dark text. No border, slight lift on hover.
  - *Outline:* Transparent background with a subtle `outline` border, used for secondary header actions.
- **Search Bar:** A specialized component combining multiple inputs within a single large `glass-surface` container. Inputs are distinguished by internal background shifts (`surface-container-low`) rather than borders.
- **Treatment Tiles:** Square aspect-ratio tiles with centered icons. Icons are housed in circular containers that shift color on hover to provide reactive feedback.
- **Venue Cards:** Large vertical cards with high-quality imagery. These feature a "Rating Badge" in the top-right, utilizing a backdrop-blur for visibility against varying photo backgrounds.
- **Input Fields:** Minimalist design with no borders; they rely on `surface-container-low` backgrounds and Material Symbols for iconography.
- **Badges:** Small, pill-shaped tags used for ratings and price indicators, emphasizing high readability over heavy styling.