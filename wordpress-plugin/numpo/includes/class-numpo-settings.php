<?php
if(!defined('ABSPATH')) exit;
class Numpo_Settings {
 public static function init(){ add_action('admin_init',[__CLASS__,'register']); }
 public static function register(){
  register_setting('numpo','numpo_default_project',['sanitize_callback'=>'sanitize_text_field']);
  register_setting('numpo','numpo_search_url_template',['sanitize_callback'=>'esc_url_raw']);
  register_setting('numpo','numpo_max_pages',['sanitize_callback'=>'absint']);
  register_setting('numpo','numpo_max_urls',['sanitize_callback'=>'absint']);
  register_setting('numpo','numpo_max_depth',['sanitize_callback'=>'absint']);
  register_setting('numpo','numpo_max_candidates_per_page',['sanitize_callback'=>'absint']);
  register_setting('numpo','numpo_domain_rate_limit_ms',['sanitize_callback'=>'absint']);
  register_setting('numpo','numpo_probe_ttl_seconds',['sanitize_callback'=>'absint']);
  foreach(['active_probe','deep_crawl','link_discovery','sitemap','robots','subdomain_from_crawl','phone','email','business','social','page_classification','wordpress','woocommerce'] as $k){
   register_setting('numpo','numpo_cap_'.$k,['sanitize_callback'=>function($v){return $v?'1':'0';}]);
  }
  register_setting('numpo','numpo_allow_subdomains',['sanitize_callback'=>function($v){return $v?'1':'0';}]);
  register_setting('numpo','numpo_allow_external_links',['sanitize_callback'=>function($v){return $v?'1':'0';}]);
 }
 public static function default_project(){return (string)get_option('numpo_default_project','default');}
 public static function search_url_template(){return (string)get_option('numpo_search_url_template','');}
 public static function int($key,$default,$min=1){$v=absint(get_option($key,$default));return $v>=$min?$v:$default;}
 public static function cap($key,$default=true){$v=get_option('numpo_cap_'.$key,null);return $v===null?$default:(bool)$v;}
 public static function bool($key,$default=false){$v=get_option('numpo_'.$key,null);return $v===null?$default:(bool)$v;}
}
