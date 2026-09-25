<?php
if(!defined('ABSPATH')) exit;

class Numpo_Crawler {
 public static function normalize_url($raw){
  $raw=trim((string)$raw);if($raw==='')return null;$u=wp_parse_url($raw);
  if(!$u||empty($u['scheme'])||!in_array(strtolower($u['scheme']),['http','https'],true)||empty($u['host']))return null;
  $host=strtolower($u['host']);if(self::private_host($host))return null;
  $path=$u['path']??'/';if($path==='')$path='/';
  $out=strtolower($u['scheme']).'://'.$host.$path;
  if(!empty($u['query']))$out.='?'.$u['query'];return rtrim($out,'#');
 }
 public static function domain($host){$host=strtolower(trim($host));$parts=explode('.',$host);return count($parts)>=2?implode('.',array_slice($parts,-2)):$host;}
 private static function private_host($host){
  if(filter_var($host,FILTER_VALIDATE_IP)){return self::private_ip($host);}
  if(in_array($host,['localhost','localhost.localdomain'],true)||substr($host,-6)==='.local')return true;
  $ips=@gethostbynamel($host);if(is_array($ips)){foreach($ips as $ip)if(self::private_ip($ip))return true;}return false;
 }
 private static function private_ip($ip){
  if(!filter_var($ip,FILTER_VALIDATE_IP))return true;
  return !filter_var($ip,FILTER_VALIDATE_IP,FILTER_FLAG_NO_PRIV_RANGE|FILTER_FLAG_NO_RES_RANGE);
 }
 public static function fetch($url,$timeout=15,$max_bytes=2097152){
  $url=self::normalize_url($url);if(!$url)return new WP_Error('ssrf_blocked','URL is invalid or resolves to a private/local address.');
  $response=wp_safe_remote_get($url,['timeout'=>max(1,$timeout),'redirection'=>3,'limit_response_size'=>max(1024,$max_bytes),'user-agent'=>'Numpo PHP Crawler/1.0','headers'=>['Accept'=>'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8']]);
  if(is_wp_error($response))return $response;
  $status=(int)wp_remote_retrieve_response_code($response);$body=(string)wp_remote_retrieve_body($response);
  $type=(string)wp_remote_retrieve_header($response,'content-type');
  if($status===429||$status>=500)return new WP_Error('http_'.$status,'HTTP '.$status);
  $title='';$links=[];
  if(stripos($type,'text/html')!==false||stripos($body,'<html')!==false){$doc=new DOMDocument();libxml_use_internal_errors(true);@$doc->loadHTML('<?xml encoding="UTF-8">'. $body);libxml_clear_errors();$titles=$doc->getElementsByTagName('title');if($titles->length)$title=trim($titles->item(0)->textContent);
   foreach($doc->getElementsByTagName('a') as $a){$href=trim((string)$a->getAttribute('href'));$n=self::resolve($url,$href);if($n)$links[$n]=true;if(count($links)>=50)break;}
  }
  return ['url'=>$url,'status'=>$status,'title'=>$title,'content_type'=>$type,'body'=>$body,'links'=>array_keys($links)];
 }
 private static function resolve($base,$href){
  if($href===''||$href[0]==='#'||stripos($href,'javascript:')===0||stripos($href,'mailto:')===0)return null;
  if(strpos($href,'//')===0){$p=wp_parse_url($base);$href=$p['scheme'].':'.$href;}
  elseif($href[0]!=='/'){ $b=wp_parse_url($base);$dir=isset($b['path'])?dirname($b['path']):'/';$href=rtrim($b['scheme'].'://'.$b['host'],'/').'/'.ltrim(($dir==='.'?'':$dir).'/'.$href,'/'); }
  return self::normalize_url($href);
 }
 public static function technologies($body,$url){
  $l=strtolower($body);$out=[];
  $markers=['WordPress'=>['wp-content/','wp-includes/'],'WooCommerce'=>['woocommerce','wc-ajax'],'Shopify'=>['cdn.shopify.com','shopify.theme'],'Joomla'=>['/media/jui/','com_content'],'Magento'=>['mage-cache-storage','magento'],'Laravel'=>['laravel_session','laravel'],'Google Analytics'=>['google-analytics.com','gtag('],'Meta Pixel'=>['connect.facebook.net','fbq('],'Stripe'=>['stripe.com','js.stripe.com']];
  foreach($markers as $name=>$needles){foreach($needles as $needle)if(strpos($l,$needle)!==false){$out[]=[$name,.9,$needle];break;}}return $out;
 }
 public static function contacts($body){
  $out=[];$seen=[];preg_match_all('/[a-z0-9._%+\-]+@[a-z0-9.\-]+\.[a-z]{2,}/i',$body,$emails);
  foreach($emails[0]??[] as $v){$v=strtolower($v);if(empty($seen['e'.$v])){$seen['e'.$v]=1;$out[]=['email',$v,$v];}}
  preg_match_all('/\+?\d[\d\s().-]{7,}\d/',$body,$phones);
  foreach($phones[0]??[] as $v){$n=preg_replace('/\D+/','',$v);if(strlen($n)>=8&&!empty($n)&&empty($seen['p'.$n])){$seen['p'.$n]=1;$out[]=['phone',trim($v),$n];}}
  return $out;
 }
 public static function classify($body,$url){
  $l=strtolower($body);$path=strtolower((string)(wp_parse_url($url,PHP_URL_PATH)??'/'));$out=[];
  $add=function($c,$conf,$e)use(&$out,$url){$out[]=[$c,$conf,$e];};
  if($path==='/'||$path==='')$add('homepage',.95,'root path');
  if(strpos($path,'contact')!==false||strpos($l,'contact us')!==false||strpos($body,'تماس با ما')!==false)$add('contact',.9,'contact marker');
  if(strpos($path,'about')!==false||strpos($l,'about us')!==false||strpos($body,'درباره ما')!==false)$add('about',.85,'about marker');
  if(strpos($path,'product')!==false||strpos($l,'add to cart')!==false||strpos($body,'افزودن به سبد')!==false)$add('product',.8,'product marker');
  if(strpos($path,'category')!==false)$add('category',.75,'category path');
  if(strpos($path,'blog')!==false||strpos($path,'news')!==false)$add('blog',.7,'blog/news path');
  return $out;
 }
}
