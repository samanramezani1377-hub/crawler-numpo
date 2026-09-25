<?php
if(!defined('ABSPATH')) exit;
function numpo_ajax_clean_output(){
 while(ob_get_level()>0) @ob_end_clean();
}
function numpo_ajax_error($error,$fallback_status=500){
 $data=is_wp_error($error)?$error->get_error_data():[];
 $status=(is_array($data)&&isset($data['status']))?(int)$data['status']:$fallback_status;
 $code=is_wp_error($error)?$error->get_error_code():'numpo_error';
 $message=is_wp_error($error)?$error->get_error_message():(string)$error;
 if($message==='Invalid JSON.'||strcasecmp(trim($message),'Invalid JSON')===0){
  $message='Numpo request failed: the server returned an invalid JSON response. Code: '.($code?:'unknown').'. HTTP: '.$status.'. Check the Numpo error details below and engine.log.';
 }
 $details=['message'=>$message,'code'=>$code,'status'=>$status];
 if(is_array($data)){
  foreach($data as $key=>$value){
   if($key==='status')continue;
   if(is_scalar($value)||$value===null)$details[$key]=$value;
   elseif(is_array($value))$details[$key]=$value;
  }
 }
 numpo_ajax_clean_output();
 wp_send_json_error($details,$status);
}
function numpo_ajax_guard(){
 if(ob_get_level()===0)ob_start();
 static $registered=false;
 if(!$registered){
  $registered=true;
  register_shutdown_function(function(){
   $e=error_get_last();
   if(!$e||!in_array($e['type'],[E_ERROR,E_PARSE,E_CORE_ERROR,E_COMPILE_ERROR],true))return;
   while(ob_get_level()>0)@ob_end_clean();
   status_header(500);
   nocache_headers();
   header('Content-Type: application/json; charset=utf-8');
   echo wp_json_encode(['success'=>false,'data'=>['message'=>'Numpo AJAX fatal error','code'=>'numpo_ajax_fatal','status'=>500,'error'=>trim(($e['message']??'').' at '.($e['file']??'').' line '.($e['line']??''))]],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
  });
 }
 if(!current_user_can('manage_options')||!check_ajax_referer('numpo_admin','nonce',false))numpo_ajax_error(new WP_Error('forbidden','Numpo AJAX access denied. Check administrator permission and nonce.',['status'=>403]),403);
}
add_action('wp_ajax_numpo_admin_create',function(){
 numpo_ajax_guard();
 $seeds=json_decode(wp_unslash($_POST['seeds']??'[]'),true);
 $sources=json_decode(wp_unslash($_POST['sources']??'{}'),true);
 $target=json_decode(wp_unslash($_POST['target']??'{}'),true);
 $limits=json_decode(wp_unslash($_POST['limits']??'{}'),true);
 $caps=json_decode(wp_unslash($_POST['capabilities']??'{}'),true);
 $body=['project_id'=>sanitize_text_field(wp_unslash($_POST['project_id']??'')),'mode'=>sanitize_key($_POST['mode']??'manual'),'seeds'=>is_array($seeds)?array_values($seeds):[],'sources'=>is_array($sources)?$sources:[],'target'=>is_array($target)?$target:[],'limits'=>is_array($limits)?$limits:[]];
 if(is_array($caps))$body['capabilities']=$caps;
 $req=new WP_REST_Request('POST','/numpo/v1/jobs');$req->set_header('Content-Type','application/json');$req->set_body(wp_json_encode($body));
 $r=Numpo_API::create($req);
 if(is_wp_error($r))numpo_ajax_error($r);
 numpo_ajax_clean_output();wp_send_json_success($r->get_data());
});
add_action('wp_ajax_numpo_admin_proxy',function(){
 numpo_ajax_guard();
 $path=sanitize_text_field(wp_unslash($_POST['path']??''));
 if($path===''||$path[0]!=='/'||strpos($path,'..')!==false)numpo_ajax_error(new WP_Error('invalid_path','Numpo received an invalid API path.',['status'=>400]),400);
 $method=((substr($path,-7)==='/cancel')||$path==='/jobs')?'POST':'GET';
 $req=new WP_REST_Request($method,'/numpo/v1'.$path);
 $parts=explode('/',trim($path,'/')); $r=null;
 if($path==='/jobs' && $method==='POST'){
  $body=[];
  foreach(['project_id','mode','seeds','sources','target','limits','capabilities'] as $key){
   $value=wp_unslash($_POST[$key]??'');
   if(in_array($key,['seeds','sources','target','limits','capabilities'],true)){
    $decoded=json_decode($value,true); $body[$key]=is_array($decoded)?$decoded:[];
   } else $body[$key]=sanitize_text_field($value);
  }
  $req->set_header('Content-Type','application/json');$req->set_body(wp_json_encode($body)); $r=Numpo_API::create($req);
 } elseif(count($parts)>=2 && $parts[0]==='jobs'){
  $req->set_param('id',$parts[1]);
  if(count($parts)>=3 && $parts[2]==='cancel')$r=Numpo_API::cancel($req);
  elseif(count($parts)>=3 && $parts[2]==='errors')$r=Numpo_API::errors($req);
  elseif(count($parts)>=3){$req->set_param('resource',$parts[2]);$r=Numpo_API::resource($req);}
  else $r=Numpo_API::get($req);
 }
 if($r===null)numpo_ajax_error(new WP_Error('invalid_path','Numpo could not resolve the requested API operation.',['status'=>400]),400);
 if(is_wp_error($r))numpo_ajax_error($r);
 numpo_ajax_clean_output();wp_send_json_success($r->get_data());
});
add_action('wp_ajax_numpo_admin_export',function(){
 if(!current_user_can('manage_options')||!check_ajax_referer('numpo_admin','nonce',false))wp_die('Numpo export access denied.',403);
 $id=sanitize_text_field(wp_unslash($_GET['job_id']??''));
 if($id==='')wp_die('Numpo export failed: missing Job ID.',400);
 $r=Numpo_API::export_csv($id);
 if(is_wp_error($r))wp_die(esc_html($r->get_error_message()),$r->get_error_data()['status']??500);
 $response=$r->get_data();
 nocache_headers();header('Content-Type:text/csv; charset=utf-8');header('Content-Disposition: attachment; filename="numpo-candidates.csv"');echo $response;exit;
});
