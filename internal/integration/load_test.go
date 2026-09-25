package integration

import (
	"context"
	"fmt"
	"os"
	"path/filepath"
	"sync"
	"sync/atomic"
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

type stressCrawler struct {
	count atomic.Int64
}

func (c *stressCrawler) Fetch(ctx context.Context, raw string) (crawl.Page, error) {
	c.count.Add(1)
	return crawl.Page{
		URL: raw, Status: 200, ContentType: "text/html",
		Title: "Stress Shop",
		Body: "<html><body>/wp-content/ WooCommerce Phone: +989121234567</body></html>",
	}, nil
}

type stressProber struct {
	count atomic.Int64
}

func (p *stressProber) Probe(ctx context.Context, host string) (model.ProbeResult, error) {
	p.count.Add(1)
	return model.ProbeResult{Host: host, Status: "active", DNSStatus: "ok", HTTPStatus: 200, HTTPSStatus: 200}, nil
}

func TestConcurrentWorkerLoad(t *testing.T) {
	dsn := os.Getenv("NUMPO_TEST_DATABASE_URL")
	if dsn == "" { t.Skip("NUMPO_TEST_DATABASE_URL is not configured") }
	ctx, cancel := context.WithTimeout(context.Background(), 45*time.Second)
	defer cancel()

	db, err := pgxpool.New(ctx, dsn)
	if err != nil { t.Fatal(err) }
	defer db.Close()
	root, err := os.Getwd()
	if err != nil { t.Fatal(err) }
	if err := migrate.Run(ctx, db, filepath.Join(root, "../../migrations")); err != nil { t.Fatal(err) }

	projectID := uuid.NewString()
	jobID := uuid.NewString()
	if _, err := db.Exec(ctx, "INSERT INTO projects(id,name) VALUES($1,$2)", projectID, "stress-"+uuid.NewString()); err != nil {
		t.Fatal(err)
	}
	st := &store.Store{DB: db}
	if err := st.CreateDiscoveryJobWithConfig(ctx, jobID, projectID, "manual", map[string]any{
		"max_urls": 80, "max_pages": 80, "max_depth": 0, "max_candidates_per_page": 5,
		"capabilities": map[string]any{"link_discovery": false},
	}); err != nil {
		t.Fatal(err)
	}

	for i := 0; i < 80; i++ {
		host := fmt.Sprintf("shop-%03d.example.test", i)
		raw := "https://" + host + "/"
		if err := st.UpsertCandidate(ctx, uuid.NewString(), jobID, raw, raw, host, host, "stress", "", 100-i, 1); err != nil {
			t.Fatal(err)
		}
	}

	crawler := &stressCrawler{}
	prober := &stressProber{}
	workers := make([]*worker.Worker, 8)
	for i := range workers {
		workers[i] = &worker.Worker{
			Store: st, Crawler: crawler, Prober: prober,
			MaxPages: 80, MaxCandidatesPerPage: 5, MaxDepth: 0, MaxURLs: 80,
			Lease: 10 * time.Second, DomainRateLimit: 0, ProbeTTL: 0,
			AllowSubdomains: false, AllowExternalLinks: false,
		}
	}

	var wg sync.WaitGroup
	errs := make(chan error, len(workers))
	start := time.Now()
	for _, w := range workers {
		wg.Add(1)
		go func(w *worker.Worker) {
			defer wg.Done()
			if err := w.Run(ctx, jobID); err != nil { errs <- err }
		}(w)
	}
	wg.Wait()
	close(errs)
	for err := range errs {
		t.Fatal(err)
	}

	var status string
	if err := db.QueryRow(ctx, "SELECT status FROM discovery_jobs WHERE id=$1", jobID).Scan(&status); err != nil { t.Fatal(err) }
	if status != "completed" { t.Fatalf("job status = %s, want completed", status) }

	var completed, total, processed int
	if err := db.QueryRow(ctx, "SELECT count(*) FILTER (WHERE status='completed'), count(*), (SELECT processed_urls FROM discovery_jobs WHERE id=$1) FROM candidates WHERE discovery_job_id=$1", jobID).Scan(&completed, &total, &processed); err != nil {
		t.Fatal(err)
	}
	if total != 80 || completed != 80 || processed != 80 {
		t.Fatalf("load results: total=%d completed=%d processed_urls=%d", total, completed, processed)
	}
	if crawler.count.Load() != 80 || prober.count.Load() != 80 {
		t.Fatalf("duplicate processing detected: crawls=%d probes=%d", crawler.count.Load(), prober.count.Load())
	}
	t.Logf("processed 80 candidates with 8 concurrent workers in %s", time.Since(start).Round(time.Millisecond))
}
