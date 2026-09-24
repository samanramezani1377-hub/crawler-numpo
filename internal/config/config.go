package config

import("os";"strconv";"strings")

type Config struct{
 ListenAddr string
 DatabaseURL string
 APIKey string
 SearchURLTemplate string
 HTTPTimeoutSeconds int
 MaxBodyBytes int64
 MaxPages int
 MaxCandidatesPerPage int
 MaxDepth int
 MaxURLs int
 DomainRateLimitMS int
 ProbeTTLSeconds int
 AllowSubdomains bool
 AllowExternalLinks bool
 BrowserBinary string
 BrowserTimeoutSeconds int
 BrowserMaxOutput int
}
func Load() Config{
 return Config{
  ListenAddr:env("NUMPO_LISTEN_ADDR",":8080"),
  DatabaseURL:env("NUMPO_DATABASE_URL","postgres://postgres:postgres@localhost:5432/numpo?sslmode=disable"),
  APIKey:os.Getenv("NUMPO_API_KEY"),
  SearchURLTemplate:os.Getenv("NUMPO_SEARCH_URL_TEMPLATE"),
  HTTPTimeoutSeconds:intEnv("NUMPO_HTTP_TIMEOUT_SECONDS",15),
  MaxBodyBytes:int64Env("NUMPO_MAX_BODY_BYTES",2<<20),
  MaxPages:intEnv("NUMPO_MAX_PAGES",100),
  MaxCandidatesPerPage:intEnv("NUMPO_MAX_CANDIDATES_PER_PAGE",50),
  MaxDepth:intEnv("NUMPO_MAX_DEPTH",3),
  MaxURLs:intEnv("NUMPO_MAX_URLS",500),
  DomainRateLimitMS:intEnv("NUMPO_DOMAIN_RATE_LIMIT_MS",250),
  ProbeTTLSeconds:intEnv("NUMPO_PROBE_TTL_SECONDS",3600),
  AllowSubdomains:boolEnv("NUMPO_ALLOW_SUBDOMAINS",false),
  AllowExternalLinks:boolEnv("NUMPO_ALLOW_EXTERNAL_LINKS",false),
  BrowserBinary:env("NUMPO_BROWSER_BINARY",""),
  BrowserTimeoutSeconds:intEnv("NUMPO_BROWSER_TIMEOUT_SECONDS",30),
  BrowserMaxOutput:intEnv("NUMPO_BROWSER_MAX_OUTPUT",8<<20),
 }
}
func env(k,d string)string{if v:=os.Getenv(k);v!=""{return v};return d}
func intEnv(k string,d int)int{v,_:=strconv.Atoi(env(k,strconv.Itoa(d)));if v<1{return d};return v}
func int64Env(k string,d int64)int64{v,_:=strconv.ParseInt(env(k,strconv.FormatInt(d,10)),10,64);if v<1{return d};return v}
func boolEnv(k string,d bool)bool{v:=strings.ToLower(strings.TrimSpace(os.Getenv(k)));if v==""{return d};return v=="1"||v=="true"||v=="yes"}
