package main

import (
	"encoding/json"
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

// ─── Configuration ───────────────────────────────────────────────────────────

const (
	maxPages       = 100          // Maximum listing pages to scrape
	maxWorkers     = 20           // Concurrent detail-page scrapers
	requestDelay   = 500 * time.Millisecond // Delay between listing page fetches
	maxRetries     = 3
	requestTimeout = 15 * time.Second
	userAgent      = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36"
)

// ─── Domain structs (matching existing JSON schema) ──────────────────────────

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

// ─── Scraped data (intermediate) ─────────────────────────────────────────────

type ScrapedSalon struct {
	NamaSalon   string          `json:"nama_salon"`
	Alamat      string          `json:"alamat"`
	Kota        string          `json:"kota"`
	Provinsi    string          `json:"provinsi"`
	KodePos     string          `json:"kode_pos"`
	Rating      float64         `json:"rating"`
	TotalReview int             `json:"total_review"`
	OpeningTime string          `json:"opening_time"`
	ClosingTime string          `json:"closing_time"`
	Deskripsi   string          `json:"deskripsi"`
	PhoneNumber string          `json:"phone_number"`
	Latitude    *float64        `json:"latitude"`
	Longitude   *float64        `json:"longitude"`
	ImageURLs   []string        `json:"image_urls"`
	Services    []ScrapedSvc    `json:"services"`
	StaffNames  []string        `json:"staff"`
	SourceURL   string          `json:"source_url"`
}

type ScrapedSvc struct {
	Nama     string  `json:"nama"`
	Harga    float64 `json:"harga"`
	Durasi   int     `json:"durasi"`
	Kategori string  `json:"kategori"`
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
			fmt.Printf("   ⚠️  Fetch error (attempt %d): %v\n", attempt+1, err)
			time.Sleep(3*time.Second + time.Duration(rand.Intn(2000))*time.Millisecond)
			continue
		}

		bodyBytes, err := io.ReadAll(resp.Body)
		resp.Body.Close()

		if resp.StatusCode == 429 {
			fmt.Printf("   ⏳ Rate limited (429), waiting 10s...\n")
			time.Sleep(10 * time.Second)
			continue
		}
		if resp.StatusCode >= 400 {
			return "", fmt.Errorf("HTTP %d for %s", resp.StatusCode, url)
		}
		if err != nil {
			continue
		}
		return string(bodyBytes), nil
	}
	return "", fmt.Errorf("max retries reached for %s", url)
}

// ─── HTML helpers ────────────────────────────────────────────────────────────

// findNodes traverses the HTML tree and returns nodes matching a predicate.
func findNodes(n *html.Node, match func(*html.Node) bool) []*html.Node {
	var results []*html.Node
	var walk func(*html.Node)
	walk = func(node *html.Node) {
		if match(node) {
			results = append(results, node)
		}
		for c := node.FirstChild; c != nil; c = c.NextSibling {
			walk(c)
		}
	}
	walk(n)
	return results
}

func getAttr(n *html.Node, key string) string {
	for _, a := range n.Attr {
		if a.Key == key {
			return a.Val
		}
	}
	return ""
}

