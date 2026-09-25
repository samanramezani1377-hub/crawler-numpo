<?php
if(!defined('ABSPATH')) exit;
function numpo_ajax_clean_output(){while(ob_get_level()>0) @ob_end_clean();}
function numpo_json_encode($value,$context='request'){
 $json=wp_json_encode($value,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
 if($json===false){
  $error=function_exists('json_last_error_msg')?json_last_error_msg():'JSON encoding failed';
  $code=function_exists('json_last_error')?json_last_error():0;
  return new WP_Error('request_json_encode_failed','Numpo could not encode the '.$context.' as JSON: '.$error.' (JSON error '.$code.'). Check for invalid UTF-8 in the request fields.',['status'=>400,'json_error'=>$error,'json_error_code'=>$code]);
 }
 return $json;
}
function numpo_ajax_error($error,$fallback_status=500){
 $data=is_wp_error($error)?$error->get_error_data():[];
 $status=(is_array($data)&&isset($data['status']))?(int)$data['status']:$fallback_status;
 $code=is_wp_error($error)?$error->get_error_code():'numpo_error';
 $message=is_wp_error($error)?$error->get_error_message():(string)$error;
 if($message==='Invalid JSON.'||strcasecmp(trim($message),'Invalid JSON')===0)$message='Numpo request failed: invalid JSON. The engine rejected the request body; see the decoder details below.';
 $details=['message'=>$message,'code'=>$code,'status'=>$status];
 if(is_array($data)){foreach($data as $key=>$value){if($key==='status')continue;if(is_scalar($value)||$value===null)$details[$key]=$value;elseif(is_array($value))$details[$key]=$value;}}
 numpo_ajax_clean_output();wp_send_json_error($details,$status);
}
function numpo_ajax_guard(){
 if(ob_get_level()===0)ob_start();
 static $registered=false;
 if(!$registered){$registered=true;register_shutdown_function(function(){$e=error_get_last();if(!$e||!in_array($e['type'],[E_ERROR,E_PARSE,E_CORE_ERROR,E_COMPILE_ERROR],true))return;while(ob_get_level()>0)@ob_end_clean();status_header(500);nocache_headers();header('Content-Type: application/json; charset=utf-8');echo wp_json_encode(['success'=>false,'data'=>['message'=>'Numpo AJAX fatal error','code'=>'numpo_ajax_fatal','status'=>500,'error'=>trim(($e['message']??'').' at '.($e['file']??'').' line '.($e['line']??''))]],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);});}
 if(!current_user_can('manage_options')||!check_ajax_referer('numpo_admin','nonce',false))numpo_ajax_error(new WP_Error('forbidden','Numpo AJAX access denied. Check administrator permission and nonce.',['status'=>403]),403);
}
add_action('wp_ajax_numpo_admin_create',function(){
 numpo_ajax_guard();
 $fields=['seeds'=>'[]','sources'=>'{}','target'=>'{}','limits'=>'{}','capabilities'=>'{}'];$decoded=[];
 foreach($fields as $key=>$default){$raw=wp_unslash($_POST[$key]??$default);$value=json_decode($raw,true);if(json_last_error()!==JSON_ERROR_NONE||!is_array($value))numpo_ajax_error(new WP_Error('invalid_start_json','Numpo Start field '.$key.' is not valid JSON: '.json_last_error_msg(),['status'=>400,'field'=>$key,'json_error'=>json_last_error_msg(),'json_error_code'=>json_last_error()]));$decoded[$key]=$value;}
 $body=['project_id'=>sanitize_text_field(wp_unslash($_POST['project_id']??'')),'mode'=>sanitize_key($_POST['mode']??'manual'),'seeds'=>array_values($decoded['seeds']),'sources'=>$decoded['sources'],'target'=>$decoded['target'],'limits'=>$decoded['limits'],'capabilities'=>$decoded['capabilities']];
 $json=numpo_json_encode($body,'Start request');if(is_wp_error($json))numpo_ajax_error($json);
 $req=new WP_REST_Request('POST','/numpo/v1/jobs');$req->set_header('Content-Type','application/json');$req->set_body($json);
 $r=Numpo_API::create($req);if(is_wp_error($r))numpo_ajax_error($r);numpo_ajax_clean_output();wp_send_json_success($r->get_data());
});
add_action('wp_ajax_numpo_admin_proxy',function(){
 numpo_ajax_guard();$path=sanitize_text_field(wp_unslash($_POST['path']??''));
 if($path===''||$path[0]!=='/'||strpos($path,'..')!==false)numpo_ajax_error(new WP_Error('invalid_path','Numpo received an invalid API path.',['status'=>400]),400);
 $method=((substr($path,-7)==='/cancel')||substr($path,-6)==='/pause'||substr($path,-7)==='/resume'||$path==='/jobs')?'POST':'GET';$req=new WP_REST_Request($method,'/numpo/v1'.$path);$parts=explode('/',trim($path,'/'));$r=null;
 if($path==='/jobs'&&$method==='POST'){$body=[];foreach(['project_id','mode','seeds','sources','target','limits','capabilities'] as $key){$value=wp_unslash($_POST[$key]??'');if(in_array($key,['seeds','sources','target','limits','capabilities'],true)){$decoded=json_decode($value,true);$body[$key]=is_array($decoded)?$decoded:[];}else $body[$key]=sanitize_text_field($value);}$json=numpo_json_encode($body,'proxy request');if(is_wp_error($json))numpo_ajax_error($json);$req->set_header('Content-Type','application/json');$req->set_body($json);$r=Numpo_API::create($req);}
 elseif(count($parts)>=2&&$parts[0]==='jobs'){$req->set_param('id',$parts[1]);if(count($parts)>=3&&$parts[2]==='cancel')$r=Numpo_API::cancel($req);elseif(count($parts)>=3&&$parts[2]==='pause')$r=Numpo_API::pause($req);elseif(count($parts)>=3&&$parts[2]==='resume')$r=Numpo_API::resume($req);elseif(count($parts)>=3&&$parts[2]==='errors')$r=Numpo_API::errors($req);
 elseif(count($parts)>=3&&$parts[2]==='facts')$r=Numpo_API::facts($req);elseif(count($parts)>=3){$req->set_param('resource',$parts[2]);$r=Numpo_API::resource($req);}else $r=Numpo_API::get($req);}
 if($r===null)numpo_ajax_error(new WP_Error('invalid_path','Numpo could not resolve the requested API operation.',['status'=>400]),400);if(is_wp_error($r))numpo_ajax_error($r);numpo_ajax_clean_output();wp_send_json_success($r->get_data());
});
add_action('wp_ajax_numpo_admin_export',function(){
 if(!current_user_can('manage_options')||!check_ajax_referer('numpo_admin','nonce',false))wp_die('Numpo export access denied.',403);$id=sanitize_text_field(wp_unslash($_GET['job_id']??''));$resource=sanitize_key(wp_unslash($_GET['resource']??'urls'));if($id==='')wp_die('Numpo export failed: missing Job ID.',400);$req=new WP_REST_Request('GET','/numpo/v1/jobs/'.$id.'/export/'.$resource);$req->set_param('id',$id);$req->set_param('resource',$resource);$r=Numpo_API::export_resource($req);if(is_wp_error($r))wp_die(esc_html($r->get_error_message()),$r->get_error_data()['status']??500);$response=$r->get_data();nocache_headers();header('Content-Type:text/csv; charset=utf-8');header('Content-Disposition: attachment; filename="numpo-'.$resource.'.csv"');echo $response;exit;
});