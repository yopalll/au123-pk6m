package main

import (
	"encoding/json"
	"fmt"
	"os"
	"path/filepath"
	"regexp"
	"strconv"
	"strings"

	excelize "github.com/xuri/excelize/v2"
)

// ─── Domain structs ──────────────────────────────────────────────────────────

type Kota struct {
	IDKota   int    `json:"id_kota"`
	NamaKota string `json:"nama_kota"`
	Provinsi string `json:"provinsi"`
}

type Kategori struct {
	IDKategori int    `json:"id_kategori"`
	Name       string `json:"name"`
	Deskripsi  string `json:"deskripsi"`
	Slug       string `json:"slug"`
	IconURL    any    `json:"icon_url"`
	IsActive   bool   `json:"is_active"`
}

type Salon struct {
	IDSalon     int     `json:"id_salon"`
	IDUser      int     `json:"id_user"`
	IDKota      int     `json:"id_kota"`
	NamaSalon   string  `json:"nama_salon"`
	Alamat      string  `json:"alamat"`
	Deskripsi   string  `json:"deskripsi"`
	PhoneNumber any     `json:"phone_number"`
	OpeningTime string  `json:"opening_time"`
	ClosingTime string  `json:"closing_time"`
	ImageURL    any     `json:"image_url"`
	MapsURL     any     `json:"maps_url"`
	Latitude    any     `json:"latitude"`
	Longitude   any     `json:"longitude"`
	Rating      float64 `json:"rating"`
	TotalReview int     `json:"total_review"`
	Status      string  `json:"status"`
	SourceURL   string  `json:"source_url"`
}

type Service struct {
	IDService  int     `json:"id_service"`
	IDSalon    int     `json:"id_salon"`
	IDKategori int     `json:"id_kategori"`
	Nama       string  `json:"nama"`
	Deskripsi  any     `json:"deskripsi"`
	Durasi     int     `json:"durasi"`
	Harga      float64 `json:"harga"`
	Status     string  `json:"status"`
}

type Staff struct {
	IDStaff    int    `json:"id_staff"`
	IDSalon    int    `json:"id_salon"`
	Name       string `json:"name"`
	ProfileURL any    `json:"profile_url"`
	Status     string `json:"status"`
}

type SalonImage struct {
	IDSalonImage int    `json:"id_salon_image"`
	IDSalon      int    `json:"id_salon"`
	ImageURL     string `json:"image_url"`
	IsPrimary    bool   `json:"is_primary"`
	Urutan       int    `json:"urutan"`
}

// ─── Paths ───────────────────────────────────────────────────────────────────

var (
	baseDir   string
	dataDir   string
	excelFile string
)

// ─── Helpers ─────────────────────────────────────────────────────────────────

var ratingRe  = regexp.MustCompile(`^\d+(\.\d+)?$`)
var reviewRe  = regexp.MustCompile(`(\d+)\s*review`)
var hourRe    = regexp.MustCompile(`(?i)(\d+)\s*(?:hr|hrs|hour|hours)`)
var minRe     = regexp.MustCompile(`(?i)(\d+)\s*(?:min|mins|minutes)`)
var priceRe   = regexp.MustCompile(`[\d]+\.?\d*`)
var slugRe    = regexp.MustCompile(`[^a-z0-9]+`)
var timeRe    = regexp.MustCompile(`^\d{1,2}:\d{2}$`)

func parseRating(s string) float64 {
	if s == "" || s == "-.-" || s == "from" {
		return 0
	}
	if !ratingRe.MatchString(s) {
		return 0
	}
	v, _ := strconv.ParseFloat(s, 64)
	return v
}

func parseReviews(s string) int {
	m := reviewRe.FindStringSubmatch(strings.ToLower(s))
	if m == nil {
		return 0
	}
	v, _ := strconv.Atoi(m[1])
	return v
}

func parsePrice(s string) float64 {
	s = strings.NewReplacer("£", "", "€", "$", "$", "", " ", "").Replace(s)
	m := priceRe.FindString(s)
	if m == "" {
		return 0
	}
	v, _ := strconv.ParseFloat(m, 64)
	return v
}

