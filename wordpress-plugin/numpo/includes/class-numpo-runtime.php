<?php
if(!defined('ABSPATH')) exit;

class Numpo_Runtime {
 public static function init(){
  add_action('admin_init',[__CLASS__,'ensure_started'],1);
  add_action('rest_api_init',[__CLASS__,'ensure_started'],1);
 }
 public static function paths(){
  $u=wp_upload_dir();
  $base=trailingslashit($u['basedir']).'numpo-runtime/';
  return [
   'base'=>$base,
   'runtime'=>$base.'runtime/',
   'logs'=>$base.'logs/',
   'pid'=>$base.'engine.pid',
   'port'=>$base.'engine.port',
  ];
 }
 private static function engine_path(){return trailingslashit(NUMPO_DIR).'engine/numpo-engine';}
 private static function is_running($pid){
  $pid=absint($pid); if(!$pid)return false;
  if(function_exists('posix_kill')) return @posix_kill($pid,0);
  $out=[];$code=1; @exec('kill -0 '.(int)$pid.' 2>/dev/null',$out,$code); return $code===0;
 }
 private static function choose_port(){
  $p=(int)get_option('numpo_engine_port',18080);
  if($p<1024||$p>65500)$p=18080;
  return $p;
 }
 public static function activate(){
  $p=self::paths();
  foreach(['base','runtime','logs'] as $k) wp_mkdir_p($p[$k]);
  if(!get_option('numpo_engine_port')) add_option('numpo_engine_port',self::choose_port(),'',false);
  if(!get_option('numpo_api_key')) add_option('numpo_api_key',wp_generate_password(48,true,true), '', false);
  if(!get_option('numpo_engine_url')) add_option('numpo_engine_url','http://127.0.0.1:'.self::choose_port());
 }
 public static function ensure_started(){
  if(self::mode()==='external') return;
  if(!is_admin() && !defined('REST_REQUEST')) return;
  $engine=self::engine_path(); if(!is_file($engine) || !is_executable($engine)) return;
  $p=self::paths(); foreach(['base','runtime','logs'] as $k) wp_mkdir_p($p[$k]);
  $pid=is_file($p['pid'])?(int)trim((string)@file_get_contents($p['pid'])):0;
  if(self::is_running($pid)) return;
  self::start();
 }
 private static function mode(){return Numpo_Settings::runtime_mode();}
 public static function start(){
  if(self::mode()==='external') return false;
  $engine=self::engine_path(); if(!is_file($engine)) return false;
  $p=self::paths(); foreach(['base','runtime','logs'] as $k) wp_mkdir_p($p[$k]);
  $port=self::choose_port(); $key=Numpo_Settings::api_key();
  $env=[
   'NUMPO_LISTEN_ADDR'=>'127.0.0.1:'.$port,
   'NUMPO_API_KEY'=>$key,
   'NUMPO_ALLOW_ANONYMOUS_API'=>'false',
   'NUMPO_EMBEDDED_POSTGRES'=>'true',
   'NUMPO_EMBEDDED_RUNTIME_DIR'=>$p['runtime'],
   'NUMPO_EMBEDDED_POSTGRES_CACHE_DIR'=>trailingslashit(NUMPO_DIR).'engine/postgres-cache',
   'NUMPO_MIGRATIONS_DIR'=>trailingslashit(NUMPO_DIR).'migrations',
   'NUMPO_BROWSER_BINARY'=>(string)get_option('numpo_browser_binary',''),
  ];
  $parts=[];
  foreach($env as $k=>$v)$parts[]=$k.'='.escapeshellarg($v);
  $log=escapeshellarg($p['logs'].'engine.log');
  $cmd='nohup '.implode(' ',$parts).' '.escapeshellarg($engine).' >> '.$log.' 2>&1 & echo $!';
  $out=[];$code=1;@exec($cmd,$out,$code);
  if($code!==0 || empty($out[0])) return false;
  @file_put_contents($p['pid'],trim($out[0]),LOCK_EX);
  update_option('numpo_engine_url','http://127.0.0.1:'.$port,false);
  return true;
 }
 public static function stop(){
  $p=self::paths(); $pid=is_file($p['pid'])?(int)trim((string)@file_get_contents($p['pid'])):0;
  if($pid){
   @exec('kill -TERM '.(int)$pid.' 2>/dev/null');
   $deadline=time()+10;
   while(self::is_running($pid)&&time()<$deadline)usleep(200000);
   if(self::is_running($pid))@exec('kill -KILL '.(int)$pid.' 2>/dev/null');
  }
  @unlink($p['pid']);
 }
 public static function uninstall(){
  self::stop();
  $p=self::paths();
  // Runtime binaries, logs and temp files are owned by Numpo and may be removed.
  foreach([$p['runtime'],$p['logs']] as $dir) self::remove_dir($dir);
  @unlink($p['pid']); @unlink($p['port']);
  delete_option('numpo_engine_url'); delete_option('numpo_api_key'); delete_option('numpo_engine_port');
 }
 private static function remove_dir($dir){
  if(!is_dir($dir))return;
  $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST);
  foreach($it as $f){$f->isDir()?@rmdir($f->getPathname()):@unlink($f->getPathname());}
  @rmdir($dir);
 }
}
