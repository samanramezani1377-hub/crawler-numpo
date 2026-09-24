<?php
if(!defined('ABSPATH')) exit;
class Numpo_Admin {
 public static function init(){add_action('admin_menu',[__CLASS__,'menu']);add_action('admin_post_numpo_save',[__CLASS__,'save']);require_once NUMPO_DIR.'includes/class-numpo-ajax.php';}
 public static function menu(){add_menu_page('Numpo','Numpo','manage_options','numpo',[__CLASS__,'page'],'dashicons-search',58);add_submenu_page('numpo','Settings','Settings','manage_options','numpo-settings',[__CLASS__,'settings']);}
 public static function page(){if(!current_user_can('manage_options'))return;?>
 <div class="wrap"><h1>Numpo</h1><p>Discovery control plane</p><form id="numpo-form">
 <p><label>Project ID<br><input name="project_id" class="regular-text" required></label></p>
 <p><label>Mode<br><select name="mode"><option value="manual">Manual</option><option value="hybrid">Hybrid</option><option value="automatic">Automatic</option></select></label></p>
 <p><label>Seeds / domains<br><textarea name="seeds" rows="7" class="large-text"></textarea></label></p>
 <fieldset><legend><strong>Discovery sources</strong></legend>
 <label><input type="checkbox" name="source_search" value="1"> Search provider</label><br>
 <label><input type="checkbox" name="source_sitemap" value="1"> Sitemap</label><br>
 <label><input type="checkbox" name="source_robots" value="1"> robots.txt</label><br>
 <label><input type="checkbox" name="source_links" value="1"> Link discovery</label><br>
 <label><input type="checkbox" name="source_subdomains" value="1"> Subdomain discovery</label>
 </fieldset>
 <fieldset style="margin-top:16px;padding:10px 12px;"><legend><strong>Target filters</strong></legend>
 <p><label>Country / TLD<br><select name="target_country"><option value="">Any country / TLD</option><option value="ir">Iran (.ir)</option><option value="nl">Netherlands (.nl)</option><option value="us">United States (.us)</option><option value="de">Germany (.de)</option><option value="uk">United Kingdom (.uk)</option><option value="fr">France (.fr)</option><option value="tr">Turkey (.tr)</option></select></label></p>
 <p class="description">This is an additional target filter. It does not replace seeds or discovery sources. Country matching uses the domain TLD and available language signals.</p>
 </fieldset>
 <p><button class="button button-primary">Start discovery</button></p></form><pre id="numpo-result"></pre></div>
 <script>
document.getElementById('numpo-form').addEventListener('submit',async e=>{e.preventDefault();let f=new FormData(e.target);let seeds=f.get('seeds').split(/\r?\n/).map(x=>x.trim()).filter(Boolean);let sources={search_provider:f.has('source_search'),sitemap:f.has('source_sitemap'),robots:f.has('source_robots'),link_discovery:f.has('source_links'),subdomain_from_crawl:f.has('source_subdomains')};let target={};let country=f.get('target_country');if(country)target.countries=[country];let r=await fetch(ajaxurl,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:new URLSearchParams({action:'numpo_admin_create',nonce:'<?php echo esc_js(wp_create_nonce('numpo_admin')); ?>',project_id:f.get('project_id'),mode:f.get('mode'),seeds:JSON.stringify(seeds),sources:JSON.stringify(sources),target:JSON.stringify(target)})});document.getElementById('numpo-result').textContent=await r.text();});
 </script><?php }
 public static function settings(){if(!current_user_can('manage_options'))return;?>
 <div class="wrap"><h1>Numpo Settings</h1><form method="post" action="<?php echo esc_url(admin_url('admin-post.php'));?>"><?php wp_nonce_field('numpo_save');?><input type="hidden" name="action" value="numpo_save"><table class="form-table"><tr><th>Engine URL</th><td><input class="regular-text" name="engine_url" value="<?php echo esc_attr(Numpo_Settings::engine_url());?>"></td></tr><tr><th>API Key</th><td><input type="password" class="regular-text" name="api_key" value="<?php echo esc_attr(Numpo_Settings::api_key());?>"></td></tr></table><button class="button button-primary">Save</button></form></div><?php }
 public static function save(){if(!current_user_can('manage_options')||!check_admin_referer('numpo_save'))wp_die('Forbidden');update_option('numpo_engine_url',esc_url_raw(wp_unslash($_POST['engine_url']??'')));update_option('numpo_api_key',sanitize_text_field(wp_unslash($_POST['api_key']??'')));wp_safe_redirect(admin_url('admin.php?page=numpo-settings&updated=1'));exit;}
}