func parseDuration(s string) int {
	total := 0
	if m := hourRe.FindStringSubmatch(s); m != nil {
		v, _ := strconv.Atoi(m[1])
		total += v * 60
	}
	if m := minRe.FindStringSubmatch(s); m != nil {
		v, _ := strconv.Atoi(m[1])
		total += v
	}
	if total == 0 {
		return 60
	}
	return total
}

func parseCity(location string) string {
	parts := strings.Split(location, ",")
	if len(parts) == 0 {
		return "Unknown"
	}
	return strings.TrimSpace(parts[len(parts)-1])
}

func toSlug(s string) string {
	return strings.Trim(slugRe.ReplaceAllString(strings.ToLower(s), "-"), "-")
}

func to24h(timeStr, period string) string {
	parts := strings.Split(timeStr, ":")
	if len(parts) != 2 {
		return timeStr
	}
	h, _ := strconv.Atoi(parts[0])
	m, _ := strconv.Atoi(parts[1])
	p := strings.ToUpper(period)
	if p == "PM" && h != 12 {
		h += 12
	} else if p == "AM" && h == 12 {
		h = 0
	}
	return fmt.Sprintf("%02d:%02d", h, m)
}

// parseOpeningHours scans cols 9–55 for time+period pairs
func parseOpeningHours(row []string) (string, string) {
	var times []int

	maxIdx := len(row) - 1
	for i := 8; i <= 54 && i <= maxIdx; i++ {
		cell := strings.TrimSpace(row[i])
		if timeRe.MatchString(cell) {
			if i+1 <= maxIdx {
				period := strings.TrimSpace(row[i+1])
				if strings.EqualFold(period, "AM") || strings.EqualFold(period, "PM") {
					t24 := to24h(cell, period)
					parts := strings.Split(t24, ":")
					h, _ := strconv.Atoi(parts[0])
					m, _ := strconv.Atoi(parts[1])
					times = append(times, h*60+m)
				}
			}
		}
	}

	if len(times) < 2 {
		return "09:00", "17:00"
	}
	minT, maxT := times[0], times[0]
	for _, t := range times {
		if t < minT {
			minT = t
		}
		if t > maxT {
			maxT = t
		}
	}
	return fmt.Sprintf("%02d:%02d", minT/60, minT%60),
		fmt.Sprintf("%02d:%02d", maxT/60, maxT%60)
}

func parseDescription(row []string) string {
	skip := map[string]bool{
		"nearest public transport:":          true,
		"the team:":                          true,
		"nearest public transport":           true,
		"the team":                           true,
		"what we like about the venue :":     true,
		"what we like about the venue:":      true,
	}
	descCols := []int{55, 56, 57, 58, 59, 60, 61, 62, 71, 72} // 0-indexed
	var parts []string
	for _, idx := range descCols {
		if idx >= len(row) {
			continue
		}
		v := strings.TrimSpace(row[idx])
		if len(v) < 15 {
			continue
		}
		if skip[strings.ToLower(v)] {
			continue
		}
		parts = append(parts, v)
		if len(parts) == 3 {
			break
		}
	}
	if len(parts) == 0 {
		return ""
	}
	desc := strings.Join(parts, " ")
	if len(desc) > 500 {
		desc = desc[:500]
	}
	return desc
}

