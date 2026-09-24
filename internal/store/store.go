package store
import("context";"encoding/json";"time";"github.com/jackc/pgx/v5";"github.com/jackc/pgx/v5/pgxpool")
type Store struct{DB *pgxpool.Pool}
func New(ctx context.Context,dsn string)(*Store,error){db,e:=pgxpool.New(ctx,dsn);if e!=nil{return nil,e};if e=db.Ping(ctx);e!=nil{db.Close();return nil,e};return &Store{db},nil}
func(s *Store)Close(){s.DB.Close()}
func(s *Store)CreateDiscoveryJob(ctx context.Context,id,project,mode string)error{_,e:=s.DB.Exec(ctx,`INSERT INTO discovery_jobs(id,project_id,mode,status) VALUES($1,$2,$3,'queued')`,id,project,mode);return e}
func(s *Store)GetDiscoveryJob(ctx context.Context,id string)(map[string]any,error){r:=s.DB.QueryRow(ctx,`SELECT id,project_id,mode,status,created_at,updated_at FROM discovery_jobs WHERE id=$1`,id);var a,b,c,d string;var x,y time.Time;if e:=r.Scan(&a,&b,&c,&d,&x,&y);e!=nil{return nil,e};return map[string]any{"job_id":a,"project_id":b,"mode":c,"status":d,"created_at":x,"updated_at":y},nil}
func(s *Store)GetJobStatus(ctx context.Context,id string)(string,error){var status string;e:=s.DB.QueryRow(ctx,"SELECT status FROM discovery_jobs WHERE id=$1",id).Scan(&status);return status,e}
func(s *Store)SetJobStatus(ctx context.Context,id,status string)error{if status=="cancelled"{_,e:=s.DB.Exec(ctx,"UPDATE discovery_jobs SET status=$2,cancelled_at=now(),updated_at=now() WHERE id=$1 AND status NOT IN ('completed','failed','cancelled')",id,status);return e};_,e:=s.DB.Exec(ctx,`UPDATE discovery_jobs SET status=$2,updated_at=now() WHERE id=$1`,id,status);return e}
func(s *Store)UpsertCandidate(ctx context.Context,id,job,u,nu,domain,host,source,parent string,priority int,confidence float64)error{_,e:=s.DB.Exec(ctx,`INSERT INTO candidates(id,discovery_job_id,url,normalized_url,normalized_domain,normalized_host,source_type,parent_url,priority,confidence,status,next_attempt_at) VALUES($1,$2,$3,$4,$5,$6,$7,$8,$9,$10,'new',now()) ON CONFLICT(discovery_job_id,normalized_url) DO NOTHING`,id,job,u,nu,domain,host,source,parent,priority,confidence);return e}
func(s *Store)Candidates(ctx context.Context,job string)([]map[string]any,error){rows,e:=s.DB.Query(ctx,`SELECT id,url,normalized_url,normalized_domain,normalized_host,source_type,parent_url,priority,confidence,status,attempt_count,last_error,discovered_at FROM candidates WHERE discovery_job_id=$1 ORDER BY priority DESC,discovered_at`,job);if e!=nil{return nil,e};defer rows.Close();var out []map[string]any;for rows.Next(){var id,u,nu,d,h,src,parent,status,last string;var p,a int;var c float64;var at time.Time;if e=rows.Scan(&id,&u,&nu,&d,&h,&src,&parent,&p,&c,&status,&a,&last,&at);e!=nil{return nil,e};out=append(out,map[string]any{"id":id,"url":u,"normalized_url":nu,"normalized_domain":d,"normalized_host":h,"source_type":src,"parent_url":parent,"priority":p,"confidence":c,"status":status,"attempt_count":a,"last_error":last,"discovered_at":at})};return out,rows.Err()}
func(s *Store)ClaimCandidate(ctx context.Context,job string,lease time.Duration)(string,string,string,string,error){
 tx,e:=s.DB.Begin(ctx);if e!=nil{return "","","","",e};defer tx.Rollback()
 var id,u,domain,status string
 q:=`SELECT id,url,normalized_domain,status FROM candidates WHERE discovery_job_id=$1 AND ((status IN ('new','queued','failed_retryable') AND next_attempt_at<=now()) OR (status='processing' AND lease_until<now())) ORDER BY priority DESC,discovered_at FOR UPDATE SKIP LOCKED LIMIT 1`
 e=tx.QueryRow(ctx,q,job).Scan(&id,&u,&domain,&status);if e!=nil{return "","","","",e}
 if _,e=tx.Exec(ctx,"UPDATE candidates SET status='processing',attempt_count=attempt_count+1,processing_started_at=now(),lease_until=now()+$2::interval WHERE id=$1",id,lease.String());e!=nil{return "","","","",e}
 if e=tx.Commit(ctx);e!=nil{return "","","","",e};return id,u,domain,status,nil
}
func(s *Store)SetCandidateStatus(ctx context.Context,id,status,lastError string)error{_,e:=s.DB.Exec(ctx,`UPDATE candidates SET status=$2,last_error=$3,lease_until=NULL,completed_at=CASE WHEN $2 IN ('completed','failed_final') THEN now() ELSE completed_at END WHERE id=$1`,id,status,lastError);return e}
func(s *Store)ScheduleRetry(ctx context.Context,id,lastError string,attempt int)error{
 var n int
 if e:=s.DB.QueryRow(ctx,"SELECT attempt_count FROM candidates WHERE id=$1",id).Scan(&n);e!=nil{return e}
 if n>=3{return s.SetCandidateStatus(ctx,id,"failed_final",lastError)}
 delay:=time.Second*time.Duration(1<<(n-1));if delay>30*time.Second{delay=30*time.Second}
 _,e:=s.DB.Exec(ctx,`UPDATE candidates SET status='failed_retryable',last_error=$2,next_attempt_at=now()+$3::interval,lease_until=NULL WHERE id=$1`,id,lastError,delay.String());return e
}
func(s *Store)FinishJobIfEmpty(ctx context.Context,job string)error{var n int;if e:=s.DB.QueryRow(ctx,"SELECT count(*) FROM candidates WHERE discovery_job_id=$1 AND status IN ('new','queued','processing','failed_retryable')",job).Scan(&n);e!=nil{return e};if n==0{return s.SetJobStatus(ctx,job,"completed")};return nil}
func(s *Store)UpsertSignal(ctx context.Context,domain,typ,name,value string,confidence float64,evidence []string,source string)error{b,_:=json.Marshal(evidence);_,e:=s.DB.Exec(ctx,"INSERT INTO domain_signals(id,normalized_domain,type,name,value,confidence,evidence,source_url) VALUES(gen_random_uuid(),$1,$2,$3,$4,$5,$6::jsonb,$7) ON CONFLICT(normalized_domain,type,name) DO UPDATE SET value=EXCLUDED.value,confidence=EXCLUDED.confidence,evidence=EXCLUDED.evidence,source_url=EXCLUDED.source_url,observed_at=now()",domain,typ,name,value,confidence,string(b),source);return e}
var _=pgx.ErrNoRows
