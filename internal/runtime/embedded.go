package runtime

import (
	"context"
	"fmt"
	"os"
	"path/filepath"
	"net"
	"time"

	embeddedpostgres "github.com/fergusstrange/embedded-postgres"
)

type EmbeddedPostgres struct {
	server *embeddedpostgres.EmbeddedPostgres
	DSN    string
}

func StartEmbeddedPostgres(ctx context.Context, baseDir, cacheDir string) (*EmbeddedPostgres, error) {
	if err := os.MkdirAll(baseDir, 0700); err != nil { return nil, err }
	stateDir := filepath.Dir(baseDir)
	if err := os.MkdirAll(stateDir, 0700); err != nil { return nil, err }
	if err := os.MkdirAll(cacheDir, 0700); err != nil { return nil, err }
	port := uint32(55432)
	cfg := embeddedpostgres.DefaultConfig().
		Version(embeddedpostgres.V16).
		Port(port).
		Database("numpo").
		Username("numpo").
		Password("numpo").
		CachePath(cacheDir).
		RuntimePath(filepath.Join(baseDir, "postgres-runtime")).
		DataPath(filepath.Join(stateDir, "postgres-data")).
		StartTimeout(30 * time.Second).
		Locale("C").
		Encoding("UTF8")
	pg := embeddedpostgres.NewDatabase(cfg)
	// A previous engine process can die without running its deferred PostgreSQL
	// shutdown. Reuse an already-listening local instance instead of failing
	// with "process already listening on port 55432".
	if conn, err := net.DialTimeout("tcp", "127.0.0.1:55432", 300*time.Millisecond); err == nil {
		_ = conn.Close()
		return &EmbeddedPostgres{DSN: cfg.GetConnectionURL() + "?sslmode=disable"}, nil
	}
	if err := pg.Start(); err != nil { return nil, fmt.Errorf("start embedded postgres: %w", err) }
	select {
	case <-ctx.Done():
		_ = pg.Stop()
		return nil, ctx.Err()
	default:
	}
	return &EmbeddedPostgres{server: pg, DSN: cfg.GetConnectionURL() + "?sslmode=disable"}, nil
}

func (p *EmbeddedPostgres) Stop() error {
	if p == nil || p.server == nil { return nil }
	return p.server.Stop()
}
