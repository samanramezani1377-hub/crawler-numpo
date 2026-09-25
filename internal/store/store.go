package store
import("context";"encoding/json";"math/rand/v2";"time";"github.com/jackc/pgx/v5";"github.com/jackc/pgx/v5/pgxpool")
type Store struct{DB *pgxpool.Pool}
func New(ctx context.Context,dsn string)(*Store,error){db,e:=pgxpool.New(ctx,dsn);if e!=nil{return nil,e};if e=db.Ping(ctx);e!=nil{db.Close();return nil,e};return &Store{db},nil}
func(s *Store)Close(){s.DB.Close()}
func(s *Store)CreateDiscoveryJob(ctx context.Context,id,project,mode string)error{return s.CreateDiscoveryJobWithConfig(ctx,id,project,mode,map[string]any{})}
func(s *Store)CreateDiscoveryJobWithConfig(ctx context.Context,id,project,mode string,cfg map[string]any)error{ b,_:=json.Marshal(cfg); maxPages,maxURLs,maxDepth,maxCandidates:=100,500,3,50; if v,ok:=cfg["max_pages"].(int);ok&&v>0{maxPages=v}; if v,ok:=cfg["max_urls"].(int);ok&&v>0{maxURLs=v}; if v,ok:=cfg["max_depth"].(int);ok&&v>=0{maxDepth=v}; if v,ok:=cfg["max_candidates_per_page"].(int);ok&&v>0{maxCandidates=v}; _,e:=s.DB.Exec(ctx,"INSERT INTO discovery_jobs(id,project_id,mode,status,config,max_pages,max_urls,max_depth,max_candidates_per_page) VALUES($1,$2,$3,'queued',$4::jsonb,$5,$6,$7,$8)",id,project,mode,string(b),maxPages,maxURLs,maxDepth,maxCandidates);return e}
func(s *Store)GetDiscoveryJob(ctx context.Context,id string)(map[string]any,error){r:=s.DB.QueryRow(ctx,"SELECT id,project_id,mode,status,created_at,updated_at,config,max_pages,max_urls,max_depth,max_candidates_per_page,processed_pages,processed_urls FROM discovery_jobs WHERE id=$1",id);var a,b,c,d string;var x,y time.Time;var cfg []byte;var mp,mu,md,mc,pp,pu int;if e:=r.Scan(&a,&b,&c,&d,&x,&y,&cfg,&mp,&mu,&md,&mc,&pp,&pu);e!=nil{return nil,e};var config any;_ = json.Unmarshal(cfg,&config);return map[string]any{"job_id":a,"project_id":b,"mode":c,"status":d,"created_at":x,"updated_at":y,"config":config,"max_pages":mp,"max_urls":mu,"max_depth":md,"max_candidates_per_page":mc,"processed_pages":pp,"processed_urls":pu},nil}
func(s *Store)GetJobStatus(ctx context.Context,id string)(string,error){var status string;e:=s.DB.QueryRow(ctx,"SELECT status FROM discovery_jobs WHERE id=$1",id).Scan(&status);return status,e}
func(s *Store)SetJobStatus(ctx context.Context,id,status string)error{if status=="cancelled"{_,e:=s.DB.Exec(ctx,"UPDATE discovery_jobs SET status=$2,cancelled_at=now(),updated_at=now() WHERE id=$1 AND status NOT IN ('completed','failed','cancelled')",id,status);return e};_,e:=s.DB.Exec(ctx,`UPDATE discovery_jobs SET status=$2,updated_at=now() WHERE id=$1 AND status NOT IN ('completed','failed','cancelled')`,id,status);return e}
func(s *Store)UpsertCandidate(ctx context.Context,id,job,u,nu,domain,host,source,parent string,priority int,confidence float64)error{_,e:=s.DB.Exec(ctx,`INSERT INTO candidates(id,discovery_job_id,url,normalized_url,normalized_domain,normalized_host,source_type,parent_url,priority,confidence,status,next_attempt_at) VALUES($1,$2,$3,$4,$5,$6,$7,$8,$9,$10,'new',now()) ON CONFLICT(discovery_job_id,normalized_url) DO NOTHING`,id,job,u,nu,domain,host,source,parent,priority,confidence);return e}
func(s *Store)UpsertCandidateDepth(ctx context.Context,id,job,u,nu,domain,host,source,parent string,priority int,confidence float64,depth int)error{_,e:=s.DB.Exec(ctx,`INSERT INTO candidates(id,discovery_job_id,url,normalized_url,normalized_domain,normalized_host,source_type,parent_url,priority,confidence,status,next_attempt_at,depth) VALUES($1,$2,$3,$4,$5,$6,$7,$8,$9,$10,'new',now(),$11) ON CONFLICT(discovery_job_id,normalized_url) DO NOTHING`,id,job,u,nu,domain,host,source,parent,priority,confidence,depth);return e}
func(s *Store)Candidates(ctx context.Context,job string)([]map[string]any,error){rows,e:=s.DB.Query(ctx,`SELECT id,url,normalized_url,normalized_domain,normalized_host,source_type,parent_url,priority,confidence,status,attempt_count,last_error,discovered_at FROM candidates WHERE discovery_job_id=$1 ORDER BY priority DESC,discovered_at`,job);if e!=nil{return nil,e};defer rows.Close();var out []map[string]any;for rows.Next(){var id,u,nu,d,h,src,parent,status,last string;var p,a int;var c float64;var at time.Time;if e=rows.Scan(&id,&u,&nu,&d,&h,&src,&parent,&p,&c,&status,&a,&last,&at);e!=nil{return nil,e};out=append(out,map[string]any{"id":id,"url":u,"normalized_url":nu,"normalized_domain":d,"normalized_host":h,"source_type":src,"parent_url":parent,"priority":p,"confidence":c,"status":status,"attempt_count":a,"last_error":last,"discovered_at":at})};return out,rows.Err()}
func(s *Store)ClaimCandidate(ctx context.Context,job string,lease time.Duration)(string,string,string,string,int,error){
 tx,e:=s.DB.Begin(ctx);if e!=nil{return "","","","",0,e};defer tx.Rollback(ctx)
 var id,u,domain,status string;var depth int
 q:=`SELECT id,url,normalized_domain,status,depth FROM candidates WHERE discovery_job_id=$1 AND ((status IN ('new','queued','failed_retryable') AND next_attempt_at<=now()) OR (status='processing' AND lease_until<now())) ORDER BY priority DESC,discovered_at FOR UPDATE SKIP LOCKED LIMIT 1`
 e=tx.QueryRow(ctx,q,job).Scan(&id,&u,&domain,&status,&depth);if e!=nil{return "","","","",0,e}
 if _,e=tx.Exec(ctx,"UPDATE candidates SET status='processing',attempt_count=attempt_count+1,processing_started_at=now(),lease_until=now()+($2 * interval '1 millisecond') WHERE id=$1",id,lease.Milliseconds());e!=nil{return "","","","",0,e}
 if e=tx.Commit(ctx);e!=nil{return "","","","",0,e};return id,u,domain,status,depth,nil
}
func(s *Store)SetCandidateStatus(ctx context.Context,id,status,lastError string)error{_,e:=s.DB.Exec(ctx,`UPDATE candidates SET status=$2,last_error=$3,lease_until=NULL,completed_at=CASE WHEN $2 IN ('completed','failed_final') THEN now() ELSE completed_at END WHERE id=$1`,id,status,lastError);return e}
func(s *Store)ScheduleRetry(ctx context.Context,id,lastError string,attempt int)error{
 var n int
 var job string
 if e:=s.DB.QueryRow(ctx,"SELECT attempt_count,discovery_job_id::text FROM candidates WHERE id=$1",id).Scan(&n,&job);e!=nil{return e}
 if n>=3{
  tx,e:=s.DB.Begin(ctx);if e!=nil{return e};defer tx.Rollback(ctx)
  if _,e=tx.Exec(ctx,`INSERT INTO candidate_dead_letters(candidate_id,discovery_job_id,attempts,error) VALUES($1,$2,$3,$4) ON CONFLICT(candidate_id) DO UPDATE SET attempts=EXCLUDED.attempts,error=EXCLUDED.error,failed_at=now()`,id,job,n,lastError);e!=nil{return e}
  if _,e=tx.Exec(ctx,`UPDATE candidates SET status='failed_final',last_error=$2,lease_until=NULL,completed_at=now() WHERE id=$1`,id,lastError);e!=nil{return e}
  return tx.Commit(ctx)
 }
 base:=time.Second*time.Duration(1<<(n-1));if base>30*time.Second{base=30*time.Second}
 delay:=time.Duration(rand.Int64N(int64(base)+1))
 _,e:=s.DB.Exec(ctx,"UPDATE candidates SET status='failed_retryable',last_error=$2,next_attempt_at=now()+($3 * interval '1 millisecond'),lease_until=NULL WHERE id=$1",id,lastError,delay.Milliseconds());return e
}
func(s *Store)GetJobRuntimeMetrics(ctx context.Context,job string)(map[string]any,error){
 var candidates,queued,processing,retryable,completed,failed int
 if e:=s.DB.QueryRow(ctx,"SELECT count(*) FROM candidates WHERE discovery_job_id=$1",job).Scan(&candidates);e!=nil{return nil,e}
 if e:=s.DB.QueryRow(ctx,"SELECT count(*) FROM candidates WHERE discovery_job_id=$1 AND status IN ('new','queued')",job).Scan(&queued);e!=nil{return nil,e}
 if e:=s.DB.QueryRow(ctx,"SELECT count(*) FROM candidates WHERE discovery_job_id=$1 AND status='processing'",job).Scan(&processing);e!=nil{return nil,e}
 if e:=s.DB.QueryRow(ctx,"SELECT count(*) FROM candidates WHERE discovery_job_id=$1 AND status='failed_retryable'",job).Scan(&retryable);e!=nil{return nil,e}
 if e:=s.DB.QueryRow(ctx,"SELECT count(*) FROM candidates WHERE discovery_job_id=$1 AND status='completed'",job).Scan(&completed);e!=nil{return nil,e}
 if e:=s.DB.QueryRow(ctx,"SELECT count(*) FROM candidates WHERE discovery_job_id=$1 AND status='failed_final'",job).Scan(&failed);e!=nil{return nil,e}
 var pages,domains,technologies,contacts,business,social,classifications,probes,errors int
 var project string
 if e:=s.DB.QueryRow(ctx,"SELECT project_id FROM discovery_jobs WHERE id=$1",job).Scan(&project);e!=nil{return nil,e}
 queries:=[]struct{dest *int;q string}{
  {&domains,"SELECT count(*) FROM domains WHERE project_id=$1"},
  {&pages,"SELECT count(*) FROM pages p JOIN domains d ON p.domain_id=d.id WHERE d.project_id=$1"},
  {&technologies,"SELECT count(*) FROM technologies t JOIN domains d ON t.domain_id=d.id WHERE d.project_id=$1"},
  {&contacts,"SELECT count(*) FROM contacts t JOIN domains d ON t.domain_id=d.id WHERE d.project_id=$1"},
  {&business,"SELECT count(*) FROM business_profiles t JOIN domains d ON t.domain_id=d.id WHERE d.project_id=$1"},
  {&social,"SELECT count(*) FROM social_profiles t JOIN domains d ON t.domain_id=d.id WHERE d.project_id=$1"},
  {&classifications,"SELECT count(*) FROM page_classifications t JOIN domains d ON t.domain_id=d.id WHERE d.project_id=$1"},
  {&probes,"SELECT count(*) FROM domain_probes p JOIN hosts h ON p.host_id=h.id JOIN domains d ON h.domain_id=d.id WHERE d.project_id=$1"},
  {&errors,"SELECT count(*) FROM job_errors WHERE job_id=$1"},
 }
 for _,x:=range queries{arg:=any(project);if x.dest==&errors{arg=job};if e:=s.DB.QueryRow(ctx,x.q,arg).Scan(x.dest);e!=nil{return nil,e}}
 var currentURL string
 _=s.DB.QueryRow(ctx,"SELECT url FROM candidates WHERE discovery_job_id=$1 AND status='processing' ORDER BY processing_started_at DESC NULLS LAST LIMIT 1",job).Scan(&currentURL)
 var lastError,lastErrorAt string
 _=s.DB.QueryRow(ctx,"SELECT message,created_at::text FROM job_errors WHERE job_id=$1 ORDER BY created_at DESC LIMIT 1",job).Scan(&lastError,&lastErrorAt)
 var lastCandidate,lastCandidateAt string
 _=s.DB.QueryRow(ctx,"SELECT url,discovered_at::text FROM candidates WHERE discovery_job_id=$1 ORDER BY discovered_at DESC LIMIT 1",job).Scan(&lastCandidate,&lastCandidateAt)
 return map[string]any{
  "candidates":candidates,"queued":queued,"processing":processing,"retryable":retryable,"completed":completed,"failed":failed,
  "domains":domains,"pages":pages,"technologies":technologies,"contacts":contacts,"business":business,"social":social,"classifications":classifications,"probes":probes,"errors":errors,
  "current_url":currentURL,"last_error":lastError,"last_error_at":lastErrorAt,"last_candidate":lastCandidate,"last_candidate_at":lastCandidateAt,
 },nil
}

