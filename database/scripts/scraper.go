// scraper.go — VIYGO Treatwell Scraper Part 5 (URL-based + registry fallback)
//
// Cara kerja:
//   - Mode URL  : pass 1+ URL Treatwell sebagai positional arg → scrape
//                 listing tsb. Kategori/sub/kota di-auto-detect dari pattern URL.
//   - Mode auto : tanpa URL — semua URL dibangun dari registry internal
//                 (7 kategori + 42 sub_kategori) × kota list.
//   - Output: 8 file JSON di database/data/ — siap di-seed.
//
// Build:
//   go build -o scraper.exe scraper.go
//
// Usage (URL mode — yang dipakai sekarang):
//   scraper.exe "https://www.treatwell.co.uk/places/treatment-blow-dry/in-london-uk/"
//   scraper.exe "URL1" "URL2" "URL3"              # multiple URL sekaligus
//   scraper.exe --max-pages=10 "URL"              # batasi pagination
//
// Usage (auto mode — kalau gak kasih URL):
//   scraper.exe                                   # full crawl 7 kat × 42 sub × 3 kota
//   scraper.exe --kota=london,manchester          # batasi kota
//   scraper.exe --skip-kategori                   # skip 7 kategori utama
//   scraper.exe --skip-sub                        # skip 42 sub_kategori
//   scraper.exe --only-kategori=hair              # cuma 1 kategori utama
//   scraper.exe --only-sub=blow-dry               # cuma 1 sub_kategori

package main

import (
	"encoding/json"
	"flag"
	"fmt"
	"io"
	"math/rand"
	"net/http"
	"os"
	"path/filepath"
	"regexp"
	"sort"
	"strconv"
	"strings"
	"sync"
	"time"

	"golang.org/x/net/html"
)

// ─── Config ──────────────────────────────────────────────────────────────────

const (
	defaultMaxPages = 300
	maxWorkers      = 15                       // turun dari 100 → Treatwell/Cloudflare batasi 502 di concurrency tinggi
	retryWorkers    = 5                        // workers utk retry pass (lebih pelan, lebih aman)
	requestDelay    = 600 * time.Millisecond
	maxRetries      = 5                        // naik dari 3 — kasih kesempatan recover dari 5xx transient
	requestTimeout  = 20 * time.Second
	userAgent       = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36"
)

var defaultKotaList = []string{"london", "manchester", "birmingham"}

// ─── Registry ────────────────────────────────────────────────────────────────

type KategoriDef struct {
	IDKategori    int
	Slug          string
	Name          string
	TreatwellSlug string
}

var kategoriRegistry = []KategoriDef{
	{1, "hair",         "Hair",         "hair"},
	{2, "hair-removal", "Hair Removal", "hair-removal"},
	{3, "massage",      "Massage",      "massage"},
	{4, "nails",        "Nails",        "nails"},
	{5, "face",         "Face",         "face-beauty"},
	{6, "body",         "Body",         "body-treatments"},
	{7, "mens",         "Men's",        "mens-grooming"},
}

type SubKategoriDef struct {
	IDSubKategori int
	IDKategori    int
	Slug          string
	Name          string
	TreatwellSlug string
}

var subKategoriRegistry = []SubKategoriDef{
	{1, 1, "ladies-haircuts", "Ladies' Haircuts", "ladies-haircuts-1"},
	{2, 1, "blow-dry", "Blow Dry", "blow-dry"},
	{3, 1, "ladies-hair-colouring-highlights", "Ladies' Hair Colouring & Highlights", "hair-colouring"},
	{4, 1, "ladies-brazilian-blow-dry", "Ladies' Brazilian Blow Dry", "ladies-brazilian-blow-dry"},
	{5, 1, "balayage-ombre", "Balayage & Ombre", "balayage"},
	{6, 1, "mens-haircut-hair", "Men's Haircut", "men-s-haircut"},
	{7, 2, "facial-threading", "Facial Threading", "facial-threading"},
	{8, 2, "ladies-waxing", "Ladies' Waxing", "ladies-waxing"},
	{9, 2, "sugaring", "Sugaring", "sugaring"},
	{10, 2, "hollywood-waxing", "Hollywood Waxing", "hollywood-waxing"},
	{11, 2, "mens-waxing-hair-removal", "Men's Waxing", "men-s-waxing"},
	{12, 2, "ladies-leg-waxing", "Ladies' Leg Waxing", "ladies-leg-waxing"},
	{13, 3, "deep-tissue-massage", "Deep Tissue Massage", "deep-tissue-massage"},
	{14, 3, "swedish-massage", "Swedish Massage", "swedish-massage"},
	{15, 3, "therapeutic-massage", "Therapeutic Massage", "therapeutic-massage"},
	{16, 3, "thai-massage", "Thai Massage", "thai-massage"},
	{17, 3, "aromatherapy-massage", "Aromatherapy Massage", "aromatherapy-massage"},
	{18, 3, "hot-stone-massage", "Hot Stone Massage", "hot-stone-massage"},
	{19, 4, "pedicure", "Pedicure", "pedicure"},
	{20, 4, "manicure", "Manicure", "manicure"},
	{21, 4, "nail-gel-polish-removal", "Nail or Gel Polish Removal", "nail-or-gel-polish-removal"},
	{22, 4, "gel-nails-manicure", "Gel Nails Manicure", "gel-nails-manicure"},
	{23, 4, "gel-nails-pedicure", "Gel Nails Pedicure", "gel-nails-pedicure"},
	{24, 4, "acrylic-hard-gel-nail-extensions", "Acrylic, Hard Gel & Nail Extensions", "hard-gel-extensions-overlays"},
	{25, 5, "classic-facials", "Classic Facials", "classic-facials"},
	{26, 5, "eyelash-extensions", "Eyelash Extensions", "eyelash-extensions"},
	{27, 5, "eyebrow-eyelash-tinting", "Eyebrow and Eyelash Tinting", "eyebrow-eyelash-tinting"},
	{28, 5, "eyebrow-threading", "Eyebrow Threading", "eyebrow-threading"},
	{29, 5, "eyebrow-waxing", "Eyebrow Waxing", "eyebrow-waxing"},
	{30, 5, "definition-brows", "Definition Brows", "brow-definition"},
	{31, 6, "spray-tanning-sunless-tanning", "Spray Tanning and Sunless Tanning", "spray-tanning-and-sunless-tanning"},
	{32, 6, "body-exfoliation-treatments", "Body Exfoliation Treatments", "body-exfoliation-treatments"},
	{33, 6, "body-wraps", "Body Wraps", "body-wraps"},
	{34, 6, "colonic-hydrotherapy", "Colonic Hydrotherapy", "colonic-hydrotherapy"},
	{35, 6, "cryolipolysis", "Cryolipolysis", "cryolipolysis"},
	{36, 6, "cellulite-treatments", "Cellulite Treatments", "cellulite-treatments"},
	{37, 7, "mens-haircut-mens", "Men's Haircut", "men-s-haircut"},
	{38, 7, "beard-trims-shaves", "Beard Trims and Shaves", "beard-trimming"},
	{39, 7, "mens-hair-colouring", "Men's Hair Colouring", "men-s-hair-colouring"},
	{40, 7, "mens-brazilian-blow-dry", "Men's Brazilian Blow Dry", "men-s-brazilian-blow-dry"},
	{41, 7, "mens-facials", "Men's Facials", "men-s-facials"},
	{42, 7, "mens-waxing-mens", "Men's Waxing", "men-s-waxing"},
}

// ─── Sub-kategori matcher (untuk auto-tag service ke id_sub_kategori) ───────

