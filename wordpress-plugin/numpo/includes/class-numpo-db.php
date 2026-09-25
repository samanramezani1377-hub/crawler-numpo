<?php
if(!defined('ABSPATH')) exit;

class Numpo_DB {
 private static $ready=false;
 public static function tables(){
  global $wpdb;
  $p=$wpdb->prefix.'numpo_';
  return [
   'jobs'=>$p.'jobs','candidates'=>$p.'candidates','domains'=>$p.'domains','pages'=>$p.'pages',
   'technologies'=>$p.'technologies','contacts'=>$p.'contacts','facts'=>$p.'facts','errors'=>$p.'errors','memory'=>$p.'crawl_memory'
  ];
 }
 public static function install(){
  if(self::$ready)return;
  global $wpdb;
  require_once ABSPATH.'wp-admin/includes/upgrade.php';
  $t=self::tables();$charset=$wpdb->get_charset_collate();
  $sql=[];
  $sql[]="CREATE TABLE {$t['memory']} (
   id varchar(64) NOT NULL, project_id varchar(191) NOT NULL, normalized_url text NOT NULL,
   url_hash char(64) NOT NULL, normalized_domain varchar(191) NOT NULL,
   first_seen_at datetime NOT NULL, last_seen_at datetime NOT NULL, last_crawled_at datetime NULL,
   crawl_count int unsigned NOT NULL DEFAULT 0, content_hash char(64) NULL,
   next_crawl_at datetime NULL, status varchar(30) NOT NULL DEFAULT 'discovered',
   PRIMARY KEY(id), UNIQUE KEY project_url(project_id,url_hash), KEY project_next(project_id,next_crawl_at),
   KEY project_domain(project_id,normalized_domain)
  ) $charset;";
  $sql[]="CREATE TABLE {$t['jobs']} (
   id varchar(64) NOT NULL, project_id varchar(191) NOT NULL, mode varchar(20) NOT NULL,
   status varchar(30) NOT NULL DEFAULT 'queued', config longtext NULL, max_pages int unsigned NOT NULL DEFAULT 100,
   max_urls int unsigned NOT NULL DEFAULT 500, max_depth int unsigned NOT NULL DEFAULT 3,
   max_candidates_per_page int unsigned NOT NULL DEFAULT 50, processed_pages int unsigned NOT NULL DEFAULT 0,
   processed_urls int unsigned NOT NULL DEFAULT 0, created_at datetime NOT NULL, updated_at datetime NOT NULL,
   started_at datetime NULL, completed_at datetime NULL, cancelled_at datetime NULL,
   PRIMARY KEY(id), KEY status(status), KEY project_id(project_id)
  ) $charset;";
  $sql[]="CREATE TABLE {$t['candidates']} (
   id varchar(64) NOT NULL, job_id varchar(64) NOT NULL, url text NOT NULL, normalized_url text NOT NULL,
   normalized_domain varchar(191) NOT NULL, normalized_host varchar(191) NOT NULL, source_type varchar(50) NOT NULL,
   parent_url text NULL, priority int NOT NULL DEFAULT 0, confidence double NOT NULL DEFAULT 0,
   status varchar(30) NOT NULL DEFAULT 'new', attempt_count int unsigned NOT NULL DEFAULT 0,
   last_error text NULL, depth int unsigned NOT NULL DEFAULT 0, discovered_at datetime NOT NULL,
   next_attempt_at datetime NULL, completed_at datetime NULL, processing_started_at datetime NULL,
   PRIMARY KEY(id), KEY job_status(job_id,status), KEY job_url(job_id(32),normalized_domain(100))
  ) $charset;";
  $sql[]="CREATE TABLE {$t['domains']} (
   id varchar(64) NOT NULL, project_id varchar(191) NOT NULL, normalized_domain varchar(191) NOT NULL,
   created_at datetime NOT NULL, updated_at datetime NOT NULL, crawl_pages int unsigned NOT NULL DEFAULT 0,
   PRIMARY KEY(id), UNIQUE KEY project_domain(project_id,normalized_domain)
  ) $charset;";
  $sql[]="CREATE TABLE {$t['pages']} (
   id varchar(64) NOT NULL, domain_id varchar(64) NOT NULL, normalized_url text NOT NULL, url_hash char(64) NOT NULL,
   status int NOT NULL DEFAULT 0, title text NULL, content_type varchar(191) NULL, depth int unsigned NOT NULL DEFAULT 0,
   fetched_at datetime NULL, PRIMARY KEY(id), UNIQUE KEY domain_url(domain_id,url_hash)
  ) $charset;";
  $sql[]="CREATE TABLE {$t['technologies']} (
   id varchar(64) NOT NULL, domain_id varchar(64) NOT NULL, name varchar(191) NOT NULL, version varchar(100) NOT NULL DEFAULT '',
   confidence double NOT NULL DEFAULT 0, evidence longtext NULL, source_url text NULL, detected_at datetime NOT NULL,
   PRIMARY KEY(id), UNIQUE KEY domain_name(domain_id,name)
  ) $charset;";
  $sql[]="CREATE TABLE {$t['contacts']} (
   id varchar(64) NOT NULL, domain_id varchar(64) NOT NULL, type varchar(30) NOT NULL, value text NOT NULL,
   normalized_value varchar(191) NOT NULL, source_url text NULL, detected_at datetime NOT NULL,
   PRIMARY KEY(id), UNIQUE KEY domain_contact(domain_id,type,normalized_value)
  ) $charset;";
  $sql[]="CREATE TABLE {$t['facts']} (
   id varchar(64) NOT NULL, job_id varchar(64) NOT NULL, domain_id varchar(64) NOT NULL,
   type varchar(40) NOT NULL, subtype varchar(60) NOT NULL DEFAULT '', value text NOT NULL,
   normalized_value varchar(255) NOT NULL DEFAULT '', source_url text NULL, confidence double NOT NULL DEFAULT 0,
   evidence text NULL, created_at datetime NOT NULL, PRIMARY KEY(id),
   KEY domain_type(domain_id,type), KEY job_type(job_id,type), KEY normalized(normalized_value(191))
  ) $charset;";
  $sql[]="CREATE TABLE {$t['errors']} (
   id varchar(64) NOT NULL, job_id varchar(64) NOT NULL, category varchar(50) NOT NULL, code varchar(100) NOT NULL DEFAULT '',
   message text NOT NULL, retryable tinyint(1) NOT NULL DEFAULT 0, attempt int unsigned NOT NULL DEFAULT 0,
   created_at datetime NOT NULL, PRIMARY KEY(id), KEY job_created(job_id,created_at)
  ) $charset;";
  foreach($sql as $statement) dbDelta($statement);
  self::$ready=true;
 }
 public static function id(){return wp_generate_uuid4();}
 public static function now(){return current_time('mysql',true);}
 public static function create_job($data){
  global $wpdb;$t=self::tables();$now=self::now();
  $cfg=$data['config']??[];
  $ok=$wpdb->insert($t['jobs'],[
   'id'=>$data['id'],'project_id'=>$data['project_id'],'mode'=>$data['mode'],'status'=>'queued',
   'config'=>wp_json_encode($cfg),'max_pages'=>max(1,(int)($cfg['max_pages']??100)),
   'max_urls'=>max(1,(int)($cfg['max_urls']??500)),'max_depth'=>max(0,(int)($cfg['max_depth']??3)),
   'max_candidates_per_page'=>max(1,(int)($cfg['max_candidates_per_page']??50)),
   'created_at'=>$now,'updated_at'=>$now
  ]);
  return $ok!==false;
 }
 public static function list_jobs($limit=50){
  global $wpdb;$t=self::tables();$limit=min(100,max(1,(int)$limit));
  return $wpdb->get_results($wpdb->prepare("SELECT id AS job_id,project_id,mode,status,max_pages,max_urls,processed_pages,processed_urls,created_at,updated_at,started_at,completed_at,cancelled_at FROM {$t['jobs']} ORDER BY created_at DESC LIMIT %d",$limit),ARRAY_A);
 }
 public static function get_job($id){
  global $wpdb;$t=self::tables();$row=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$t['jobs']} WHERE id=%s",$id),ARRAY_A);
  if(!$row)return null;$row['config']=$row['config']?json_decode($row['config'],true):[];
  $row['job_id']=$row['id'];unset($row['id']);return $row;
 }
 public static function set_job_status($id,$status){
  global $wpdb;$t=self::tables();$data=['status'=>$status,'updated_at'=>self::now()];
  if($status==='running')$data['started_at']=self::now();
  if($status==='completed')$data['completed_at']=self::now();
  if($status==='cancelled')$data['cancelled_at']=self::now();
  return $wpdb->update($t['jobs'],$data,['id'=>$id])!==false;
 }
 public static function remember_url($project,$url,$crawled=false,$content_hash='',$revisit_after=0){
  global $wpdb;$t=self::tables();$n=Numpo_Crawler::normalize_url($url);if(!$n)return null;$parts=wp_parse_url($n);if(empty($parts['host']))return null;$domain=Numpo_Crawler::domain(strtolower($parts['host']));$hash=hash('sha256',$n);$now=self::now();
  $row=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$t['memory']} WHERE project_id=%s AND url_hash=%s",$project,$hash),ARRAY_A);
  if($row){$data=['last_seen_at'=>$now];if($crawled){$data['last_crawled_at']=$now;$data['crawl_count']=(int)$row['crawl_count']+1;$data['content_hash']=$content_hash?:$row['content_hash'];$data['next_crawl_at']=$revisit_after>0?gmdate('Y-m-d H:i:s',time()+$revisit_after):null;$data['status']='crawled';}$wpdb->update($t['memory'],$data,['id'=>$row['id']]);return $row['id'];}
  $id=self::id();$wpdb->insert($t['memory'],['id'=>$id,'project_id'=>$project,'normalized_url'=>$n,'url_hash'=>$hash,'normalized_domain'=>$domain,'first_seen_at'=>$now,'last_seen_at'=>$now,'last_crawled_at'=>$crawled?$now:null,'crawl_count'=>$crawled?1:0,'content_hash'=>$content_hash?:null,'next_crawl_at'=>$crawled&&$revisit_after>0?gmdate('Y-m-d H:i:s',time()+$revisit_after):null,'status'=>$crawled?'crawled':'discovered']);return $id;
 }
 public static function memory_row($project,$url){global $wpdb;$t=self::tables();$n=Numpo_Crawler::normalize_url($url);if(!$n)return null;return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$t['memory']} WHERE project_id=%s AND url_hash=%s",$project,hash('sha256',$n)),ARRAY_A);}
 public static function should_crawl($project,$url,$revisit_after=0){$r=self::memory_row($project,$url);if(!$r)return true;if(!$r['last_crawled_at'])return true;if($revisit_after<=0)return false;return empty($r['next_crawl_at'])||strtotime($r['next_crawl_at'])<=time();}
 public static function project_metrics($project){global $wpdb;$t=self::tables();return ['jobs'=>(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t['jobs']} WHERE project_id=%s",$project)),'urls'=>(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t['memory']} WHERE project_id=%s",$project)),'crawled_urls'=>(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t['memory']} WHERE project_id=%s AND last_crawled_at IS NOT NULL",$project)),'domains'=>(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(DISTINCT normalized_domain) FROM {$t['memory']} WHERE project_id=%s",$project))];}
 public static function checkpoint($job,$current_url=''){
  global $wpdb;$t=self::tables();$j=self::get_job($job);if(!$j)return false;
  $cfg=is_array($j['config']??null)?$j['config']:[];$cfg['last_heartbeat']=self::now();if($current_url!=='')$cfg['current_url']=$current_url;
  return $wpdb->update($t['jobs'],['config'=>wp_json_encode($cfg),'updated_at'=>self::now()],['id'=>$job])!==false;
 }
 public static function recover_stale_candidates($job,$minutes=15){
  global $wpdb;$t=self::tables();$minutes=max(1,(int)$minutes);
  return $wpdb->query($wpdb->prepare("UPDATE {$t['candidates']} SET status='failed_retryable',processing_started_at=NULL,next_attempt_at=UTC_TIMESTAMP(),last_error='Worker interrupted; candidate returned to queue.' WHERE job_id=%s AND status='processing' AND processing_started_at<DATE_SUB(UTC_TIMESTAMP(),INTERVAL %d MINUTE)",$job,$minutes));
 }
 public static function has_resumable_work($job){
  global $wpdb;$t=self::tables();
  return (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t['candidates']} WHERE job_id=%s AND (status IN ('new','failed_retryable') OR status='processing')",$job))>0;
 }
 public static function add_candidate($job,$url,$source,$parent='',$priority=100,$confidence=1,$depth=0){
  global $wpdb;$t=self::tables();$n=Numpo_Crawler::normalize_url($url);if(!$n)return false;
  $parts=wp_parse_url($n);if(empty($parts['host']))return false;$host=strtolower($parts['host']);
  $domain=Numpo_Crawler::domain($host);$exists=$wpdb->get_var($wpdb->prepare("SELECT id FROM {$t['candidates']} WHERE job_id=%s AND normalized_url=%s",$job,$n));
  if($exists)return true;
  $jobRow=self::get_job($job);$project=$jobRow['project_id']??'';$cfg=$jobRow['config']??[];$revisit=(int)($cfg['revisit_after']??0);self::remember_url($project,$n,false);
  if(!self::should_crawl($project,$n,$revisit))return true;
  return $wpdb->insert($t['candidates'],['id'=>self::id(),'job_id'=>$job,'url'=>$url,'normalized_url'=>$n,'normalized_domain'=>$domain,'normalized_host'=>$host,'source_type'=>$source,'parent_url'=>$parent,'priority'=>$priority,'confidence'=>$confidence,'status'=>'new','depth'=>max(0,$depth),'discovered_at'=>self::now(),'next_attempt_at'=>self::now()])!==false;
 }
 public static function next_candidate($job){
  global $wpdb;$t=self::tables();
  return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$t['candidates']} WHERE job_id=%s AND ((status IN ('new','failed_retryable') AND (next_attempt_at IS NULL OR next_attempt_at<=UTC_TIMESTAMP())) OR (status='processing' AND processing_started_at<DATE_SUB(UTC_TIMESTAMP(),INTERVAL 10 MINUTE))) ORDER BY priority DESC,discovered_at ASC LIMIT 1",$job),ARRAY_A);
 }
 public static function mark_processing($id){
  global $wpdb;$t=self::tables();
  return $wpdb->query($wpdb->prepare("UPDATE {$t['candidates']} SET status='processing',attempt_count=attempt_count+1,processing_started_at=UTC_TIMESTAMP(),next_attempt_at=NULL WHERE id=%s AND (status IN ('new','failed_retryable') OR (status='processing' AND processing_started_at<DATE_SUB(UTC_TIMESTAMP(),INTERVAL 10 MINUTE)))",$id))>0;
 }
 public static function finish_candidate($id,$status,$error=''){
  global $wpdb;$t=self::tables();return $wpdb->update($t['candidates'],['status'=>$status,'last_error'=>$error,'completed_at'=>in_array($status,['completed','failed_final'],true)?self::now():null],['id'=>$id])!==false;
 }
 public static function retry_candidate($row,$error){
  global $wpdb;$t=self::tables();$attempt=(int)$row['attempt_count'];
  if($attempt>=3){self::finish_candidate($row['id'],'failed_final',$error);self::record_error($row['job_id'],'crawl','retry_exhausted',$error,false,$attempt);return;}
  $delay=min(30,pow(2,max(0,$attempt-1)));$when=gmdate('Y-m-d H:i:s',time()+$delay);
  $wpdb->update($t['candidates'],['status'=>'failed_retryable','last_error'=>$error,'next_attempt_at'=>$when,'processing_started_at'=>null],['id'=>$row['id']]);
 }
 public static function record_error($job,$category,$code,$message,$retryable=false,$attempt=0){
  global $wpdb;$t=self::tables();return $wpdb->insert($t['errors'],['id'=>self::id(),'job_id'=>$job,'category'=>$category,'code'=>$code,'message'=>$message,'retryable'=>$retryable?1:0,'attempt'=>$attempt,'created_at'=>self::now()])!==false;
 }
 public static function increment_job($job,$field){
  global $wpdb;$t=self::tables();if(!in_array($field,['processed_pages','processed_urls'],true))return false;
  return $wpdb->query($wpdb->prepare("UPDATE {$t['jobs']} SET {$field}={$field}+1,updated_at=UTC_TIMESTAMP() WHERE id=%s",$job))>0;
 }
 public static function ensure_domain($project,$domain){
  global $wpdb;$t=self::tables();$id=$wpdb->get_var($wpdb->prepare("SELECT id FROM {$t['domains']} WHERE project_id=%s AND normalized_domain=%s",$project,$domain));
  if($id)return $id;$id=self::id();$wpdb->insert($t['domains'],['id'=>$id,'project_id'=>$project,'normalized_domain'=>$domain,'created_at'=>self::now(),'updated_at'=>self::now()]);return $id;
 }
 public static function add_page($domain,$url,$status,$title,$type,$depth){
  global $wpdb;$t=self::tables();$hash=hash('sha256',$url);$id=$wpdb->get_var($wpdb->prepare("SELECT id FROM {$t['pages']} WHERE domain_id=%s AND url_hash=%s",$domain,$hash));
  $data=['status'=>$status,'title'=>$title,'content_type'=>$type,'depth'=>$depth,'fetched_at'=>self::now()];
  if($id){$wpdb->update($t['pages'],$data,['id'=>$id]);return $id;}
  $id=self::id();$data=array_merge($data,['id'=>$id,'domain_id'=>$domain,'normalized_url'=>$url,'url_hash'=>$hash]);$wpdb->insert($t['pages'],$data);return $id;
 }
 public static function add_technology($domain,$name,$confidence,$evidence,$source){
  global $wpdb;$t=self::tables();$id=$wpdb->get_var($wpdb->prepare("SELECT id FROM {$t['technologies']} WHERE domain_id=%s AND name=%s",$domain,$name));$data=['version'=>'','confidence'=>$confidence,'evidence'=>wp_json_encode($evidence),'source_url'=>$source,'detected_at'=>self::now()];
  if($id)return $wpdb->update($t['technologies'],$data,['id'=>$id])!==false;$data=array_merge($data,['id'=>self::id(),'domain_id'=>$domain,'name'=>$name]);return $wpdb->insert($t['technologies'],$data)!==false;
 }
 public static function add_contact($domain,$type,$value,$normalized,$source){
  global $wpdb;$t=self::tables();$exists=$wpdb->get_var($wpdb->prepare("SELECT id FROM {$t['contacts']} WHERE domain_id=%s AND type=%s AND normalized_value=%s",$domain,$type,$normalized));if($exists)return true;
  return $wpdb->insert($t['contacts'],['id'=>self::id(),'domain_id'=>$domain,'type'=>$type,'value'=>$value,'normalized_value'=>$normalized,'source_url'=>$source,'detected_at'=>self::now()])!==false;
 }
 public static function add_fact($job,$domain,$type,$subtype,$value,$normalized='',$source='',$confidence=.5,$evidence=''){
  global $wpdb;$t=self::tables();$exists=$wpdb->get_var($wpdb->prepare("SELECT id FROM {$t['facts']} WHERE domain_id=%s AND type=%s AND subtype=%s AND normalized_value=%s",$domain,$type,$subtype,$normalized));if($exists)return true;
  return $wpdb->insert($t['facts'],['id'=>self::id(),'job_id'=>$job,'domain_id'=>$domain,'type'=>$type,'subtype'=>$subtype,'value'=>$value,'normalized_value'=>$normalized,'source_url'=>$source,'confidence'=>$confidence,'evidence'=>$evidence,'created_at'=>self::now()])!==false;
 }
 public static function facts($job,$type='',$limit=200,$offset=0){
  global $wpdb;$t=self::tables();$limit=min(500,max(1,(int)$limit));$offset=max(0,(int)$offset);$sql="SELECT f.* FROM {$t['facts']} f WHERE f.job_id=%s";$args=[$job];if($type!==''){$sql.=" AND f.type=%s";$args[]=$type;}$sql.=" ORDER BY f.created_at DESC LIMIT %d OFFSET %d";$args[]=$limit;$args[]=$offset;return $wpdb->get_results($wpdb->prepare($sql,...$args),ARRAY_A);
 }
 public static function candidates_page($job,$limit=50,$offset=0){
  global $wpdb;$t=self::tables();$limit=min(200,max(1,(int)$limit));$offset=max(0,(int)$offset);
  $rows=$wpdb->get_results($wpdb->prepare("SELECT id,url,normalized_url,normalized_domain,normalized_host,source_type,parent_url,priority,confidence,status,attempt_count,last_error,discovered_at FROM {$t['candidates']} WHERE job_id=%s ORDER BY priority DESC,discovered_at ASC LIMIT %d OFFSET %d",$job,$limit,$offset),ARRAY_A);
  $total=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t['candidates']} WHERE job_id=%s",$job));return [$rows,$total];
 }
 public static function errors($job,$limit=50,$offset=0){
  global $wpdb;$t=self::tables();$limit=min(200,max(1,(int)$limit));$offset=max(0,(int)$offset);
  return $wpdb->get_results($wpdb->prepare("SELECT category,code,message,retryable,attempt,created_at FROM {$t['errors']} WHERE job_id=%s ORDER BY created_at DESC LIMIT %d OFFSET %d",$job,$limit,$offset),ARRAY_A);
 }
 public static function metrics($job){global $wpdb;$t=self::tables();$j=self::get_job($job);if(!$j)return null;$m=[];$m['candidates']=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t['candidates']} WHERE job_id=%s",$job));foreach(['new'=>'queued','processing'=>'processing','failed_retryable'=>'retryable','completed'=>'completed','failed_final'=>'failed'] as $s=>$k)$m[$k]=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t['candidates']} WHERE job_id=%s AND status=%s",$job,$s));$m['domains']=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(DISTINCT normalized_domain) FROM {$t['candidates']} WHERE job_id=%s",$job));$m['pages']=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t['pages']} p INNER JOIN {$t['domains']} d ON d.id=p.domain_id INNER JOIN (SELECT DISTINCT normalized_domain FROM {$t['candidates']} WHERE job_id=%s) c ON c.normalized_domain=d.normalized_domain",$job));$m['technologies']=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t['technologies']} x INNER JOIN {$t['domains']} d ON d.id=x.domain_id INNER JOIN (SELECT DISTINCT normalized_domain FROM {$t['candidates']} WHERE job_id=%s) c ON c.normalized_domain=d.normalized_domain",$job));$m['contacts']=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t['contacts']} x INNER JOIN {$t['domains']} d ON d.id=x.domain_id INNER JOIN (SELECT DISTINCT normalized_domain FROM {$t['candidates']} WHERE job_id=%s) c ON c.normalized_domain=d.normalized_domain",$job));$m['facts']=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t['facts']} WHERE job_id=%s",$job));foreach(['business','social','page_classification','probe'] as $ft){$key=$ft==='page_classification'?'classifications':($ft==='probe'?'probes':$ft);$m[$key]=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t['facts']} WHERE job_id=%s AND type=%s",$job,$ft));}$m['errors']=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t['errors']} WHERE job_id=%s",$job));$m['processed_urls']=(int)$j['processed_urls'];$m['processed_pages']=(int)$j['processed_pages'];$cfg=is_array($j['config']??null)?$j['config']:[];$m['last_heartbeat']=(string)($cfg['last_heartbeat']??'');$next=wp_next_scheduled('numpo_process_job',[$job]);$m['next_worker_at']=$next?(int)$next:0;$m['worker_scheduled']=$next?true:false;$m['worker_state']=$j['status']==='paused'?'paused':($next?'scheduled':($j['status']==='running'?'idle':'stopped'));$m['current_url']=(string)$wpdb->get_var($wpdb->prepare("SELECT normalized_url FROM {$t['candidates']} WHERE job_id=%s AND status='processing' ORDER BY processing_started_at DESC LIMIT 1",$job));$m['last_error']=(string)$wpdb->get_var($wpdb->prepare("SELECT message FROM {$t['errors']} WHERE job_id=%s ORDER BY created_at DESC LIMIT 1",$job));$m['last_candidate']=(string)$wpdb->get_var($wpdb->prepare("SELECT normalized_url FROM {$t['candidates']} WHERE job_id=%s ORDER BY discovered_at DESC LIMIT 1",$job));return $m;}
 public static function resource_rows($job,$resource,$limit=200,$offset=0){global $wpdb;$t=self::tables();$limit=min(500,max(1,(int)$limit));$offset=max(0,(int)$offset);if($resource==='candidates'||$resource==='urls')return self::candidates_page($job,$limit,$offset)[0];if($resource==='errors')return self::errors($job,$limit,$offset);if(in_array($resource,['business','social','classifications','probes'],true)){return self::facts($job,['business'=>'business','social'=>'social','classifications'=>'page_classification','probes'=>'probe'][$resource],$limit,$offset);}if($resource==='domains')return $wpdb->get_results($wpdb->prepare("SELECT d.normalized_domain AS domain, CASE WHEN SUM(CASE WHEN c.status='completed' THEN 1 ELSE 0 END)>0 THEN 'completed' ELSE 'pending' END AS status, COUNT(DISTINCT p.id) AS pages, COUNT(DISTINCT tech.id) AS technologies, COUNT(DISTINCT con.id) AS contacts, (SELECT COUNT(*) FROM {$t['errors']} e WHERE e.job_id=%s) AS errors FROM {$t['domains']} d INNER JOIN (SELECT DISTINCT normalized_domain FROM {$t['candidates']} WHERE job_id=%s) c ON c.normalized_domain=d.normalized_domain LEFT JOIN {$t['pages']} p ON p.domain_id=d.id LEFT JOIN {$t['technologies']} tech ON tech.domain_id=d.id LEFT JOIN {$t['contacts']} con ON con.domain_id=d.id GROUP BY d.id,d.normalized_domain ORDER BY d.normalized_domain LIMIT %d OFFSET %d",$job,$job,$limit,$offset),ARRAY_A);if(in_array($resource,['pages','technologies','contacts'],true)){$table=$t[$resource];$date=$resource==='pages'?'fetched_at':($resource==='technologies'?'detected_at':'detected_at');return $wpdb->get_results($wpdb->prepare("SELECT DISTINCT x.* FROM {$table} x INNER JOIN {$t['domains']} d ON d.id=x.domain_id INNER JOIN (SELECT DISTINCT normalized_domain FROM {$t['candidates']} WHERE job_id=%s) c ON c.normalized_domain=d.normalized_domain ORDER BY x.{$date} DESC LIMIT %d OFFSET %d",$job,$limit,$offset),ARRAY_A);}return [];}

 public static function has_pending($job){
  global $wpdb;$t=self::tables();return (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t['candidates']} WHERE job_id=%s AND (status IN ('new','failed_retryable') OR (status='processing' AND processing_started_at>=DATE_SUB(UTC_TIMESTAMP(),INTERVAL 10 MINUTE)))",$job))>0;
 }
}