func(s *Store)FinishJobIfEmpty(ctx context.Context,job string)error{var n int;if e:=s.DB.QueryRow(ctx,"SELECT count(*) FROM candidates WHERE discovery_job_id=$1 AND status IN ('new','queued','processing','failed_retryable')",job).Scan(&n);e!=nil{return e};if n==0{return s.SetJobStatus(ctx,job,"completed")};return nil}
func(s *Store)EnsureDomain(ctx context.Context,project,domain string)(string,error){_,e:=s.DB.Exec(ctx,`INSERT INTO domains(id,project_id,normalized_domain) VALUES(gen_random_uuid(),$1,$2) ON CONFLICT(project_id,normalized_domain) DO NOTHING`,project,domain);if e!=nil{return "",e};var out string;e=s.DB.QueryRow(ctx,`SELECT id::text FROM domains WHERE project_id=$1 AND normalized_domain=$2`,project,domain).Scan(&out);return out,e}
func(s *Store)EnsureHost(ctx context.Context,domainID,host string)(string,error){_,e:=s.DB.Exec(ctx,`INSERT INTO hosts(id,domain_id,normalized_host) VALUES(gen_random_uuid(),$1,$2) ON CONFLICT(domain_id,normalized_host) DO NOTHING`,domainID,host);if e!=nil{return "",e};var out string;e=s.DB.QueryRow(ctx,`SELECT id::text FROM hosts WHERE domain_id=$1 AND normalized_host=$2`,domainID,host).Scan(&out);return out,e}
func(s *Store)UpsertPage(ctx context.Context,domainID,url string,status int,title,contentType string,depth int)(string,error){_,e:=s.DB.Exec(ctx,`INSERT INTO pages(id,domain_id,normalized_url,status,title,content_type,depth,fetched_at) VALUES(gen_random_uuid(),$1,$2,$3,$4,$5,$6,now()) ON CONFLICT(domain_id,normalized_url) DO UPDATE SET status=EXCLUDED.status,title=EXCLUDED.title,content_type=EXCLUDED.content_type,depth=EXCLUDED.depth,fetched_at=now()`,domainID,url,status,title,contentType,depth);if e!=nil{return "",e};var id string;e=s.DB.QueryRow(ctx,`SELECT id::text FROM pages WHERE domain_id=$1 AND normalized_url=$2`,domainID,url).Scan(&id);return id,e}
func(s *Store)UpsertTechnology(ctx context.Context,domainID,name,version string,confidence float64,evidence []string,source string)error{b,_:=json.Marshal(evidence);_,e:=s.DB.Exec(ctx,`INSERT INTO technologies(id,domain_id,name,version,confidence,evidence,source_url) VALUES(gen_random_uuid(),$1,$2,$3,$4,$5::jsonb,$6) ON CONFLICT(domain_id,name) DO UPDATE SET version=EXCLUDED.version,confidence=EXCLUDED.confidence,evidence=EXCLUDED.evidence,source_url=EXCLUDED.source_url,detected_at=now()`,domainID,name,version,confidence,string(b),source);return e}
func(s *Store)UpsertContact(ctx context.Context,domainID,typ,value,normalized,source string)error{_,e:=s.DB.Exec(ctx,`INSERT INTO contacts(id,domain_id,type,value,normalized_value,source_url) VALUES(gen_random_uuid(),$1,$2,$3,$4,$5) ON CONFLICT(domain_id,type,normalized_value) DO UPDATE SET value=EXCLUDED.value,source_url=EXCLUDED.source_url,detected_at=now()`,domainID,typ,value,normalized,source);return e}
func(s *Store)UpsertProbe(ctx context.Context,hostID,status,version,dns string,httpStatus,httpsStatus int,redirect string,response int64,code,message string)error{_,e:=s.DB.Exec(ctx,`INSERT INTO domain_probes(id,host_id,status,probe_version,dns_status,http_status,https_status,redirect_target,response_time_ms,error_code,error_message,last_probe_at) VALUES(gen_random_uuid(),$1,$2,$3,$4,$5,$6,$7,$8,$9,$10,now()) ON CONFLICT(host_id) DO UPDATE SET status=EXCLUDED.status,probe_version=EXCLUDED.probe_version,dns_status=EXCLUDED.dns_status,http_status=EXCLUDED.http_status,https_status=EXCLUDED.https_status,redirect_target=EXCLUDED.redirect_target,response_time_ms=EXCLUDED.response_time_ms,error_code=EXCLUDED.error_code,error_message=EXCLUDED.error_message,last_probe_at=now()`,hostID,status,version,dns,httpStatus,httpsStatus,redirect,response,code,message);return e}
func(s *Store)UpsertSignal(ctx context.Context,domain,typ,name,value string,confidence float64,evidence []string,source string)error{b,_:=json.Marshal(evidence);_,e:=s.DB.Exec(ctx,"INSERT INTO domain_signals(id,normalized_domain,type,name,value,confidence,evidence,source_url) VALUES(gen_random_uuid(),$1,$2,$3,$4,$5,$6::jsonb,$7) ON CONFLICT(normalized_domain,type,name) DO UPDATE SET value=EXCLUDED.value,confidence=EXCLUDED.confidence,evidence=EXCLUDED.evidence,source_url=EXCLUDED.source_url,observed_at=now()",domain,typ,name,value,confidence,string(b),source);return e}
var _=pgx.ErrNoRows

