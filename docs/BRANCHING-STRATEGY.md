# VIYGO Branching Strategy & Feature Breakdown

## 📌 Overview

VIYGO uses a **feature-branch strategy** where each major feature gets its own branch. All branches are merged/tracked from `main` and ready for integration.

---

## 🌳 Branch Hierarchy

```
main (stable)
├── feature/polish-round ..................... UI Polish & Bug Fixes
├── feature/dummy-pages-polish .............. Static Pages Polish
├── feature/owner-panel ..................... Salon Owner Admin Panel
├── feature/review-system ................... Review & Rating System
├── feature/smart-booking-payment .......... Booking Wizard + Midtrans
├── fix/post-payment-audit ................. Critical Bug Fixes
├── eloquent ............................... Database Models & Seeders
├── branch-viter ........................... Customer Panel Experiments
└── go-fresh .............................. Fresh Start / Testing Branch
```

---

## 🎯 Feature Branches Detailed

### 1️⃣ feature/polish-round
**Status:** 🟢 JUST PUSHED (May 3, 2026)  
**Commit:** `4ff0dea`  
**Task:** TUGAS 9 (Polish Round)  
**Depends on:** All previous features

**What's included:**
```
┌─ Controllers (Backend)
│  ├─ AkunController.php ................ User account management
│  ├─ BookingController.php ............ Booking logic & updates
│  ├─ PaymentController.php ............ Payment webhook handling
│  └─ SearchController.php ............ Search refinements
│
├─ Services
│  └─ BookingSlotService.php ......... Availability calculation
│
├─ Models
│  └─ User.php ...................... User model enhancements
│
├─ Views (Frontend - 15 files)
│  ├─ home.blade.php
│  ├─ booking/create.blade.php
│  ├─ akun/*.blade.php (5 files)
│  ├─ components/*.blade.php (4 files)
│  └─ static/*.blade.php (2 files)
│
├─ Routes
│  └─ web.php .................... Route refinements
│
└─ Filament (Admin)
   ├─ MitraApplicationResource.php
   └─ ListMitraApplications, ViewMitraApplication
```

**Key Improvements:**
- ✅ Bug fixes: BUG-08, BUG-09, BUG-10
- ✅ Form backend validation
- ✅ Partner application tracking
- ✅ Auto-complete booking console command
- ✅ Final audit documentation

**Files Changed:** 33 files, +2489 lines

---

### 2️⃣ feature/dummy-pages-polish
**Status:** 🟡 READY FOR MERGE  
**Commit:** `e126231`  
**Task:** TUGAS 7 (Dummy Pages Polish)  
**Depends on:** `eloquent`

**What's included:**
```
Static Pages Enhanced:
├─ About page ................. Company information
├─ Careers page ............... Job opportunities
├─ Gift Card page ............ Gift card promotions
├─ Lookbook page ............ Style inspiration
└─ Treatment Files page ..... Beauty articles
```

**Focus:** Header/navbar polish, English translation, component refinements

---

### 3️⃣ feature/owner-panel
**Status:** 🟡 READY FOR MERGE  
**Commit:** `b6bba4c`  
**Task:** TUGAS 2 (Salon Owner Panel)  
**Depends on:** `eloquent`

**What's included:**
```
Filament Admin Panel for Salon Owners:
├─ SalonResource ............. CRUD for salon info
├─ StaffResource ............ Staff management
├─ ServiceResource ......... Service listing & editing
├─ OrderResource ......... Order/booking management
└─ SalonImageResource ... Gallery management
```

**Key Features:**
- ✅ Dashboard with stats
- ✅ Staff scheduling
- ✅ Service pricing management
- ✅ Order status tracking
- ✅ Image upload & gallery

---

### 4️⃣ feature/review-system
**Status:** 🟡 READY FOR MERGE  
**Commit:** `e5dbe6a`  
**Task:** TUGAS 5 (Review System)  
**Depends on:** `eloquent`

