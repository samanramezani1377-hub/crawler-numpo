<?php
if(!defined('ABSPATH')) exit;
function numpo_ajax_guard(){
 if(!current_user_can('manage_options')||!check_ajax_referer('numpo_admin','nonce',false))wp_send_json_error(['message'=>'Forbidden'],403);
}
add_action('wp_ajax_numpo_admin_create',function(){
 numpo_ajax_guard();
 $seeds=json_decode(wp_unslash($_POST['seeds']??'[]'),true);
 $sources=json_decode(wp_unslash($_POST['sources']??'{}'),true);
 $target=json_decode(wp_unslash($_POST['target']??'{}'),true);
 $limits=json_decode(wp_unslash($_POST['limits']??'{}'),true);
 $caps=json_decode(wp_unslash($_POST['capabilities']??'{}'),true);
 $body=['project_id'=>sanitize_text_field(wp_unslash($_POST['project_id']??'')),'mode'=>sanitize_key($_POST['mode']??'manual'),'seeds'=>is_array($seeds)?array_values($seeds):[],'sources'=>is_array($sources)?$sources:[],'target'=>is_array($target)?$target:[],'limits'=>is_array($limits)?$limits:[]];
 if(is_array($caps))$body['sources']=array_merge($body['sources'],$caps);
 $req=new WP_REST_Request('POST','/numpo/v1/jobs');$req->set_body_params($body);
 $r=Numpo_API::create($req);
 if(is_wp_error($r))wp_send_json_error($r->get_error_message(),$r->get_error_data()['status']??500);
 wp_send_json_success($r->get_data());
});
add_action('wp_ajax_numpo_admin_proxy',function(){
 numpo_ajax_guard();
 $path=sanitize_text_field(wp_unslash($_POST['path']??''));
 if($path===''||$path[0]!=='/'||strpos($path,'..')!==false)wp_send_json_error('Invalid path',400);
 $method=str_ends_with($path,'/cancel')?'POST':'GET';
 $req=new WP_REST_Request($method,'/numpo/v1'.$path);
 $r=Numpo_API::get($req);
 if($method==='POST')$r=Numpo_API::cancel($req);
 if(is_wp_error($r))wp_send_json_error($r->get_error_message(),$r->get_error_data()['status']??500);
 wp_send_json_success($r->get_data());
});
add_action('wp_ajax_numpo_admin_export',function(){
 if(!current_user_can('manage_options')||!check_ajax_referer('numpo_admin','nonce',false))wp_die('Forbidden',403);
 $id=sanitize_text_field(wp_unslash($_GET['job_id']??''));
 if($id==='')wp_die('Missing job',400);
 $r=Numpo_API::export_csv($id);
 if(is_wp_error($r))wp_die(esc_html($r->get_error_message()),$r->get_error_data()['status']??500);
 $response=$r->get_data();
 nocache_headers();header('Content-Type:text/csv; charset=utf-8');header('Content-Disposition: attachment; filename="numpo-candidates.csv"');echo $response;exit;
});
