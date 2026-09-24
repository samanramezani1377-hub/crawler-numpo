<?php
if(!defined('ABSPATH')) exit;
class Numpo_Settings {
 public static function init(){ add_action('admin_init',[__CLASS__,'register']); }
 public static function register(){
  register_setting('numpo','numpo_engine_url',['sanitize_callback'=>'esc_url_raw']);
  register_setting('numpo','numpo_api_key',['sanitize_callback'=>'sanitize_text_field']);
 }
 public static function engine_url(){return rtrim((string)get_option('numpo_engine_url',''),' /');}
 public static function api_key(){return (string)get_option('numpo_api_key','');}
}