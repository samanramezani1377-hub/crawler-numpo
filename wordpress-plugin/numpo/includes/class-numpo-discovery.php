<?php
if(!defined('ABSPATH')) exit;
class Numpo_Discovery {
 public static function seed_job($job,$project,$seed,$config=[]){
  $base=Numpo_Crawler::normalize_url($seed);if(!$base)return;
  $host=wp_parse_url($base,PHP_URL_HOST);if(!$host)return;
  $origin=Numpo_Crawler::normalize_url((wp_parse_url($base,PHP_URL_SCHEME)?:'https').'://'.$host.'/');
  $urls=[$origin.'robots.txt',$origin.'sitemap.xml',$origin.'sitemap_index.xml'];
  foreach($urls as $url){
   $r=Numpo_Crawler::fetch($url,10,524288);if(is_wp_error($r))continue;
   $domain=Numpo_DB::ensure_domain($project,Numpo_Crawler::domain($host));
   if(substr($url,-11)==='/robots.txt'){Numpo_DB::add_fact($job,$domain,'discovery','robots',$url,$url,$base,1,'robots.txt');self::robots_sitemaps($r['body'],$job,$project,$domain,$base);}
   else {Numpo_DB::add_fact($job,$domain,'discovery','sitemap',$url,$url,$base,1,'sitemap.xml');self::sitemap_urls($r['body'],$job,$domain,$base);}
  }
 }
 private static function robots_sitemaps($body,$job,$project,$domain,$source){
  if(!preg_match_all('/^\s*Sitemap:\s*(\S+)/im',$body,$m))return;
  foreach(array_slice($m[1],0,10) as $url){$r=Numpo_Crawler::fetch($url,10,1048576,['respect_robots'=>false,'rate_limit_ms'=>250]);if(is_wp_error($r))continue;Numpo_DB::add_fact($job,$domain,'discovery','sitemap',$url,$url,$source,.95,'robots Sitemap directive');self::sitemap_urls($r['body'],$job,$domain,$source,0);}
 }
 private static function sitemap_urls($xml,$job,$domain,$source,$depth=0){
  if(!preg_match_all('/<loc>\s*(.*?)\s*<\/loc>/is',$xml,$m))return;
  foreach(array_slice($m[1],0,200) as $url){$url=html_entity_decode(trim(strip_tags($url)));if(!Numpo_Crawler::normalize_url($url))continue;\n   if(preg_match('/<sitemapindex|<sitemap>/i',$xml)&&$depth<2){$r=Numpo_Crawler::fetch($url,10,1048576,['respect_robots'=>false,'rate_limit_ms'=>250]);if(!is_wp_error($r))self::sitemap_urls($r['body'],$job,$domain,$source,$depth+1);continue;}\n   Numpo_DB::add_candidate($job,$url,'sitemap',$source,80,.9,1);}
 }
 public static function seed_links($job,$project,$seed,$body,$source){
  $domain=Numpo_DB::ensure_domain($project,Numpo_Crawler::domain((string)wp_parse_url($seed,PHP_URL_HOST)));
  foreach(self::socials($body) as $s)Numpo_DB::add_fact($job,$domain,'social',$s[0],$s[1],$s[1],$source,.9,'public link');
  foreach(self::business($body) as $b)Numpo_DB::add_fact($job,$domain,'business',$b[0],$b[1],sanitize_title($b[1]),$source,.65,'visible page text');
 }
 private static function socials($body){
  $out=[];$patterns=['instagram'=>'instagram\.com/[A-Za-z0-9_.-]+','telegram'=>'t\.me/[A-Za-z0-9_+-]+','whatsapp'=>'wa\.me/[0-9]+','linkedin'=>'linkedin\.com/(?:company|in)/[A-Za-z0-9_.-]+','facebook'=>'facebook\.com/[A-Za-z0-9_.-]+','youtube'=>'youtube\.com/(?:@|channel/|c/)[A-Za-z0-9_.-]+','x'=>'(?:x|twitter)\.com/[A-Za-z0-9_.-]+'];
  foreach($patterns as $type=>$pattern)if(preg_match_all('~https?://(?:www\.)?'.$pattern.'~i',$body,$m))foreach(array_unique($m[0]) as $u)$out[]=[$type,$u];
  return $out;
 }
 private static function business($body){
  $out=[];$doc=new DOMDocument();libxml_use_internal_errors(true);@$doc->loadHTML('<?xml encoding="UTF-8">'.$body);libxml_clear_errors();
  $names=['address','business_name'];foreach($doc->getElementsByTagName('address') as $node){$v=trim(preg_replace('/\s+/',' ',$node->textContent));if($v!=='')$out[]=['address',$v];}
  foreach($doc->getElementsByTagName('meta') as $meta){$p=strtolower($meta->getAttribute('property'));$n=strtolower($meta->getAttribute('name'));if(in_array($p,['og:site_name','og:title'],true)||$n==='application-name'){$v=trim($meta->getAttribute('content'));if($v!=='')$out[]=['brand_name',$v];}}
  return $out;
 }
}