**What's included:**
```
Review Implementation:
├─ Review Model ........... Rating, visibility control
├─ ReviewController ..... CRUD operations
├─ Migrations ........... Schema updates
├─ Views
│  ├─ review/create.blade.php ..... Leave review form
│  ├─ review/index.blade.php ...... All reviews list
│  └─ salon/show.blade.php (updated with reviews)
└─ Seeders ............... Sample review data
```

**Key Features:**
- ✅ 1-5 star ratings
- ✅ Written reviews
- ✅ Review visibility control (admin approves)
- ✅ User can edit/delete own reviews
- ✅ Salon owner can manage reviews

---

### 5️⃣ feature/smart-booking-payment
**Status:** 🟡 READY FOR MERGE  
**Commit:** `488e707` (docs: add bug audit report)  
**Task:** TUGAS 4 (Smart Booking + Payment)  
**Priority:** 4 & 5 (highest)

**What's included:**
```
Booking Wizard (3-step flow):
│
├─ Step 1: Service Selection
│  └─ BookingController@create
│     ├─ Show services for salon
│     ├─ Display prices & duration
│     └─ Allow service selection
│
├─ Step 2: DateTime Selection
│  ├─ Date picker (calendar UI)
│  ├─ Time slot finder (with availability check)
│  └─ BookingSlotService
│     ├─ Check staff_schedule
│     ├─ Check existing orders
│     └─ Calculate available slots
│
└─ Step 3: Confirmation & Payment
   ├─ Order summary
   ├─ Midtrans Snap payment iframe
   ├─ Order creation (pending payment)
   └─ PaymentController webhook handling

Payment Integration:
├─ Midtrans Snap v2 API
├─ Payment callback processing
├─ Order status updates
└─ Receipt email
```

**Key Files:**
- `app/Http/Controllers/BookingController.php`
- `app/Services/BookingSlotService.php`
- `app/Http/Controllers/PaymentController.php`
- `resources/views/booking/create.blade.php`
- `resources/views/booking/konfirmasi.blade.php`

---

### 6️⃣ fix/post-payment-audit
**Status:** 🟡 HOTFIX READY  
**Commit:** `4bf3a5b`  
**Task:** Bug Fixes (BUG-01, 03, 04, 05, 06)  
**Depends on:** `feature/smart-booking-payment`

**Bugs Fixed:**
```
BUG-01: ❌ Incorrect time slot calculation
        ✅ Fixed: Proper duration calculation with staff_schedule

BUG-03: ❌ Price mismatch in orders
        ✅ Fixed: Use harga_at_order from service at booking time

BUG-04: ❌ Duplicate order creation
        ✅ Fixed: Add unique constraint on order creation

BUG-05: ❌ Payment callback not processing
        ✅ Fixed: Proper Midtrans webhook validation & DB update

BUG-06: ❌ Staff availability not checking properly
        ✅ Fixed: Check both staff_schedule AND existing orders
```

---

### 7️⃣ eloquent
**Status:** 🟢 FOUNDATIONAL  
**Commit:** `7e90f0d`  
**Task:** Core Database & Models (TUGAS 0)  
**No dependencies** (foundation)

**What's included:**
```
Models (13 total):
├─ User ..................... Authentication + profile
├─ Salon .................... Salon information
├─ Service .................. Beauty services
├─ Staff .................... Staff members
├─ StaffSchedule .......... Staff working hours
├─ Kategori ............... Service categories
├─ Kota .................. Cities/locations
├─ SalonImage ............ Salon photos
├─ Order ............... Bookings
├─ OrderDetail ........ Booking items
├─ Review ............ Customer reviews
├─ Pembayaran ... Payments
└─ Promo ........ Discount codes

Migrations:
├─ 2026_05_01_000001_add_slug_to_salon
├─ 2026_05_01_000002_add_catatan_to_order_detail
└─ 2026_05_01_000003_add_unique_index_to_salon_slug

Seeders (15+):
├─ KategoriSeeder ............. 7,183 categories
├─ KotaSeeder ................. 1,700+ cities
├─ SalonSeeder ............... 5,767 salons
├─ ServiceSeeder ............ 190K+ services
├─ SalonImageSeeder ...... 50K+ images
└─ ... (staff, reviews, etc.)
```