func(s *Store)ConsumeURLBudget(ctx context.Context,job string)(bool,error){var ok bool;e:=s.DB.QueryRow(ctx,"UPDATE discovery_jobs SET processed_urls=processed_urls+1,updated_at=now() WHERE id=$1 AND processed_urls<max_urls AND status NOT IN ('cancelled','completed','failed') RETURNING true",job).Scan(&ok);if e!=nil{if e==pgx.ErrNoRows{return false,nil};return false,e};return ok,nil}
func(s *Store)ConsumePageBudget(ctx context.Context,job,domainID string)(bool,error){tx,e:=s.DB.Begin(ctx);if e!=nil{return false,e};defer tx.Rollback(ctx);var ok bool;e=tx.QueryRow(ctx,"UPDATE discovery_jobs j SET processed_pages=j.processed_pages+1,updated_at=now() FROM domains d WHERE j.id=$1 AND d.id=$2 AND j.processed_pages<j.max_pages AND j.status NOT IN ('cancelled','completed','failed') AND d.crawl_pages<j.max_pages RETURNING true",job,domainID).Scan(&ok);if e!=nil{if e==pgx.ErrNoRows{return false,nil};return false,e};if _,e=tx.Exec(ctx,"UPDATE domains SET crawl_pages=crawl_pages+1,updated_at=now() WHERE id=$1",domainID);e!=nil{return false,e};if e=tx.Commit(ctx);e!=nil{return false,e};return ok,nil}
func(s *Store)RecordJobError(ctx context.Context,job,category,code,message string,retryable bool,attempt int)error{_,e:=s.DB.Exec(ctx,"INSERT INTO job_errors(id,job_id,category,code,message,retryable,attempt) VALUES(gen_random_uuid(),$1,$2,$3,$4,$5,$6)",job,category,code,message,retryable,attempt);return e}
func(s *Store)ProbeFresh(ctx context.Context,hostID string,ttl time.Duration)(bool,bool,error){var last time.Time;var status string;e:=s.DB.QueryRow(ctx,"SELECT last_probe_at,status FROM domain_probes WHERE host_id=$1",hostID).Scan(&last,&status);if e!=nil{if e==pgx.ErrNoRows{return false,false,nil};return false,false,e};return time.Since(last)<ttl,status=="active",nil}

