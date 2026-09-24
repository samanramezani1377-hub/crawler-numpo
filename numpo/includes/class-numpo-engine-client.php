<?php
if (!defined('ABSPATH')) exit;

final class Numpo_Engine_Client {
    const OPTION_URL='numpo_engine_url';
    const OPTION_KEY='numpo_engine_key';

    public static function init() {
        add_action('admin_init', array(__CLASS__,'register_settings'));
    }

    public static function register_settings() {
        register_setting('numpo_settings','numpo_engine_url',array(
            'type'=>'string','sanitize_callback'=>array(__CLASS__,'sanitize_url'),'default'=>''
        ));
        register_setting('numpo_settings','numpo_engine_key',array(
            'type'=>'string','sanitize_callback'=>array(__CLASS__,'sanitize_key'),'default'=>''
        ));
    }

    public static function sanitize_url($value) {
        $value=esc_url_raw(trim((string)$value));
        return rtrim($value,'/');
    }

    public static function sanitize_key($value) {
        return trim((string)$value);
    }

    public static function request($method,$path,$body=null) {
        $base=get_option(self::OPTION_URL,'');
        if (!$base) return new WP_Error('numpo_engine_not_configured','Engine URL is not configured.');
        $url=$base.'/api/v1/'.ltrim($path,'/');
        $headers=array('Accept'=>'application/json');
        $key=get_option(self::OPTION_KEY,'');
        if ($key) $headers['Authorization']='Bearer '.$key;
        if ($body!==null) $headers['Content-Type']='application/json';

        $args=array('method'=>strtoupper($method),'timeout'=>20,'redirection'=>3,'headers'=>$headers,'body'=>$body===null?null:wp_json_encode($body));
        $response=wp_safe_remote_request($url,$args);
        if (is_wp_error($response)) return $response;

        $status=wp_remote_retrieve_response_code($response);
        $raw=wp_remote_retrieve_body($response);
        $data=json_decode($raw,true);
        if ($status < 200 || $status >= 300) {
            $message=is_array($data)&&isset($data['error']['message'])?$data['error']['message']:'Engine request failed.';
            return new WP_Error('numpo_engine_http_'.$status,$message,array('status'=>$status,'body'=>$data));
        }
        return is_array($data)?$data:array();
    }

    public static function health() {
        return self::request('GET','health');
    }

    public static function create_discovery_job($payload) {
        return self::request('POST','discovery/jobs',$payload);
    }

    public static function discovery_job($id) {
        return self::request('GET','discovery/jobs/'.rawurlencode($id));
    }

    public static function candidates($id,$query=array()) {
        $path='discovery/jobs/'.rawurlencode($id).'/candidates';
        if ($query) $path.='?'.http_build_query($query,'','&',PHP_QUERY_RFC3986);
        return self::request('GET',$path);
    }

    public static function cancel_discovery_job($id) {
        return self::request('POST','discovery/jobs/'.rawurlencode($id).'/cancel',array());
    }
}
