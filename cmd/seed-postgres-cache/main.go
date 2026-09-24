package main

import (
	"log"
	"os"
	"path/filepath"
	"time"

	embeddedpostgres "github.com/fergusstrange/embedded-postgres"
)

func main() {
	cache := os.Getenv("NUMPO_POSTGRES_CACHE")
	if cache == "" { cache = filepath.Join("dist", "postgres-cache") }
	if err := os.MkdirAll(cache, 0700); err != nil { log.Fatal(err) }
	cfg := embeddedpostgres.DefaultConfig().
		Version(embeddedpostgres.V17).
		Port(55433).
		Database("numpo").
		Username("numpo").
		Password("numpo").
		CachePath(cache).
		RuntimePath(filepath.Join(os.TempDir(), "numpo-postgres-seed-runtime")).
		DataPath(filepath.Join(os.TempDir(), "numpo-postgres-seed-data")).
		StartTimeout(60 * time.Second).
		Locale("C").
		Encoding("UTF8")
	pg := embeddedpostgres.NewDatabase(cfg)
	if err := pg.Start(); err != nil { log.Fatal(err) }
	if err := pg.Stop(); err != nil { log.Fatal(err) }
}
