package integration

import (
	"context"
	"encoding/json"
	"net/http"
	"net/http/httptest"
	"os"
	"path/filepath"
	"strings"
	"testing"
	"time"

	"github.com/google/uuid"
	"github.com/jackc/pgx/v5/pgxpool"
	"github.com/samanramezani1377-hub/crawler-numpo/internal/config"
	"github.com/samanramezani1377-hub/crawler-numpo/internal/crawl"
	"github.com/samanramezani1377-hub/crawler-numpo/internal/httpapi"
	"github.com/samanramezani1377-hub/crawler-numpo/internal/migrate"
	"github.com/samanramezani1377-hub/crawler-numpo/internal/store"
)

func TestHTTPDiscoveryEndToEnd(t *testing.T) {
	dsn := os.Getenv("NUMPO_TEST_DATABASE_URL")
	if dsn == "" { t.Skip("NUMPO_TEST_DATABASE_URL is not configured") }
	ctx, cancel := context.WithTimeout(context.Background(), 30*time.Second)
	defer cancel()
	db, err := pgxpool.New(ctx, dsn)
	if err != nil { t.Fatal(err) }
	defer db.Close()
	root, err := os.Getwd()
	if err != nil { t.Fatal(err) }
	if err := migrate.Run(ctx, db, filepath.Join(root, "../../migrations")); err != nil { t.Fatal(err) }

	projectID := uuid.NewString()
	if _, err := db.Exec(ctx, "INSERT INTO projects(id,name) VALUES($1,$2)", projectID, "http-e2e-"+uuid.NewString()); err != nil { t.Fatal(err) }

	st := &store.Store{DB: db}
	cfg := config.Load()
	cfg.APIKey = "integration-test-api-key"
	cfg.AllowAnonymousAPI = false
	cfg.SearchURLTemplate = ""
	cfg.MaxPages = 2
	cfg.MaxURLs = 1
	cfg.MaxDepth = 0
	cfg.MaxCandidatesPerPage = 10
	cfg.DomainRateLimitMS = 0
	cfg.ProbeTTLSeconds = 0

	srv := httpapi.New(st, cfg)
	srv.Worker.Crawler = fakeCrawler{page: crawl.Page{
		URL: "https://shop.example.test/",
		Status: 200,
		ContentType: "text/html",
		Title: "Example Shop",
		Body: "<html lang="en"><head><title>Example Shop</title></head><body>" +
			"/wp-content/ /wp-includes/ WooCommerce " +
			"<a href="https://shop.example.test/contact">Contact</a> " +
			"Phone: +989121234567</body></html>",
	}}
	srv.Worker.Prober = fakeProber{}

	body := map[string]any{
		"project_id": projectID,
		"mode": "manual",
		"seeds": []string{"https://shop.example.test/"},
		"limits": map[string]int{"max_pages": 1, "max_urls": 1, "max_depth": 0},
		"target": map[string]any{
			"technologies": []any{"WordPress", "WooCommerce"},
			"has_public_phone": true,
		},
	}
	payload, err := json.Marshal(body)
	if err != nil { t.Fatal(err) }

	req := httptest.NewRequest(http.MethodPost, "/api/v1/discovery/jobs", strings.NewReader(string(payload)))
	req.Header.Set("Authorization", "Bearer "+cfg.APIKey)
	req.Header.Set("Content-Type", "application/json")
	rec := httptest.NewRecorder()
	srv.ServeHTTP(rec, req)
	if rec.Code != http.StatusAccepted { t.Fatalf("create job status = %d, body = %s", rec.Code, rec.Body.String()) }

	var created map[string]any
	if err := json.Unmarshal(rec.Body.Bytes(), &created); err != nil { t.Fatal(err) }
	jobID, _ := created["job_id"].(string)
	if jobID == "" { t.Fatal("create job returned an empty job_id") }

	deadline := time.Now().Add(15 * time.Second)
	for time.Now().Before(deadline) {
		req = httptest.NewRequest(http.MethodGet, "/api/v1/discovery/jobs/"+jobID, nil)
		req.Header.Set("Authorization", "Bearer "+cfg.APIKey)
		rec = httptest.NewRecorder()
		srv.ServeHTTP(rec, req)
		if rec.Code != http.StatusOK { t.Fatalf("job status request = %d, body = %s", rec.Code, rec.Body.String()) }
		var job map[string]any
		if err := json.Unmarshal(rec.Body.Bytes(), &job); err != nil { t.Fatal(err) }
		if job["status"] == "completed" { break }
		if job["status"] == "failed" || job["status"] == "cancelled" { t.Fatalf("job ended with status %v", job["status"]) }
		time.Sleep(100 * time.Millisecond)
	}
	var status string
	if err := db.QueryRow(ctx, "SELECT status FROM discovery_jobs WHERE id=$1", jobID).Scan(&status); err != nil { t.Fatal(err) }
	if status != "completed" { t.Fatalf("job status = %s, want completed", status) }

	req = httptest.NewRequest(http.MethodGet, "/api/v1/discovery/jobs/"+jobID+"/technologies", nil)
	req.Header.Set("Authorization", "Bearer "+cfg.APIKey)
	rec = httptest.NewRecorder()
	srv.ServeHTTP(rec, req)
	if rec.Code != http.StatusOK { t.Fatalf("technologies status = %d, body = %s", rec.Code, rec.Body.String()) }
	var resources map[string]any
	if err := json.Unmarshal(rec.Body.Bytes(), &resources); err != nil { t.Fatal(err) }
	items, _ := resources["items"].([]any)
	found := map[string]bool{}
	for _, raw := range items {
		if item, ok := raw.(map[string]any); ok {
			if name, ok := item["name"].(string); ok { found[name] = true }
		}
	}
	if !found["WordPress"] || !found["WooCommerce"] { t.Fatalf("technology output missing WordPress/WooCommerce: %#v", found) }
}
