package integration

import (
	"context"
	"os"
	"path/filepath"
	"testing"
	"time"

	"github.com/google/uuid"
	"github.com/jackc/pgx/v5/pgxpool"
	"github.com/samanramezani1377-hub/crawler-numpo/internal/crawl"
	"github.com/samanramezani1377-hub/crawler-numpo/internal/migrate"
	"github.com/samanramezani1377-hub/crawler-numpo/internal/model"
	"github.com/samanramezani1377-hub/crawler-numpo/internal/store"
	"github.com/samanramezani1377-hub/crawler-numpo/internal/worker"
)

type fakeCrawler struct{ page crawl.Page }
func (f fakeCrawler) Fetch(context.Context, string) (crawl.Page, error) { return f.page, nil }

type fakeProber struct{}
func (fakeProber) Probe(context.Context, string) (model.ProbeResult, error) {
	return model.ProbeResult{Status:"active", DNSStatus:"ok", HTTPStatus:200, HTTPSStatus:200}, nil
}

func TestWorkerEndToEndPipeline(t *testing.T) {
	dsn := os.Getenv("NUMPO_TEST_DATABASE_URL")
	if dsn == "" { t.Skip("NUMPO_TEST_DATABASE_URL is not configured") }
	ctx, cancel := context.WithTimeout(context.Background(), 20*time.Second)
	defer cancel()
	db, err := pgxpool.New(ctx, dsn)
	if err != nil { t.Fatal(err) }
	defer db.Close()
	root, _ := os.Getwd()
	if err := migrate.Run(ctx, db, filepath.Join(root, "../../migrations")); err != nil { t.Fatal(err) }

	s := &store.Store{DB: db}
	projectName := "e2e-" + uuid.NewString()
	var projectID string
	if err := db.QueryRow(ctx, "INSERT INTO projects(id,name) VALUES(gen_random_uuid(),$1) RETURNING id::text", projectName).Scan(&projectID); err != nil { t.Fatal(err) }
	jobID := uuid.NewString()
	cfg := map[string]any{
		"max_urls": 1, "max_pages": 1, "max_depth": 0, "max_candidates_per_page": 5,
		"target": map[string]any{
			"technologies": []any{"WordPress", "WooCommerce"},
			"has_public_phone": true,
		},
	}
	if err := s.CreateDiscoveryJobWithConfig(ctx, jobID, projectID, "manual", cfg); err != nil { t.Fatal(err) }
	if err := s.UpsertCandidate(ctx, uuid.NewString(), jobID, "https://shop.example.com/", "https://shop.example.com/", "shop.example.com", "shop.example.com", "manual_seed", "", 100, 1); err != nil { t.Fatal(err) }

	w := &worker.Worker{
		Store: s, Crawler: fakeCrawler{page: crawl.Page{
			URL: "https://shop.example.com/", Status: 200, ContentType: "text/html",
			Title: "Shop", Body: `<html lang="en"><head><title>Shop</title></head><body>
				/wp-content/ wp-includes/ WooCommerce
				Phone: +989121234567
			</body></html>`,
		}},
		Prober: fakeProber{}, MaxPages: 1, MaxCandidatesPerPage: 5, MaxDepth: 0, MaxURLs: 1,
		Lease: time.Minute, DomainRateLimit: 0, ProbeTTL: 0, AllowSubdomains: false, AllowExternalLinks: false,
	}
	if err := w.Run(ctx, jobID); err != nil { t.Fatal(err) }

	var status string
	if err := db.QueryRow(ctx, "SELECT status FROM discovery_jobs WHERE id=$1", jobID).Scan(&status); err != nil { t.Fatal(err) }
	if status != "completed" { t.Fatalf("job status = %s, want completed", status) }

	var candidates int
	if err := db.QueryRow(ctx, "SELECT count(*) FROM candidates WHERE discovery_job_id=$1 AND status='completed'", jobID).Scan(&candidates); err != nil { t.Fatal(err) }
	if candidates != 1 { t.Fatalf("completed candidates = %d, want 1", candidates) }

	var technologies int
	if err := db.QueryRow(ctx, "SELECT count(*) FROM technologies t JOIN domains d ON d.id=t.domain_id WHERE d.project_id=$1 AND t.name IN ('WordPress','WooCommerce')", projectID).Scan(&technologies); err != nil { t.Fatal(err) }
	if technologies != 2 { t.Fatalf("technologies = %d, want 2", technologies) }

	var phones int
	if err := db.QueryRow(ctx, "SELECT count(*) FROM contacts c JOIN domains d ON d.id=c.domain_id WHERE d.project_id=$1 AND c.type='phone'", projectID).Scan(&phones); err != nil { t.Fatal(err) }
	if phones != 1 { t.Fatalf("phones = %d, want 1", phones) }

	var pages int
	if err := db.QueryRow(ctx, "SELECT count(*) FROM pages p JOIN domains d ON d.id=p.domain_id WHERE d.project_id=$1", projectID).Scan(&pages); err != nil { t.Fatal(err) }
	if pages != 1 { t.Fatalf("pages = %d, want 1", pages) }
}