var subKategoriKeywords = map[int][]string{
	1:  {"ladies haircut", "ladies' haircut", "ladies cut", "ladies' cut", "women's haircut"},
	2:  {"blow dry", "blowdry", "blow-dry"},
	3:  {"colour", "color", "highlight", "tint", "toner"},
	4:  {"brazilian blow", "keratin", "smoothing"},
	5:  {"balayage", "ombre", "babylights", "foilyage"},
	6:  {"men's haircut", "mens haircut", "gents cut"},
	7:  {"facial threading", "face threading"},
	8:  {"ladies wax", "ladies' wax", "underarm wax", "arm wax", "face wax"},
	9:  {"sugaring", "sugar wax"},
	10: {"hollywood wax"},
	11: {"men's wax", "mens wax", "back wax", "chest wax"},
	12: {"leg wax", "half leg", "full leg"},
	13: {"deep tissue", "deep-tissue"},
	14: {"swedish"},
	15: {"therapeutic", "remedial"},
	16: {"thai"},
	17: {"aromatherapy", "aroma"},
	18: {"hot stone", "stone massage"},
	19: {"pedicure", "pedi"},
	20: {"manicure", "mani"},
	21: {"polish removal", "gel removal", "soak off"},
	22: {"gel manicure", "gel mani", "shellac mani"},
	23: {"gel pedicure", "gel pedi", "shellac pedi"},
	24: {"acrylic", "hard gel", "extension", "overlay", "tip"},
	25: {"classic facial", "express facial", "deep cleanse"},
	26: {"eyelash extension", "lash extension", "individual lash", "russian volume", "classic lash"},
	27: {"brow tint", "lash tint", "eyebrow tint", "eyelash tint"},
	28: {"brow thread", "eyebrow thread"},
	29: {"brow wax", "eyebrow wax"},
	30: {"definition brow", "brow definition", "hd brow", "henna brow", "brow lamination"},
	31: {"spray tan", "sunless tan", "fake tan"},
	32: {"body scrub", "body exfoliation", "body polish"},
	33: {"body wrap", "seaweed wrap", "mud wrap"},
	34: {"colonic", "colon hydrotherapy"},
	35: {"cryolipolysis", "fat freez"},
	36: {"cellulite", "lipo cavitation"},
	37: {"men's haircut", "mens haircut", "gents cut"},
	38: {"beard trim", "beard shape", "wet shave", "hot towel shave"},
	39: {"men's colour", "mens colour", "men's hair colour", "grey blending"},
	40: {"men's brazilian", "mens brazilian"},
	41: {"men's facial", "mens facial"},
	42: {"men's wax", "mens wax"},
}

func matchSubKategori(svcName, hint string, idKategori int) int {
	nameLower := strings.ToLower(svcName)
	hintLower := strings.ToLower(hint)
	for _, sub := range subKategoriRegistry {
		if sub.IDKategori != idKategori {
			continue
		}
		for _, kw := range subKategoriKeywords[sub.IDSubKategori] {
			if strings.Contains(nameLower, kw) {
				return sub.IDSubKategori
			}
		}
	}
	for _, sub := range subKategoriRegistry {
		if sub.IDKategori != idKategori {
			continue
		}
		for _, kw := range subKategoriKeywords[sub.IDSubKategori] {
			if strings.Contains(hintLower, kw) {
				return sub.IDSubKategori
			}
		}
	}
	return 0
}

// ─── Domain structs (output JSON) ────────────────────────────────────────────

