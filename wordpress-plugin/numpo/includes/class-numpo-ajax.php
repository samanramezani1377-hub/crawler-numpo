<?php
if(!defined('ABSPATH')) exit;
add_action('wp_ajax_numpo_admin_create',function(){
 if(!current_user_can('manage_options')||!check_ajax_referer('numpo_admin','nonce',false))wp_send_json_error(['message'=>'Forbidden'],403);
 $seeds=json_decode(wp_unslash($_POST['seeds']??'[]'),true);
 $sources=json_decode(wp_unslash($_POST['sources']??'{}'),true);
 $body=['project_id'=>sanitize_text_field(wp_unslash($_POST['project_id']??'')),'mode'=>sanitize_key($_POST['mode']??'manual'),'seeds'=>is_array($seeds)?array_values($seeds):[],'sources'=>is_array($sources)?$sources:[]];
 $req=new WP_REST_Request('POST','/numpo/v1/jobs');$req->set_body_params($body);
 $r=Numpo_API::create($req);
 if(is_wp_error($r))wp_send_json_error($r->get_error_message(),$r->get_error_data()['status']??500);
 wp_send_json_success($r->get_data());
});
