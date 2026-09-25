<?php
if(!defined('ABSPATH')) exit;
class Numpo_Worker {
 public static function init(){add_action('numpo_process_job',[__CLASS__,'process']);}
 public static function schedule($job){if(!wp_next_scheduled('numpo_process_job',[$job]))wp_schedule_single_event(time()+1,'numpo_process_job',[$job]);}
 public static function process($job){
  $row=Numpo_DB::get_job($job);if(!$row)return;
  if(in_array($row['status'],['cancelled','completed','failed'],true))return;
  Numpo_DB::set_job_status($job,'running');
  $candidate=Numpo_DB::next_candidate($job);
  if(!$candidate){Numpo_DB::set_job_status($job,'completed');return;}
  if(!Numpo_DB::mark_processing($candidate['id'])){self::schedule($job);return;}
  if((int)$row['processed_urls'] >= (int)$row['max_urls']){Numpo_DB::finish_candidate($candidate['id'],'failed_final','URL budget exhausted.');Numpo_DB::set_job_status($job,'completed');return;}
  $result=Numpo_Crawler::fetch($candidate['normalized_url']);
  if(is_wp_error($result)){Numpo_DB::retry_candidate($candidate,$result->get_error_message());self::schedule($job);return;}
  Numpo_DB::increment_job($job,'processed_urls');
  $domain_id=Numpo_DB::ensure_domain($row['project_id'],$candidate['normalized_domain']);
  Numpo_DB::add_page($domain_id,$result['url'],$result['status'],$result['title'],$result['content_type'],$candidate['depth']);
  foreach(Numpo_Crawler::technologies($result['body'],$result['url']) as $tech)Numpo_DB::add_technology($domain_id,$tech[0],$tech[1],[$tech[2]],$result['url']);
  Numpo_Discovery::seed_links($job,$row['project_id'],$result['url'],$result['body'],$result['url']);
  foreach(Numpo_Crawler::classify($result['body'],$result['url']) as $cls)Numpo_DB::add_fact($job,$domain_id,'page_classification',$cls[0],$cls[0],$cls[0],$result['url'],$cls[1],$cls[2]);
  foreach(Numpo_Crawler::contacts($result['body']) as $contact)Numpo_DB::add_contact($domain_id,$contact[0],$contact[1],$contact[2],$result['url']);
  if((int)$row['processed_pages'] < (int)$row['max_pages']){
   Numpo_DB::increment_job($job,'processed_pages');$added=0;
   foreach($result['links'] as $link){
    if((int)$candidate['depth'] >= (int)$row['max_depth']||$added >= (int)$row['max_candidates_per_page'])break;
    $parts=wp_parse_url($link);$host=strtolower($parts['host']??'');
    if($host===''||(!empty($row['config']['allow_external_links']) && Numpo_Crawler::domain($host)!==$candidate['normalized_domain']))continue;
    if(Numpo_Crawler::domain($host)!==$candidate['normalized_domain'])continue;
    if(Numpo_DB::add_candidate($job,$link,'link_discovery',$result['url'],50,.7,(int)$candidate['depth']+1))$added++;
   }
  }
  Numpo_DB::finish_candidate($candidate['id'],'completed');
  $next=Numpo_DB::get_job($job);
  if($next && (int)$next['processed_urls'] >= (int)$next['max_urls']){Numpo_DB::set_job_status($job,'completed');return;}
  if(Numpo_DB::has_pending($job))self::schedule($job);else Numpo_DB::set_job_status($job,'completed');
 }
}
