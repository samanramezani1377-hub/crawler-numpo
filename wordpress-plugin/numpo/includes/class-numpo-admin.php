<?php
if(!defined('ABSPATH')) exit;
class Numpo_Admin {
 public static function init(){add_action('admin_menu',[__CLASS__,'menu']);add_action('admin_post_numpo_save',[__CLASS__,'save']);require_once NUMPO_DIR.'includes/class-numpo-ajax.php';}
 public static function menu(){add_menu_page('Numpo','Numpo','manage_options','numpo',[__CLASS__,'page'],'dashicons-search',58);add_submenu_page('numpo','Settings','Settings','manage_options','numpo-settings',[__CLASS__,'settings']);}
 private static function capChecks(){
  $caps=['active_probe'=>'Active Probe','deep_crawl'=>'Deep Crawl','link_discovery'=>'Link discovery','sitemap'=>'Sitemap','robots'=>'robots.txt','subdomain_from_crawl'=>'Subdomain discovery','wordpress'=>'WordPress detection','woocommerce'=>'WooCommerce detection','phone'=>'Phone extraction','email'=>'Email extraction','business'=>'Business extraction','social'=>'Social extraction','page_classification'=>'Page classification'];
  $html='';
  foreach($caps as $key=>$label){$html.='<label style="display:inline-block;min-width:230px;margin:4px 12px 4px 0;"><input type="checkbox" name="cap_'.$key.'" value="1" '.checked(Numpo_Settings::cap($key,true),true,false).'> '.esc_html($label).'</label>';}
  return $html;
 }
 public static function page(){if(!current_user_can('manage_options'))return;?>
 <style>
#numpo-app{--p:#6d5dfc;--pd:#5548d9;--b:#e5e7ef;--m:#687083;max-width:1240px;margin:20px 20px 40px 0;color:#171925}
#numpo-app *{box-sizing:border-box}#numpo-app .numpo-hero{background:linear-gradient(135deg,#171925,#302b61 55%,#6d5dfc);color:#fff;border-radius:20px;padding:26px 28px;margin-bottom:18px;box-shadow:0 12px 35px rgba(35,31,83,.18)}
#numpo-app .numpo-hero h1{color:#fff;margin:0 0 7px;font-size:26px}.numpo-hero p{margin:0;color:rgba(255,255,255,.82)}
#numpo-app .numpo-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}
#numpo-app .numpo-card,#numpo-app .postbox{background:#fff;border:1px solid var(--b);border-radius:16px;box-shadow:0 3px 14px rgba(20,24,40,.045);padding:20px}
#numpo-app .numpo-card h2,#numpo-app .postbox h2,#numpo-app .postbox h3{margin:0 0 16px;font-size:16px}
#numpo-app input[type=text],#numpo-app input[type=number],#numpo-app select,#numpo-app textarea{width:100%;border:1px solid #d8dce6;border-radius:10px;padding:8px 11px;min-height:42px}
#numpo-app textarea{min-height:130px;line-height:1.7}#numpo-app input:focus,#numpo-app select:focus,#numpo-app textarea:focus{border-color:var(--p);box-shadow:0 0 0 3px rgba(109,93,252,.12);outline:none}
#numpo-app .description,#numpo-app .numpo-help{color:var(--m);font-size:12px;line-height:1.7}
#numpo-app .numpo-checks{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:9px}
#numpo-app .numpo-check{display:flex;align-items:center;gap:9px;padding:11px 12px;border:1px solid var(--b);border-radius:11px;background:#fafbfc;cursor:pointer;min-height:44px}
#numpo-app .numpo-check input{margin:0}.numpo-check:hover{border-color:#c6c0ff;background:#f7f5ff}
#numpo-app .numpo-limits{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}
#numpo-app .numpo-actions{display:flex;align-items:center;gap:9px;margin:16px 0 22px;flex-wrap:wrap}
#numpo-app .numpo-actions .button-primary{background:var(--p);border-color:var(--p)}#numpo-app .numpo-wide{grid-column:1/-1}
#numpo-app .numpo-pill{display:inline-flex;padding:5px 10px;border-radius:999px;background:#f0efff;color:#5548d9;font-size:12px;font-weight:600}
#numpo-app .numpo-section-title{display:flex;justify-content:space-between;align-items:center;margin:24px 0 10px}#numpo-app .numpo-section-title h2{margin:0}
#numpo-app .numpo-runtime{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;margin-bottom:18px}
#numpo-app .numpo-runtime-item{background:#fafbfc;border:1px solid var(--b);border-radius:12px;padding:12px}.numpo-ok{color:#16834b}.numpo-warn{color:#b56b00}
#numpo-app .numpo-settings-card{background:#fff;border:1px solid var(--b);border-radius:16px;padding:4px 18px 14px}
#numpo-app .numpo-savebar{position:sticky;bottom:12px;z-index:5;background:rgba(255,255,255,.94);backdrop-filter:blur(12px);border:1px solid var(--b);border-radius:14px;padding:12px 14px;margin-top:18px;box-shadow:0 8px 25px rgba(20,24,40,.10)}
#numpo-app .numpo-table-wrap{overflow:auto;border:1px solid var(--b);border-radius:12px}
@media(max-width:900px){#numpo-app{margin-right:10px}.numpo-grid{grid-template-columns:1fr!important}.numpo-wide{grid-column:auto!important}.numpo-checks{grid-template-columns:repeat(2,minmax(0,1fr))!important}.numpo-limits{grid-template-columns:repeat(2,minmax(0,1fr))!important}.numpo-runtime{grid-template-columns:1fr}}
@media(max-width:600px){#numpo-app{margin:12px 8px 30px 0}.numpo-hero{border-radius:15px!important;padding:20px!important}.numpo-hero h1{font-size:21px}.numpo-card,.numpo-postbox{padding:15px!important;border-radius:13px!important}.numpo-checks,.numpo-limits{grid-template-columns:1fr!important}.numpo-actions{display:grid;grid-template-columns:1fr 1fr}.numpo-actions .button{width:100%;min-height:42px}.numpo-status{grid-column:1/-1}.numpo-settings-card{padding:4px 12px 12px}}
@media(max-width:480px){#numpo-app .numpo-actions{grid-template-columns:1fr}.numpo-hero p{font-size:13px}}
</style><div class="wrap" id="numpo-app">
  <div class="numpo-hero"><h1>Numpo Discovery</h1><p>کراول، کشف و تحلیل سایت‌ها؛ سریع، امن و PHP-only.</p></div>
  <form id="numpo-form">
   <div class="numpo-grid">
    <div class="numpo-card"><h2>Job</h2>
     <p><label>Project ID<br><input name="project_id" class="regular-text" value="<?php echo esc_attr(Numpo_Settings::default_project());?>" required></label></p>
     <p><label>Mode<br><select name="mode"><option value="manual">Manual</option><option value="hybrid">Hybrid</option><option value="automatic">Automatic</option></select></label></p>
     <p><label>Seeds / domains<br><textarea name="seeds" rows="8" class="large-text" placeholder="https://example.com"></textarea></label></p>
    </div>
    <div class="numpo-card"><h2>Discovery sources</h2>
     <label><input type="checkbox" name="source_search" value="1"> Search provider</label><br>
     <label><input type="checkbox" name="source_sitemap" value="1"> Sitemap</label><br>
     <label><input type="checkbox" name="source_robots" value="1"> robots.txt</label><br>
     <label><input type="checkbox" name="source_links" value="1" checked> Link discovery</label><br>
     <label><input type="checkbox" name="source_subdomains" value="1"> Subdomain discovery</label>
     <p class="description">در Automatic/Hybrid حداقل یک منبع خودکار لازم است.</p>
    </div>
    <div class="numpo-card"><h2>Target filters</h2>
     <p><label>Country / TLD<br><select name="target_country"><option value="">Any country / TLD</option><option value="ir">Iran (.ir)</option><option value="nl">Netherlands (.nl)</option><option value="us">United States (.us)</option><option value="de">Germany (.de)</option><option value="uk">United Kingdom (.uk)</option><option value="fr">France (.fr)</option><option value="tr">Turkey (.tr)</option></select></label></p>
     <p class="description">فیلتر اضافه است و جای Seed یا Source را نمی‌گیرد. TLD و سیگنال زبان صفحه بررسی می‌شوند.</p>
    </div>
    <div class="numpo-card"><h2>Limits</h2>
     <div class="numpo-limits">
      <label>Max pages<br><input type="number" min="1" name="max_pages" value="<?php echo esc_attr(Numpo_Settings::int('numpo_max_pages',100));?>"></label>
      <label>Max URLs<br><input type="number" min="1" name="max_urls" value="<?php echo esc_attr(Numpo_Settings::int('numpo_max_urls',500));?>"></label>
      <label>Max depth<br><input type="number" min="0" name="max_depth" value="<?php echo esc_attr(Numpo_Settings::int('numpo_max_depth',3));?>"></label>
      <label>Candidates/page<br><input type="number" min="1" name="max_candidates_per_page" value="<?php echo esc_attr(Numpo_Settings::int('numpo_max_candidates_per_page',50));?>"></label>
     </div>
    </div>
   </div>
   <div class="numpo-card numpo-wide"><h2>Routing / capabilities</h2><div class="numpo-checks"><?php echo self::capChecks();?></div></div>
   <div class="numpo-actions"><button class="button button-primary">Start discovery</button><button type="button" class="button" id="numpo-refresh">Refresh</button><span id="numpo-status" class="numpo-status"></span></div>
  </form>
  <div id="numpo-dashboard" style="max-width:1100px"></div>
 </div>
 <script>
(function(){
 const root=document.getElementById('numpo-dashboard'), form=document.getElementById('numpo-form');
 const ajaxUrl=<?php echo wp_json_encode(admin_url('admin-ajax.php')); ?>;
 const adminNonce=<?php echo wp_json_encode(wp_create_nonce('numpo_admin')); ?>;
 const restBase=<?php echo wp_json_encode(trailingslashit(rest_url('numpo/v1'))); ?>;
 const restNonce=<?php echo wp_json_encode(wp_create_nonce('wp_rest')); ?>;
 let activeJobId=null;

 function esc(v){return String(v??'').replace(/[&<>\"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','\"':'&quot;',"'":'&#039;'}[m]||m));}
 function caps(f){
  const out={};
  ['active_probe','deep_crawl','link_discovery','sitemap','robots','subdomain_from_crawl','wordpress','woocommerce','phone','email','business','social','page_classification'].forEach(k=>out[k]=f.has('cap_'+k));
  return out;
 }
 function responsePreview(text){
  const normalized=String(text||'').replace(/\s+/g,' ').trim();
  return normalized.length>700?normalized.slice(0,700)+'…':normalized;
 }
 async function parseResponse(response){
  const text=await response.text();
  if(!text)throw new Error('پاسخ خالی از WordPress دریافت شد (HTTP '+response.status+').');
  try{
   const data=JSON.parse(text);
   if(!response.ok){
    const message=data?.message||data?.data?.message||data?.code||('HTTP '+response.status);
    throw new Error(message);
   }
   return data;
  }catch(e){
   if(e instanceof SyntaxError)throw new Error('پاسخ WordPress JSON معتبر نبود (HTTP '+response.status+'). پاسخ: '+responsePreview(text));
   throw e;
  }
 }
 async function api(path,options={}){
  if(path==='/jobs'&&options.method==='POST'&&options.numpoStart){
   const payload=options.body||{},fd=new URLSearchParams();
   fd.set('action','numpo_admin_create');fd.set('nonce',adminNonce);fd.set('project_id',payload.project_id||'');fd.set('mode',payload.mode||'manual');
   fd.set('seeds',JSON.stringify(payload.seeds||[]));fd.set('sources',JSON.stringify(payload.sources||{}));fd.set('target',JSON.stringify(payload.target||{}));fd.set('limits',JSON.stringify(payload.limits||{}));fd.set('capabilities',JSON.stringify(payload.capabilities||{}));
   const response=await fetch(ajaxUrl,{method:'POST',credentials:'same-origin',headers:{'Accept':'application/json','Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'},body:fd.toString()});
   const text=await response.text();let data;
   try{data=JSON.parse(text);}catch(e){throw new Error('پاسخ Start از WordPress JSON معتبر نبود (HTTP '+response.status+'). پاسخ: '+responsePreview(text));}
   if(!response.ok||!data||data.success!==true){
    const message=data?.data?.message||data?.data||data?.message||('HTTP '+response.status);
    const err=new Error(typeof message==='string'?message:'خطا در ایجاد Job.'); err.details=data?.data||{}; throw err;
   }
   return data.data;
  }
  const opts={...options,credentials:'same-origin',headers:{'Accept':'application/json','X-WP-Nonce':restNonce,...(options.headers||{})}};
  if(opts.body!==undefined&&opts.body!==null&&typeof opts.body!=='string'){opts.headers['Content-Type']='application/json';opts.body=JSON.stringify(opts.body);}
  const response=await fetch(restBase+String(path).replace(/^\//,''),opts);
  return parseResponse(response);
 }
 function formatError(error){
  const d=error?.details||error;
  let message=String(d?.message||error?.message||error||'خطای نامشخص در Numpo.');
  if(message==='Invalid JSON.'||/^Invalid JSON\\.?$/i.test(message.trim()))message='Numpo پاسخ JSON نامعتبر از سرور دریافت کرد.';
  const code=d?.code?String(d.code):'';
  const status=d?.status?String(d.status):'';
  const preview=d?.body_preview?String(d.body_preview):'';
  const engineStatus=d?.engine_status?String(d.engine_status):'';
  const jsonError=d?.json_error?String(d.json_error):'';
  const jsonErrorCode=d?.json_error_code?String(d.json_error_code):'';
  const field=d?.field?String(d.field):'';
  const parts=[message];
  if(code)parts.push('کد خطا: '+code);
  if(status)parts.push('HTTP: '+status);
  if(engineStatus)parts.push('Engine HTTP: '+engineStatus);
  if(field)parts.push('فیلد: '+field);
  if(jsonError)parts.push('JSON decoder: '+jsonError+(jsonErrorCode?' ('+jsonErrorCode+')':''));
  if(preview)parts.push('پاسخ Engine: '+preview);
  return parts.join(' | ');
 }
 function showError(error){root.innerHTML='<div class="notice notice-error"><p>'+esc(formatError(error))+'</p></div>';}
 async function load(id){
  activeJobId=id;
  try{
   const j=await api('/jobs/'+encodeURIComponent(id));
   root.innerHTML='<div class="postbox" style="padding:16px"><h2>Job '+esc(j.job_id||id)+'</h2><p>Status: <strong>'+esc(j.status)+'</strong> · Candidates: '+esc(j.candidate_count||0)+' · Probes: '+esc(j.probe_count||0)+'</p><p><button class="button" id="numpo-cancel">Cancel</button> <a class="button" href="<?php echo esc_url(admin_url('admin-ajax.php')); ?>?action=numpo_admin_export&nonce=<?php echo esc_js(wp_create_nonce('numpo_admin')); ?>&job_id='+encodeURIComponent(id)+'">Export CSV</a></p></div>';
   document.getElementById('numpo-cancel').onclick=async()=>{try{await api('/jobs/'+encodeURIComponent(id)+'/cancel',{method:'POST'});await load(id);}catch(e){showError(e);}};
   const resources=['candidates','domains','pages','technologies','contacts','business','social','classifications','probes'];
   for(const res of resources){
    try{
     const d=await api('/jobs/'+encodeURIComponent(id)+'/'+res),items=d.items||[],keys=items.length?Object.keys(items[0]).slice(0,7):[];
     root.innerHTML+='<div class="postbox" style="padding:12px"><h3>'+esc(res)+' ('+esc(d.total||items.length)+')</h3>'+(items.length?'<div style="overflow:auto"><table class="widefat striped"><thead><tr>'+keys.map(k=>'<th>'+esc(k)+'</th>').join('')+'</tr></thead><tbody>'+items.slice(0,20).map(x=>'<tr>'+keys.map(k=>'<td>'+esc(typeof x[k]==='object'?JSON.stringify(x[k]):x[k])+'</td>').join('')+'</tr>').join('')+'</tbody></table></div>':'<p>No data.</p>')+'</div>';
    }catch(e){root.innerHTML+='<div class="notice notice-warning"><p>'+esc(res+': '+(e.message||e))+'</p></div>';}
   }
  }catch(e){showError(e);}
 }
 form.addEventListener('submit',async e=>{
  e.preventDefault();
  const button=form.querySelector('button[type="submit"], button:not([type])'),oldText=button?button.textContent:'';
  if(button){button.disabled=true;button.textContent='Starting…';}
  try{
   const f=new FormData(form),seeds=String(f.get('seeds')||'').split(/\r?\n/).map(x=>x.trim()).filter(Boolean);
   const sources={search_provider:f.has('source_search'),sitemap:f.has('source_sitemap'),robots:f.has('source_robots'),link_discovery:f.has('source_links'),subdomain_from_crawl:f.has('source_subdomains')};
   const target={},country=f.get('target_country');if(country)target.countries=[country];
   const limits={max_pages:Number(f.get('max_pages')),max_urls:Number(f.get('max_urls')),max_depth:Number(f.get('max_depth')),max_candidates_per_page:Number(f.get('max_candidates_per_page'))};
   const body={project_id:String(f.get('project_id')||''),mode:String(f.get('mode')||'manual'),seeds,sources,target,limits,capabilities:caps(f)};
   const result=await api('/jobs',{method:'POST',numpoStart:true,body});
   if(!result||!result.job_id)throw new Error('Engine job ID در پاسخ ایجاد Job وجود ندارد.');
   await load(result.job_id);
  }catch(e){showError(e);}
  finally{if(button){button.disabled=false;button.textContent=oldText;}}
 });
 document.getElementById('numpo-refresh').onclick=()=>{if(activeJobId)load(activeJobId);};
})();
 </script><?php }
 public static function settings(){if(!current_user_can('manage_options'))return;?>
 <div class="wrap" id="numpo-app"><div class="numpo-hero"><h1>تنظیمات Numpo</h1><p>Runtime، محدودیت‌های Crawl، امنیت و قابلیت‌های پیش‌فرض را از اینجا مدیریت کنید.</p></div>
 <?php $diag=Numpo_Diagnostics::check(); ?>
 <div class="numpo-runtime"><div class="numpo-runtime-item"><strong>PHP Runtime</strong><span class="<?php echo $diag['ok']?'numpo-ok':'numpo-warn'; ?>"><?php echo $diag['ok']?'Ready':'Blocked'; ?></span></div><div class="numpo-runtime-item"><strong>Architecture</strong><span class="numpo-ok">PHP-only</span></div><div class="numpo-runtime-item"><strong>External Engine</strong><span class="numpo-ok">Not required</span></div></div><div class="numpo-settings-card"><h2>PHP Runtime</h2><p><strong><?php echo $diag['ok']?'Ready':'Blocked'; ?></strong></p>
 <table class="widefat striped"><thead><tr><th>Component</th><th>Status</th><th>Value</th></tr></thead><tbody>
 <?php foreach($diag['checks'] as $check): ?><tr><td><?php echo esc_html($check['label']); ?></td><td><?php echo $check['ok']?'OK':($check['required']?'Required':'Optional'); ?></td><td><?php echo esc_html($check['value']); ?></td></tr><?php endforeach; ?>
 </tbody></table><p class="description">Numpo PHP-only است و برای Crawl به Go Engine، Chromium یا PostgreSQL داخلی نیاز ندارد.</p></div>
 <form method="post" action="<?php echo esc_url(admin_url('admin-post.php'));?>"><?php wp_nonce_field('numpo_save');?><input type="hidden" name="action" value="numpo_save">
 <div class="numpo-section-title"><h2>Discovery defaults</h2><span class="numpo-pill">پیش‌فرض‌ها</span></div><div class="numpo-settings-card"><table class="form-table">
 <tr><th>Default project</th><td><input class="regular-text" name="default_project" value="<?php echo esc_attr(Numpo_Settings::default_project());?>"></td></tr>
 <tr><th>Search provider template</th><td><input class="large-text" name="search_url_template" value="<?php echo esc_attr(Numpo_Settings::search_url_template());?>" placeholder="https://provider.example/search?q={query}"></td></tr>
 </table></div><div class="numpo-section-title"><h2>Safety & crawl limits</h2><span class="numpo-pill">امنیت و عملکرد</span></div><div class="numpo-settings-card"><table class="form-table"><table class="form-table">
 <tr><th>Limits</th><td>Max pages <input type="number" min="1" name="max_pages" value="<?php echo esc_attr(Numpo_Settings::int('numpo_max_pages',100));?>"> &nbsp; Max URLs <input type="number" min="1" name="max_urls" value="<?php echo esc_attr(Numpo_Settings::int('numpo_max_urls',500));?>"> &nbsp; Max depth <input type="number" min="0" name="max_depth" value="<?php echo esc_attr(Numpo_Settings::int('numpo_max_depth',3));?>"> &nbsp; Candidates/page <input type="number" min="1" name="max_candidates_per_page" value="<?php echo esc_attr(Numpo_Settings::int('numpo_max_candidates_per_page',50));?>"></td></tr>
 <tr><th>Rate limit</th><td><input type="number" min="0" name="domain_rate_limit_ms" value="<?php echo esc_attr(Numpo_Settings::int('numpo_domain_rate_limit_ms',250,0));?>"> ms per domain</td></tr>
 <tr><th>Probe cache TTL</th><td><input type="number" min="1" name="probe_ttl_seconds" value="<?php echo esc_attr(Numpo_Settings::int('numpo_probe_ttl_seconds',3600));?>"> seconds</td></tr>
 <tr><th>Scope</th><td><label><input type="checkbox" name="allow_subdomains" value="1" <?php checked(Numpo_Settings::bool('allow_subdomains',false),true);?>> Allow subdomains</label><br><label><input type="checkbox" name="allow_external_links" value="1" <?php checked(Numpo_Settings::bool('allow_external_links',false),true);?>> Allow external links</label></td></tr>
 </table></div><div class="numpo-section-title"><h2>Default capabilities</h2><span class="numpo-pill">قابلیت‌ها</span></div><div class="numpo-settings-card"><div class="numpo-checks"><?php echo self::capChecks();?></div>
 <div class="numpo-savebar"><button class="button button-primary button-hero">ذخیره تنظیمات</button><span class="description">تنظیمات جدید برای Jobهای بعدی اعمال می‌شوند.</span></div></div></form></div><?php }
 public static function save(){
  if(!current_user_can('manage_options')||!check_admin_referer('numpo_save'))wp_die('Forbidden');
  update_option('numpo_default_project',sanitize_text_field(wp_unslash($_POST['default_project']??'default')));
  update_option('numpo_search_url_template',esc_url_raw(wp_unslash($_POST['search_url_template']??'')));
  foreach(['max_pages'=>100,'max_urls'=>500,'max_depth'=>3,'max_candidates_per_page'=>50,'domain_rate_limit_ms'=>250,'probe_ttl_seconds'=>3600] as $k=>$d)update_option('numpo_'.$k,max(1,absint($_POST[$k]??$d)));
  foreach(['active_probe','deep_crawl','link_discovery','sitemap','robots','subdomain_from_crawl','wordpress','woocommerce','phone','email','business','social','page_classification'] as $k=>$_)update_option('numpo_cap_'.$k,isset($_POST['cap_'.$k])?'1':'0');
  update_option('numpo_allow_subdomains',isset($_POST['allow_subdomains'])?'1':'0');
  update_option('numpo_allow_external_links',isset($_POST['allow_external_links'])?'1':'0');
  wp_safe_redirect(admin_url('admin.php?page=numpo-settings&updated=1'));exit;
 }
}