type Kota struct {
	IDKota   int    `json:"id_kota"`
	NamaKota string `json:"nama_kota"`
	Provinsi string `json:"provinsi"`
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
	IDService     int     `json:"id_service"`
	IDSalon       int     `json:"id_salon"`
	IDKategori    *int    `json:"id_kategori"`
	IDSubKategori *int    `json:"id_sub_kategori"`
	Nama          string  `json:"nama"`
	Deskripsi     any     `json:"deskripsi"`
	Durasi        int     `json:"durasi"`
	Harga         float64 `json:"harga"`
	Status        string  `json:"status"`
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

// Pivot — output langsung scraper, tidak derived
type SalonKategori struct {
	IDSalon     int `json:"id_salon"`
	IDKategori  int `json:"id_kategori"`
}

type SalonSubKategori struct {
	IDSalon       int `json:"id_salon"`
	IDSubKategori int `json:"id_sub_kategori"`
}

// User (owner) — 1 per salon, generated saat scraping. Password plain-text
// (di-hash oleh UserSeeder.php saat seed ke DB).
type User struct {
	IDUser    int    `json:"id_user"`
	FirstName string `json:"first_name"`
	LastName  string `json:"last_name"`
	Email     string `json:"email"`
	Password  string `json:"password"`         // plain-text, di-hash saat seed
	Role      string `json:"role"`             // 'salon_owner', 'admin', 'customer'
	IsActive  bool   `json:"is_active"`
	IDSalon   *int   `json:"id_salon,omitempty"` // metadata: link ke salon (kalau owner)
}

// ─── Scraped intermediate ────────────────────────────────────────────────────

type ScrapedSalon struct {
	NamaSalon   string
	Alamat      string
	Kota        string
	Provinsi    string
	KodePos     string
	Rating      float64
	TotalReview int
	OpeningTime string
	ClosingTime string
	Deskripsi   string
	PhoneNumber string
	Latitude    *float64
	Longitude   *float64
	ImageURLs   []string
	Services    []ScrapedSvc
	StaffNames  []string
	SourceURL   string
}

type ScrapedSvc struct {
	Nama         string
	Harga        float64
	Durasi       int
	CategoryHint string
}

type ListingEntry struct {
	Name string
	URL  string
}

// ─── HTTP client ─────────────────────────────────────────────────────────────

var httpClient = &http.Client{
	Timeout: requestTimeout,
	CheckRedirect: func(req *http.Request, via []*http.Request) error {
		if len(via) >= 5 {
			return fmt.Errorf("too many redirects")
		}
		return nil
	},
}

func fetchHTML(url string) (string, error) {
	var lastStatus int
	for attempt := 0; attempt < maxRetries; attempt++ {
		req, err := http.NewRequest("GET", url, nil)
		if err != nil {
			return "", err
		}
		req.Header.Set("User-Agent", userAgent)
		req.Header.Set("Accept", "text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8")
		req.Header.Set("Accept-Language", "en-GB,en;q=0.9")

		resp, err := httpClient.Do(req)
		if err != nil {
			// Network/connection error — backoff & retry
			wait := time.Duration(2<<attempt)*time.Second + time.Duration(rand.Intn(2000))*time.Millisecond
			time.Sleep(wait)
			continue
		}
		bodyBytes, _ := io.ReadAll(resp.Body)
		resp.Body.Close()
		lastStatus = resp.StatusCode

		// Rate limited — wait longer
		if resp.StatusCode == 429 {
			wait := time.Duration(15+attempt*15) * time.Second
			time.Sleep(wait)
			continue
		}
		// 5xx transient — retry dengan exponential backoff
		if resp.StatusCode >= 500 && resp.StatusCode < 600 {
			wait := time.Duration(2<<attempt)*time.Second + time.Duration(rand.Intn(3000))*time.Millisecond
			time.Sleep(wait)
			continue
		}
		// 4xx (selain 429) — fail fast, gak akan recover
		if resp.StatusCode >= 400 {
			return "", fmt.Errorf("HTTP %d for %s", resp.StatusCode, url)
		}
		return string(bodyBytes), nil
	}
	if lastStatus > 0 {
		return "", fmt.Errorf("HTTP %d after %d retries for %s", lastStatus, maxRetries, url)
	}
	return "", fmt.Errorf("max retries reached for %s", url)
}

// ─── HTML helpers ────────────────────────────────────────────────────────────

func findNodes(n *html.Node, match func(*html.Node) bool) []*html.Node {
	var res []*html.Node
	var walk func(*html.Node)
	walk = func(node *html.Node) {
		if match(node) {
			res = append(res, node)
		}
		for c := node.FirstChild; c != nil; c = c.NextSibling {
			walk(c)
		}
	}
	walk(n)
	return res
}

func getAttr(n *html.Node, key string) string {
	for _, a := range n.Attr {
		if a.Key == key {
			return a.Val
		}
	}
	return ""
}

func getTextContent(n *html.Node) string {
	if n == nil {
		return ""
	}
	var sb strings.Builder
	var walk func(*html.Node)
	walk = func(node *html.Node) {
		if node.Type == html.TextNode {
			sb.WriteString(node.Data)
		}
		for c := node.FirstChild; c != nil; c = c.NextSibling {
			walk(c)
		}
	}
	walk(n)
	return strings.TrimSpace(sb.String())
}

// ─── JSON-LD extraction ─────────────────────────────────────────────────────

func extractJSONLD(doc *html.Node) []map[string]any {
	var results []map[string]any
	scripts := findNodes(doc, func(n *html.Node) bool {
		return n.Type == html.ElementNode && n.Data == "script" && getAttr(n, "type") == "application/ld+json"
	})
	for _, s := range scripts {
		text := getTextContent(s)
		if text == "" {
			continue
		}
		var obj map[string]any
		if json.Unmarshal([]byte(text), &obj) == nil {
			results = append(results, obj)
			continue
		}
		var arr []map[string]any
		if json.Unmarshal([]byte(text), &arr) == nil {
			results = append(results, arr...)
		}
	}
	return results
}

func findStructuredBusiness(jsonlds []map[string]any) map[string]any {
	for _, obj := range jsonlds {
		t, _ := obj["@type"].(string)
		if t == "LocalBusiness" || t == "HealthAndBeautyBusiness" || t == "BeautySalon" {
			return obj
		}
		if graph, ok := obj["@graph"].([]any); ok {
			for _, item := range graph {
				if m, ok := item.(map[string]any); ok {
					gt, _ := m["@type"].(string)
					if gt == "LocalBusiness" || gt == "HealthAndBeautyBusiness" || gt == "BeautySalon" {
						return m
					}
				}
			}
		}
	}
	return nil
}

func findItemList(jsonlds []map[string]any) []ListingEntry {
	var entries []ListingEntry
	for _, obj := range jsonlds {
		t, _ := obj["@type"].(string)
		if t != "ItemList" {
			continue
		}
		items, ok := obj["itemListElement"].([]any)
		if !ok {
			continue
		}
		for _, item := range items {
			m, ok := item.(map[string]any)
			if !ok {
				continue
			}
			name, _ := m["name"].(string)
			url, _ := m["url"].(string)
			if url != "" {
				entries = append(entries, ListingEntry{Name: name, URL: url})
			}
		}
	}
	return entries
}

// ─── Listing page parser ────────────────────────────────────────────────────

var (
	placeURLRe   = regexp.MustCompile(`https?://www\.treatwell\.co\.uk/place/([^/?#"'\s]+)`)
	paginationRe = regexp.MustCompile(`https?://www\.treatwell\.co\.uk/places/[^"'\s]*page-(\d+)[^"'\s]*`)
	pageNumRe    = regexp.MustCompile(`page-(\d+)`)
)

func extractPageNum(url string) int {
	if m := pageNumRe.FindStringSubmatch(url); m != nil {
		n, _ := strconv.Atoi(m[1])
		return n
	}
	return 0
}

type ListingResult struct {
	Entries      []ListingEntry
	NextPageURLs []string
}

func scrapeListingPage(htmlStr string, baseOrigin string) ListingResult {
	result := ListingResult{}
	doc, err := html.Parse(strings.NewReader(htmlStr))
	if err != nil {
		return result
	}

	jsonlds := extractJSONLD(doc)
	entries := findItemList(jsonlds)
	if len(entries) > 0 {
		for i := range entries {
			if !strings.HasPrefix(entries[i].URL, "http") {
				entries[i].URL = baseOrigin + entries[i].URL
			}
		}
		result.Entries = entries
	}

	if len(result.Entries) == 0 {
		seen := make(map[string]bool)
		allLinks := findNodes(doc, func(n *html.Node) bool {
			return n.Type == html.ElementNode && n.Data == "a"
		})
		for _, link := range allLinks {
			href := getAttr(link, "href")
			if href == "" {
				continue
			}
			if !strings.HasPrefix(href, "http") {
				href = baseOrigin + href
			}
			matches := placeURLRe.FindStringSubmatch(href)
			if matches == nil {
				continue
			}
			slug := matches[1]
			cleanURL := fmt.Sprintf("https://www.treatwell.co.uk/place/%s/", strings.TrimSuffix(slug, "/"))
			if seen[cleanURL] {
				continue
			}
			seen[cleanURL] = true
			name := getTextContent(link)
			if name == "Go to venue" || name == "" || len(name) > 200 {
				name = strings.Title(strings.ReplaceAll(strings.TrimSuffix(slug, "/"), "-", " "))
			}
			entries = append(entries, ListingEntry{Name: name, URL: cleanURL})
		}
		result.Entries = entries
	}

	allLinks := findNodes(doc, func(n *html.Node) bool {
		return n.Type == html.ElementNode && n.Data == "a"
	})
	pageURLsSeen := make(map[string]bool)
	for _, link := range allLinks {
		href := getAttr(link, "href")
		if href == "" {
			continue
		}
		if !strings.HasPrefix(href, "http") {
			href = baseOrigin + href
		}
		if paginationRe.MatchString(href) {
			cleanPageURL := strings.Split(href, "?")[0]
			if !strings.HasSuffix(cleanPageURL, "/") {
				cleanPageURL += "/"
			}
			if !pageURLsSeen[cleanPageURL] {
				pageURLsSeen[cleanPageURL] = true
				result.NextPageURLs = append(result.NextPageURLs, cleanPageURL)
			}
		}
	}
	sort.Slice(result.NextPageURLs, func(i, j int) bool {
		return extractPageNum(result.NextPageURLs[i]) < extractPageNum(result.NextPageURLs[j])
	})

	return result
}

// ─── Detail page parser ─────────────────────────────────────────────────────

func scrapeDetailPage(url string) (*ScrapedSalon, error) {
	htmlStr, err := fetchHTML(url)
	if err != nil {
		return nil, err
	}
	doc, err := html.Parse(strings.NewReader(htmlStr))
	if err != nil {
		return nil, err
	}
	salon := &ScrapedSalon{
		OpeningTime: "09:00",
		ClosingTime: "17:00",
		SourceURL:   url,
	}

	jsonlds := extractJSONLD(doc)
	biz := findStructuredBusiness(jsonlds)

	if biz != nil {
		salon.NamaSalon, _ = biz["name"].(string)
		salon.Deskripsi, _ = biz["description"].(string)
		salon.PhoneNumber, _ = biz["telephone"].(string)

		if addr, ok := biz["address"].(map[string]any); ok {
			street, _ := addr["streetAddress"].(string)
			locality, _ := addr["addressLocality"].(string)
			postalCode, _ := addr["postalCode"].(string)
			region, _ := addr["addressRegion"].(string)
			parts := []string{}
			for _, p := range []string{street, locality, postalCode} {
				if p != "" {
					parts = append(parts, p)
				}
			}
			salon.Alamat = strings.Join(parts, ", ")
			salon.Kota = locality
			salon.Provinsi = region
			salon.KodePos = postalCode
		}
		if agg, ok := biz["aggregateRating"].(map[string]any); ok {
			if rv, ok := agg["ratingValue"]; ok {
				salon.Rating = toFloat(rv)
			}
			if rc, ok := agg["reviewCount"]; ok {
				salon.TotalReview = toInt(rc)
			}
		}
		if geo, ok := biz["geo"].(map[string]any); ok {
			if lat, ok := geo["latitude"]; ok {
				v := toFloat(lat)
				salon.Latitude = &v
			}
			if lng, ok := geo["longitude"]; ok {
				v := toFloat(lng)
				salon.Longitude = &v
			}
		}
		if specs, ok := biz["openingHoursSpecification"].([]any); ok && len(specs) > 0 {
			var allOpens, allCloses []string
			for _, spec := range specs {
				if m, ok := spec.(map[string]any); ok {
					if o, ok := m["opens"].(string); ok {
						allOpens = append(allOpens, o)
					}
					if c, ok := m["closes"].(string); ok {
						allCloses = append(allCloses, c)
					}
				}
			}
			if len(allOpens) > 0 {
				salon.OpeningTime = allOpens[0]
			}
			if len(allCloses) > 0 {
				salon.ClosingTime = allCloses[0]
			}
		}
		if img, ok := biz["image"]; ok {
			switch v := img.(type) {
			case string:
				salon.ImageURLs = append(salon.ImageURLs, v)
			case []any:
				for _, u := range v {
					if s, ok := u.(string); ok {
						salon.ImageURLs = append(salon.ImageURLs, s)
					}
				}
			}
		}
		if catalog, ok := biz["hasOfferCatalog"].(map[string]any); ok {
			extractServicesFromCatalog(catalog, "", salon)
		}
	}

	if salon.NamaSalon == "" {
		h1s := findNodes(doc, func(n *html.Node) bool {
			return n.Type == html.ElementNode && n.Data == "h1"
		})
		if len(h1s) > 0 {
			salon.NamaSalon = getTextContent(h1s[0])
		}
	}

	staffSeen := make(map[string]bool)
	staffRe := regexp.MustCompile(`(?i)(?:treatment by|service by|styled by|treated by)\s+([^"<>]+)`)
	empDescRe := regexp.MustCompile(`"employeeDescription"\s*:\s*"([^"]+)"`)
	for _, m := range empDescRe.FindAllStringSubmatch(htmlStr, -1) {
		if sm := staffRe.FindStringSubmatch(m[1]); sm != nil {
			name := strings.TrimSpace(sm[1])
			if name != "" && !staffSeen[name] && len(name) < 60 {
				staffSeen[name] = true
				salon.StaffNames = append(salon.StaffNames, name)
			}
		}
	}
	return salon, nil
}

func extractServicesFromCatalog(catalog map[string]any, parentCategory string, salon *ScrapedSalon) {
	categoryName, _ := catalog["name"].(string)
	if categoryName == "Available Services" {
		categoryName = ""
	}
	items, ok := catalog["itemListElement"].([]any)
	if !ok {
		return
	}
	for _, item := range items {
		m, ok := item.(map[string]any)
		if !ok {
			continue
		}
		itemType, _ := m["@type"].(string)
		if itemType == "OfferCatalog" {
			extractServicesFromCatalog(m, categoryName, salon)
			continue
		}
		if itemType == "Offer" || itemType == "AggregateOffer" {
			offered, ok := m["itemOffered"].(map[string]any)
			if !ok {
				continue
			}
			svcName, _ := offered["name"].(string)
			if svcName == "" {
				continue
			}
			var price float64
			if p, ok := m["price"]; ok {
				price = toFloat(p)
			} else if p, ok := m["lowPrice"]; ok {
				price = toFloat(p)
			}
			durasi := 60
			if prop, ok := offered["additionalProperty"].(map[string]any); ok {
				if propName, _ := prop["name"].(string); propName == "Duration" {
					if val, ok := prop["value"].(string); ok {
						durasi = parseISODuration(val)
					}
				}
			}
			cat := categoryName
			if cat == "" {
				cat = parentCategory
			}
			salon.Services = append(salon.Services, ScrapedSvc{
				Nama: svcName, Harga: price, Durasi: durasi, CategoryHint: cat,
			})
		}
	}
}

func parseISODuration(s string) int {
	if idx := strings.Index(s, " - "); idx > 0 {
		s = s[:idx]
	}
	s = strings.TrimSpace(s)
	total := 0
	if m := regexp.MustCompile(`(\d+)H`).FindStringSubmatch(s); m != nil {
		v, _ := strconv.Atoi(m[1])
		total += v * 60
	}
	if m := regexp.MustCompile(`(\d+)M`).FindStringSubmatch(s); m != nil {
		v, _ := strconv.Atoi(m[1])
		total += v
	}
	if total == 0 {
		return 60
	}
	return total
}

// ─── Helpers ─────────────────────────────────────────────────────────────────

func toFloat(v any) float64 {
	switch val := v.(type) {
	case float64:
		return val
	case string:
		f, _ := strconv.ParseFloat(val, 64)
		return f
	case json.Number:
		f, _ := val.Float64()
		return f
	}
	return 0
}

func toInt(v any) int {
	switch val := v.(type) {
	case float64:
		return int(val)
	case string:
		i, _ := strconv.Atoi(val)
		return i
	case json.Number:
		i, _ := val.Int64()
		return int(i)
	}
	return 0
}

func loadJSON(path string, v any) {
	data, err := os.ReadFile(path)
	if err != nil {
		return
	}
	_ = json.Unmarshal(data, v)
}

func saveJSON(path string, v any) error {
	data, err := json.MarshalIndent(v, "", "  ")
	if err != nil {
		return err
	}
	return os.WriteFile(path, data, 0644)
}

func maxID(ids []int) int {
	m := 0
	for _, v := range ids {
		if v > m {
			m = v
		}
	}
	return m
}

func kotaSlugToName(slug string) string {
	if slug == "" {
		return ""
	}
	parts := strings.Split(slug, "-")
	for i, p := range parts {
		if p == "" {
			continue
		}
		parts[i] = strings.ToUpper(p[:1]) + p[1:]
	}
	return strings.Join(parts, " ")
}

// ─── URL parser (auto-detect kategori/sub/kota dari URL Treatwell) ──────────

var (
	urlSubKategoriRe  = regexp.MustCompile(`/places/treatment-([^/]+?)/`)
	urlKategoriRe     = regexp.MustCompile(`/places/treatment-group-([^/]+?)/`)
	urlKotaRe         = regexp.MustCompile(`/in-([^/]+?)-uk/?`)
)

// parseURL — extract id_kategori, id_sub_kategori, kota name dari URL.
// Return (0, 0, "") kalau gak ada pattern yg match — salon tetap di-scrape
// tapi tanpa pivot tag.
func parseURL(rawURL string) (idKategori, idSubKategori int, kotaName, label string) {
	url := strings.ToLower(rawURL)

	// Sub_kategori: /treatment-<slug>/ (tapi BUKAN /treatment-group-<slug>/)
	// Cek group dulu, kalau gak match baru sub.
	if m := urlKategoriRe.FindStringSubmatch(url); m != nil {
		slug := m[1]
		for _, k := range kategoriRegistry {
			if k.TreatwellSlug == slug {
				idKategori = k.IDKategori
				label = "KAT/" + k.Slug
				break
			}
		}
	} else if m := urlSubKategoriRe.FindStringSubmatch(url); m != nil {
		slug := m[1]
		for _, s := range subKategoriRegistry {
			if s.TreatwellSlug == slug {
				idKategori = s.IDKategori
				idSubKategori = s.IDSubKategori
				label = "SUB/" + s.Slug
				break
			}
		}
	}

	if m := urlKotaRe.FindStringSubmatch(url); m != nil {
		kotaName = kotaSlugToName(m[1])
		if label != "" {
			label = label + "/" + m[1]
		} else {
			label = "URL/" + m[1]
		}
	}
	if label == "" {
		label = "URL"
	}
	return
}

// ─── Listing collector ──────────────────────────────────────────────────────

func collectListings(baseURL string, maxPages int) []ListingEntry {
	var all []ListingEntry
	pageQueue := []string{baseURL}
	visited := map[string]bool{}
	seenSalonURLs := map[string]bool{}

	parts := strings.SplitN(baseURL, "//", 2)
	origin := ""
	if len(parts) == 2 {
		if idx := strings.Index(parts[1], "/"); idx > 0 {
			origin = parts[0] + "//" + parts[1][:idx]
		}
	}

	// Tracking: kalau N page berturut-turut return 0 entries, baru kita anggap
	// listing habis & stop. Kalau cuma 1 page transient error, lanjut ke page
	// lain di queue.
	emptyStreak := 0
	const maxEmptyStreak = 3
	var failedPages []string

	for len(pageQueue) > 0 && len(visited) < maxPages {
		pageURL := pageQueue[0]
		pageQueue = pageQueue[1:]
		if visited[pageURL] {
			continue
		}
		visited[pageURL] = true
		fmt.Printf("   📄 Page %d: %s\n", len(visited), pageURL)
		htmlStr, err := fetchHTML(pageURL)
		if err != nil {
			fmt.Printf("      ❌ %v — lanjut ke page berikutnya\n", err)
			failedPages = append(failedPages, pageURL)
			continue
		}
		result := scrapeListingPage(htmlStr, origin)
		if len(result.Entries) == 0 {
			emptyStreak++
			fmt.Printf("      ⚠️  0 entries (streak %d/%d)\n", emptyStreak, maxEmptyStreak)
			if emptyStreak >= maxEmptyStreak {
				fmt.Printf("      ⏹️  Stop: %d page kosong berturut-turut\n", emptyStreak)
				break
			}
			continue
		}
		emptyStreak = 0
		newCount := 0
		for _, entry := range result.Entries {
			if !seenSalonURLs[entry.URL] {
				seenSalonURLs[entry.URL] = true
				all = append(all, entry)
				newCount++
			}
		}
		fmt.Printf("      → %d salon (%d baru di page ini)\n", len(result.Entries), newCount)
		for _, nextURL := range result.NextPageURLs {
			if !visited[nextURL] {
				pageQueue = append(pageQueue, nextURL)
			}
		}
		time.Sleep(requestDelay + time.Duration(rand.Intn(400))*time.Millisecond)
	}

	// Retry pass utk listing page yg gagal (server transient errors)
	if len(failedPages) > 0 {
		fmt.Printf("   🔁 Retry %d listing page yg gagal...\n", len(failedPages))
		time.Sleep(5 * time.Second)
		for _, pageURL := range failedPages {
			htmlStr, err := fetchHTML(pageURL)
			if err != nil {
				fmt.Printf("      ❌ Tetap gagal: %s\n", pageURL)
				continue
			}
			result := scrapeListingPage(htmlStr, origin)
			newCount := 0
			for _, entry := range result.Entries {
				if !seenSalonURLs[entry.URL] {
					seenSalonURLs[entry.URL] = true
					all = append(all, entry)
					newCount++
				}
			}
			fmt.Printf("      ✅ Recovered: +%d salon baru dari %s\n", newCount, pageURL)
			time.Sleep(requestDelay)
		}
	}
	return all
}

// is4xxError — cek apakah error dari fetchHTML adalah client error (404, 410, dst).
// 4xx = permanent (page udah gak ada / dihapus), gak akan recover walau di-retry.
func is4xxError(err error) bool {
	if err == nil {
		return false
	}
	msg := err.Error()
	// fetchHTML format: "HTTP 404 for <url>" atau "HTTP 410 after N retries..."
	return strings.Contains(msg, "HTTP 4")
}

// scrapeAllDetails — scrape detail tiap listing. Return scraped salons,
// retriable failures (5xx/timeout/network), dan permanent failures (4xx).
// `workers` = jumlah concurrent worker.
func scrapeAllDetails(listings []ListingEntry, label string, workers int) (results []ScrapedSalon, retriable, permanent []ListingEntry) {
	results = make([]ScrapedSalon, 0, len(listings))
	var mu sync.Mutex
	var wg sync.WaitGroup
	sem := make(chan struct{}, workers)
	progress := 0
	for i, listing := range listings {
		wg.Add(1)
		sem <- struct{}{}
		go func(idx int, entry ListingEntry) {
			defer wg.Done()
			defer func() { <-sem }()
			time.Sleep(time.Duration(rand.Intn(300)) * time.Millisecond)
			salon, err := scrapeDetailPage(entry.URL)
			if err != nil {
				mu.Lock()
				if is4xxError(err) {
					permanent = append(permanent, entry)
					fmt.Printf("   [%s %d/%d] 🚫 %s: %v (permanent, skip retry)\n", label, idx+1, len(listings), entry.Name, err)
				} else {
					retriable = append(retriable, entry)
					fmt.Printf("   [%s %d/%d] ❌ %s: %v\n", label, idx+1, len(listings), entry.Name, err)
				}
				mu.Unlock()
				return
			}
			mu.Lock()
			results = append(results, *salon)
			progress++
			fmt.Printf("   [%s %d/%d] ✅ %s (%d services)\n", label, progress, len(listings), salon.NamaSalon, len(salon.Services))
			mu.Unlock()
		}(i, listing)
	}
	wg.Wait()
	return
}

// scrapeWithRetry — pass utama + retry pass utk URL yg gagal (5xx/network).
// 4xx (404, 410, dst) di-skip dari retry karena pasti tetap gagal.
func scrapeWithRetry(listings []ListingEntry, label string) []ScrapedSalon {
	results, retriable, permanent := scrapeAllDetails(listings, label, maxWorkers)
	if len(retriable) == 0 {
		if len(permanent) > 0 {
			fmt.Printf("\n   🚫 %d URL permanent error (4xx) — skip retry:\n", len(permanent))
			for _, f := range permanent {
				fmt.Printf("      - %s\n", f.URL)
			}
		}
		return results
	}
	fmt.Printf("\n   🔁 Retry pass: %d URL retriable error — jalan ulang dengan %d worker (lebih pelan)\n", len(retriable), retryWorkers)
	time.Sleep(5 * time.Second) // kasih server breathing room
	retried, stillRetriable, retryPermanent := scrapeAllDetails(retriable, label+"/retry", retryWorkers)
	results = append(results, retried...)

	// Gabung permanent failures dari pass 1 + pass 2 (kalau ada yg berubah jadi 4xx)
	allPermanent := append(permanent, retryPermanent...)
	if len(stillRetriable) > 0 {
		fmt.Printf("   ⚠️  %d URL TETAP gagal setelah retry (5xx/network) — di-skip:\n", len(stillRetriable))
		for _, f := range stillRetriable {
			fmt.Printf("      - %s\n", f.URL)
		}
	}
	if len(allPermanent) > 0 {
		fmt.Printf("   🚫 %d URL permanent error (4xx) — di-skip:\n", len(allPermanent))
		for _, f := range allPermanent {
			fmt.Printf("      - %s\n", f.URL)
		}
	}
	return results
}

// ─── Accumulator ────────────────────────────────────────────────────────────

type mergeAcc struct {
	exKota         []Kota
	salons         []Salon
	services       []Service
	staff          []Staff
	images         []SalonImage
	salonKategori  []SalonKategori
	salonSubKat    []SalonSubKategori
	users          []User
	cityIDMap      map[string]int
	salonURLToID   map[int]int   // index source_url hash → unused, use salonByURL instead
	salonByURL     map[string]int // source_url → id_salon
	pivotKatSeen   map[string]bool // "id_salon:id_kategori" → exists
	pivotSubSeen   map[string]bool // "id_salon:id_sub_kategori" → exists
	emailSeen      map[string]bool // email user yg sudah dipakai (dedup)
	nextKotaID, nextSalonID, nextServiceID, nextStaffID, nextImageID, nextUserID int
}

func loadAcc(dataDir string) *mergeAcc {
	a := &mergeAcc{
		cityIDMap:    map[string]int{},
		salonByURL:   map[string]int{},
		pivotKatSeen: map[string]bool{},
		pivotSubSeen: map[string]bool{},
		emailSeen:    map[string]bool{},
	}
	loadJSON(filepath.Join(dataDir, "kota.json"), &a.exKota)
	loadJSON(filepath.Join(dataDir, "salon.json"), &a.salons)
	loadJSON(filepath.Join(dataDir, "service.json"), &a.services)
	loadJSON(filepath.Join(dataDir, "staff.json"), &a.staff)
	loadJSON(filepath.Join(dataDir, "salon_images.json"), &a.images)
	loadJSON(filepath.Join(dataDir, "salon_kategori.json"), &a.salonKategori)
	loadJSON(filepath.Join(dataDir, "salon_sub_kategori.json"), &a.salonSubKat)
	loadJSON(filepath.Join(dataDir, "users.json"), &a.users)

	for _, k := range a.exKota {
		a.cityIDMap[k.NamaKota] = k.IDKota
	}
	for _, s := range a.salons {
		a.salonByURL[strings.Split(s.SourceURL, "?")[0]] = s.IDSalon
	}
	for _, p := range a.salonKategori {
		a.pivotKatSeen[fmt.Sprintf("%d:%d", p.IDSalon, p.IDKategori)] = true
	}
	for _, p := range a.salonSubKat {
		a.pivotSubSeen[fmt.Sprintf("%d:%d", p.IDSalon, p.IDSubKategori)] = true
	}
	for _, u := range a.users {
		a.emailSeen[u.Email] = true
	}

	// Bootstrap admin + customer kalau users.json kosong (id 1 & 2 reserved).
	if len(a.users) == 0 {
		a.users = append(a.users, User{
			IDUser: 1, FirstName: "Admin", LastName: "Viygo",
			Email: "admin@viygo.com", Password: "password",
			Role: "admin", IsActive: true,
		})
		a.users = append(a.users, User{
			IDUser: 2, FirstName: "Test", LastName: "Customer",
			Email: "customer@viygo.com", Password: "password",
			Role: "customer", IsActive: true,
		})
		a.emailSeen["admin@viygo.com"] = true
		a.emailSeen["customer@viygo.com"] = true
	}

	var kotaIDs, salonIDs, serviceIDs, staffIDs, imageIDs, userIDs []int
	for _, v := range a.exKota {
		kotaIDs = append(kotaIDs, v.IDKota)
	}
	for _, v := range a.salons {
		salonIDs = append(salonIDs, v.IDSalon)
	}
	for _, v := range a.services {
		serviceIDs = append(serviceIDs, v.IDService)
	}
	for _, v := range a.staff {
		staffIDs = append(staffIDs, v.IDStaff)
	}
	for _, v := range a.images {
		imageIDs = append(imageIDs, v.IDSalonImage)
	}
	for _, v := range a.users {
		userIDs = append(userIDs, v.IDUser)
	}
	a.nextKotaID = maxID(kotaIDs) + 1
	a.nextSalonID = maxID(salonIDs) + 1
	a.nextServiceID = maxID(serviceIDs) + 1
	a.nextStaffID = maxID(staffIDs) + 1
	a.nextImageID = maxID(imageIDs) + 1
	a.nextUserID = maxID(userIDs) + 1
	return a
}

func (a *mergeAcc) save(dataDir string) {
	files := []struct {
		name string
		data any
	}{
		{"kota.json", a.exKota},
		{"users.json", a.users},
		{"salon.json", a.salons},
		{"service.json", a.services},
		{"staff.json", a.staff},
		{"salon_images.json", a.images},
		{"salon_kategori.json", a.salonKategori},
		{"salon_sub_kategori.json", a.salonSubKat},
	}
	for _, f := range files {
		if err := saveJSON(filepath.Join(dataDir, f.name), f.data); err != nil {
			fmt.Printf("   ❌ Save %s: %v\n", f.name, err)
			os.Exit(1)
		}
	}
	fmt.Printf("   💾 saved %d JSON files\n", len(files))
}

// merge — masukkan hasil scrape ke accumulator.
//
// Setiap salon (baru atau existing) di-tag ke pivot:
//   - salon_kategori   : (id_salon, primaryKategori)
//   - salon_sub_kategori: (id_salon, primarySubKategori) — kalau primarySubKategori > 0
//
// Service di-insert HANYA kalau salon baru (dedup by source_url). Service
// existing dari run sebelumnya tidak di-touch.
func merge(scraped []ScrapedSalon, primaryKategori, primarySubKategori int, defaultKotaName string, a *mergeAcc) (newSalons, taggedKat, taggedSub int) {
	for _, sc := range scraped {
		baseURL := strings.Split(sc.SourceURL, "?")[0]
		var salonID int
		isNew := false
		if existingID, ok := a.salonByURL[baseURL]; ok {
			// Salon sudah ada — re-use ID, hanya update pivot kategori/sub
			salonID = existingID
		} else {
			// Salon baru — insert
			isNew = true
			salonID = a.nextSalonID
			a.nextSalonID++
			a.salonByURL[baseURL] = salonID

			cityName := sc.Kota
			if cityName == "" {
				cityName = defaultKotaName
			}
			if cityName == "" {
				cityName = "Unknown"
			}
			if _, ok := a.cityIDMap[cityName]; !ok {
				a.cityIDMap[cityName] = a.nextKotaID
				a.exKota = append(a.exKota, Kota{IDKota: a.nextKotaID, NamaKota: cityName, Provinsi: sc.Provinsi})
				a.nextKotaID++
			}
			idKota := a.cityIDMap[cityName]

			// Generate 1 owner user per salon baru
			ownerID := a.nextUserID
			a.nextUserID++
			ownerEmail := fmt.Sprintf("owner_%d@viygo.com", salonID)
			// Pastikan email unique (defensif — biasanya sudah unique by salonID)
			for a.emailSeen[ownerEmail] {
				ownerID = a.nextUserID
				a.nextUserID++
				ownerEmail = fmt.Sprintf("owner_%d_%d@viygo.com", salonID, ownerID)
			}
			a.emailSeen[ownerEmail] = true
			ownerLastName := sc.NamaSalon
			if len(ownerLastName) > 100 {
				ownerLastName = ownerLastName[:100]
			}
			salonIDPtr := salonID
			a.users = append(a.users, User{
				IDUser:    ownerID,
				FirstName: "Owner",
				LastName:  ownerLastName,
				Email:     ownerEmail,
				Password:  "password", // PLAIN-TEXT — di-hash oleh UserSeeder.php saat seed
				Role:      "salon_owner",
				IsActive:  true,
				IDSalon:   &salonIDPtr,
			})

			desc := sc.Deskripsi
			if desc == "" {
				desc = sc.NamaSalon + " is a professional beauty salon."
			}
			var phone any
			if sc.PhoneNumber != "" {
				phone = sc.PhoneNumber
			}
			var imageURL any
			if len(sc.ImageURLs) > 0 {
				imageURL = sc.ImageURLs[0]
			}
			a.salons = append(a.salons, Salon{
				IDSalon: salonID, IDUser: ownerID, IDKota: idKota,
				NamaSalon: sc.NamaSalon, Alamat: sc.Alamat, Deskripsi: desc,
				PhoneNumber: phone, OpeningTime: sc.OpeningTime, ClosingTime: sc.ClosingTime,
				ImageURL: imageURL, MapsURL: nil, Latitude: sc.Latitude, Longitude: sc.Longitude,
				Rating: sc.Rating, TotalReview: sc.TotalReview, Status: "active", SourceURL: baseURL,
			})
			newSalons++

			// Service & staff & images hanya utk salon baru
			for _, svc := range sc.Services {
				var idKatPtr, idSubPtr *int
				if primaryKategori > 0 {
					v := primaryKategori
					idKatPtr = &v
				}
				subID := 0
				if primarySubKategori > 0 {
					subID = primarySubKategori
				} else if primaryKategori > 0 {
					subID = matchSubKategori(svc.Nama, svc.CategoryHint, primaryKategori)
				}
				if subID > 0 {
					idSubPtr = &subID
				}
				a.services = append(a.services, Service{
					IDService: a.nextServiceID, IDSalon: salonID,
					IDKategori: idKatPtr, IDSubKategori: idSubPtr,
					Nama: svc.Nama, Deskripsi: nil,
					Durasi: svc.Durasi, Harga: svc.Harga, Status: "active",
				})
				a.nextServiceID++
			}
			for _, name := range sc.StaffNames {
				a.staff = append(a.staff, Staff{
					IDStaff: a.nextStaffID, IDSalon: salonID, Name: name, ProfileURL: nil, Status: "active",
				})
				a.nextStaffID++
			}
			for i, imgURL := range sc.ImageURLs {
				a.images = append(a.images, SalonImage{
					IDSalonImage: a.nextImageID, IDSalon: salonID, ImageURL: imgURL,
					IsPrimary: i == 0, Urutan: i + 1,
				})
				a.nextImageID++
			}
		}

		// Pivot — selalu di-tag (salon baru maupun existing) sesuai context URL
		if primaryKategori > 0 {
			key := fmt.Sprintf("%d:%d", salonID, primaryKategori)
			if !a.pivotKatSeen[key] {
				a.pivotKatSeen[key] = true
				a.salonKategori = append(a.salonKategori, SalonKategori{IDSalon: salonID, IDKategori: primaryKategori})
				taggedKat++
			}
		}
		if primarySubKategori > 0 {
			key := fmt.Sprintf("%d:%d", salonID, primarySubKategori)
			if !a.pivotSubSeen[key] {
				a.pivotSubSeen[key] = true
				a.salonSubKat = append(a.salonSubKat, SalonSubKategori{IDSalon: salonID, IDSubKategori: primarySubKategori})
				taggedSub++
			}
		}

		_ = isNew
	}
	return
}

// ─── Job runner ─────────────────────────────────────────────────────────────

func runJob(label, baseURL string, primaryKategori, primarySub int, defaultKota string, maxPages int, acc *mergeAcc) {
	fmt.Printf("\n┌─ %s ──────────────────────────────────────────────────────\n", label)
	fmt.Printf("│  URL: %s\n", baseURL)
	fmt.Printf("│  Tag: id_kategori=%d, id_sub_kategori=%d\n", primaryKategori, primarySub)
	fmt.Println("└──────────────────────────────────────────────────────────────")

	listings := collectListings(baseURL, maxPages)
	if len(listings) == 0 {
		fmt.Println("   ⚠️  Tidak ada listing — skip job ini.")
		return
	}
	results := scrapeWithRetry(listings, label)
	newSalons, taggedKat, taggedSub := merge(results, primaryKategori, primarySub, defaultKota, acc)
	fmt.Printf("\n   📊 +%d salon baru, +%d pivot salon_kategori, +%d pivot salon_sub_kategori\n", newSalons, taggedKat, taggedSub)
	acc.save(filepath.Dir(filepath.Dir(filepath.Dir(baseURL + "/data/dummy"))))
}

// ─── Main ────────────────────────────────────────────────────────────────────

func main() {
	kotaFlag := flag.String("kota", "", "Comma-separated kota slug (default: london,manchester,birmingham)")
	maxPagesFlag := flag.Int("max-pages", defaultMaxPages, "Halaman listing maksimum per URL (default 5)")
	skipKategoriFlag := flag.Bool("skip-kategori", false, "Skip 7 kategori utama (treatment-group-X)")
	skipSubFlag := flag.Bool("skip-sub", false, "Skip 42 sub_kategori (treatment-X)")
	onlyKategoriFlag := flag.String("only-kategori", "", "Hanya scrape 1 kategori utama (slug VIYGO, mis. 'hair')")
	onlySubFlag := flag.String("only-sub", "", "Hanya scrape 1 sub_kategori (slug VIYGO, mis. 'blow-dry')")
	flag.Parse()

	// Resolve kota list
	var kotaList []string
	if *kotaFlag == "" {
		kotaList = defaultKotaList
	} else {
		for _, k := range strings.Split(*kotaFlag, ",") {
			kotaList = append(kotaList, strings.TrimSpace(strings.ToLower(k)))
		}
	}

	// Resolve dataDir (relatif terhadap working dir)
	cwd, _ := os.Getwd()
	var dataDir string
	switch filepath.Base(cwd) {
	case "scripts":
		dataDir = filepath.Join(filepath.Dir(cwd), "data")
	case "database":
		dataDir = filepath.Join(cwd, "data")
	default:
		dataDir = filepath.Join(cwd, "database", "data")
	}

	// Build job list
	type job struct {
		label             string
		baseURL           string
		primaryKategori   int
		primarySubKategori int
		defaultKota       string
	}
	var jobs []job

	// ─── Mode URL ───
	// Kalau ada positional argument, jalankan URL mode (abaikan registry build).
	urlArgs := flag.Args()
	if len(urlArgs) > 0 {
		for _, rawURL := range urlArgs {
			rawURL = strings.TrimSpace(rawURL)
			if rawURL == "" {
				continue
			}
			if !strings.HasPrefix(rawURL, "http") {
				fmt.Printf("⚠️  Skip URL invalid (harus mulai http): %s\n", rawURL)
				continue
			}
			idKat, idSub, kotaName, label := parseURL(rawURL)
			if idKat == 0 && idSub == 0 {
				fmt.Printf("ℹ️  URL %s — tidak match pattern kategori/sub registry. Salon akan di-scrape tanpa pivot tag.\n", rawURL)
			}
			jobs = append(jobs, job{
				label:              label,
				baseURL:            rawURL,
				primaryKategori:    idKat,
				primarySubKategori: idSub,
				defaultKota:        kotaName,
			})
		}
	} else {
		// ─── Mode auto (registry-driven) ───
		// Kategori utama (7)
		if !*skipKategoriFlag {
			for _, k := range kategoriRegistry {
				if *onlyKategoriFlag != "" && k.Slug != *onlyKategoriFlag {
					continue
				}
				for _, kota := range kotaList {
					url := fmt.Sprintf("https://www.treatwell.co.uk/places/treatment-group-%s/in-%s-uk/",
						k.TreatwellSlug, kota)
					jobs = append(jobs, job{
						label:           fmt.Sprintf("KAT/%s/%s", k.Slug, kota),
						baseURL:         url,
						primaryKategori: k.IDKategori,
						defaultKota:     kotaSlugToName(kota),
					})
				}
			}
		}

		// Sub_kategori (42)
		if !*skipSubFlag {
			for _, s := range subKategoriRegistry {
				if *onlySubFlag != "" && s.Slug != *onlySubFlag {
					continue
				}
				for _, kota := range kotaList {
					url := fmt.Sprintf("https://www.treatwell.co.uk/places/treatment-%s/in-%s-uk/",
						s.TreatwellSlug, kota)
					jobs = append(jobs, job{
						label:              fmt.Sprintf("SUB/%s/%s", s.Slug, kota),
						baseURL:            url,
						primaryKategori:    s.IDKategori,
						primarySubKategori: s.IDSubKategori,
						defaultKota:        kotaSlugToName(kota),
					})
				}
			}
		}
	}

	if len(jobs) == 0 {
		fmt.Println("❌ Tidak ada job. Pass URL sbg positional arg, atau cek flag --skip-* / --only-*.")
		fmt.Println("   Contoh: scraper.exe \"https://www.treatwell.co.uk/places/treatment-blow-dry/in-london-uk/\"")
		os.Exit(1)
	}

	mode := "auto (registry-driven)"
	if len(urlArgs) > 0 {
		mode = "URL (manual)"
	}

	startTime := time.Now()
	fmt.Println("╔══════════════════════════════════════════════════════════════╗")
	fmt.Println("║   🚀 VIYGO Treatwell Scraper Part 5                          ║")
	fmt.Println("╚══════════════════════════════════════════════════════════════╝")
	fmt.Printf("⚙️  Mode       : %s\n", mode)
	fmt.Printf("📁 Data dir   : %s\n", dataDir)
	if len(urlArgs) == 0 {
		fmt.Printf("📍 Kota       : %s (%d kota)\n", strings.Join(kotaList, ", "), len(kotaList))
	}
	fmt.Printf("📄 Max pages  : %d per URL\n", *maxPagesFlag)
	fmt.Printf("🎯 Total jobs : %d\n\n", len(jobs))

	acc := loadAcc(dataDir)

	// Snapshot "before" — utk hitung delta di banner akhir
	beforeSalon := len(acc.salons)
	beforeUser := len(acc.users)
	beforeService := len(acc.services)
	beforeStaff := len(acc.staff)
	beforeImage := len(acc.images)
	beforeKota := len(acc.exKota)
	beforePivotKat := len(acc.salonKategori)
	beforePivotSub := len(acc.salonSubKat)

	fmt.Printf("📊 Sebelum scrape — salon: %d, service: %d, user: %d (loaded dari JSON)\n\n",
		beforeSalon, beforeService, beforeUser)

	for i, j := range jobs {
		fmt.Printf("\n══════ Job %d/%d ══════\n", i+1, len(jobs))
		listings := collectListings(j.baseURL, *maxPagesFlag)
		if len(listings) == 0 {
			fmt.Printf("   ⚠️  Tidak ada listing utk %s — skip\n", j.label)
			continue
		}
		fmt.Printf("\n📋 %d listing → detail scraping\n", len(listings))
		results := scrapeWithRetry(listings, j.label)
		newSalons, taggedKat, taggedSub := merge(results, j.primaryKategori, j.primarySubKategori, j.defaultKota, acc)
		fmt.Printf("\n📊 Job %s: +%d salon baru, +%d pivot salon_kategori, +%d pivot salon_sub_kategori\n",
			j.label, newSalons, taggedKat, taggedSub)
		acc.save(dataDir)
	}

	elapsed := time.Since(startTime)

	// Hitung delta = berapa yg bertambah dari run ini
	dSalon := len(acc.salons) - beforeSalon
	dUser := len(acc.users) - beforeUser
	dService := len(acc.services) - beforeService
	dStaff := len(acc.staff) - beforeStaff
	dImage := len(acc.images) - beforeImage
	dKota := len(acc.exKota) - beforeKota
	dPivotKat := len(acc.salonKategori) - beforePivotKat
	dPivotSub := len(acc.salonSubKat) - beforePivotSub

	fmt.Printf("\n╔════════════════════════════════════════════════════════════════════╗\n")
	fmt.Printf("║                       🎉 SCRAPING SELESAI                          ║\n")
	fmt.Printf("╠════════════════════════════════════════════════════════════════════╣\n")
	fmt.Printf("║  ⏱️  Total durasi      : %-40s   ║\n", elapsed.Round(time.Millisecond))
	fmt.Printf("╠══════════════════════╤═══════════════╤═════════════╤═══════════════╣\n")
	fmt.Printf("║  Kategori            │  Sebelum      │  +Baru      │  Total        ║\n")
	fmt.Printf("╠══════════════════════╪═══════════════╪═════════════╪═══════════════╣\n")
	fmt.Printf("║  🏪 Salon            │  %-12d │  +%-10d│  %-12d ║\n", beforeSalon, dSalon, len(acc.salons))
	fmt.Printf("║  👤 User (owner+)    │  %-12d │  +%-10d│  %-12d ║\n", beforeUser, dUser, len(acc.users))
	fmt.Printf("║  💇 Service          │  %-12d │  +%-10d│  %-12d ║\n", beforeService, dService, len(acc.services))
	fmt.Printf("║  👥 Staff            │  %-12d │  +%-10d│  %-12d ║\n", beforeStaff, dStaff, len(acc.staff))
	fmt.Printf("║  🖼️  Images           │  %-12d │  +%-10d│  %-12d ║\n", beforeImage, dImage, len(acc.images))
	fmt.Printf("║  🏙️  Kota             │  %-12d │  +%-10d│  %-12d ║\n", beforeKota, dKota, len(acc.exKota))
	fmt.Printf("║  🔗 Pivot salon-kat  │  %-12d │  +%-10d│  %-12d ║\n", beforePivotKat, dPivotKat, len(acc.salonKategori))
	fmt.Printf("║  🔗 Pivot salon-sub  │  %-12d │  +%-10d│  %-12d ║\n", beforePivotSub, dPivotSub, len(acc.salonSubKat))
	fmt.Printf("╚══════════════════════╧═══════════════╧═════════════╧═══════════════╝\n")

	// Insight tambahan: berapa salon di-tag ulang ke kategori/sub baru (existing salon)
	totalListingsProcessed := 0
	for _, j := range jobs {
		_ = j
		totalListingsProcessed++
	}
	if dSalon > 0 {
		fmt.Printf("\n💡 Insight:\n")
		fmt.Printf("   - Run ini nemu %d salon baru yg sebelumnya belum ada di JSON\n", dSalon)
		fmt.Printf("   - Setiap salon baru otomatis dapet 1 owner user (+%d user)\n", dUser)
		fmt.Printf("   - Salon yg URL-nya sudah ada di JSON di-skip insert, tapi pivot kategori/sub tetap di-update\n")
	} else if dPivotKat > 0 || dPivotSub > 0 {
		fmt.Printf("\n💡 Insight: tidak ada salon baru, tapi +%d pivot kategori & +%d pivot sub di-tag ulang ke salon existing\n", dPivotKat, dPivotSub)
	}

	fmt.Println("\n👉 Langkah berikut:")
	fmt.Println("   php artisan db:seed   (truncate + reseed dari JSON)")
}