var categoryMap = []struct {
	cat      string
	keywords []string
}{
	{"Face", []string{"facial", "face", "dermaplaning", "microneedling",
		"peel", "hydrating", "microdermabrasion", "gua sha",
		"anti-aging", "anti-ageing", "hydrafacial", "skin",
		"complexion", "rejuvenat"}},
	{"Eyebrows & Lashes", []string{"brow", "lash", "eyebrow", "eyelash", "tinting",
		"lamination", "lash lift", "henna brow", "lvl"}},
	{"Medical Aesthetics", []string{"aesthetic", "filler", "dermal", "botox",
		"lip filler", "chemical peel", "prp", "vitamin infusion",
		"micro-needling", "meso"}},
	{"Hair Removal", []string{"wax", "waxing", "threading", "laser", "hair removal",
		"sugaring", "brazilian", "bikini"}},
	{"Massage", []string{"massage", "spa", "reflexology", "aromatherapy",
		"deep tissue", "hot stone", "swedish", "thai", "cupping",
		"shiatsu", "sports massage", "pregnancy massage"}},
	{"Body", []string{"body", "scrub", "wrap", "tan", "tanning", "spray tan",
		"slimming", "exfoliation", "hifu", "cellulite", "lymph",
		"detox", "contouring", "back ", "anti-cellulite"}},
	{"Hair", []string{"hair", "haircut", "blowdry", "blow dry", "colour",
		"color", "highlight", "balayage", "perm", "braid",
		"extension", "trim", "shave", "barber", "keratin",
		"scalp", "toner", "ombre", "root", "cornrow"}},
	{"Nails", []string{"nail", "manicure", "pedicure", "gel", "acrylic",
		"shellac", "polish", "dipping"}},
	{"Counselling & Holistic", []string{"counsell", "holistic", "meditation", "reiki",
		"crystal", "chakra", "acupuncture", "hypnotherapy"}},
}

func guessCategory(name string) string {
	n := strings.ToLower(name)
	for _, entry := range categoryMap {
		for _, kw := range entry.keywords {
			if strings.Contains(n, kw) {
				return entry.cat
			}
		}
	}
	return "Face" // default for face-category Excel
}

// ─── JSON I/O ────────────────────────────────────────────────────────────────

func loadJSON(filename string, v any) {
	path := filepath.Join(dataDir, filename)
	data, err := os.ReadFile(path)
	if err != nil {
		return // file doesn't exist yet, start empty
	}
	_ = json.Unmarshal(data, v)
}

func saveJSON(filename string, v any) error {
	path := filepath.Join(dataDir, filename)
	data, err := json.MarshalIndent(v, "", "  ")
	if err != nil {
		return err
	}
	err = os.WriteFile(path, data, 0644)
	if err != nil {
		return err
	}
	return nil
}

func maxInt(slice []int) int {
	m := 0
	for _, v := range slice {
		if v > m {
			m = v
		}
	}
	return m
}

// ─── Main ────────────────────────────────────────────────────────────────────

