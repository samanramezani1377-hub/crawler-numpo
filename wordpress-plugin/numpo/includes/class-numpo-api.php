<?php
if(!defined('ABSPATH')) exit;
class Numpo_API {
 public static function init(){add_action('rest_api_init',[__CLASS__,'routes']);}
 public static function routes(){
  register_rest_route('numpo/v1','/jobs',['methods'=>'POST','permission_callback'=>[__CLASS__,'permission'],'callback'=>[__CLASS__,'create']]);
  register_rest_route('numpo/v1','/jobs/(?P<id>[A-Za-z0-9-]+)',['methods'=>'GET','permission_callback'=>[__CLASS__,'permission'],'callback'=>[__CLASS__,'get']]);
  register_rest_route('numpo/v1','/jobs/(?P<id>[A-Za-z0-9-]+)/candidates',['methods'=>'GET','permission_callback'=>[__CLASS__,'permission'],'callback'=>[__CLASS__,'candidates']]);
  register_rest_route('numpo/v1','/jobs/(?P<id>[A-Za-z0-9-]+)/cancel',['methods'=>'POST','permission_callback'=>[__CLASS__,'permission'],'callback'=>[__CLASS__,'cancel']]);
  register_rest_route('numpo/v1','/jobs/(?P<id>[A-Za-z0-9-]+)/(?P<resource>domains|pages|technologies|contacts)',['methods'=>'GET','permission_callback'=>[__CLASS__,'permission'],'callback'=>[__CLASS__,'resource']]);
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
  $cfg=is_array($p['limits']??null)?$p['limits']:[];$cfg['allow_external_links']=false;
  $job=Numpo_DB::id();if(!Numpo_DB::create_job(['id'=>$job,'project_id'=>$project,'mode'=>$mode,'config'=>$cfg]))return new WP_Error('internal_error','Could not create Numpo job.',['status'=>500]);
  foreach($seeds as $seed)if(!Numpo_DB::add_candidate($job,(string)$seed,'manual_seed','',100,1,0)){Numpo_DB::record_error($job,'discovery','invalid_seed','Invalid seed: '.(string)$seed,false);Numpo_DB::set_job_status($job,'failed');return new WP_Error('invalid_seed','Invalid seed URL.',['status'=>400]);}
  Numpo_Worker::schedule($job);
  return self::ok(['job_id'=>$job,'status'=>'queued','mode'=>$mode,'created_at'=>gmdate('c')],202);
 }
 public static function get(WP_REST_Request $r){$job=Numpo_DB::get_job($r['id']);return $job?self::ok($job):new WP_Error('not_found','Job not found.',['status'=>404]);}
 public static function candidates(WP_REST_Request $r){$per=min(200,max(1,(int)($r->get_param('per_page')?:50)));$page=max(1,(int)($r->get_param('page')?:1));[$rows,$total]=Numpo_DB::candidates_page($r['id'],$per,($page-1)*$per);return self::ok(['items'=>$rows,'page'=>$page,'per_page'=>$per,'total'=>$total]);}
 public static function cancel(WP_REST_Request $r){$job=Numpo_DB::get_job($r['id']);if(!$job)return new WP_Error('not_found','Job not found.',['status'=>404]);Numpo_DB::set_job_status($r['id'],'cancelled');return self::ok(['job_id'=>$r['id'],'status'=>'cancelled']);}
 public static function resource(WP_REST_Request $r){
  global $wpdb;$t=Numpo_DB::tables();$res=$r['resource'];$table=$t[$res]??null;if(!$table)return new WP_Error('not_found','Resource not found.',['status'=>404]);
  $project=$wpdb->get_var($wpdb->prepare("SELECT project_id FROM {$t['jobs']} WHERE id=%s",$r['id']));if(!$project)return new WP_Error('not_found','Job not found.',['status'=>404]);
  $where=$res==='domains'?'project_id=%s':'domain_id IN (SELECT id FROM '.$t['domains'].' WHERE project_id=%s)';
  $rows=$wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE {$where} ORDER BY 1 DESC LIMIT 200",$project),ARRAY_A);
  return self::ok(['items'=>$rows,'page'=>1,'per_page'=>200,'total'=>count($rows)]);
 }
 public static function errors(WP_REST_Request $r){$per=min(200,max(1,(int)($r->get_param('per_page')?:50)));$page=max(1,(int)($r->get_param('page')?:1));return self::ok(['items'=>Numpo_DB::errors($r['id'],$per,($page-1)*$per),'page'=>$page,'per_page'=>$per]);}
 public static function export_csv($id){return new WP_Error('not_supported','CSV export is being migrated to the PHP-only crawler.',['status'=>501]);}
}