**Key Features:**
- ✅ All model relationships defined
- ✅ Custom primary keys (id_salon, id_service, etc.)
- ✅ SoftDeletes on key models
- ✅ Query scopes (Salon::active(), etc.)
- ✅ Accessors for computed properties
- ✅ 5,767 salons with images seeded

---

### 8️⃣ branch-viter
**Status:** 🟡 IN DEVELOPMENT  
**Commit:** `087b4c2`  
**Task:** Customer Panel Polish (TUGAS 8)  
**Depends on:** `eloquent`

**What's included:**
```
Customer Account Features:
├─ Dashboard .............. Account overview
├─ Booking History ....... Upcoming & past bookings
├─ Personal Info ......... Profile management
├─ Favorites ........... Saved salons
├─ Rewards ........... Loyalty points
└─ Settings ....... Preferences
```

---

### 9️⃣ go-fresh
**Status:** 🟠 EXPERIMENTAL  
**Commit:** `d501e7e`  
**Task:** Fresh Start / UI Testing  
**Depends on:** `eloquent`

**Note:** Experimental branch for new UI approaches and testing

---

## 📊 Dependency Map

```
eloquent (FOUNDATION)
├── feature/owner-panel
├── feature/review-system
├── branch-viter
│   ├── feature/dummy-pages-polish
│   └── (merged for viter's experiments)
└── feature/smart-booking-payment
    ├── feature/polish-round (current)
    ├── fix/post-payment-audit
    └── go-fresh
```

---

## ✅ Integration Order (Recommended)

For merging to `main` (from oldest to newest):

1. **eloquent** — Database models & seeders (foundation)
2. **feature/owner-panel** — Salon owner panel
3. **feature/review-system** — Reviews
4. **feature/smart-booking-payment** — Booking + Midtrans
5. **fix/post-payment-audit** — Bug fixes (hotfix priority)
6. **branch-viter** — Customer panel
7. **feature/dummy-pages-polish** — Static pages
8. **feature/polish-round** — Final polish
9. **go-fresh** — (optional, experimental)

---

## 🔄 Merge Strategy

### Option A: Squash Merge (Recommended for Features)
```bash
git checkout main
git pull origin main
git merge --squash feature/polish-round
git commit -m "feat: merge feature/polish-round (TUGAS 9)"
git push origin main
```

### Option B: Conventional Merge (Preserves History)
```bash
git checkout main
git pull origin main
git merge --no-ff feature/polish-round
git commit -m "Merge branch 'feature/polish-round'"
git push origin main
```

### Option C: Rebase Merge (Linear History)
```bash
git checkout main
git pull origin main
git rebase feature/polish-round
git push origin main
```

---

## 🐛 Known Issues & TODOs

- [ ] **Admin panel** (50% complete, not yet on dedicated branch)
- [ ] **Email notifications** (skeleton, needs implementation)
- [ ] **SMS booking reminders** (planned, not started)
- [ ] **Salon search filters** (basic, needs geo-distance query)
- [ ] **Image optimization** (resize on upload)
- [ ] **Cache busting** (Vite assets)

---

## 🚀 Deployment Checklist

Before going live, ensure:

- [ ] All branches merged to `main`
- [ ] `php artisan migrate --force` run on production
- [ ] `npm run build` executed (Vite assets)
- [ ] `php artisan cache:clear` run
- [ ] `.env` configured with:
  - [ ] `APP_ENV=production`
  - [ ] `APP_DEBUG=false`
  - [ ] Midtrans keys set
  - [ ] Database credentials
  - [ ] Mail configuration
- [ ] SSL certificate installed
- [ ] Database backups configured
- [ ] Monitoring set up (errors, performance)

---

**Last Updated:** May 3, 2026  
**Status:** 🟢 All branches successfully pushed to GitHub  
**Repository:** https://github.com/yopalll/VIYGO.git
