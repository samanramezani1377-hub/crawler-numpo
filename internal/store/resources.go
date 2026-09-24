package store

import (
 "context"
 "fmt"
)

func (s *Store) ListJobResource(ctx context.Context, job, resource string, limit, offset int) ([]map[string]any, int, error) {
 if limit < 1 || limit > 200 { limit = 50 }
 if offset < 0 { offset = 0 }
 allowed := map[string]string{
  "domains": "domains",
  "hosts": "hosts",
  "pages": "pages",
  "technologies": "technologies",
  "contacts": "contacts",
  "business": "business_profiles",
  "social": "social_profiles",
  "classifications": "page_classifications",
  "probes": "domain_probes",
 }
 table, ok := allowed[resource]
 if !ok { return nil, 0, fmt.Errorf("unsupported resource") }
 var total int
 if err := s.DB.QueryRow(ctx, "SELECT count(*) FROM "+table+" t JOIN domains d ON "+joinDomain(resource)+" WHERE d.project_id=(SELECT project_id FROM discovery_jobs WHERE id=$1)", job).Scan(&total); err != nil { return nil, 0, err }
 q := resourceQuery(resource)
 rows, err := s.DB.Query(ctx, q, job, limit, offset)
 if err != nil { return nil, 0, err }
 defer rows.Close()
 var out []map[string]any
 for rows.Next() {
  switch resource {
  case "domains":
   var id, project, domain string
   if err=rows.Scan(&id,&project,&domain); err!=nil{return nil,0,err}
   out=append(out,map[string]any{"id":id,"project_id":project,"normalized_domain":domain})
  case "hosts":
   var id,domainID,host string
   if err=rows.Scan(&id,&domainID,&host);err!=nil{return nil,0,err}
   out=append(out,map[string]any{"id":id,"domain_id":domainID,"normalized_host":host})
  case "pages":
   var id,domainID,u,title,ct string; var status,depth int
   if err=rows.Scan(&id,&domainID,&u,&status,&title,&ct,&depth);err!=nil{return nil,0,err}
   out=append(out,map[string]any{"id":id,"domain_id":domainID,"normalized_url":u,"status":status,"title":title,"content_type":ct,"depth":depth})
  case "technologies":
   var id,domainID,name,version,source string;var conf float64
   if err=rows.Scan(&id,&domainID,&name,&version,&conf,&source);err!=nil{return nil,0,err}
   out=append(out,map[string]any{"id":id,"domain_id":domainID,"name":name,"version":version,"confidence":conf,"source_url":source})
  case "contacts":
   var id,domainID,typ,value,normalized,source string
   if err=rows.Scan(&id,&domainID,&typ,&value,&normalized,&source);err!=nil{return nil,0,err}
   out=append(out,map[string]any{"id":id,"domain_id":domainID,"type":typ,"value":value,"normalized_value":normalized,"source_url":source})
  case "business":
   var id,domainID,name,description,address,source string;var conf float64
   if err=rows.Scan(&id,&domainID,&name,&description,&address,&source,&conf);err!=nil{return nil,0,err}
   out=append(out,map[string]any{"id":id,"domain_id":domainID,"name":name,"description":description,"address":address,"source_url":source,"confidence":conf})
  case "social":
   var id,domainID,network,u,normalized,source string;var conf float64
   if err=rows.Scan(&id,&domainID,&network,&u,&normalized,&source,&conf);err!=nil{return nil,0,err}
   out=append(out,map[string]any{"id":id,"domain_id":domainID,"network":network,"url":u,"normalized_url":normalized,"source_url":source,"confidence":conf})
  case "classifications":
   var id,domainID,pageID,class,source string;var conf float64
   if err=rows.Scan(&id,&domainID,&pageID,&class,&conf,&source);err!=nil{return nil,0,err}
   out=append(out,map[string]any{"id":id,"domain_id":domainID,"page_id":pageID,"class":class,"confidence":conf,"source_url":source})
  case "probes":
   var id,hostID,status,version,dns,redirect,code,message string;var hs,https int;var response int64
   if err=rows.Scan(&id,&hostID,&status,&version,&dns,&hs,&https,&redirect,&response,&code,&message);err!=nil{return nil,0,err}
   out=append(out,map[string]any{"id":id,"host_id":hostID,"status":status,"probe_version":version,"dns_status":dns,"http_status":hs,"https_status":https,"redirect_target":redirect,"response_time_ms":response,"error_code":code,"error_message":message})
  }
 }
 return out,total,rows.Err()
}

