# VIYGO V2 — Design Reference

> Design System: **Serene Floral Noir**  
> Tool: Stitch (Google)  
> Spesifikasi lengkap: [DESIGN-SYSTEM.md](DESIGN-SYSTEM.md)

Tiap folder berisi `screen.png` (screenshot final) dan `code.html` (HTML prototype lengkap).
Gunakan `code.html` sebagai referensi UI saat membangun Blade views.

---

## Prefix Naming

| Prefix | Modul | Phase |
|--------|-------|-------|
| `a*` | Salon Discovery (search, category, detail) | V1 — sudah ada |
| `b*` | Auth (login, register, forgot password) | V1 |
| `c*` | Booking Flow (wizard, success) | V1 |
| `d*` | Payment Page | V1 |
| `e*` | Account Pages | V1 + V2 |
| `g*` | Salon Owner Panel | V1 |
| `h*` | Platform Admin Panel | V1 |
| `j*` | **Shop / E-commerce** | **V2 — Phase 2B** |
| `k*` | **Lookbook** | **V2 — Phase 3A** |
| `l*` | **Empty Return / Sustainability** | **V2 — Phase 3B** |
| `m*` / `m_*` | Mobile versi dari semua screen di atas | |
| `m1*` | Help Center | |

---

## Screen Index — V2 Baru

### J — Shop / E-commerce
| Folder | Deskripsi |
|--------|-----------|
| `m_j1_skincare_shop_landing/` | Mobile: landing halaman shop |
| `j2_product_detail_midnight_renewal_serum_1/` | Detail produk (bagian atas) |
| `j2_product_detail_midnight_renewal_serum_2/` | Detail produk (bagian bawah: ingredients, review) |
| `j3_shopping_bag/` | Keranjang belanja |
| `j3_shopping_bag_1/` | Keranjang (versi 1) |
| `j3_shopping_bag_2/` | Keranjang (versi 2 — lebih lengkap) |
| `j4_order_confirmation/` | Konfirmasi pesanan |
| `j4_order_confirmation_1/` | Order confirmation (versi 1) |
| `j4_order_confirmation_2/` | Order confirmation (versi 2) |

### K — Lookbook
| Folder | Deskripsi |
|--------|-----------|
| `k1_lookbook_index_editorial_view/` | Halaman index lookbook — editorial grid |
| `k1.1_lookbook_detail_midnight_muse/` | Detail lookbook "Midnight Muse" |
| `k2_editorial_article_the_art_of_nightly_restoration/` | Editorial article |
| `k2.1_editorial_the_alchemy_of_orchids/` | Editorial article (versi lain) |
| `m_k1_lookbook_index/` | Mobile: lookbook index |
| `m_k2_editorial_detail/` | Mobile: editorial detail |

### L — Empty Return / Sustainability
| Folder | Deskripsi |
|--------|-----------|
| `l1_sustainability_landing/` | Landing page Empty Return |
| `l1.1_detailed_sustainability_report/` | Sustainability report detail |
| `l2_return_request_flow/` | Form pengajuan pengembalian botol |
| `l4_points_dashboard/` | Dashboard poin & tier user |
| `l5_our_story_editorial/` | Halaman "Our Story" |
| `m_l1_sustainability_landing/` | Mobile: sustainability landing |
| `m_l2_return_request_flow/` | Mobile: return request flow |
| `m1_help_center/` | Help center |
| `m1.1_help_center_recycling_guide/` | Recycling guide |

---

## Screen Index — V1 (Sudah Ada, Dijadikan Referensi Redesign)

### A — Salon Discovery
| Folder | Deskripsi |
|--------|-----------|
| `a2_search_results/` | Hasil pencarian salon |
| `a2_search_results_discovery/` | Search (versi discovery — lebih refined) |
| `a2_search_results_refined_luxury/` | Search (versi luxury) |
| `a3_category_page/` | Halaman kategori |
| `a3_category_facial_treatments/` | Kategori Facial Treatments |
| `a3_category_page_refined_luxury/` | Kategori (versi luxury) |
| `a5_salon_detail/` | Detail salon |

### B — Auth
| Folder | Deskripsi |
|--------|-----------|
| `b1_login/` | Login |
| `b1_login_luxury_experience/` | Login (luxury version) |
| `b2_register/` | Register |
| `b2_register_join_the_elite/` | Register (luxury version) |
| `b3_forgot_password/` | Forgot password |

### C — Booking
| Folder | Deskripsi |
|--------|-----------|
| `c1_booking_wizard_services/` | Step 1: pilih service |
| `c1_booking_wizard_schedule/` | Step 2: pilih jadwal |
| `c1_booking_wizard_review/` | Step 3: review booking |
| `c1_booking_success/` | Booking berhasil |

### D, E — Payment & Account
| Folder | Deskripsi |
|--------|-----------|
| `d1_payment_page/` | Halaman pembayaran |
| `e1_account_overview/` | Dashboard akun |
| `e2_bookings_history/` | Riwayat booking |
| `e2.1_order_booking_history/` | Booking history (versi baru + V2) |
| `e3_favorites/` | Favorit salon |
| `e4_account_settings/` | Pengaturan akun |

### G — Salon Owner Panel
| Folder | Deskripsi |
|--------|-----------|
| `g1_salon_owner_dashboard/` | Dashboard owner |
| `g2_salon_profile_management/` | Manajemen profil salon |
| `g3_concierge_inbox/` | Inbox / booking requests |
| `g4_salon_inventory_management/` | Inventaris |
| `g5_salon_scheduling_calendar/` | Kalender jadwal |
| `g6_stylist_profile_detail/` | Profil stylist |
| `g7_stylist_profile/` | List stylist |

### H — Platform Admin
| Folder | Deskripsi |
|--------|-----------|
| `h1_platform_admin_dashboard/` | Admin dashboard |
| `h2_platform_metrics_reporting/` | Metrics & reporting |
| `h2.1_analytics_deep_dive/` | Analytics detail |
| `h3_platform_settings_global_config/` | Global settings |

---

## Design System Highlights

**Colors:** Dark luxury (background `#111316`, primary `#ffdbc8` warm peach, secondary `#a5cbea` soft blue)  
**Typography:** Playfair Display (headings) + Manrope (body)  
**Tone:** Premium, editorial, sophisticated — cocok untuk beauty/skincare brand