func hasClass(n *html.Node, substr string) bool {
	cls := getAttr(n, "class")
	return strings.Contains(cls, substr)
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
		// Try single object
		var obj map[string]any
		if json.Unmarshal([]byte(text), &obj) == nil {
			results = append(results, obj)
			continue
		}
		// Try array
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
		// Check @graph
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

// placeURLRe matches Treatwell salon detail page URLs
var placeURLRe = regexp.MustCompile(`https?://www\.treatwell\.co\.uk/place/([^/?#"'\s]+)`)

// paginationRe matches Treatwell pagination links like /places/.../page-N/
var paginationRe = regexp.MustCompile(`https?://www\.treatwell\.co\.uk/places/[^"'\s]*page-(\d+)[^"'\s]*`)

// pageNumRe extracts page number from URL
var pageNumRe = regexp.MustCompile(`page-(\d+)`)

func extractPageNum(url string) int {
	if m := pageNumRe.FindStringSubmatch(url); m != nil {
		n, _ := strconv.Atoi(m[1])
		return n
	}
	return 0
}

type ListingResult struct {
	Entries       []ListingEntry
	NextPageURLs  []string // Actual pagination URLs found on the page
}

func scrapeListingPage(htmlStr string, baseOrigin string) ListingResult {
	result := ListingResult{}

	doc, err := html.Parse(strings.NewReader(htmlStr))
	if err != nil {
		return result
	}

	// Try JSON-LD first (most reliable)
	jsonlds := extractJSONLD(doc)
	entries := findItemList(jsonlds)
	if len(entries) > 0 {
		for i := range entries {
			if !strings.HasPrefix(entries[i].URL, "http") {
				entries[i].URL = baseOrigin + entries[i].URL
			}
		}
		result.Entries = entries
		return result
	}

	// Fallback 1: DOM-based venue cards
	venueCards := findNodes(doc, func(n *html.Node) bool {
		if n.Type != html.ElementNode {
			return false
		}
		testID := getAttr(n, "data-testid")
		if testID == "search-result-venue" {
			return true
		}
		cls := getAttr(n, "class")
		return strings.Contains(cls, "venue-card") || strings.Contains(cls, "search-result")
	})

	for _, card := range venueCards {
		links := findNodes(card, func(n *html.Node) bool {
			return n.Type == html.ElementNode && n.Data == "a" && strings.Contains(getAttr(n, "href"), "/place/")
		})
		if len(links) == 0 {
			continue
		}
		href := getAttr(links[0], "href")
		if !strings.HasPrefix(href, "http") {
			href = baseOrigin + href
		}

		var name string
		nameNodes := findNodes(card, func(n *html.Node) bool {
			return n.Type == html.ElementNode && (n.Data == "h2" || n.Data == "h3" || hasClass(n, "name"))
		})
		if len(nameNodes) > 0 {
			name = getTextContent(nameNodes[0])
		}

		if href != "" {
			entries = append(entries, ListingEntry{Name: name, URL: href})
		}
	}

	if len(entries) > 0 {
		result.Entries = entries
		return result
	}

	// Fallback 2: Extract ALL /place/ links from the entire page
	// This works because Treatwell renders salon cards as plain <a> tags
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

		// Match /place/ URLs
		matches := placeURLRe.FindStringSubmatch(href)
		if matches == nil {
			continue
		}

		// Strip query params for dedup key
		slug := matches[1]
		cleanURL := fmt.Sprintf("https://www.treatwell.co.uk/place/%s/", strings.TrimSuffix(slug, "/"))

		if seen[cleanURL] {
			continue
		}
		seen[cleanURL] = true

		// Try to get link text as name
		name := getTextContent(link)
		// Clean up "Go to venue" type texts
		if name == "Go to venue" || name == "" || len(name) > 200 {
			// Try to extract name from slug
			name = strings.ReplaceAll(slug, "-", " ")
			name = strings.Title(strings.TrimSuffix(name, "/"))
		}

		entries = append(entries, ListingEntry{Name: name, URL: cleanURL})
	}

	result.Entries = entries

	// Also extract pagination URLs
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
			// Clean URL
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

	// Sort pagination URLs by page number (ascending) to avoid zigzag
	sort.Slice(result.NextPageURLs, func(i, j int) bool {
		numI := extractPageNum(result.NextPageURLs[i])
		numJ := extractPageNum(result.NextPageURLs[j])
		return numI < numJ
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

	// === JSON-LD structured data ===
	jsonlds := extractJSONLD(doc)
	biz := findStructuredBusiness(jsonlds)

	if biz != nil {
		salon.NamaSalon, _ = biz["name"].(string)
		salon.Deskripsi, _ = biz["description"].(string)
		salon.PhoneNumber, _ = biz["telephone"].(string)

		// Address
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

		// Rating
		if agg, ok := biz["aggregateRating"].(map[string]any); ok {
			if rv, ok := agg["ratingValue"]; ok {
				salon.Rating = toFloat(rv)
			}
			if rc, ok := agg["reviewCount"]; ok {
				salon.TotalReview = toInt(rc)
			}
		}

		// Geo
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

		// Opening hours
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

		// Images
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
	}

	// Fallback: DOM h1 for name
	if salon.NamaSalon == "" {
		h1s := findNodes(doc, func(n *html.Node) bool {
			return n.Type == html.ElementNode && n.Data == "h1"
		})
		if len(h1s) > 0 {
			salon.NamaSalon = getTextContent(h1s[0])
		}
	}

	// === JSON-LD service extraction from hasOfferCatalog ===
	if biz != nil {
		if catalog, ok := biz["hasOfferCatalog"].(map[string]any); ok {
			extractServicesFromCatalog(catalog, "", salon)
		}
	}

	// === Staff extraction from JSON-LD reviews (employeeDescription) ===
	if biz != nil {
		if reviews, ok := biz["review"].([]any); ok {
			staffSeen := make(map[string]bool)
			staffRe := regexp.MustCompile(`(?i)(?:treatment by|service by|styled by|treated by)\s+(.+)`)
			for _, r := range reviews {
				if rm, ok := r.(map[string]any); ok {
					// Check reviewBody for staff mentions
					if body, ok := rm["reviewBody"].(string); ok {
						_ = body // not used directly for staff
					}
				}
			}
			// Also look in raw HTML for employeeDescription patterns
			empDescRe := regexp.MustCompile(`"employeeDescription"\s*:\s*"([^"]+)"`)
			empMatches := empDescRe.FindAllStringSubmatch(htmlStr, -1)
			for _, m := range empMatches {
				desc := m[1]
				if staffMatch := staffRe.FindStringSubmatch(desc); staffMatch != nil {
					name := strings.TrimSpace(staffMatch[1])
					if name != "" && !staffSeen[name] && len(name) < 60 {
						staffSeen[name] = true
						salon.StaffNames = append(salon.StaffNames, name)
					}
				}
			}
		}
	}

	// Images already extracted from JSON-LD biz["image"] above
	// No need for DOM gallery extraction since JSON-LD has full image list

	return salon, nil
}

// extractServicesFromCatalog recursively extracts services from JSON-LD OfferCatalog
func extractServicesFromCatalog(catalog map[string]any, parentCategory string, salon *ScrapedSalon) {
	// The catalog has a "name" which is the category name
	categoryName, _ := catalog["name"].(string)
	if categoryName == "Available Services" {
		categoryName = "" // top-level, not a real category
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

		// If this is a nested OfferCatalog (category grouping), recurse
		if itemType == "OfferCatalog" {
			extractServicesFromCatalog(m, categoryName, salon)
			continue
		}

		// This is an Offer or AggregateOffer — extract service
		if itemType == "Offer" || itemType == "AggregateOffer" {
			offered, ok := m["itemOffered"].(map[string]any)
			if !ok {
				continue
			}

			svcName, _ := offered["name"].(string)
			if svcName == "" {
				continue
			}

			// Parse price (use "price" for Offer, "lowPrice" for AggregateOffer)
			var price float64
			if p, ok := m["price"]; ok {
				price = toFloat(p)
			} else if p, ok := m["lowPrice"]; ok {
				price = toFloat(p)
			}

			// Parse duration from additionalProperty
			var durasi int = 60 // default
			if prop, ok := offered["additionalProperty"].(map[string]any); ok {
				if propName, _ := prop["name"].(string); propName == "Duration" {
					if val, ok := prop["value"].(string); ok {
						durasi = parseISODuration(val)
					}
				}
			}

			// Determine category: use parent catalog name, fallback to guessCategory
			cat := categoryName
			if cat == "" {
				cat = parentCategory
			}
			if cat == "" {
				cat = guessCategory(svcName)
			}

			salon.Services = append(salon.Services, ScrapedSvc{
				Nama:     svcName,
				Harga:    price,
				Durasi:   durasi,
				Kategori: cat,
			})
		}
	}
}

// parseISODuration parses ISO 8601 duration like "PT30M", "PT1H", "PT1H30M", "PT15M - PT20M"
func parseISODuration(s string) int {
	// Handle range format "PT15M - PT20M" → take first value
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

var (
	priceRe    = regexp.MustCompile(`[\d]+\.?\d*`)
	hourRe     = regexp.MustCompile(`(?i)(\d+)\s*(?:hr|hrs|hour|hours)`)
	minRe      = regexp.MustCompile(`(?i)(\d+)\s*(?:min|mins|minutes)`)
	slugRe     = regexp.MustCompile(`[^a-z0-9]+`)
)

func parsePrice(s string) float64 {
	s = strings.NewReplacer("£", "", "€", "", "$", "", " ", "").Replace(s)
	m := priceRe.FindString(s)
	if m == "" {
		return 0
	}
	v, _ := strconv.ParseFloat(m, 64)
	return v
}

func parseDuration(text string) int {
	if text == "" {
		return 60
	}
	total := 0
	if m := hourRe.FindStringSubmatch(text); m != nil {
		v, _ := strconv.Atoi(m[1])
		total += v * 60
	}
	if m := minRe.FindStringSubmatch(text); m != nil {
		v, _ := strconv.Atoi(m[1])
		total += v
	}
	if total == 0 {
		return 60
	}
	return total
}

func toSlug(s string) string {
	return strings.Trim(slugRe.ReplaceAllString(strings.ToLower(s), "-"), "-")
}

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

var categoryMap = []struct {
	cat      string
	keywords []string
}{
	{"Hair", []string{"hair", "cut", "blow", "colour", "color", "highlight", "balayage", "perm", "braid",
		"extension", "keratin", "trim", "fade", "barber", "scalp", "toner", "ombre", "cornrow", "shave"}},
	{"Face", []string{"facial", "face", "derma", "peel", "hydrat", "anti-aging", "anti-ageing",
		"hydrafacial", "skin", "complexion", "rejuvenat", "microdermabrasion", "gua sha"}},
	{"Nails", []string{"nail", "manicure", "pedicure", "gel", "acrylic", "shellac", "polish", "dipping"}},
	{"Massage", []string{"massage", "spa", "reflexology", "aromatherapy", "stone", "swedish",
		"deep tissue", "thai", "cupping", "shiatsu", "sports massage"}},
	{"Hair Removal", []string{"wax", "waxing", "thread", "laser", "hair removal", "epilat", "sugar",
		"brazilian", "bikini"}},
	{"Eyebrows & Lashes", []string{"brow", "lash", "eyebrow", "eyelash", "tint", "lamination",
		"lash lift", "henna brow", "lvl"}},
	{"Body", []string{"body", "scrub", "wrap", "tan", "tanning", "spray tan", "slimming",
		"exfoliation", "hifu", "cellulite", "lymph", "detox", "contouring"}},
	{"Medical Aesthetics", []string{"aesthetic", "filler", "botox", "lip filler", "chemical peel",
		"prp", "vitamin infusion", "micro-needling", "meso"}},
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
	return "Other"
}

// ─── JSON I/O ────────────────────────────────────────────────────────────────

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

// ─── Main ────────────────────────────────────────────────────────────────────

func main() {
	if len(os.Args) < 2 {
		fmt.Println("╔══════════════════════════════════════════════════════════════╗")
		fmt.Println("║          🚀 Treatwell Scraper (Go - High Performance)       ║")
		fmt.Println("╠══════════════════════════════════════════════════════════════╣")
		fmt.Println("║                                                              ║")
		fmt.Println("║  Usage:                                                      ║")
		fmt.Println("║    treatwell_scraper.exe <listing-url>                        ║")
		fmt.Println("║                                                              ║")
		fmt.Println("║  Example:                                                    ║")
		fmt.Println("║    treatwell_scraper.exe https://www.treatwell.co.uk/places/  ║")
		fmt.Println("║                          hair-salons-in-london/               ║")
		fmt.Println("║                                                              ║")
		fmt.Println("║  Config:                                                     ║")
		fmt.Printf("║    Max pages    : %d                                         ║\n", maxPages)
		fmt.Printf("║    Workers      : %d                                         ║\n", maxWorkers)
		fmt.Printf("║    Max retries  : %d                                          ║\n", maxRetries)
		fmt.Println("║                                                              ║")
		fmt.Println("║  Output: Merges directly into database/data/*.json           ║")
		fmt.Println("╚══════════════════════════════════════════════════════════════╝")
		os.Exit(1)
	}

	baseURL := os.Args[1]
	// Strip trailing page number if present
	pageRe := regexp.MustCompile(`/page-\d+/?$`)
	baseURL = pageRe.ReplaceAllString(baseURL, "/")
	if !strings.HasSuffix(baseURL, "/") {
		baseURL += "/"
	}

	// Resolve origin for relative URLs
	parts := strings.SplitN(baseURL, "//", 2)
	origin := ""
	if len(parts) == 2 {
		slashIdx := strings.Index(parts[1], "/")
		if slashIdx > 0 {
			origin = parts[0] + "//" + parts[1][:slashIdx]
		}
	}

	// Resolve paths
	cwd, _ := os.Getwd()
	var dataDir string
	if filepath.Base(cwd) == "scripts" {
		dataDir = filepath.Join(filepath.Dir(cwd), "data")
	} else if filepath.Base(cwd) == "database" {
		dataDir = filepath.Join(cwd, "data")
	} else {
		dataDir = filepath.Join(cwd, "database", "data")
	}

	fmt.Println("╔══════════════════════════════════════════════════════════════╗")
	fmt.Println("║          🚀 Treatwell Scraper - Starting...                 ║")
	fmt.Println("╚══════════════════════════════════════════════════════════════╝")
	fmt.Printf("\n📋 Base URL  : %s\n", baseURL)
	fmt.Printf("📁 Data dir  : %s\n", dataDir)
	fmt.Printf("⚡ Workers   : %d concurrent\n", maxWorkers)
	fmt.Printf("📄 Max pages : %d\n\n", maxPages)

	startTime := time.Now()

	// ── Phase 1: Collect all listing entries ──────────────────────────────
	fmt.Println("═══ Phase 1: Collecting salon listings ═══")

	var allListings []ListingEntry
	seenSalonURLs := make(map[string]bool) // dedup across pages

	// Build initial page queue: page 1 is the base URL
	pageQueue := []string{baseURL}
	visitedPages := make(map[string]bool)

	for len(pageQueue) > 0 && len(visitedPages) < maxPages {
		pageURL := pageQueue[0]
		pageQueue = pageQueue[1:]

		if visitedPages[pageURL] {
			continue
		}
		visitedPages[pageURL] = true

		pageNum := len(visitedPages)
		fmt.Printf("\n📄 Page %d: %s\n", pageNum, pageURL)

		htmlStr, err := fetchHTML(pageURL)
		if err != nil {
			fmt.Printf("   ❌ Error: %v — Stopping pagination.\n", err)
			break
		}

		result := scrapeListingPage(htmlStr, origin)
		if len(result.Entries) == 0 {
			fmt.Println("   ❌ No listings found on this page. Stopping.")
			break
		}

		// Deduplicate listings across pages
		newCount := 0
		for _, entry := range result.Entries {
			if !seenSalonURLs[entry.URL] {
				seenSalonURLs[entry.URL] = true
				allListings = append(allListings, entry)
				newCount++
			}
		}

		fmt.Printf("   ✅ Found %d salons (%d new, %d total)\n", len(result.Entries), newCount, len(allListings))

		// Add discovered pagination URLs to queue
		for _, nextURL := range result.NextPageURLs {
			if !visitedPages[nextURL] {
				pageQueue = append(pageQueue, nextURL)
			}
		}

		time.Sleep(requestDelay + time.Duration(rand.Intn(500))*time.Millisecond)
	}

	fmt.Printf("\n📊 Total listings collected: %d\n\n", len(allListings))

	if len(allListings) == 0 {
		fmt.Println("❌ No listings found. Exiting.")
		os.Exit(0)
	}

	// ── Phase 2: Scrape detail pages (concurrent) ────────────────────────
	fmt.Println("═══ Phase 2: Scraping detail pages (concurrent) ═══")

	type indexedResult struct {
		idx   int
		salon *ScrapedSalon
	}

	results := make([]ScrapedSalon, 0, len(allListings))
	var mu sync.Mutex
	var wg sync.WaitGroup

	sem := make(chan struct{}, maxWorkers)
	progress := 0

	for i, listing := range allListings {
		wg.Add(1)
		sem <- struct{}{} // acquire

		go func(idx int, entry ListingEntry) {
			defer wg.Done()
			defer func() { <-sem }() // release

			// Small random jitter to avoid bursting
			time.Sleep(time.Duration(rand.Intn(300)) * time.Millisecond)

			salon, err := scrapeDetailPage(entry.URL)
			if err != nil {
				fmt.Printf("   [%d/%d] ❌ %s: %v\n", idx+1, len(allListings), entry.Name, err)
				return
			}

			mu.Lock()
			results = append(results, *salon)
			progress++
			fmt.Printf("   [%d/%d] ✅ %s (%d services)\n",
				progress, len(allListings), salon.NamaSalon, len(salon.Services))
			mu.Unlock()
		}(i, listing)
	}

	wg.Wait()

	fmt.Printf("\n📊 Successfully scraped: %d / %d salons\n\n", len(results), len(allListings))

	// ── Phase 3: Merge into existing JSON database ───────────────────────
	fmt.Println("═══ Phase 3: Merging into JSON database ═══")

	// Load existing data
	var exKota []Kota
	var exKategori []Kategori
	var exSalon []Salon
	var exService []Service
	var exStaff []Staff
	var exImages []SalonImage

	loadJSON(filepath.Join(dataDir, "kota.json"), &exKota)
	loadJSON(filepath.Join(dataDir, "kategori.json"), &exKategori)
	loadJSON(filepath.Join(dataDir, "salon.json"), &exSalon)
	loadJSON(filepath.Join(dataDir, "service.json"), &exService)
	loadJSON(filepath.Join(dataDir, "staff.json"), &exStaff)
	loadJSON(filepath.Join(dataDir, "salon_images.json"), &exImages)

	// Build lookup maps
	cityIDMap := make(map[string]int)
	catIDMap := make(map[string]int)
	salonURLs := make(map[string]bool)

	for _, k := range exKota {
		cityIDMap[k.NamaKota] = k.IDKota
	}
	for _, c := range exKategori {
		catIDMap[c.Name] = c.IDKategori
	}
	for _, s := range exSalon {
		base := strings.Split(s.SourceURL, "?")[0]
		salonURLs[base] = true
	}

	// ID counters
	var kotaIDs, catIDs, salonIDs, serviceIDs, staffIDs, imageIDs []int
	for _, v := range exKota {
		kotaIDs = append(kotaIDs, v.IDKota)
	}
	for _, v := range exKategori {
		catIDs = append(catIDs, v.IDKategori)
	}
	for _, v := range exSalon {
		salonIDs = append(salonIDs, v.IDSalon)
	}
	for _, v := range exService {
		serviceIDs = append(serviceIDs, v.IDService)
	}
	for _, v := range exStaff {
		staffIDs = append(staffIDs, v.IDStaff)
	}
	for _, v := range exImages {
		imageIDs = append(imageIDs, v.IDSalonImage)
	}

	nextKotaID := maxID(kotaIDs) + 1
	nextCatID := maxID(catIDs) + 1
	nextSalonID := maxID(salonIDs) + 1
	nextServiceID := maxID(serviceIDs) + 1
	nextStaffID := maxID(staffIDs) + 1
	nextImageID := maxID(imageIDs) + 1

	// Process scraped salons
	var newSalons []Salon
	var newServices []Service
	var newStaff []Staff
	var newImages []SalonImage
	skipped := 0

	for _, scraped := range results {
		baseURL := strings.Split(scraped.SourceURL, "?")[0]
		if salonURLs[baseURL] {
			skipped++
			continue
		}
		salonURLs[baseURL] = true

		salonID := nextSalonID
		nextSalonID++

		// Resolve city
		cityName := scraped.Kota
		if cityName == "" {
			cityName = "Unknown"
		}
		if _, ok := cityIDMap[cityName]; !ok {
			cityIDMap[cityName] = nextKotaID
			exKota = append(exKota, Kota{
				IDKota:   nextKotaID,
				NamaKota: cityName,
				Provinsi: scraped.Provinsi,
			})
			nextKotaID++
		}
		idKota := cityIDMap[cityName]

		// Description fallback
		desc := scraped.Deskripsi
		if desc == "" {
			desc = scraped.NamaSalon + " is a professional beauty salon."
		}

		// Phone
		var phone any
		if scraped.PhoneNumber != "" {
			phone = scraped.PhoneNumber
		}

		// Image URL (first image as primary)
		var imageURL any
		if len(scraped.ImageURLs) > 0 {
			imageURL = scraped.ImageURLs[0]
		}

		newSalons = append(newSalons, Salon{
			IDSalon:     salonID,
			IDUser:      salonID,
			IDKota:      idKota,
			NamaSalon:   scraped.NamaSalon,
			Alamat:      scraped.Alamat,
			Deskripsi:   desc,
			PhoneNumber: phone,
			OpeningTime: scraped.OpeningTime,
			ClosingTime: scraped.ClosingTime,
			ImageURL:    imageURL,
			MapsURL:     nil,
			Latitude:    scraped.Latitude,
			Longitude:   scraped.Longitude,
			Rating:      scraped.Rating,
			TotalReview: scraped.TotalReview,
			Status:      "active",
			SourceURL:   baseURL,
		})

		// Services
		for _, svc := range scraped.Services {
			catName := svc.Kategori
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
				Nama:       svc.Nama,
				Deskripsi:  nil,
				Durasi:     svc.Durasi,
				Harga:      svc.Harga,
				Status:     "active",
			})
			nextServiceID++
		}

		// Staff
		for _, name := range scraped.StaffNames {
			newStaff = append(newStaff, Staff{
				IDStaff:    nextStaffID,
				IDSalon:    salonID,
				Name:       name,
				ProfileURL: nil,
				Status:     "active",
			})
			nextStaffID++
		}

		// Images
		for i, imgURL := range scraped.ImageURLs {
			newImages = append(newImages, SalonImage{
				IDSalonImage: nextImageID,
				IDSalon:      salonID,
				ImageURL:     imgURL,
				IsPrimary:    i == 0,
				Urutan:       i + 1,
			})
			nextImageID++
		}
	}

	// ── Save merged data ─────────────────────────────────────────────────
	fmt.Println("\n[WRITE] Saving JSON files...")

	allSalons := append(exSalon, newSalons...)
	allServices := append(exService, newServices...)
	allStaff := append(exStaff, newStaff...)
	allImages := append(exImages, newImages...)

	files := []struct {
		name string
		data any
		count int
	}{
		{"kota.json", exKota, len(exKota)},
		{"kategori.json", exKategori, len(exKategori)},
		{"salon.json", allSalons, len(allSalons)},
		{"service.json", allServices, len(allServices)},
		{"staff.json", allStaff, len(allStaff)},
		{"salon_images.json", allImages, len(allImages)},
	}

	for _, f := range files {
		path := filepath.Join(dataDir, f.name)
		if err := saveJSON(path, f.data); err != nil {
			fmt.Fprintf(os.Stderr, "ERROR saving %s: %v\n", f.name, err)
			os.Exit(1)
		}
		fmt.Printf("   [OK] %-22s %d records\n", f.name, f.count)
	}

	// ── Summary ──────────────────────────────────────────────────────────
	elapsed := time.Since(startTime)

	fmt.Println("\n╔══════════════════════════════════════════════════════════════╗")
	fmt.Println("║                     🎉 SCRAPING COMPLETE                    ║")
	fmt.Println("╠══════════════════════════════════════════════════════════════╣")
	fmt.Printf("║  ⏱️  Duration          : %-20s               ║\n", elapsed.Round(time.Millisecond))
	fmt.Printf("║  🏪 New salons         : %-5d                              ║\n", len(newSalons))
	fmt.Printf("║  💇 New services       : %-5d                              ║\n", len(newServices))
	fmt.Printf("║  👥 New staff          : %-5d                              ║\n", len(newStaff))
	fmt.Printf("║  🖼️  New images         : %-5d                              ║\n", len(newImages))
	fmt.Printf("║  ⏭️  Skipped duplicates : %-5d                              ║\n", skipped)
	fmt.Println("╠══════════════════════════════════════════════════════════════╣")
	fmt.Printf("║  📊 Total salons       : %-5d                              ║\n", len(allSalons))
	fmt.Printf("║  📊 Total services     : %-5d                              ║\n", len(allServices))
	fmt.Printf("║  📊 Total kota         : %-5d                              ║\n", len(exKota))
	fmt.Printf("║  📊 Total kategori     : %-5d                              ║\n", len(exKategori))
	fmt.Printf("║  📁 Output dir         : %s\n", dataDir)
	fmt.Println("╚══════════════════════════════════════════════════════════════╝")

	if len(results) > 0 {
		avgTime := elapsed / time.Duration(len(results))
		fmt.Printf("\n⚡ Average: %s per salon (vs ~3s in JS = %.1fx faster)\n",
			avgTime.Round(time.Millisecond), 3000.0/float64(avgTime.Milliseconds()))
	}
}
