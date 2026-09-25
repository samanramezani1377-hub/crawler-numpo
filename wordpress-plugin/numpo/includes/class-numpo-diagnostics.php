<?php
if(!defined('ABSPATH')) exit;

class Numpo_Diagnostics {
 public static function init(){
  add_action('admin_notices',[__CLASS__,'notice']);
 }
 public static function check(){
  $checks=[];
  $engine=trailingslashit(NUMPO_DIR).'engine/numpo-engine';
  $engine_file=is_file($engine);
  $engine_exec=$engine_file && is_executable($engine);
  $engine_dir=is_dir(trailingslashit(NUMPO_DIR).'engine');

  $checks['os']=[
   'label'=>'Linux',
   'ok'=>stripos(PHP_OS_FAMILY,'Linux')===0,
   'value'=>PHP_OS_FAMILY,
   'required'=>true
  ];
  $arch=php_uname('m');
  $checks['arch']=[
   'label'=>'CPU architecture',
   'ok'=>in_array(strtolower($arch),['x86_64','amd64'],true),
   'value'=>$arch,
   'required'=>true
  ];
  $checks['exec']=[
   'label'=>'PHP exec()',
   'ok'=>function_exists('exec'),
   'value'=>function_exists('exec')?'enabled':'disabled',
   'required'=>true
  ];
  $engine_value='missing';
  if($engine_file){
   if(!$engine_exec){
    $engine_value='present, not executable';
   }else{
    $engine_value='present, executable';
   }
  }elseif($engine_dir){
   $engine_value='engine directory present, binary missing';
  }elseif(!is_dir(NUMPO_DIR)){
   $engine_value='plugin directory missing';
  }
  $checks['engine']=[
   'label'=>'Bundled Go engine',
   'ok'=>$engine_file && $engine_exec,
   'value'=>$engine_value,
   'required'=>true
  ];
  $checks['engine_path']=[
   'label'=>'Engine path',
   'ok'=>$engine_file,
   'value'=>$engine,
   'required'=>false
  ];

  $cache=trailingslashit(NUMPO_DIR).'engine/postgres-cache';
  $cache_dir=is_dir($cache);
  $cache_items=$cache_dir ? glob($cache.'/*') : false;
  $cache_ready=$cache_dir && is_array($cache_items) && count($cache_items)>0;
  $checks['postgres']=[
   'label'=>'Bundled PostgreSQL runtime',
   'ok'=>$cache_ready,
   'value'=>$cache_ready?'present':($cache_dir?'directory empty or unreadable':'missing'),
   'required'=>true
  ];

  $browser=self::find_browser();
  $checks['chromium']=[
   'label'=>'Chromium',
   'ok'=>$browser!==null,
   'value'=>$browser??'not found',
   'required'=>false
  ];

  $required_ok=true;
  foreach($checks as $c) if($c['required']&&!$c['ok']) $required_ok=false;
  return ['ok'=>$required_ok,'checks'=>$checks];
 }
 private static function find_browser(){
  $configured=trim((string)get_option('numpo_browser_binary',''));
  $candidates=array_filter([
   $configured,
   '/usr/bin/chromium',
   '/usr/bin/chromium-browser',
   '/usr/bin/google-chrome',
   '/usr/bin/google-chrome-stable',
   '/snap/bin/chromium'
  ]);
  foreach($candidates as $path){
   if(is_file($path)&&is_executable($path)) return $path;
  }
  if(function_exists('exec')){
   $out=[];$code=1;
   @exec('command -v chromium 2>/dev/null || command -v chromium-browser 2>/dev/null || command -v google-chrome 2>/dev/null || command -v google-chrome-stable 2>/dev/null',$out,$code);
   if($code===0&&!empty($out[0])) return trim($out[0]);
  }
  return null;
 }
 public static function notice(){
  if(!current_user_can('manage_options')) return;
  $d=self::check();
  $failed=[];
  foreach($d['checks'] as $c) if(!$c['ok']) $failed[]=$c;
  if(!$failed) return;
  echo '<div class="notice notice-'.($d['ok']?'warning':'error').'"><p><strong>Numpo Runtime:</strong> ';
  if(!$d['ok']) echo 'پیش‌نیازهای اصلی Runtime کامل نیستند. ';
  foreach($failed as $c){
   $suffix=$c['required']?'مسدودکننده':'اختیاری';
   echo esc_html($c['label'].': '.$c['value'].' ('.$suffix.')').' ';
  }
  echo '</p><p>Chromium فقط برای Browser Rendering لازم است؛ HTTP crawler و PostgreSQL داخلی بدون Chromium قابل اجرا هستند.</p></div>';
 }
}