func(s *Store)ScheduleRetryAfter(ctx context.Context,id,lastError string,delay time.Duration)error{var n int;var job string;if e:=s.DB.QueryRow(ctx,"SELECT attempt_count,discovery_job_id::text FROM candidates WHERE id=$1",id).Scan(&n,&job);e!=nil{return e};if n>=3{tx,e:=s.DB.Begin(ctx);if e!=nil{return e};defer tx.Rollback(ctx);if _,e=tx.Exec(ctx,`INSERT INTO candidate_dead_letters(candidate_id,discovery_job_id,attempts,error) VALUES($1,$2,$3,$4) ON CONFLICT(candidate_id) DO UPDATE SET attempts=EXCLUDED.attempts,error=EXCLUDED.error,failed_at=now()`,id,job,n,lastError);e!=nil{return e};if _,e=tx.Exec(ctx,`UPDATE candidates SET status='failed_final',last_error=$2,lease_until=NULL,completed_at=now() WHERE id=$1`,id,lastError);e!=nil{return e};return tx.Commit(ctx)};if delay<time.Second{delay=time.Second};if delay>30*time.Second{delay=30*time.Second};_,e:=s.DB.Exec(ctx,"UPDATE candidates SET status='failed_retryable',last_error=$2,next_attempt_at=now()+($3 * interval '1 millisecond'),lease_until=NULL WHERE id=$1",id,lastError,delay.Milliseconds());return e}

