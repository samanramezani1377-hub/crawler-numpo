<?php
if(!defined('ABSPATH')) exit;
class Numpo_Runtime {
 public static function init(){}
 public static function activate(){Numpo_DB::install();}
 public static function ensure_started(){return true;}
 public static function start(){return true;}
 public static function stop(){}
 public static function uninstall(){}
}
