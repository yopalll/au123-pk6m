// Package main — Fresh.com product scraper untuk VIYGO V2.
//
// Scrape data produk skincare dari fresh.com (sekali, untuk seed data demo),
// lalu export ke JSON per kategori di output/ untuk di-import via FreshProductSeeder.
//
// Dependency:
//   - github.com/chromedp/chromedp   (headless Chrome — render JS fresh.com)
//   - github.com/PuerkitoBio/goquery (parse DOM)
//
// Cara pakai:
//   cd scripts/scraper
//   go mod tidy
//   go run fresh_scraper.go
package main

import (
	"context"
	"encoding/json"
	"fmt"
	"io"
	"log"
	"net/http"
	"os"
	"path/filepath"
	"strings"
	"time"

	"github.com/PuerkitoBio/goquery"
	"github.com/chromedp/chromedp"
)

type Config struct {
	BaseURL      string   `json:"base_url"`
	Collections  []string `json:"collections"`
	UsdToIdrRate float64  `json:"usd_to_idr_rate"`
	OutputDir    string   `json:"output_dir"`
	ImageDir     string   `json:"image_dir"`
	DelayMs      int      `json:"delay_ms"`
	Headless     bool     `json:"headless"`
	UserAgent    string   `json:"user_agent"`
}

type ProductData struct {
	FreshProductID  string   `json:"fresh_product_id"`
	FreshURL        string   `json:"fresh_url"`
	Nama            string   `json:"nama"`
	Kategori        string   `json:"kategori"`
	Koleksi         string   `json:"koleksi"`
	Deskripsi       string   `json:"deskripsi"`
	KeyIngredients  string   `json:"key_ingredients"`
	FullIngredients string   `json:"full_ingredients"`
	CaraPemakaian   string   `json:"cara_pemakaian"`
	HargaUSD        float64  `json:"harga_usd"`
	HargaIDR        float64  `json:"harga_idr"`
	VolumeML        int      `json:"volume_ml"`
	BeratGram       int      `json:"berat_gram"`
	SkinType        string   `json:"skin_type"`
	SkinConcern     string   `json:"skin_concern"`
	Badge           string   `json:"badge"`
	Images          []string `json:"images"`
}

var cfg Config

func main() {
	loadConfig("config.json")

	if err := os.MkdirAll(cfg.OutputDir, 0o755); err != nil {
		log.Fatalf("gagal buat output dir: %v", err)
	}
	if err := os.MkdirAll(cfg.ImageDir, 0o755); err != nil {
		log.Fatalf("gagal buat image dir: %v", err)
	}

	ctx, cancel := newChromeContext()
	defer cancel()

	for _, col := range cfg.Collections {
		colURL := cfg.BaseURL + col
		log.Printf("scrape collection: %s", colURL)

		productURLs, err := scrapeProductList(ctx, colURL)
		if err != nil {
			log.Printf("  skip (gagal list): %v", err)
			continue
		}

		var products []ProductData
		for _, pURL := range productURLs {
			pd, err := scrapeProductDetail(ctx, pURL)
			if err != nil {
				log.Printf("  gagal detail %s: %v", pURL, err)
				continue
			}
			products = append(products, pd)
			time.Sleep(time.Duration(cfg.DelayMs) * time.Millisecond)
		}

		slug := strings.Trim(strings.ReplaceAll(col, "/collections/", ""), "/")
		exportToJSON(products, filepath.Join(cfg.OutputDir, slug+".json"))
	}

	log.Println("selesai. Jalankan: php artisan db:seed --class=FreshProductSeeder")
}

func loadConfig(path string) {
	b, err := os.ReadFile(path)
	if err != nil {
		log.Fatalf("gagal baca config: %v", err)
	}
	if err := json.Unmarshal(b, &cfg); err != nil {
		log.Fatalf("config invalid: %v", err)
	}
}

func newChromeContext() (context.Context, context.CancelFunc) {
	opts := append(chromedp.DefaultExecAllocatorOptions[:],
		chromedp.Flag("headless", cfg.Headless),
		chromedp.UserAgent(cfg.UserAgent),
	)
	allocCtx, _ := chromedp.NewExecAllocator(context.Background(), opts...)
	ctx, cancel := chromedp.NewContext(allocCtx)
	return ctx, cancel
}

