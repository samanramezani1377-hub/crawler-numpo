<?php
if (!defined('ABSPATH')) exit;

final class Numpo_Admin {
    public static function init() {
        add_action('admin_menu',array(__CLASS__,'menu'));
        add_action('admin_post_numpo_save_project',array(__CLASS__,'save_project'));
        add_action('admin_post_numpo_add_domain',array(__CLASS__,'add_domain'));
        add_action('admin_post_numpo_start_discovery',array(__CLASS__,'start_discovery'));
        add_action('admin_post_numpo_cancel_job',array(__CLASS__,'cancel_job'));
    }

    public static function menu() {
        add_menu_page('Numpo','Numpo','manage_options','numpo',array(__CLASS__,'dashboard'),'dashicons-search',58);
        add_submenu_page('numpo','Projects','Projects','manage_options','numpo',array(__CLASS__,'dashboard'));
        add_submenu_page('numpo','Discovery','Discovery','manage_options','numpo-discovery',array(__CLASS__,'discovery'));
        add_submenu_page('numpo','Settings','Settings','manage_options','numpo-settings',array(__CLASS__,'settings'));
    }

    private static function guard() {
        if(!current_user_can('manage_options')) wp_die('Permission denied.');
    }

    private static function nonce($action) {
        return wp_nonce_field($action,'numpo_nonce',true,false);
    }

    public static function dashboard() {
        self::guard();
        $projects=Numpo_DB::projects();
        echo '<div class="wrap"><h1>Numpo</h1>';
        echo '<p>WordPress control plane for the Go crawler engine.</p>';
        echo '<h2>Add project</h2><form method="post" action="'.esc_url(admin_url('admin-post.php')).'">';
        echo '<input type="hidden" name="action" value="numpo_save_project">'.self::nonce('numpo_save_project');
        echo '<input class="regular-text" name="name" required placeholder="Project name"> ';
        echo '<input class="regular-text" name="description" placeholder="Description"> ';
        submit_button('Create project','primary','submit',false);
        echo '</form><hr><h2>Projects</h2>';
        if(!$projects){echo '<p>No projects yet.</p>';} else {
            echo '<table class="widefat striped"><thead><tr><th>ID</th><th>Name</th><th>Description</th><th>Created</th></tr></thead><tbody>';
            foreach($projects as $p){
                echo '<tr><td>'.(int)$p->id.'</td><td>'.esc_html($p->name).'</td><td>'.esc_html($p->description).'</td><td>'.esc_html($p->created_at).'</td></tr>';
            }
            echo '</tbody></table>';
        }
        echo '</div>';
    }

    public static function discovery() {
        self::guard();
        $projects=Numpo_DB::projects();
        $selected=isset($_GET['project_id'])?(int)$_GET['project_id']:($projects[0]->id??0);
        $project=$selected?Numpo_DB::get_project($selected):null;
        $domains=$selected?Numpo_DB::domains($selected):array();
        $jobs=$selected?Numpo_DB::jobs($selected):array();

        echo '<div class="wrap"><h1>Discovery</h1>';
        echo '<form method="get"><input type="hidden" name="page" value="numpo-discovery"><select name="project_id" onchange="this.form.submit()">';
        foreach($projects as $p) echo '<option value="'.(int)$p->id.'" '.selected($selected,$p->id,false).'>'.esc_html($p->name).'</option>';
        echo '</select></form>';

        if(!$project){echo '<p>Create a project first.</p></div>';return;}

        echo '<h2>Seed domains</h2><form method="post" action="'.esc_url(admin_url('admin-post.php')).'">';
        echo '<input type="hidden" name="action" value="numpo_add_domain"><input type="hidden" name="project_id" value="'.(int)$selected.'">'.self::nonce('numpo_add_domain');
        echo '<input class="regular-text" name="domain" required placeholder="example.com"> ';
        submit_button('Add domain','secondary','submit',false);
        echo '</form>';

        echo '<ul>';
        foreach($domains as $d) echo '<li>'.esc_html($d->normalized_domain).'</li>';
        echo '</ul>';

        echo '<h2>Start Discovery</h2><form method="post" action="'.esc_url(admin_url('admin-post.php')).'">';
        echo '<input type="hidden" name="action" value="numpo_start_discovery"><input type="hidden" name="project_id" value="'.(int)$selected.'">'.self::nonce('numpo_start_discovery');
        echo '<select name="mode"><option value="manual">Manual</option><option value="automatic">Automatic</option><option value="hybrid">Hybrid</option></select> ';
        echo '<label><input type="checkbox" name="search_provider" value="1"> Search</label> ';
        echo '<label><input type="checkbox" name="sitemap" value="1" checked> Sitemap</label> ';
        echo '<label><input type="checkbox" name="robots" value="1" checked> Robots</label> ';
        echo '<label><input type="checkbox" name="link_discovery" value="1" checked> Links</label> ';
        echo '<label><input type="checkbox" name="subdomain_from_crawl" value="1" checked> Subdomains</label> ';
        submit_button('Start Discovery','primary','submit',false);
        echo '</form>';

        echo '<h2>Jobs</h2><table class="widefat striped"><thead><tr><th>ID</th><th>Engine Job</th><th>Mode</th><th>Status</th><th>Created</th><th></th></tr></thead><tbody>';
        foreach($jobs as $j){
            echo '<tr><td>'.(int)$j->id.'</td><td>'.esc_html($j->engine_job_id).'</td><td>'.esc_html($j->mode).'</td><td>'.esc_html($j->status).'</td><td>'.esc_html($j->created_at).'</td><td>';
            if($j->engine_job_id && !in_array($j->status,array('completed','failed','cancelled'),true)){
                echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="numpo_cancel_job"><input type="hidden" name="job_id" value="'.(int)$j->id.'">'.self::nonce('numpo_cancel_job').'<button class="button">Cancel</button></form>';
            }
            echo '</td></tr>';
        }
        echo '</tbody></table></div>';
    }

