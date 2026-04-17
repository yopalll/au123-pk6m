"""
parse_treatwell_json.py
=======================
Converts the JSON output from treatwell_scraper.js into
database-ready JSON files for Laravel seeders.

Usage:
    python database/scripts/parse_treatwell_json.py [input_file.json]

If no input file is specified, it will look for the most recent
treatwell_scrape_*.json in database/data/
"""

import sys
import io
import json
import re
import os
from pathlib import Path

sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8', errors='replace')

BASE_DIR = Path(__file__).resolve().parent.parent  # database/
OUTPUT_DIR = BASE_DIR / "data"
OUTPUT_DIR.mkdir(exist_ok=True)


def find_input_file():
    """Find the most recent scrape JSON file."""
    data_dir = BASE_DIR / "data"
    candidates = sorted(data_dir.glob("treatwell_scrape_*.json"), reverse=True)
    if candidates:
        return candidates[0]

    # Also check database/ root
    candidates = sorted(BASE_DIR.glob("treatwell_scrape_*.json"), reverse=True)
    if candidates:
        return candidates[0]

    return None


def parse_duration(text):
    """Convert duration string to minutes."""
    if not text:
        return 60
    if isinstance(text, (int, float)):
        return int(text)
    total = 0
    hr_match = re.search(r'(\d+)\s*(?:hr|hour)', str(text), re.IGNORECASE)
    min_match = re.search(r'(\d+)\s*(?:min)', str(text), re.IGNORECASE)
    if hr_match:
        total += int(hr_match.group(1)) * 60
    if min_match:
        total += int(min_match.group(1))
    return total if total > 0 else 60


def guess_provinsi(city):
    """Guess province/region for UK cities."""
    uk_regions = {
        "Birmingham": "West Midlands",
        "London": "Greater London",
        "Manchester": "Greater Manchester",
        "Leeds": "West Yorkshire",
        "Liverpool": "Merseyside",
        "Bristol": "Avon",
        "Sheffield": "South Yorkshire",
        "Edinburgh": "Scotland",
        "Glasgow": "Scotland",
        "Cardiff": "Wales",
        "Nottingham": "Nottinghamshire",
        "Newcastle": "Tyne and Wear",
        "Brighton": "East Sussex",
        "Oxford": "Oxfordshire",
    }
    return uk_regions.get(city, "England")


def main():
    # Find input file
    if len(sys.argv) > 1:
        input_file = Path(sys.argv[1])
    else:
        input_file = find_input_file()

    if not input_file or not input_file.exists():
        print("[ERROR] No input file found!")
        print("Usage: python parse_treatwell_json.py <path/to/treatwell_scrape.json>")
        sys.exit(1)

    print(f"[READ] Loading {input_file}...")
    with open(input_file, 'r', encoding='utf-8') as f:
        salons_raw = json.load(f)

    print(f"  Found {len(salons_raw)} salon records")

    # Collect unique cities and categories
    cities_set = set()
    categories_set = set()

    for salon in salons_raw:
        city = salon.get('kota', '').strip() or 'Unknown'
        cities_set.add(city)
        for svc in salon.get('services', []):
            cat = svc.get('kategori', 'Other')
            categories_set.add(cat)

    # Build kota
    city_id_map = {}
    kota_list = []
    for idx, city in enumerate(sorted(cities_set), 1):
        city_id_map[city] = idx
        kota_list.append({
            "id_kota": idx,
            "nama_kota": city,
            "provinsi": guess_provinsi(city),
        })

    # Build kategori
    cat_id_map = {}
    kategori_list = []
    for idx, cat in enumerate(sorted(categories_set), 1):
        slug = re.sub(r'[^a-z0-9]+', '-', cat.lower()).strip('-')
        cat_id_map[cat] = idx
        kategori_list.append({
            "id_kategori": idx,
            "name": cat,
            "deskripsi": f"Services related to {cat}",
            "slug": slug,
            "icon_url": None,
            "is_active": True,
        })
    if "Other" not in cat_id_map:
        idx = len(kategori_list) + 1
        cat_id_map["Other"] = idx
        kategori_list.append({
            "id_kategori": idx,
            "name": "Other",
            "deskripsi": "Other services",
            "slug": "other",
            "icon_url": None,
            "is_active": True,
        })

    # Build salon, service, staff, images
    salon_list = []
    service_list = []
    staff_list = []
    images_list = []

    service_id = 0
    staff_id = 0
    image_id = 0

    for salon_idx, salon in enumerate(salons_raw, 1):
        city = salon.get('kota', '').strip() or 'Unknown'
        id_kota = city_id_map.get(city, 1)

        salon_list.append({
            "id_salon": salon_idx,
            "id_user": salon_idx,
            "id_kota": id_kota,
            "nama_salon": salon.get('nama_salon', ''),
            "alamat": salon.get('alamat', ''),
            "deskripsi": salon.get('deskripsi', ''),
            "phone_number": salon.get('phone_number') or None,
            "opening_time": salon.get('opening_time', '09:00'),
            "closing_time": salon.get('closing_time', '17:00'),
            "image_url": salon.get('image_urls', [None])[0] if salon.get('image_urls') else None,
            "maps_url": None,
            "latitude": salon.get('latitude'),
            "longitude": salon.get('longitude'),
            "rating": salon.get('rating', 0),
            "total_review": salon.get('total_review', 0),
            "status": "active",
            "source_url": salon.get('source_url', ''),
        })

        # Services
        for svc in salon.get('services', []):
            service_id += 1
            cat = svc.get('kategori', 'Other')
            service_list.append({
                "id_service": service_id,
                "id_salon": salon_idx,
                "id_kategori": cat_id_map.get(cat, cat_id_map.get("Other", 1)),
                "nama": svc.get('nama', ''),
                "deskripsi": None,
                "durasi": parse_duration(svc.get('durasi', 60)),
                "harga": svc.get('harga', 0),
                "status": "active",
            })

        # Staff
        for member in salon.get('staff', []):
            staff_id += 1
            staff_list.append({
                "id_staff": staff_id,
                "id_salon": salon_idx,
                "name": member.get('name', ''),
                "profile_url": None,
                "status": "active",
            })

        # Images
        for img_idx, img_url in enumerate(salon.get('image_urls', [])):
            image_id += 1
            images_list.append({
                "id_salon_image": image_id,
                "id_salon": salon_idx,
                "image_url": img_url,
                "is_primary": img_idx == 0,
                "urutan": img_idx + 1,
            })

    # Save
    def save_json(filename, data):
        filepath = OUTPUT_DIR / filename
        with open(filepath, 'w', encoding='utf-8') as f:
            json.dump(data, f, indent=2, ensure_ascii=False)
        print(f"  [OK] {filename}: {len(data)} records")

    print("[WRITE] Writing JSON files...")
    save_json("kota.json", kota_list)
    save_json("kategori.json", kategori_list)
    save_json("salon.json", salon_list)
    save_json("service.json", service_list)
    save_json("staff.json", staff_list)
    save_json("salon_images.json", images_list)

    print(f"\n[DONE] All files written to {OUTPUT_DIR}")
    print(f"\n[SUMMARY]")
    print(f"  Cities:     {len(kota_list)}")
    print(f"  Categories: {len(kategori_list)}")
    print(f"  Salons:     {len(salon_list)}")
    print(f"  Services:   {len(service_list)}")
    print(f"  Staff:      {len(staff_list)}")
    print(f"  Images:     {len(images_list)}")


if __name__ == "__main__":
    main()