func main() {
	// Resolve paths
	cwd, _ := os.Getwd()
	if filepath.Base(cwd) == "scripts" {
		baseDir = filepath.Dir(cwd)
	} else {
		baseDir = cwd
	}
	dataDir   = filepath.Join(baseDir, "data")
	excelFile = filepath.Join(baseDir, "SCRAP", "face_allcategory_uk.xlsx")

	fmt.Printf("[READ] Opening %s ...\n", excelFile)
	f, err := excelize.OpenFile(excelFile)
	if err != nil {
		fmt.Fprintf(os.Stderr, "ERROR: Cannot open excel: %v\n", err)
		os.Exit(1)
	}
	defer f.Close()

	sheetName := f.GetSheetName(0)
	rows, err := f.GetRows(sheetName)
	if err != nil {
		fmt.Fprintf(os.Stderr, "ERROR: Cannot get rows: %v\n", err)
		os.Exit(1)
	}
	fmt.Printf("   Sheet: %q  |  Rows: %d  |  Cols: %d\n\n",
		sheetName, len(rows)-1, len(rows[0]))

	// ── Load existing JSON ────────────────────────────────────────────
	fmt.Println("[LOAD] Reading existing JSON files...")
	var exKota     []Kota
	var exKategori []Kategori
	var exSalon    []Salon
	var exService  []Service
	var exStaff    []Staff
	var exImages   []SalonImage

	loadJSON("kota.json",        &exKota)
	loadJSON("kategori.json",    &exKategori)
	loadJSON("salon.json",       &exSalon)
	loadJSON("service.json",     &exService)
	loadJSON("staff.json",       &exStaff)
	loadJSON("salon_images.json",&exImages)

	// Build lookup maps
	cityIDMap  := make(map[string]int)
	catIDMap   := make(map[string]int)
	salonURLs  := make(map[string]bool)

	for _, k := range exKota     { cityIDMap[k.NamaKota] = k.IDKota }
	for _, c := range exKategori { catIDMap[c.Name]      = c.IDKategori }
	for _, s := range exSalon    {
		base := strings.Split(s.SourceURL, "?")[0]
		salonURLs[base] = true
	}

	// ID counters
	var kotaIDs, catIDs, salonIDs, serviceIDs, staffIDs, imageIDs []int
	for _, v := range exKota     { kotaIDs    = append(kotaIDs,    v.IDKota) }
	for _, v := range exKategori { catIDs     = append(catIDs,     v.IDKategori) }
	for _, v := range exSalon    { salonIDs   = append(salonIDs,   v.IDSalon) }
	for _, v := range exService  { serviceIDs = append(serviceIDs, v.IDService) }
	for _, v := range exStaff    { staffIDs   = append(staffIDs,   v.IDStaff) }
	for _, v := range exImages   { imageIDs   = append(imageIDs,   v.IDSalonImage) }

	nextKotaID    := maxInt(kotaIDs) + 1
	nextCatID     := maxInt(catIDs) + 1
	nextSalonID   := maxInt(salonIDs) + 1
	nextServiceID := maxInt(serviceIDs) + 1
	nextStaffID   := maxInt(staffIDs) + 1
	nextImageID   := maxInt(imageIDs) + 1

	// ── Process rows ─────────────────────────────────────────────────
	var newSalons   []Salon
	var newServices []Service
	skipped, processed := 0, 0

	for i, row := range rows {
		if i == 0 {
			continue // skip header
		}

		// Pad row to at least 78 cols to avoid index out of bounds
		for len(row) < 78 {
			row = append(row, "")
		}

		salonURL  := strings.TrimSpace(row[0])
		salonName := strings.TrimSpace(row[1])

		if salonName == "" {
			continue
		}

		baseURL := strings.Split(salonURL, "?")[0]
		if salonURLs[baseURL] {
			skipped++
			continue
		}
		salonURLs[baseURL] = true
		processed++

		salonID := nextSalonID
		nextSalonID++

		// ── Kota ──────────────────────────────────────────────────
		locationRaw := strings.TrimSpace(row[4])
		cityName    := parseCity(locationRaw)
		if _, ok := cityIDMap[cityName]; !ok {
			cityIDMap[cityName] = nextKotaID
			exKota = append(exKota, Kota{
				IDKota:   nextKotaID,
				NamaKota: cityName,
				Provinsi: "England",
			})
			nextKotaID++
		}
		idKota := cityIDMap[cityName]

		// ── Rating & Reviews ──────────────────────────────────────
		rating      := parseRating(strings.TrimSpace(row[2]))
		totalReview := parseReviews(strings.TrimSpace(row[3]))

		// ── Hours ─────────────────────────────────────────────────
		openTime, closeTime := parseOpeningHours(row)

		// ── Description ───────────────────────────────────────────
		desc := parseDescription(row)
		if desc == "" {
			desc = salonName + " is a professional facial and beauty salon."
		}

		// ── Salon ─────────────────────────────────────────────────
		newSalons = append(newSalons, Salon{
			IDSalon:     salonID,
			IDUser:      salonID,
			IDKota:      idKota,
			NamaSalon:   salonName,
			Alamat:      locationRaw,
			Deskripsi:   desc,
			PhoneNumber: nil,
			OpeningTime: openTime,
			ClosingTime: closeTime,
			ImageURL:    nil,
			MapsURL:     nil,
			Latitude:    nil,
			Longitude:   nil,
			Rating:      rating,
			TotalReview: totalReview,
			Status:      "active",
			SourceURL:   baseURL,
		})

		// ── Services ──────────────────────────────────────────────
		type rawSvc struct{ name, dur string; price float64 }
		var svcs []rawSvc

		// Primary service: col 6(idx5), col 7(idx6), col 8(idx7)
		if n := strings.TrimSpace(row[5]); n != "" {
			svcs = append(svcs, rawSvc{n, strings.TrimSpace(row[6]), parsePrice(strings.TrimSpace(row[7]))})
		}
		// Secondary: col 10(idx9), col 11(idx10), col 12(idx11)
		if n := strings.TrimSpace(row[9]); n != "" {
			svcs = append(svcs, rawSvc{n, strings.TrimSpace(row[10]), parsePrice(strings.TrimSpace(row[11]))})
		}
		// Tertiary: col 14(idx13), col 15(idx14), col 16(idx15)
		if n := strings.TrimSpace(row[13]); n != "" {
			svcs = append(svcs, rawSvc{n, strings.TrimSpace(row[14]), parsePrice(strings.TrimSpace(row[15]))})
		}

		// Additional services from extended cols if not description
		for _, pair := range [][3]int{{64, 65, 63}, {67, 68, 66}} {
			n  := strings.TrimSpace(row[pair[0]])
			d  := strings.TrimSpace(row[pair[1]])
			p  := parsePrice(strings.TrimSpace(row[pair[2]]))
			// Skip if it looks like description text
			if n != "" && n != "Off peak" && len(n) < 80 &&
				!strings.Contains(strings.ToLower(n), "nearest") &&
				!strings.Contains(strings.ToLower(n), "the team") &&
				!strings.Contains(strings.ToLower(n), "what we like") &&
				!strings.Contains(strings.ToLower(n), "public transport") &&
				!strings.Contains(strings.ToLower(n), "atmosphere") &&
				!strings.Contains(strings.ToLower(n), "specialises in") {
				svcs = append(svcs, rawSvc{n, d, p})
			}
		}

		for _, svc := range svcs {
			catName := guessCategory(svc.name)
			if _, ok := catIDMap[catName]; !ok {
				catIDMap[catName] = nextCatID
				exKategori = append(exKategori, Kategori{
					IDKategori: nextCatID,
					Name:       catName,
					Deskripsi:  "Services related to " + catName,
					Slug:       toSlug(catName),
					IconURL:    nil,
					IsActive:   true,
				})
				nextCatID++
			}
			idKat := catIDMap[catName]

			newServices = append(newServices, Service{
				IDService:  nextServiceID,
				IDSalon:    salonID,
				IDKategori: idKat,
				Nama:       svc.name,
				Deskripsi:  nil,
				Durasi:     parseDuration(svc.dur),
				Harga:      svc.price,
				Status:     "active",
			})
			nextServiceID++
		}
		_ = nextStaffID
		_ = nextImageID
	}

	// ── Save ──────────────────────────────────────────────────────────
	fmt.Printf("\n[MERGE] New salons: %d  |  Skipped duplicates: %d\n\n", processed, skipped)
	fmt.Println("[WRITE] Saving JSON files...")

	allSalons   := append(exSalon,   newSalons...)
	allServices := append(exService, newServices...)

	files := []struct {
		name string
		data any
	}{
		{"kota.json",        exKota},
		{"kategori.json",    exKategori},
		{"salon.json",       allSalons},
		{"service.json",     allServices},
		{"staff.json",       exStaff},
		{"salon_images.json",exImages},
	}

	for _, f := range files {
		if err := saveJSON(f.name, f.data); err != nil {
			fmt.Fprintf(os.Stderr, "ERROR saving %s: %v\n", f.name, err)
			os.Exit(1)
		}
		switch v := f.data.(type) {
		case []Kota:       fmt.Printf("   [OK] %-22s %d records\n", f.name, len(v))
		case []Kategori:   fmt.Printf("   [OK] %-22s %d records\n", f.name, len(v))
		case []Salon:      fmt.Printf("   [OK] %-22s %d records\n", f.name, len(v))
		case []Service:    fmt.Printf("   [OK] %-22s %d records\n", f.name, len(v))
		case []Staff:      fmt.Printf("   [OK] %-22s %d records\n", f.name, len(v))
		case []SalonImage: fmt.Printf("   [OK] %-22s %d records\n", f.name, len(v))
		}
	}

	fmt.Printf("\n[DONE]\n")
	fmt.Printf("   New salons added   : %d\n", len(newSalons))
	fmt.Printf("   New services added : %d\n", len(newServices))
	fmt.Printf("   Total kota         : %d\n", len(exKota))
	fmt.Printf("   Total kategori     : %d\n", len(exKategori))
	fmt.Printf("   Total salons       : %d\n", len(allSalons))
	fmt.Printf("   Total services     : %d\n", len(allServices))
	fmt.Printf("   Output dir         : %s\n", dataDir)
}
