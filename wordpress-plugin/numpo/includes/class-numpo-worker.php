<?php
if(!defined('ABSPATH')) exit;
class Numpo_Worker {
 public static function init(){
  add_filter('cron_schedules',[__CLASS__,'cron_schedules']);
  add_action('numpo_process_job',[__CLASS__,'process']);
  add_action('numpo_recover_jobs',[__CLASS__,'recover']);
  add_action('numpo_bootstrap_job',[__CLASS__,'bootstrap_job']);
  add_action('numpo_discover_seed',[__CLASS__,'discover_seed']);
  if(!get_option('numpo_recover_schedule_v2')){ $old=wp_next_scheduled('numpo_recover_jobs'); if($old)wp_unschedule_event($old,'numpo_recover_jobs'); wp_schedule_event(time()+60,'numpo_five_minutes','numpo_recover_jobs'); update_option('numpo_recover_schedule_v2','1',false); } elseif(!wp_next_scheduled('numpo_recover_jobs'))wp_schedule_event(time()+60,'numpo_five_minutes','numpo_recover_jobs');
  if(!wp_next_scheduled('numpo_worker_tick'))wp_schedule_event(time()+60,'numpo_one_minute','numpo_worker_tick');
 }
 public static function cron_schedules($schedules){
  if(!isset($schedules['numpo_five_minutes']))$schedules['numpo_five_minutes']=['interval'=>300,'display'=>'Numpo every 5 minutes'];
  if(!isset($schedules['numpo_one_minute']))$schedules['numpo_one_minute']=['interval'=>60,'display'=>'Numpo every minute'];
  return $schedules;
 }
 public static function schedule($job){
  $row=Numpo_DB::get_job($job);
  if(!$row||in_array($row['status'],['paused','cancelled','completed','failed'],true))return false;
  if(wp_next_scheduled('numpo_process_job',[$job]))return true;
  $result=wp_schedule_single_event(time()+2,'numpo_process_job',[$job],true);
  if($result===true)return true;
  if(is_wp_error($result)&&$result->get_error_code()==='duplicate_event')return true;
  Numpo_DB::record_error($job,'worker','schedule_failed',is_wp_error($result)?$result->get_error_message():'Could not schedule worker.',false);
  return false;
 }
 public static function tick(){
  global $wpdb;$t=Numpo_DB::tables();
  $jobs=$wpdb->get_col("SELECT id FROM {$t['jobs']} WHERE status IN ('queued','running') ORDER BY updated_at ASC LIMIT 20");
  foreach($jobs as $job)self::schedule($job);
 }
 public static function bootstrap_job($job,$project,$seeds=[],$config=[]){
  $row=Numpo_DB::get_job($job);if(!$row||in_array($row['status'],['cancelled','failed','completed'],true))return;
  $seeds=is_array($seeds)?$seeds:[];$config=is_array($config)?$config:[];
  foreach($seeds as $seed){
   if(!Numpo_DB::add_candidate($job,(string)$seed,'manual_seed','',100,1,0)){
    Numpo_DB::record_error($job,'discovery','invalid_seed','Invalid seed: '.(string)$seed,false);
    Numpo_DB::set_job_status($job,'failed');return;
   }
  }
  foreach($seeds as $seed){
   $result=wp_schedule_single_event(time()+1,'numpo_discover_seed',[$job,$project,(string)$seed,$config],true);
   if($result!==true && !(is_wp_error($result)&&$result->get_error_code()==='duplicate_event')){
    Numpo_DB::record_error($job,'discovery','schedule_failed',is_wp_error($result)?$result->get_error_message():'Could not schedule discovery.',false);
    Numpo_DB::set_job_status($job,'failed');return;
   }
  }
  self::schedule($job);
 }
 public static function discover_seed($job,$project,$seed,$config=[]){
  $row=Numpo_DB::get_job($job);if(!$row||in_array($row['status'],['paused','cancelled','failed'],true))return;
  Numpo_Discovery::seed_job($job,$project,$seed,is_array($config)?$config:[]);
  self::schedule($job);
 }
 public static function recover(){
  global $wpdb;$t=Numpo_DB::tables();$jobs=$wpdb->get_col("SELECT id FROM {$t['jobs']} WHERE status IN ('running','queued') AND updated_at<DATE_SUB(UTC_TIMESTAMP(),INTERVAL 15 MINUTE) LIMIT 20");foreach($jobs as $job){Numpo_DB::recover_stale_candidates($job,15);self::schedule($job);}
 }
 public static function process($job){
  global $wpdb;
  $lock='numpo_job_'.md5((string)$job);
  $acquired=(int)$wpdb->get_var($wpdb->prepare("SELECT GET_LOCK(%s,0)",$lock));
  if($acquired!==1)return;
  try{
   self::process_locked($job);
  } finally {
   $wpdb->get_var($wpdb->prepare("SELECT RELEASE_LOCK(%s)",$lock));
  }
 }
 private static function process_locked($job){
  $row=Numpo_DB::get_job($job);if(!$row)return;
  if(in_array($row['status'],['cancelled','completed','failed'],true))return;
  if($row['status']==='paused')return;
  Numpo_DB::set_job_status($job,'running');
  Numpo_DB::recover_stale_candidates($job,15);
  $row=Numpo_DB::get_job($job);if(!$row||$row['status']==='paused'||$row['status']==='cancelled')return;
  $candidate=Numpo_DB::next_candidate($job);
  if(!$candidate){if(Numpo_DB::has_resumable_work($job)){self::schedule($job);return;}Numpo_DB::set_job_status($job,'completed');return;}
  if(!Numpo_DB::mark_processing($candidate['id'])){self::schedule($job);return;}
  Numpo_DB::checkpoint($job,$candidate['normalized_url']);
  if((int)$row['processed_urls'] >= (int)$row['max_urls']){Numpo_DB::finish_candidate($candidate['id'],'failed_final','URL budget exhausted.');Numpo_DB::set_job_status($job,'completed');return;}
  $cfg=is_array($row['config']??null)?$row['config']:[];$result=Numpo_Crawler::fetch($candidate['normalized_url'],(int)($cfg['request_timeout']??15),(int)($cfg['max_response_bytes']??2097152),['rate_limit_ms'=>(int)($cfg['rate_limit_ms']??250),'respect_robots'=>array_key_exists('respect_robots',$cfg)?(bool)$cfg['respect_robots']:true]);
  if(is_wp_error($result)){Numpo_DB::retry_candidate($candidate,$result->get_error_message());Numpo_DB::checkpoint($job,$candidate['normalized_url']);self::schedule($job);return;}
  Numpo_DB::increment_job($job,'processed_urls');
  $domain_id=Numpo_DB::ensure_domain($row['project_id'],$candidate['normalized_domain']);
  Numpo_DB::add_page($domain_id,$result['url'],$result['status'],$result['title'],$result['content_type'],$candidate['depth']);
  $probe=Numpo_Crawler::probe($candidate['normalized_host'],(int)($cfg['request_timeout']??10));Numpo_DB::add_fact($job,$domain_id,'probe','status',$probe['status'],$probe['status'],$result['url'],.9,'host probe');Numpo_DB::add_fact($job,$domain_id,'probe','http_status',(string)$probe['http_status'],(string)$probe['http_status'],$result['url'],.9,'host probe');Numpo_DB::add_fact($job,$domain_id,'probe','https_status',(string)$probe['https_status'],(string)$probe['https_status'],$result['url'],.9,'host probe');
  foreach($result['headers']??[] as $h)Numpo_DB::add_fact($job,$domain_id,'http_header',$h[0],$h[1],$h[1],$result['url'],.7,'response-header');
  foreach(Numpo_Crawler::technologies($result['body'],$result['url']) as $tech)Numpo_DB::add_technology($domain_id,$tech[0],$tech[1],[$tech[2]],$result['url']);
  foreach(Numpo_Crawler::metadata($result['body']) as $meta)Numpo_DB::add_fact($job,$domain_id,'metadata',$meta[0],$meta[1],$meta[1],$result['url'],.8,'html-meta');
  foreach(Numpo_Crawler::socials($result['body']) as $social)Numpo_DB::add_fact($job,$domain_id,'social',$social[0],$social[1],$social[1],$result['url'],.85,'social-link');
  foreach(Numpo_Crawler::business($result['body']) as $biz)Numpo_DB::add_fact($job,$domain_id,'business',$biz[0],$biz[1],$biz[1],$result['url'],.75,'business-marker');
  Numpo_Discovery::seed_links($job,$row['project_id'],$result['url'],$result['body'],$result['url']);
  foreach(Numpo_Crawler::classify($result['body'],$result['url']) as $cls)Numpo_DB::add_fact($job,$domain_id,'page_classification',$cls[0],$cls[0],$cls[0],$result['url'],$cls[1],$cls[2]);
  foreach(Numpo_Crawler::contacts($result['body']) as $contact)Numpo_DB::add_contact($domain_id,$contact[0],$contact[1],$contact[2],$result['url']);
  if((int)$row['processed_pages'] < (int)$row['max_pages']){
   Numpo_DB::increment_job($job,'processed_pages');$added=0;
   foreach($result['links'] as $link){
    if((int)$candidate['depth'] >= (int)$row['max_depth']||$added >= (int)$row['max_candidates_per_page'])break;
    $parts=wp_parse_url($link);$host=strtolower($parts['host']??'');
    if($host==='')continue;
    if(empty($cfg['allow_external_links']) && Numpo_Crawler::domain($host)!==$candidate['normalized_domain'])continue;
    if(Numpo_DB::add_candidate($job,$link,'link_discovery',$result['url'],50,.7,(int)$candidate['depth']+1))$added++;
   }
  }
  Numpo_DB::finish_candidate($candidate['id'],'completed');
  Numpo_DB::remember_url($row['project_id'],$candidate['normalized_url'],true,'',(int)($cfg['revisit_after']??0));
  Numpo_DB::checkpoint($job,'');
  $next=Numpo_DB::get_job($job);
  if($next && (int)$next['processed_urls'] >= (int)$next['max_urls']){Numpo_DB::set_job_status($job,'completed');return;}
  if(Numpo_DB::has_pending($job))self::schedule($job);else Numpo_DB::set_job_status($job,'completed');
 }
}