func(s *Store)AcquireDomainRateLimit(ctx context.Context,domain string,interval time.Duration)error{
 if interval<=0{return nil}
 for{
  tx,e:=s.DB.Begin(ctx);if e!=nil{return e}
  var last time.Time
  e=tx.QueryRow(ctx,`INSERT INTO domain_rate_limits(normalized_domain,last_started_at) VALUES($1,'epoch') ON CONFLICT(normalized_domain) DO UPDATE SET normalized_domain=EXCLUDED.normalized_domain RETURNING last_started_at`,domain).Scan(&last)
  if e!=nil{_ = tx.Rollback(ctx);return e}
  now:=time.Now()
  wait:=interval-now.Sub(last)
  if wait<=0{
   if _,e=tx.Exec(ctx,`UPDATE domain_rate_limits SET last_started_at=now() WHERE normalized_domain=$1`,domain);e!=nil{_ = tx.Rollback(ctx);return e}
   if e=tx.Commit(ctx);e!=nil{return e}
   return nil
  }
  _=tx.Rollback(ctx)
  timer:=time.NewTimer(wait)
  select{case<-ctx.Done():timer.Stop();return ctx.Err();case<-timer.C:}
 }
}

func(s *Store)UpsertPageClassification(ctx context.Context,domainID,pageID,class string,confidence float64,evidence []string,source string)error{b,_:=json.Marshal(evidence);_,e:=s.DB.Exec(ctx,`INSERT INTO page_classifications(id,domain_id,page_id,class,confidence,evidence,source_url) VALUES(gen_random_uuid(),$1,$2,$3,$4,$5::jsonb,$6) ON CONFLICT(domain_id,page_id,class) DO UPDATE SET confidence=EXCLUDED.confidence,evidence=EXCLUDED.evidence,source_url=EXCLUDED.source_url,detected_at=now()`,domainID,pageID,class,confidence,string(b),source);return e}
func(s *Store)UpsertBusiness(ctx context.Context,domainID,name,description,address,source string,confidence float64)error{_,e:=s.DB.Exec(ctx,`INSERT INTO business_profiles(id,domain_id,name,description,address,source_url,confidence) VALUES(gen_random_uuid(),$1,$2,$3,$4,$5,$6) ON CONFLICT(domain_id) DO UPDATE SET name=CASE WHEN EXCLUDED.name<>'' THEN EXCLUDED.name ELSE business_profiles.name END,address=CASE WHEN EXCLUDED.address<>'' THEN EXCLUDED.address ELSE business_profiles.address END,source_url=EXCLUDED.source_url,confidence=GREATEST(business_profiles.confidence,EXCLUDED.confidence),detected_at=now()`,domainID,name,description,address,source,confidence);return e}
func(s *Store)UpsertSocial(ctx context.Context,domainID,network,url,normalized,source string,confidence float64)error{_,e:=s.DB.Exec(ctx,`INSERT INTO social_profiles(id,domain_id,network,url,normalized_url,source_url,confidence) VALUES(gen_random_uuid(),$1,$2,$3,$4,$5,$6) ON CONFLICT(domain_id,network,normalized_url) DO UPDATE SET url=EXCLUDED.url,source_url=EXCLUDED.source_url,confidence=GREATEST(social_profiles.confidence,EXCLUDED.confidence),detected_at=now()`,domainID,network,url,normalized,source,confidence);return e}

