<?php
if(!defined('ABSPATH')) exit;
class Numpo_Diagnostics {
 public static function init(){add_action('admin_notices',[__CLASS__,'notice']);}
 public static function check(){
  $checks=[];
  $checks['php']=['label'=>'PHP','ok'=>version_compare(PHP_VERSION,'7.4','>='),'value'=>PHP_VERSION,'required'=>true];
  $checks['curl']=['label'=>'WordPress HTTP / cURL','ok'=>function_exists('wp_safe_remote_get'),'value'=>function_exists('wp_safe_remote_get')?'available':'missing','required'=>true];
  $checks['dom']=['label'=>'DOMDocument','ok'=>class_exists('DOMDocument'),'value'=>class_exists('DOMDocument')?'available':'missing','required'=>true];
  global $wpdb;$checks['database']=['label'=>'WordPress database','ok'=>isset($wpdb)&&!empty($wpdb->dbh),'value'=>isset($wpdb)&&!empty($wpdb->dbh)?'connected':'unavailable','required'=>true];
  $tables=Numpo_DB::tables();$missing=[];foreach($tables as $name=>$table){$found=$wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s",$table));if($found!==$table)$missing[]=$name;}
  $checks['schema']=['label'=>'Numpo database schema','ok'=>!$missing,'value'=>$missing?'missing: '.implode(', ',$missing):'ready','required'=>true];
  $required_ok=true;foreach($checks as $c)if($c['required']&&!$c['ok'])$required_ok=false;
  return ['ok'=>$required_ok,'checks'=>$checks];
 }
 public static function notice(){
  if(!current_user_can('manage_options'))return;
  $d=self::check();$failed=[];foreach($d['checks'] as $c)if(!$c['ok'])$failed[]=$c;
  if(!$failed)return;
  echo '<div class="notice notice-error"><p><strong>Numpo PHP Runtime:</strong> پیش‌نیازهای اصلی کامل نیستند. ';
  foreach($failed as $c)echo esc_html($c['label'].': '.$c['value'].' ('.($c['required']?'مسدودکننده':'اختیاری').')').' ';
  echo '</p></div>';
 }
}
