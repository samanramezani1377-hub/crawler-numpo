package integration

import("context";"os";"path/filepath";"testing";"time";"github.com/jackc/pgx/v5/pgxpool";"github.com/samanramezani1377-hub/crawler-numpo/internal/migrate";"github.com/samanramezani1377-hub/crawler-numpo/internal/store")

func TestPostgresPersistence(t *testing.T){
 dsn:=os.Getenv("NUMPO_TEST_DATABASE_URL");if dsn==""{t.Skip("NUMPO_TEST_DATABASE_URL is not configured")}
 ctx,cancel:=context.WithTimeout(context.Background(),20*time.Second);defer cancel()
 db,e:=pgxpool.New(ctx,dsn);if e!=nil{t.Fatal(e)};defer db.Close()
 if e=db.Ping(ctx);e!=nil{t.Fatal(e)}
 root,_:=os.Getwd();if e=migrate.Run(ctx,db,filepath.Join(root,"../../migrations"));e!=nil{t.Fatal(e)}
 s:=&store.Store{DB:db};project:="integration-"+time.Now().UTC().Format("20060102150405.000000000");job:="00000000-0000-0000-0000-"+time.Now().UTC().Format("150405000000")
 if _,e=db.Exec(ctx,"INSERT INTO projects(id,name) VALUES(gen_random_uuid(),$1)",project);e!=nil{t.Fatal(e)}
 var pid string;if e=db.QueryRow(ctx,"SELECT id::text FROM projects WHERE name=$1",project).Scan(&pid);e!=nil{t.Fatal(e)}
 if e=s.CreateDiscoveryJobWithConfig(ctx,job,pid,"manual",map[string]any{"max_urls":2,"max_pages":2});e!=nil{t.Fatal(e)}
 if e=s.UpsertCandidate(ctx,"11111111-1111-1111-1111-111111111111",job,"https://example.com","https://example.com/","example.com","example.com","manual_seed","",100,1);e!=nil{t.Fatal(e)}
 ok,e:=s.ConsumeURLBudget(ctx,job);if e!=nil||!ok{t.Fatalf("budget: ok=%v err=%v",ok,e)}
 info,e:=s.GetDiscoveryJob(ctx,job);if e!=nil{t.Fatal(e)};if info["max_urls"].(int)!=2{t.Fatalf("unexpected max_urls: %#v",info["max_urls"])}
}
