<?php
if(!defined('ABSPATH')) exit;
class Numpo_API {
 public static function init(){add_action('rest_api_init',[__CLASS__,'routes']);}
 public static function routes(){
  register_rest_route('numpo/v1','/jobs',['methods'=>'POST','permission_callback'=>[__CLASS__,'permission'],'callback'=>[__CLASS__,'create']]);
  register_rest_route('numpo/v1','/project/(?P<project>[A-Za-z0-9_-]+)/metrics',['methods'=>'GET','permission_callback'=>[__CLASS__,'permission'],'callback'=>function($r){return self::ok(Numpo_DB::project_metrics(sanitize_text_field($r['project'])));}]);
  register_rest_route('numpo/v1','/jobs',['methods'=>'GET','permission_callback'=>[__CLASS__,'permission'],'callback'=>[__CLASS__,'jobs']]);
  register_rest_route('numpo/v1','/jobs/(?P<id>[A-Za-z0-9-]+)',['methods'=>'GET','permission_callback'=>[__CLASS__,'permission'],'callback'=>[__CLASS__,'get']]);
  register_rest_route('numpo/v1','/jobs/(?P<id>[A-Za-z0-9-]+)/candidates',['methods'=>'GET','permission_callback'=>[__CLASS__,'permission'],'callback'=>[__CLASS__,'candidates']]);
  register_rest_route('numpo/v1','/jobs/(?P<id>[A-Za-z0-9-]+)/cancel',['methods'=>'POST','permission_callback'=>[__CLASS__,'permission'],'callback'=>[__CLASS__,'cancel']]);
  register_rest_route('numpo/v1','/jobs/(?P<id>[A-Za-z0-9-]+)/pause',['methods'=>'POST','permission_callback'=>[__CLASS__,'permission'],'callback'=>[__CLASS__,'pause']]);
  register_rest_route('numpo/v1','/jobs/(?P<id>[A-Za-z0-9-]+)/resume',['methods'=>'POST','permission_callback'=>[__CLASS__,'permission'],'callback'=>[__CLASS__,'resume']]);
  register_rest_route('numpo/v1','/jobs/(?P<id>[A-Za-z0-9-]+)/export/(?P<resource>candidates|urls|domains|pages|technologies|contacts|business|social|classifications|probes|errors|zip|txt)',['methods'=>'GET','permission_callback'=>[__CLASS__,'permission'],'callback'=>[__CLASS__,'export_resource']]);
  register_rest_route('numpo/v1','/jobs/(?P<id>[A-Za-z0-9-]+)/(?P<resource>domains|hosts|pages|technologies|contacts|business|social|classifications|probes)',['methods'=>'GET','permission_callback'=>[__CLASS__,'permission'],'callback'=>[__CLASS__,'resource']]);
  register_rest_route('numpo/v1','/jobs/(?P<id>[A-Za-z0-9-]+)/facts',['methods'=>'GET','permission_callback'=>[__CLASS__,'permission'],'callback'=>[__CLASS__,'facts']]);
  register_rest_route('numpo/v1','/jobs/(?P<id>[A-Za-z0-9-]+)/errors',['methods'=>'GET','permission_callback'=>[__CLASS__,'permission'],'callback'=>[__CLASS__,'errors']]);
 }
 public static function permission(){return current_user_can('manage_options');}
 private static function ok($data,$status=200){return new WP_REST_Response($data,$status);}
 public static function create(WP_REST_Request $r){
  $p=$r->get_json_params();if(!is_array($p))$p=$r->get_body_params();if(!is_array($p))return new WP_Error('invalid_request_body','Numpo received an invalid request body.',['status'=>400]);
  $project=sanitize_text_field($p['project_id']??'');$mode=sanitize_key($p['mode']??'manual');$seeds=is_array($p['seeds']??null)?array_values($p['seeds']):[];
  if($project==='')return new WP_Error('invalid_project_id','project_id is required.',['status'=>400]);
  if(!in_array($mode,['manual','automatic','hybrid'],true))return new WP_Error('invalid_discovery_mode','Discovery mode is invalid.',['status'=>400]);
  if($mode==='manual'&&!$seeds)return new WP_Error('manual_seeds_required','Manual mode requires seeds.',['status'=>400]);
  $cfg=is_array($p['limits']??null)?$p['limits']:[];
  if(is_array($p['sources']??null))$cfg['sources']=$p['sources'];if(is_array($p['target']??null))$cfg['target']=$p['target'];if(is_array($p['capabilities']??null))$cfg['capabilities']=$p['capabilities'];if(is_array($p['routing_rules']??null))$cfg['routing_rules']=$p['routing_rules'];
  $cfg['max_urls']=max(1,(int)($cfg['max_urls']??1000));$cfg['max_pages']=max(1,(int)($cfg['max_pages']??$cfg['max_urls']));$cfg['max_depth']=max(0,(int)($cfg['max_depth']??3));$cfg['max_candidates_per_page']=max(1,(int)($cfg['max_candidates_per_page']??50));$cfg['request_timeout']=max(3,min(60,(int)($cfg['request_timeout']??15)));$cfg['max_response_bytes']=max(65536,min(10485760,(int)($cfg['max_response_bytes']??2097152)));$cfg['rate_limit_ms']=max(0,min(10000,(int)($cfg['rate_limit_ms']??250)));$cfg['respect_robots']=array_key_exists('respect_robots',$cfg)?(bool)$cfg['respect_robots']:true;$cfg['allow_external_links']=!empty($cfg['allow_external_links']);
  $job=Numpo_DB::id();if(!Numpo_DB::create_job(['id'=>$job,'project_id'=>$project,'mode'=>$mode,'config'=>$cfg]))return new WP_Error('internal_error','Could not create Numpo job.',['status'=>500]);
  foreach($seeds as $seed)if(!Numpo_DB::add_candidate($job,(string)$seed,'manual_seed','',100,1,0)){Numpo_DB::record_error($job,'discovery','invalid_seed','Invalid seed: '.(string)$seed,false);Numpo_DB::set_job_status($job,'failed');return new WP_Error('invalid_seed','Invalid seed URL.',['status'=>400]);}
  foreach($seeds as $seed)Numpo_Discovery::seed_job($job,$project,(string)$seed,$cfg);
  Numpo_Worker::schedule($job);
  return self::ok(['job_id'=>$job,'status'=>'queued','mode'=>$mode,'created_at'=>gmdate('c')],202);
 }
 public static function jobs(WP_REST_Request $r){return self::ok(['items'=>Numpo_DB::list_jobs((int)($r->get_param('limit')?:50))]);}
 public static function get(WP_REST_Request $r){$job=Numpo_DB::get_job($r['id']);if(!$job)return new WP_Error('not_found','Job not found.',['status'=>404]);$job['metrics']=Numpo_DB::metrics($r['id']);return self::ok($job);}
 public static function candidates(WP_REST_Request $r){$per=min(200,max(1,(int)($r->get_param('per_page')?:50)));$page=max(1,(int)($r->get_param('page')?:1));[$rows,$total]=Numpo_DB::candidates_page($r['id'],$per,($page-1)*$per);return self::ok(['items'=>$rows,'page'=>$page,'per_page'=>$per,'total'=>$total]);}
 public static function cancel(WP_REST_Request $r){$job=Numpo_DB::get_job($r['id']);if(!$job)return new WP_Error('not_found','Job not found.',['status'=>404]);Numpo_DB::set_job_status($r['id'],'cancelled');return self::ok(['job_id'=>$r['id'],'status'=>'cancelled']);}
 public static function pause(WP_REST_Request $r){$job=Numpo_DB::get_job($r['id']);if(!$job)return new WP_Error('not_found','Job not found.',['status'=>404]);if(!in_array($job['status'],['queued','running'],true))return new WP_Error('invalid_state','Job cannot be paused.',['status'=>409]);Numpo_DB::set_job_status($r['id'],'paused');return self::ok(['job_id'=>$r['id'],'status'=>'paused']);}
 public static function resume(WP_REST_Request $r){$job=Numpo_DB::get_job($r['id']);if(!$job)return new WP_Error('not_found','Job not found.',['status'=>404]);if(!in_array($job['status'],['paused','queued','running'],true))return new WP_Error('invalid_state','Job cannot be resumed from its current state.',['status'=>409]);Numpo_DB::recover_stale_candidates($r['id'],15);Numpo_DB::set_job_status($r['id'],'running');Numpo_Worker::schedule($r['id']);return self::ok(['job_id'=>$r['id'],'status'=>'running']);}
 public static function export_resource(WP_REST_Request $r){$job=Numpo_DB::get_job($r['id']);if(!$job)return new WP_Error('not_found','Job not found.',['status'=>404]);$resource=$r['resource'];if($resource==='zip')return self::export_zip($r['id']);$as_txt=$resource==='txt';$resource=$as_txt?'candidates':($resource==='urls'?'candidates':$resource);$out=fopen('php://temp','w+');$wrote=false;$keys=[];for($offset=0;;$offset+=500){$rows=Numpo_DB::resource_rows($r['id'],$resource,500,$offset);if(!$rows)break;if($as_txt){foreach($rows as $row)fwrite($out,(string)($row['normalized_url']??$row['url']??'')."\n");$wrote=true;}else{if(!$wrote){$keys=array_keys($rows[0]);fputcsv($out,$keys);$wrote=true;}foreach($rows as $row){$vals=[];foreach($keys as $k){$v=$row[$k]??'';$vals[]=is_scalar($v)?$v:wp_json_encode($v,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);}fputcsv($out,$vals);}}if(count($rows)<500)break;}if(!$wrote)fwrite($out,$as_txt?'':'no_results\n');rewind($out);$data=stream_get_contents($out);fclose($out);return new WP_REST_Response($data,200,['Content-Type'=>$as_txt?'text/plain; charset=utf-8':'text/csv; charset=utf-8']);}
 public static function export_zip($id){if(!class_exists('ZipArchive'))return new WP_Error('zip_unavailable','PHP ZipArchive extension is required for ZIP export.',['status'=>500]);$tmp=wp_tempnam('numpo-'.$id.'.zip');$zip=new ZipArchive();if($zip->open($tmp,ZipArchive::CREATE)!==true)return new WP_Error('zip_failed','Could not create ZIP.',['status'=>500]);foreach(['urls','domains','pages','technologies','contacts','business','social','classifications','probes','errors'] as $res){$req=new WP_REST_Request('GET','/');$req->set_param('id',$id);$req->set_param('resource',$res);$r=self::export_resource($req);if(is_wp_error($r))continue;$zip->addFromString($res.'.csv',$r->get_data());}$zip->addFromString('README.txt',"Numpo Job ".$id."\nComplete exported results for this Crawl Job.\n");$zip->close();$data=file_get_contents($tmp);@unlink($tmp);return new WP_REST_Response($data,200,['Content-Type'=>'application/zip']);}
 public static function resource(WP_REST_Request $r){
  global $wpdb;$t=Numpo_DB::tables();$res=$r['resource'];$table=$t[$res]??null;if(!$table)return new WP_Error('not_found','Resource not found.',['status'=>404]);
  $project=$wpdb->get_var($wpdb->prepare("SELECT project_id FROM {$t['jobs']} WHERE id=%s",$r['id']));if(!$project)return new WP_Error('not_found','Job not found.',['status'=>404]);
  if(in_array($res,['business','social','classifications','probes'],true)){ $type=$res==='business'?'business':($res==='social'?'social':($res==='classifications'?'page_classification':'probe')); $rows=$wpdb->get_results($wpdb->prepare("SELECT * FROM {$t['facts']} WHERE job_id=%s AND type=%s ORDER BY created_at DESC LIMIT 200",$r['id'],$type),ARRAY_A); return self::ok(['items'=>$rows,'page'=>1,'per_page'=>200,'total'=>count($rows)]); }
  if($res==='hosts')$res='domains';$table=$t[$res]??null;if(!$table)return new WP_Error('not_found','Resource not found.',['status'=>404]);$where=$res==='domains'?'project_id=%s':'domain_id IN (SELECT id FROM '.$t['domains'].' WHERE project_id=%s)';
  $rows=$wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE {$where} ORDER BY 1 DESC LIMIT 200",$project),ARRAY_A);
  return self::ok(['items'=>$rows,'page'=>1,'per_page'=>200,'total'=>count($rows)]);
 }
 public static function facts(WP_REST_Request $r){$job=Numpo_DB::get_job($r['id']);if(!$job)return new WP_Error('not_found','Job not found.',['status'=>404]);$type=sanitize_key((string)$r->get_param('type'));return self::ok(['items'=>Numpo_DB::facts($r['id'],$type,200,0),'page'=>1,'per_page'=>200]);}
 public static function errors(WP_REST_Request $r){$per=min(200,max(1,(int)($r->get_param('per_page')?:50)));$page=max(1,(int)($r->get_param('page')?:1));return self::ok(['items'=>Numpo_DB::errors($r['id'],$per,($page-1)*$per),'page'=>$page,'per_page'=>$per]);}
 public static function export_csv($id){
  $job=Numpo_DB::get_job($id);if(!$job)return new WP_Error('not_found','Job not found.',['status'=>404]);
  [$rows,$total]=Numpo_DB::candidates_page($id,500,0);$out=fopen('php://temp','w+');fputcsv($out,['url','normalized_url','domain','source','status','confidence','depth','attempts','error']);
  for($offset=0;$offset<$total;$offset+=200){[$rows,]=Numpo_DB::candidates_page($id,200,$offset);foreach($rows as $row)fputcsv($out,[$row['url'],$row['normalized_url'],$row['normalized_domain'],$row['source_type'],$row['status'],$row['confidence'],$row['depth'],$row['attempt_count'],$row['last_error']]);}
  rewind($out);$csv=stream_get_contents($out);fclose($out);return new WP_REST_Response($csv,200);
 }
}
