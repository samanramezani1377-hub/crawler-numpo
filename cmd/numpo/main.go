package main
import(
 "context"
 "log"
 "os"
 "os/signal"
 "syscall"
 "net/http"
 "time"
 "strings"
 "github.com/samanramezani1377-hub/crawler-numpo/internal/config"
 "github.com/samanramezani1377-hub/crawler-numpo/internal/httpapi"
 "github.com/samanramezani1377-hub/crawler-numpo/internal/migrate"
 "github.com/samanramezani1377-hub/crawler-numpo/internal/runtime"
 "github.com/samanramezani1377-hub/crawler-numpo/internal/store"
)
func env(k,d string)string{if v:=os.Getenv(k);v!=""{return v};return d}
func main(){
 cfg:=config.Load()
 if !cfg.AllowAnonymousAPI {
  key:=strings.TrimSpace(cfg.APIKey)
  if key=="" { log.Fatal("NUMPO_API_KEY is required unless NUMPO_ALLOW_ANONYMOUS_API=true") }
  if len(key)<32 { log.Fatal("NUMPO_API_KEY must be at least 32 characters") }
 }
 root,cancel:=signal.NotifyContext(context.Background(),os.Interrupt,syscall.SIGTERM)
 defer cancel()
 var embedded *runtime.EmbeddedPostgres
 if cfg.EmbeddedPostgres {
  embedded,e=runtime.StartEmbeddedPostgres(root,cfg.EmbeddedRuntimeDir,cfg.EmbeddedPostgresCacheDir);if e!=nil{log.Fatal(e)}
  defer embedded.Stop()
  cfg.DatabaseURL=embedded.DSN
 }
 st,e:=store.New(root,cfg.DatabaseURL);if e!=nil{log.Fatal(e)};defer st.Close()
 if e:=migrate.Run(root,st.DB,env("NUMPO_MIGRATIONS_DIR","./migrations"));e!=nil{log.Fatal(e)}
 srv:=&http.Server{Addr:cfg.ListenAddr,Handler:httpapi.New(st,cfg),ReadHeaderTimeout:5*time.Second,ReadTimeout:30*time.Second,WriteTimeout:30*time.Second,IdleTimeout:60*time.Second}
 go func(){<-root.Done();ctx,stop:=context.WithTimeout(context.Background(),15*time.Second);defer stop();if e:=srv.Shutdown(ctx);e!=nil{log.Printf("graceful shutdown: %v",e)}}()
 log.Printf("numpo engine listening on %s",cfg.ListenAddr)
 if e:=srv.ListenAndServe();e!=nil&&e!=http.ErrServerClosed{log.Fatal(e)}
}
