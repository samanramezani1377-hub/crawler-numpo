<?php
if(!defined('ABSPATH')) exit;
class Numpo_API {
 public static function init(){add_action('rest_api_init',[__CLASS__,'routes']);}
 public static function routes(){
  register_rest_route('numpo/v1','/jobs',['methods'=>'POST','permission_callback'=>[__CLASS__,'permission'],'callback'=>[__CLASS__,'create']]);
  register_rest_route('numpo/v1','/jobs/(?P<id>[A-Za-z0-9-]+)',['methods'=>'GET','permission_callback'=>[__CLASS__,'permission'],'callback'=>[__CLASS__,'get']]);
  register_rest_route('numpo/v1','/jobs/(?P<id>[A-Za-z0-9-]+)/candidates',['methods'=>'GET','permission_callback'=>[__CLASS__,'permission'],'callback'=>[__CLASS__,'candidates']]);
  register_rest_route('numpo/v1','/jobs/(?P<id>[A-Za-z0-9-]+)/cancel',['methods'=>'POST','permission_callback'=>[__CLASS__,'permission'],'callback'=>[__CLASS__,'cancel']]);register_rest_route('numpo/v1','/jobs/(?P<id>[A-Za-z0-9-]+)/(?P<resource>domains|hosts|pages|technologies|contacts|business|social|classifications|probes)',['methods'=>'GET','permission_callback'=>[__CLASS__,'permission'],'callback'=>[__CLASS__,'resource']]);register_rest_route('numpo/v1','/jobs/(?P<id>[A-Za-z0-9-]+)/errors',['methods'=>'GET','permission_callback'=>[__CLASS__,'permission'],'callback'=>[__CLASS__,'errors']]);register_rest_route('numpo/v1','/jobs/(?P<id>[A-Za-z0-9-]+)/csv',['methods'=>'POST','permission_callback'=>[__CLASS__,'permission'],'callback'=>[__CLASS__,'csv']]);
 }
 public static function permission(){return current_user_can('manage_options');}
 private static function call($method,$path,$body=null){
  $url=Numpo_Settings::engine_url().'/api/v1'.$path;$args=['method'=>$method,'timeout'=>30,'headers'=>['Accept'=>'application/json']];
  if($k=Numpo_Settings::api_key())$args['headers']['Authorization']='Bearer '.$k;
  if($body!==null){$args['headers']['Content-Type']='application/json';$args['body']=wp_json_encode($body);}
  $r=wp_remote_request($url,$args);if(is_wp_error($r))return new WP_Error('engine_unavailable',$r->get_error_message(),['status'=>502]);
  $status=wp_remote_retrieve_response_code($r);$data=json_decode(wp_remote_retrieve_body($r),true);
  if($status<200||$status>=300)return new WP_Error($data['error']['code']??'engine_error',$data['error']['message']??'Engine request failed',['status'=>$status]);
  return new WP_REST_Response($data,$status);
 }
 public static function create(WP_REST_Request $r){$p=$r->get_json_params();if(!is_array($p))$p=$r->get_body_params();return self::call('POST','/discovery/jobs',$p);}
 public static function get(WP_REST_Request $r){return self::call('GET','/discovery/jobs/'.rawurlencode($r['id']));}
 public static function candidates(WP_REST_Request $r){return self::call('GET','/discovery/jobs/'.rawurlencode($r['id']).'/candidates');}
 public static function cancel(WP_REST_Request $r){return self::call('POST','/discovery/jobs/'.rawurlencode($r['id']).'/cancel');} public static function resource(WP_REST_Request $r){$path='/discovery/jobs/'.rawurlencode($r['id']).'/'.rawurlencode($r['resource']);$q=$r->get_query_params();if($q){$path.='?'.http_build_query($q);}$url=Numpo_Settings::engine_url().'/api/v1'.$path;$args=['method'=>'GET','timeout'=>30,'headers'=>['Accept'=>'application/json']];if($k=Numpo_Settings::api_key())$args['headers']['Authorization']='Bearer '.$k;$res=wp_remote_request($url,$args);if(is_wp_error($res))return new WP_Error('engine_unavailable',$res->get_error_message(),['status'=>502]);$status=wp_remote_retrieve_response_code($res);$data=json_decode(wp_remote_retrieve_body($res),true);if($status<200||$status>=300)return new WP_Error($data['error']['code']??'engine_error',$data['error']['message']??'Engine request failed',['status'=>$status]);return new WP_REST_Response($data,$status);} public static function errors(WP_REST_Request $r){return self::call('GET','/discovery/jobs/'.rawurlencode($r['id']).'/errors');} public static function csv(WP_REST_Request $r){$url=Numpo_Settings::engine_url().'/api/v1/discovery/jobs/'.rawurlencode($r['id']).'/csv';$args=['method'=>'POST','timeout'=>60,'headers'=>['Accept'=>'application/json','Content-Type'=>'text/csv'],'body'=>$r->get_body()];if($k=Numpo_Settings::api_key())$args['headers']['Authorization']='Bearer '.$k;$res=wp_remote_request($url,$args);if(is_wp_error($res))return new WP_Error('engine_unavailable',$res->get_error_message(),['status'=>502]);$status=wp_remote_retrieve_response_code($res);$body=wp_remote_retrieve_body($res);$data=json_decode($body,true);if($status<200||$status>=300)return new WP_Error($data['error']['code']??'engine_error',$data['error']['message']??'Engine request failed',['status'=>$status]);return new WP_REST_Response($data,$status);}
}