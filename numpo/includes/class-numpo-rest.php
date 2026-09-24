<?php
if (!defined('ABSPATH')) exit;

final class Numpo_REST {
    public static function init() {
        add_action('rest_api_init',array(__CLASS__,'register'));
    }

    public static function register() {
        register_rest_route('numpo/v1','/health',array(
            'methods'=>WP_REST_Server::READABLE,
            'callback'=>array(__CLASS__,'health'),
            'permission_callback'=>array(__CLASS__,'can_manage')
        ));
        register_rest_route('numpo/v1','/projects',array(
            'methods'=>WP_REST_Server::READABLE,
            'callback'=>array(__CLASS__,'projects'),
            'permission_callback'=>array(__CLASS__,'can_manage')
        ));
        register_rest_route('numpo/v1','/projects/(?P<id>\d+)/domains',array(
            'methods'=>WP_REST_Server::READABLE,
            'callback'=>array(__CLASS__,'domains'),
            'permission_callback'=>array(__CLASS__,'can_manage')
        ));
    }

    public static function can_manage() {
        return current_user_can('manage_options');
    }

    public static function health() {
        $r=Numpo_Engine_Client::health();
        if(is_wp_error($r)) return new WP_REST_Response(array('status'=>'error','error'=>$r->get_error_message()),503);
        return rest_ensure_response($r);
    }

    public static function projects() {
        return rest_ensure_response(Numpo_DB::projects());
    }

    public static function domains($request) {
        $project=Numpo_DB::get_project((int)$request['id']);
        if(!$project) return new WP_Error('not_found','Project not found',array('status'=>404));
        return rest_ensure_response(Numpo_DB::domains($project->id));
    }
}
