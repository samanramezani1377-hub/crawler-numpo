package config

import "os"

type Config struct { ListenAddr string; DatabaseURL string; APIKey string; HTTPTimeoutSeconds int; MaxBodyBytes int64 }
func Load() Config { return Config{ListenAddr: env("NUMPO_LISTEN_ADDR", ":8080"), DatabaseURL: env("NUMPO_DATABASE_URL", "postgres://postgres:postgres@localhost:5432/numpo?sslmode=disable"), APIKey: os.Getenv("NUMPO_API_KEY"), HTTPTimeoutSeconds: 15, MaxBodyBytes: 2 << 20} }
func env(k,d string) string { if v:=os.Getenv(k); v!="" { return v }; return d }
