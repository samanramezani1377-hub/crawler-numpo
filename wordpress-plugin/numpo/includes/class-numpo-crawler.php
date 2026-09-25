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
 public static function domain($host){
  $host=strtolower(trim($host));$host=trim($host,'.');
  if($host===''||filter_var($host,FILTER_VALIDATE_IP))return $host;
  $parts=explode('.',$host);$n=count($parts);if($n<=2)return $host;
  $suffix2=['co.uk','org.uk','ac.uk','gov.uk','com.au','net.au','org.au','co.jp','co.nz','com.br','com.tr','com.cn'];
  $tail2=$parts[$n-2].'.'.$parts[$n-1];if(in_array($tail2,$suffix2,true)&&$n>=3)return implode('.',array_slice($parts,-3));
  return $tail2;
 }
 private static function private_host($host){
  if(filter_var($host,FILTER_VALIDATE_IP)){return self::private_ip($host);}
  if(in_array($host,['localhost','localhost.localdomain'],true)||substr($host,-6)==='.local'||substr($host,-10)==='.localhost')return true;
  if(filter_var($host,FILTER_VALIDATE_IP,FILTER_FLAG_IPV6))return self::private_ip($host);\n  $ips=@gethostbynamel($host);if(is_array($ips)){foreach($ips as $ip)if(self::private_ip($ip))return true;}\n  if(function_exists('dns_get_record')){foreach((array)@dns_get_record($host,DNS_AAAA) as $r){if(!empty($r['ipv6'])&&self::private_ip($r['ipv6']))return true;}}\n  return false;
 }
 private static function private_ip($ip){
  if(!filter_var($ip,FILTER_VALIDATE_IP))return true;
  return !filter_var($ip,FILTER_VALIDATE_IP,FILTER_FLAG_NO_PRIV_RANGE|FILTER_FLAG_NO_RES_RANGE);
 }
 public static function fetch($url,$timeout=15,$max_bytes=2097152){
  $url=self::normalize_url($url);if(!$url)return new WP_Error('ssrf_blocked','URL is invalid or resolves to a private/local address.');
  $current=$url;$response=null;
  for($hop=0;$hop<=3;$hop++){
   $response=wp_safe_remote_get($current,['timeout'=>max(1,$timeout),'redirection'=>0,'limit_response_size'=>max(1024,$max_bytes),'user-agent'=>'Numpo PHP Crawler/1.0','headers'=>['Accept'=>'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8']]);
   if(is_wp_error($response))return $response;
   $status=(int)wp_remote_retrieve_response_code($response);
   if($status>=300&&$status<400){
    $location=(string)wp_remote_retrieve_header($response,'location');
    if($location==='')return new WP_Error('redirect_missing_location','HTTP redirect without a Location header.');
    $next=self::resolve($current,$location);
    if(!$next)return new WP_Error('ssrf_blocked','Redirect target is invalid or resolves to a private/local address.');
    $current=$next;continue;
   }
   break;
  }
  if(!$response)return new WP_Error('http_error','No HTTP response.');
  if($status>=300&&$status<400)return new WP_Error('redirect_limit','Too many redirects.');
  $url=$current;$body=(string)wp_remote_retrieve_body($response);
  $type=(string)wp_remote_retrieve_header($response,'content-type');
  if($status===429||$status>=500)return new WP_Error('http_'.$status,'HTTP '.$status);
  $title='';$links=[];
  if(stripos($type,'text/html')!==false||stripos($body,'<html')!==false){$doc=new DOMDocument();libxml_use_internal_errors(true);@$doc->loadHTML('<?xml encoding="UTF-8">'. $body);libxml_clear_errors();$titles=$doc->getElementsByTagName('title');if($titles->length)$title=trim($titles->item(0)->textContent);
   foreach($doc->getElementsByTagName('a') as $a){$href=trim((string)$a->getAttribute('href'));$n=self::resolve($url,$href);if($n)$links[$n]=true;if(count($links)>=50)break;}
  }
  $headers=[];foreach(['server','x-powered-by','via','cf-ray','x-cache','x-cache-hits'] as $hn){$hv=(string)wp_remote_retrieve_header($response,$hn);if($hv!=='')$headers[]=[$hn,$hv];}\n  return ['url'=>$url,'status'=>$status,'title'=>$title,'content_type'=>$type,'body'=>$body,'links'=>array_keys($links),'headers'=>$headers];
 }
 private static function resolve($base,$href){
  $href=trim(html_entity_decode($href,ENT_QUOTES,'UTF-8'));if($href===''||$href[0]==='#'||stripos($href,'javascript:')===0||stripos($href,'mailto:')===0||stripos($href,'tel:')===0)return null;
  if(strpos($href,'//')===0){$p=wp_parse_url($base);$href=$p['scheme'].':'.$href;}
  elseif(!preg_match('#^[a-z][a-z0-9+.-]*://#i',$href)){
   $b=wp_parse_url($base);if(empty($b['scheme'])||empty($b['host']))return null;
   $origin=strtolower($b['scheme']).'://'.$b['host'].(!empty($b['port'])?':'.$b['port']:'');
   if($href[0]==='/')$href=$origin.$href;
   else{$path=$b['path']??'/';$dir=rtrim(str_replace('\\','/',dirname($path)),'/');$href=$origin.'/'.($dir!==''&&$dir!=='/'?trim($dir,'/').'/':'').ltrim($href,'/');}
  }
  $n=self::normalize_url($href);if(!$n)return null;
  $p=wp_parse_url($n);if(empty($p['host']))return null;
  $segments=[];foreach(explode('/',($p['path']??'/')) as $seg){if($seg===''||$seg==='.'){continue;}if($seg==='..'){array_pop($segments);continue;}$segments[]=$seg;}
  $path='/'.implode('/',$segments);if(($p['path']??'/')==='/'||substr($p['path']??'',-1)==='/')$path.='/';
  $out=strtolower($p['scheme']).'://'.strtolower($p['host']).(!empty($p['port'])?':'.$p['port']:'').$path;if(!empty($p['query']))$out.='?'.$p['query'];return $out;
 }
 public static function technologies($body,$url){
  $l=strtolower($body);$out=[];$seen=[];
  $markers=['WordPress'=>['wp-content/','wp-includes/'],'WooCommerce'=>['woocommerce','wc-ajax'],'Shopify'=>['cdn.shopify.com','shopify.theme'],'Joomla'=>['/media/jui/','com_content'],'Magento'=>['mage-cache-storage','magento'],'Laravel'=>['laravel_session','laravel'],'Google Analytics'=>['google-analytics.com','gtag('],'Google Tag Manager'=>['googletagmanager.com','gtm.js'],'Meta Pixel'=>['connect.facebook.net','fbq('],'Stripe'=>['js.stripe.com','stripe.com/v3'],'Cloudflare'=>['cdnjs.cloudflare.com','cloudflareinsights.com']];
  foreach($markers as $name=>$needles){foreach($needles as $needle)if(strpos($l,$needle)!==false){$out[]=[$name,.9,$needle];$seen[$name]=1;break;}}
  if(preg_match_all('#<meta[^>]+name=["\\\']generator["\\\'][^>]+content=["\\\']([^"\\\']+)["\\\']#i',$body,$m))foreach($m[1] as $v){$v=trim($v);if($v!==''){$name=preg_replace('/\\s+.*$/','',$v);if($name!==''&&!isset($seen[$name])){$out[]=[$name,.85,'meta:generator='.$v];$seen[$name]=1;}}}
  if(preg_match_all('#<(?:script|link)[^>]+(?:src|href)=["\\\']([^"\\\']+)["\\\']#i',$body,$m))foreach($m[1] as $src){$s=strtolower($src);$map=['Elementor'=>'elementor','Yoast SEO'=>'yoast','Google Maps'=>'maps.googleapis.com','reCAPTCHA'=>'google.com/recaptcha','Cloudflare Turnstile'=>'challenges.cloudflare.com'];foreach($map as $name=>$needle)if(strpos($s,$needle)!==false&&!isset($seen[$name])){$out[]=[$name,.8,$needle];$seen[$name]=1;}}
  return $out;
 }
 public static function metadata($body){
  $out=[];$patterns=[
   'title'=>'#<title[^>]*>(.*?)</title>#is',
   'description'=>'#<meta[^>]+(?:name|property)=["\\\'](?:description|og:description)["\\\'][^>]+content=["\\\'](.*?)["\\\']#is',
   'site_name'=>'#<meta[^>]+property=["\\\']og:site_name["\\\'][^>]+content=["\\\'](.*?)["\\\']#is',
   'canonical'=>'#<link[^>]+rel=["\\\']canonical["\\\'][^>]+href=["\\\'](.*?)["\\\']#is'
  ];
  foreach($patterns as $type=>$re)if(preg_match($re,$body,$m)){$v=trim(html_entity_decode(strip_tags($m[1]),ENT_QUOTES,'UTF-8'));if($v!=='')$out[]=[$type,$v];}
  return $out;
 }
 public static function socials($body){
  $out=[];$seen=[];if(!preg_match_all('#https?://[^"\\\'<>\\s]+#i',$body,$m))return $out;
  $map=['instagram.com'=>'instagram','t.me'=>'telegram','telegram.me'=>'telegram','wa.me'=>'whatsapp','whatsapp.com'=>'whatsapp','linkedin.com'=>'linkedin','facebook.com'=>'facebook','youtube.com'=>'youtube','x.com'=>'x','twitter.com'=>'twitter'];
  foreach($m[0] as $url){$host=strtolower((string)wp_parse_url($url,PHP_URL_HOST));foreach($map as $needle=>$type)if($host===$needle||substr($host,-strlen('.'.$needle))==='.'.ltrim($needle,'.')){ $key=$type.'|'.$url;if(!isset($seen[$key])){$seen[$key]=1;$out[]=[$type,$url];}break;}}
  return $out;
 }
 public static function business($body){
  $out=[];$types=['address'=>'#<address[^>]*>(.*?)</address>#is','phone'=>'#(?:tel:|تلفن|phone|تماس)[^<]{0,80}(\\+?[0-9۰-۹][0-9۰-۹\\s().-]{7,})#iu'];
  foreach($types as $type=>$re)if(preg_match_all($re,$body,$m))foreach($m[1] as $v){$v=trim(preg_replace('/\\s+/',' ',strip_tags(html_entity_decode($v,ENT_QUOTES,'UTF-8'))));if($v!=='')$out[]=[$type,$v];}
  if(preg_match('#<meta[^>]+property=["\\\']og:title["\\\'][^>]+content=["\\\'](.*?)["\\\']#is',$body,$m)&&trim($m[1])!=='')$out[]=['brand',trim(html_entity_decode($m[1],ENT_QUOTES,'UTF-8'))];
  return $out;
 }
 public static function headers($response){
  $out=[];$headers=wp_remote_retrieve_headers($response);
  foreach(['server','x-powered-by','via','cf-ray','x-cache','x-cache-hits'] as $name){$v=(string)$headers->get($name);if($v!=='')$out[]=[$name,$v];}
  return $out;
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
