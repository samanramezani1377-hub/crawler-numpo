<?php
if (!defined('ABSPATH')) exit;

final class Numpo_DB {
    private static $tables = array();

    public static function init() {
        global $wpdb;
        self::$tables = array(
            'projects' => $wpdb->prefix . 'numpo_projects',
            'domains'  => $wpdb->prefix . 'numpo_domains',
            'jobs'     => $wpdb->prefix . 'numpo_jobs',
        );
    }

    public static function table($name) {
        return self::$tables[$name] ?? '';
    }

    public static function activate() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charset = $wpdb->get_charset_collate();

        $projects = self::$tables['projects'] ?? $wpdb->prefix . 'numpo_projects';
        $domains  = self::$tables['domains'] ?? $wpdb->prefix . 'numpo_domains';
        $jobs     = self::$tables['jobs'] ?? $wpdb->prefix . 'numpo_jobs';

        dbDelta("CREATE TABLE {$projects} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(190) NOT NULL,
            description text NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY name (name)
        ) {$charset};");

        dbDelta("CREATE TABLE {$domains} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            project_id bigint(20) unsigned NOT NULL,
            domain varchar(255) NOT NULL,
            normalized_domain varchar(255) NOT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY project_domain (project_id, normalized_domain),
            KEY project_id (project_id)
        ) {$charset};");

        dbDelta("CREATE TABLE {$jobs} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            project_id bigint(20) unsigned NOT NULL,
            engine_job_id varchar(190) NULL,
            type varchar(40) NOT NULL,
            mode varchar(20) NULL,
            status varchar(40) NOT NULL,
            request_json longtext NULL,
            response_json longtext NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY project_id (project_id),
            KEY engine_job_id (engine_job_id),
            KEY status (status)
        ) {$charset};");

        update_option('numpo_db_version', NUMPO_VERSION);
    }

    public static function projects() {
        global $wpdb;
        return $wpdb->get_results("SELECT * FROM " . self::table('projects') . " ORDER BY id DESC");
    }

    public static function get_project($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM " . self::table('projects') . " WHERE id=%d", $id));
    }

    public static function create_project($name, $description='') {
        global $wpdb;
        $now=current_time('mysql');
        $ok=$wpdb->insert(self::table('projects'), array('name'=>$name,'description'=>$description,'created_at'=>$now,'updated_at'=>$now), array('%s','%s','%s','%s'));
        return $ok ? (int)$wpdb->insert_id : 0;
    }

    public static function domains($project_id) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM " . self::table('domains') . " WHERE project_id=%d ORDER BY id DESC", $project_id));
    }

    public static function add_domain($project_id, $domain) {
        global $wpdb;
        $normalized = strtolower(trim(preg_replace('#^https?://#i','',$domain)));
        $normalized = rtrim($normalized, '/');
        $normalized = preg_replace('#/.*$#','',$normalized);
        $now=current_time('mysql');
        $ok=$wpdb->query($wpdb->prepare(
            "INSERT IGNORE INTO ".self::table('domains')." (project_id,domain,normalized_domain,created_at,updated_at) VALUES (%d,%s,%s,%s,%s)",
            $project_id,$domain,$normalized,$now,$now
        ));
        return $ok ? (int)$wpdb->insert_id : 0;
    }

    public static function jobs($project_id) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM " . self::table('jobs') . " WHERE project_id=%d ORDER BY id DESC", $project_id));
    }

    public static function create_job($project_id,$type,$mode,$request) {
        global $wpdb;
        $now=current_time('mysql');
        $ok=$wpdb->insert(self::table('jobs'),array(
            'project_id'=>$project_id,'type'=>$type,'mode'=>$mode,'status'=>'queued',
            'request_json'=>wp_json_encode($request),'created_at'=>$now,'updated_at'=>$now
        ),array('%d','%s','%s','%s','%s','%s','%s'));
        return $ok ? (int)$wpdb->insert_id : 0;
    }

    public static function update_job($id,$data) {
        global $wpdb;
        $data['updated_at']=current_time('mysql');
        return $wpdb->update(self::table('jobs'),$data,array('id'=>$id));
    }
}
