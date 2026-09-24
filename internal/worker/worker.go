package worker
import("context";"errors";"strings";"time";"github.com/google/uuid";"github.com/samanramezani1377-hub/crawler-numpo/internal/crawl";"github.com/samanramezani1377-hub/crawler-numpo/internal/detect";"github.com/samanramezani1377-hub/crawler-numpo/internal/probe";"github.com/samanramezani1377-hub/crawler-numpo/internal/policy";"github.com/samanramezani1377-hub/crawler-numpo/internal/store";"github.com/samanramezani1377-hub/crawler-numpo/internal/urlnorm")
type Worker struct{Store *store.Store;Crawler *crawl.Crawler;Prober *probe.Prober;MaxPages int;MaxCandidatesPerPage int;MaxDepth int;MaxURLs int;Lease time.Duration}
func(w *Worker)Run(ctx context.Context,job string)error{
 pages:=0;processed:=0
 for pages<w.MaxPages&&processed<w.MaxURLs{
  if ctx.Err()!=nil{return ctx.Err()}
  status,e:=w.Store.GetJobStatus(ctx,job);if e!=nil{return e};if status=="cancelled"{return nil};if status=="completed"||status=="failed"{return nil}
  id,raw,domain,_,depth,e:=w.Store.ClaimCandidate(ctx,job,w.Lease);processed++
  if e!=nil{if errors.Is(e,context.Canceled)||errors.Is(e,context.DeadlineExceeded){return e};return w.Store.FinishJobIfEmpty(ctx,job)}
  jobInfo,_:=w.Store.GetDiscoveryJob(ctx,job);projectID,_:=jobInfo["project_id"].(string);domainID,_:=w.Store.EnsureDomain(ctx,projectID,domain);hostID,_:=w.Store.EnsureHost(ctx,domainID,strings.ToLower(domain));pr,e:=w.Prober.Probe(ctx,domain);_ = w.Store.UpsertProbe(ctx,hostID,pr.Status,"v1",pr.DNSStatus,pr.HTTPStatus,pr.HTTPSStatus,pr.RedirectTarget,pr.ResponseTimeMS,pr.ErrorCode,pr.ErrorMessage)
  if e!=nil{_ = w.Store.SetCandidateStatus(ctx,id,"completed",pr.ErrorMessage);_ = w.Store.UpsertSignal(ctx,domain,"probe","active","false",0.9,nil,raw);continue}
  _ = w.Store.UpsertSignal(ctx,domain,"probe","active","true",1,nil,raw)
  p,e:=w.Crawler.Fetch(ctx,raw)
  if e!=nil{_ = w.Store.ScheduleRetry(ctx,id,e.Error(),0);continue}
  pages++
  _ = w.Store.UpsertPage(ctx,domainID,p.URL,p.Status,p.Title,p.ContentType,depth)
  tech:=detect.Technologies(p.Body,p.URL);wp:=false;wc:=false
  for _,t:=range tech{_ = w.Store.UpsertTechnology(ctx,domainID,t.Name,t.Version,t.Confidence,t.Evidence,t.URL);_ = w.Store.UpsertSignal(ctx,domain,"technology",t.Name,t.Version,t.Confidence,t.Evidence,t.URL);if t.Name=="WordPress"{wp=true};if t.Name=="WooCommerce"{wc=true}}
  for _,ct:=range detect.Contacts(p.Body,p.URL){_ = w.Store.UpsertSignal(ctx,domain,"contact",ct.Type,ct.NormalizedValue,1,nil,ct.URL)}
  _ = w.Store.SetCandidateStatus(ctx,id,"completed","")
  if !wp&&!wc{continue}
  for i,link:=range p.Links{if i>=w.MaxCandidatesPerPage||depth>=w.MaxDepth{break};u,e:=urlnorm.URL(link);if e!=nil{continue};if policy.ValidateURL(link)!=nil{continue};if !strings.EqualFold(u.Hostname(),domain){continue};d,_:=urlnorm.Domain(link);_ = w.Store.UpsertCandidate(ctx,uuid.NewString(),job,link,u.String(),d,u.Hostname(),"deep_crawl",raw,50,0.8)}
 }
 return w.Store.FinishJobIfEmpty(ctx,job)
}