func(s *Store)JobErrors(ctx context.Context,job string,limit,offset int)([]map[string]any,error){if limit<1||limit>200{limit=50};if offset<0{offset=0};rows,e:=s.DB.Query(ctx,`SELECT category,code,message,retryable,attempt,created_at FROM job_errors WHERE job_id=$1 ORDER BY created_at DESC LIMIT $2 OFFSET $3`,job,limit,offset);if e!=nil{return nil,e};defer rows.Close();var out []map[string]any;for rows.Next(){var cat,code,msg string;var retry bool;var attempt int;var at time.Time;if e=rows.Scan(&cat,&code,&msg,&retry,&attempt,&at);e!=nil{return nil,e};out=append(out,map[string]any{"category":cat,"code":code,"message":msg,"retryable":retry,"attempt":attempt,"created_at":at})};return out,rows.Err()}

func(s *Store)CandidatesPage(ctx context.Context,job string,limit,offset int)([]map[string]any,int,error){if limit<1||limit>200{limit=50};if offset<0{offset=0};var total int;if e:=s.DB.QueryRow(ctx,"SELECT count(*) FROM candidates WHERE discovery_job_id=$1",job).Scan(&total);e!=nil{return nil,0,e};rows,e:=s.DB.Query(ctx,`SELECT id,url,normalized_url,normalized_domain,normalized_host,source_type,parent_url,priority,confidence,status,attempt_count,last_error,discovered_at FROM candidates WHERE discovery_job_id=$1 ORDER BY priority DESC,discovered_at LIMIT $2 OFFSET $3`,job,limit,offset);if e!=nil{return nil,0,e};defer rows.Close();var out []map[string]any;for rows.Next(){var id,u,nu,d,h,src,parent,status,last string;var p,a int;var conf float64;var at time.Time;if e=rows.Scan(&id,&u,&nu,&d,&h,&src,&parent,&p,&conf,&status,&a,&last,&at);e!=nil{return nil,0,e};out=append(out,map[string]any{"id":id,"url":u,"normalized_url":nu,"normalized_domain":d,"normalized_host":h,"source_type":src,"parent_url":parent,"priority":p,"confidence":conf,"status":status,"attempt_count":a,"last_error":last,"discovered_at":at})};return out,total,rows.Err()}