// scrapeProductList — render halaman collection, ambil semua URL produk.
func scrapeProductList(ctx context.Context, url string) ([]string, error) {
	var html string
	tctx, cancel := context.WithTimeout(ctx, 30*time.Second)
	defer cancel()

	if err := chromedp.Run(tctx,
		chromedp.Navigate(url),
		chromedp.WaitReady("body"),
		chromedp.OuterHTML("html", &html),
	); err != nil {
		return nil, err
	}

	doc, err := goquery.NewDocumentFromReader(strings.NewReader(html))
	if err != nil {
		return nil, err
	}

	var urls []string
	seen := map[string]bool{}
	doc.Find("a[href*='/products/']").Each(func(_ int, s *goquery.Selection) {
		if href, ok := s.Attr("href"); ok {
			if !strings.HasPrefix(href, "http") {
				href = cfg.BaseURL + href
			}
			if !seen[href] {
				seen[href] = true
				urls = append(urls, href)
			}
		}
	})
	return urls, nil
}

// scrapeProductDetail — render halaman produk, parse jadi ProductData.
func scrapeProductDetail(ctx context.Context, url string) (ProductData, error) {
	var html string
	tctx, cancel := context.WithTimeout(ctx, 30*time.Second)
	defer cancel()

	if err := chromedp.Run(tctx,
		chromedp.Navigate(url),
		chromedp.WaitReady("body"),
		chromedp.OuterHTML("html", &html),
	); err != nil {
		return ProductData{}, err
	}

	doc, err := goquery.NewDocumentFromReader(strings.NewReader(html))
	if err != nil {
		return ProductData{}, err
	}
	return parseProductData(doc, url), nil
}

func parseProductData(doc *goquery.Document, url string) ProductData {
	text := func(sel string) string {
		return strings.TrimSpace(doc.Find(sel).First().Text())
	}

	pd := ProductData{
		FreshURL:        url,
		FreshProductID:  lastPathSegment(url),
		Nama:            text("h1.product-title, h1"),
		Deskripsi:       text(".product-description"),
		KeyIngredients:  text(".key-ingredients, .ingredient-highlight"),
		FullIngredients: text(".full-ingredients"),
		CaraPemakaian:   text(".how-to-use"),
		SkinType:        "all",
		BeratGram:       200,
	}

	priceStr := text(".product-price")
	pd.HargaUSD = parsePrice(priceStr)
	pd.HargaIDR = pd.HargaUSD * cfg.UsdToIdrRate

	doc.Find("img.product-image").Each(func(_ int, s *goquery.Selection) {
		if src, ok := s.Attr("src"); ok {
			local, err := downloadImage(src)
			if err == nil {
				pd.Images = append(pd.Images, local)
			}
		}
	})

	return pd
}

func downloadImage(url string) (string, error) {
	if !strings.HasPrefix(url, "http") {
		url = cfg.BaseURL + url
	}
	resp, err := http.Get(url)
	if err != nil {
		return "", err
	}
	defer resp.Body.Close()

	name := lastPathSegment(url)
	if name == "" {
		name = fmt.Sprintf("img-%d.jpg", time.Now().UnixNano())
	}
	dest := filepath.Join(cfg.ImageDir, name)

	f, err := os.Create(dest)
	if err != nil {
		return "", err
	}
	defer f.Close()
	if _, err := io.Copy(f, resp.Body); err != nil {
		return "", err
	}

	// Path relatif untuk disimpan di DB (public/...)
	return "public/images/products/fresh/" + name, nil
}

func exportToJSON(products []ProductData, path string) {
	b, err := json.MarshalIndent(products, "", "  ")
	if err != nil {
		log.Printf("gagal marshal %s: %v", path, err)
		return
	}
	if err := os.WriteFile(path, b, 0o644); err != nil {
		log.Printf("gagal tulis %s: %v", path, err)
		return
	}
	log.Printf("  export %d produk -> %s", len(products), path)
}

func parsePrice(s string) float64 {
	s = strings.NewReplacer("$", "", "USD", "", ",", "", " ", "").Replace(s)
	var v float64
	fmt.Sscanf(s, "%f", &v)
	return v
}

func lastPathSegment(url string) string {
	url = strings.TrimRight(url, "/")
	if i := strings.LastIndex(url, "/"); i >= 0 {
		seg := url[i+1:]
		if j := strings.IndexAny(seg, "?#"); j >= 0 {
			seg = seg[:j]
		}
		return seg
	}
	return url
}