    public static function settings() {
        self::guard();
        echo '<div class="wrap"><h1>Numpo Settings</h1><form method="post" action="options.php">';
        settings_fields('numpo_settings');
        echo '<table class="form-table"><tr><th>Engine URL</th><td><input type="url" class="regular-text" name="numpo_engine_url" value="'.esc_attr(get_option('numpo_engine_url','')).'" placeholder="https://crawler.example.com"></td></tr>';
        echo '<tr><th>Engine API Key</th><td><input type="password" class="regular-text" name="numpo_engine_key" value="'.esc_attr(get_option('numpo_engine_key','')).'" autocomplete="new-password"><p class="description">Stored in WordPress options; never committed to Git.</p></td></tr></table>';
        submit_button('Save settings');
        echo '</form></div>';
    }

    public static function save_project() {
        self::guard(); check_admin_referer('numpo_save_project','numpo_nonce');
        $name=sanitize_text_field(wp_unslash($_POST['name']??''));
        $description=sanitize_textarea_field(wp_unslash($_POST['description']??''));
        if($name) Numpo_DB::create_project($name,$description);
        wp_safe_redirect(admin_url('admin.php?page=numpo')); exit;
    }

    public static function add_domain() {
        self::guard(); check_admin_referer('numpo_add_domain','numpo_nonce');
        $project=(int)($_POST['project_id']??0);
        $domain=sanitize_text_field(wp_unslash($_POST['domain']??''));
        if($project && $domain) Numpo_DB::add_domain($project,$domain);
        wp_safe_redirect(admin_url('admin.php?page=numpo-discovery&project_id='.$project)); exit;
    }

    public static function start_discovery() {
        self::guard(); check_admin_referer('numpo_start_discovery','numpo_nonce');
        $project=(int)($_POST['project_id']??0);
        $mode=sanitize_key(wp_unslash($_POST['mode']??'manual'));
        $allowed=array('manual','automatic','hybrid');
        if(!in_array($mode,$allowed,true)) $mode='manual';
        $domains=Numpo_DB::domains($project);
        $seeds=array_map(function($d){return $d->normalized_domain;},$domains);
        $sources=array(
            'manual_seeds'=>true,
            'csv_import'=>false,
            'search_provider'=>!empty($_POST['search_provider']),
            'sitemap'=>!empty($_POST['sitemap']),
            'robots'=>!empty($_POST['robots']),
            'link_discovery'=>!empty($_POST['link_discovery']),
            'subdomain_from_crawl'=>!empty($_POST['subdomain_from_crawl'])
        );
        $payload=array(
            'project_id'=>(string)$project,
            'mode'=>$mode,
            'seeds'=>$seeds,
            'sources'=>$sources,
            'target'=>array(),
            'limits'=>array('max_candidates'=>10000,'max_pages_per_domain'=>100,'max_depth'=>3,'max_candidates_per_page'=>50)
        );
        $local=Numpo_DB::create_job($project,'discovery',$mode,$payload);
        $response=Numpo_Engine_Client::create_discovery_job($payload);
        if(is_wp_error($response)){
            Numpo_DB::update_job($local,array('status'=>'failed','response_json'=>wp_json_encode(array('error'=>$response->get_error_message()))));
        } else {
            $engine_id=sanitize_text_field($response['job_id']??'');
            Numpo_DB::update_job($local,array('engine_job_id'=>$engine_id,'status'=>sanitize_key($response['status']??'queued'),'response_json'=>wp_json_encode($response)));
        }
        wp_safe_redirect(admin_url('admin.php?page=numpo-discovery&project_id='.$project)); exit;
    }

    public static function cancel_job() {
        self::guard(); check_admin_referer('numpo_cancel_job','numpo_nonce');
        $id=(int)($_POST['job_id']??0);
        global $wpdb;
        $job=$wpdb->get_row($wpdb->prepare("SELECT * FROM ".Numpo_DB::table('jobs')." WHERE id=%d",$id));
        if($job && $job->engine_job_id){
            $r=Numpo_Engine_Client::cancel_discovery_job($job->engine_job_id);
            if(!is_wp_error($r)) Numpo_DB::update_job($id,array('status'=>'cancelled','response_json'=>wp_json_encode($r)));
        }
        wp_safe_redirect(admin_url('admin.php?page=numpo-discovery&project_id='.(int)$job->project_id)); exit;
    }
}