func joinDomain(resource string) string {
 switch resource {
 case "domains": return "t.id=d.id"
 case "hosts","pages","technologies","contacts","business","social","classifications": return "t.domain_id=d.id"
 case "probes": return "t.host_id IN (SELECT id FROM hosts WHERE domain_id=d.id)"
 default: return "false"
 }
}

func resourceQuery(resource string) string {
 order := "t.id"
 switch resource {
 case "domains": return "SELECT t.id::text,t.project_id,t.normalized_domain FROM domains t WHERE t.project_id=(SELECT project_id FROM discovery_jobs WHERE id=$1) ORDER BY "+order+" LIMIT $2 OFFSET $3"
 case "hosts": return "SELECT t.id::text,t.domain_id::text,t.normalized_host FROM hosts t JOIN domains d ON t.domain_id=d.id WHERE d.project_id=(SELECT project_id FROM discovery_jobs WHERE id=$1) ORDER BY "+order+" LIMIT $2 OFFSET $3"
 case "pages": return "SELECT t.id::text,t.domain_id::text,t.normalized_url,t.status,t.title,t.content_type,t.depth FROM pages t JOIN domains d ON t.domain_id=d.id WHERE d.project_id=(SELECT project_id FROM discovery_jobs WHERE id=$1) ORDER BY "+order+" LIMIT $2 OFFSET $3"
 case "technologies": return "SELECT t.id::text,t.domain_id::text,t.name,t.version,t.confidence,t.source_url FROM technologies t JOIN domains d ON t.domain_id=d.id WHERE d.project_id=(SELECT project_id FROM discovery_jobs WHERE id=$1) ORDER BY "+order+" LIMIT $2 OFFSET $3"
 case "contacts": return "SELECT t.id::text,t.domain_id::text,t.type,t.value,t.normalized_value,t.source_url FROM contacts t JOIN domains d ON t.domain_id=d.id WHERE d.project_id=(SELECT project_id FROM discovery_jobs WHERE id=$1) ORDER BY "+order+" LIMIT $2 OFFSET $3"
 case "business": return "SELECT t.id::text,t.domain_id::text,t.name,t.description,t.address,t.source_url,t.confidence FROM business_profiles t JOIN domains d ON t.domain_id=d.id WHERE d.project_id=(SELECT project_id FROM discovery_jobs WHERE id=$1) ORDER BY "+order+" LIMIT $2 OFFSET $3"
 case "social": return "SELECT t.id::text,t.domain_id::text,t.network,t.url,t.normalized_url,t.source_url,t.confidence FROM social_profiles t JOIN domains d ON t.domain_id=d.id WHERE d.project_id=(SELECT project_id FROM discovery_jobs WHERE id=$1) ORDER BY "+order+" LIMIT $2 OFFSET $3"
 case "classifications": return "SELECT t.id::text,t.domain_id::text,t.page_id::text,t.class,t.confidence,t.source_url FROM page_classifications t JOIN domains d ON t.domain_id=d.id WHERE d.project_id=(SELECT project_id FROM discovery_jobs WHERE id=$1) ORDER BY "+order+" LIMIT $2 OFFSET $3"
 case "probes": return "SELECT t.id::text,t.host_id::text,t.status,t.probe_version,t.dns_status,t.http_status,t.https_status,t.redirect_target,t.response_time_ms,t.error_code,t.error_message FROM domain_probes t JOIN hosts h ON t.host_id=h.id JOIN domains d ON h.domain_id=d.id WHERE d.project_id=(SELECT project_id FROM discovery_jobs WHERE id=$1) ORDER BY "+order+" LIMIT $2 OFFSET $3"
 default: return ""
 }
}
