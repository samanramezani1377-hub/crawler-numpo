<?php
if(!defined('ABSPATH')) exit;
class Numpo_Admin {
 public static function init(){add_action('admin_menu',[__CLASS__,'menu']);add_action('admin_post_numpo_save',[__CLASS__,'save']);require_once NUMPO_DIR.'includes/class-numpo-ajax.php';}
 public static function menu(){add_menu_page('Numpo','Numpo','manage_options','numpo',[__CLASS__,'page'],'dashicons-search',58);}
 private static function capChecks(){
  $caps=['active_probe'=>'Active Probe','deep_crawl'=>'Deep Crawl','link_discovery'=>'Link discovery','sitemap'=>'Sitemap','robots'=>'robots.txt','subdomain_from_crawl'=>'Subdomain discovery','wordpress'=>'WordPress detection','woocommerce'=>'WooCommerce detection','phone'=>'Phone extraction','email'=>'Email extraction','business'=>'Business extraction','social'=>'Social extraction','page_classification'=>'Page classification'];
  $html='';
  foreach($caps as $key=>$label){$html.='<label style="display:inline-block;min-width:230px;margin:4px 12px 4px 0;"><input type="checkbox" name="cap_'.$key.'" value="1" '.checked(Numpo_Settings::cap($key,true),true,false).'> '.esc_html($label).'</label>';}
  return $html;
 }
 public static function page(){if(!current_user_can('manage_options'))return;
 $diag=Numpo_Diagnostics::check(); ?>
 <style>
 #numpo-app{max-width:1500px;margin:18px 20px 40px 0;font-family:inherit;direction:rtl}
 #numpo-app *{box-sizing:border-box}.np-hero{background:linear-gradient(135deg,#25104f,#5b21b6 55%,#7c3aed);color:#fff;border-radius:18px;padding:28px 30px;margin-bottom:18px;box-shadow:0 12px 35px rgba(76,29,149,.18)}
 .np-hero-top{display:flex;justify-content:space-between;gap:20px;align-items:center}.np-brand{font-size:25px;font-weight:800}.np-muted{opacity:.78}.np-live{display:inline-flex;align-items:center;gap:7px;background:rgba(255,255,255,.13);border:1px solid rgba(255,255,255,.18);padding:7px 12px;border-radius:999px;font-size:12px}.np-dot{width:9px;height:9px;border-radius:50%;background:#34d399;box-shadow:0 0 0 4px rgba(52,211,153,.15)}
 .np-grid{display:grid;grid-template-columns:repeat(12,1fr);gap:14px}.np-card{background:#fff;border:1px solid #e5e7eb;border-radius:15px;padding:18px;box-shadow:0 2px 8px rgba(15,23,42,.04)}.np-span-12{grid-column:span 12}.np-span-8{grid-column:span 8}.np-span-6{grid-column:span 6}.np-span-4{grid-column:span 4}.np-span-3{grid-column:span 3}
 .np-card h2,.np-card h3{margin:0 0 14px}.np-section{font-size:12px;font-weight:800;color:#6b7280;text-transform:uppercase;letter-spacing:.06em;margin-bottom:9px}.np-fields{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.np-field label{font-weight:700;font-size:12px;display:block;margin-bottom:5px}.np-field input,.np-field select,.np-field textarea{width:100%;border:1px solid #d1d5db;border-radius:9px;padding:8px 10px;background:#fff}.np-field textarea{min-height:100px}.np-help{font-size:11px;color:#6b7280;margin-top:5px}
 .np-actions{display:flex;gap:8px;align-items:center;flex-wrap:wrap}.np-btn{border:0;border-radius:9px;padding:9px 15px;font-weight:700;cursor:pointer}.np-primary{background:#5b21b6;color:#fff}.np-danger{background:#fee2e2;color:#991b1b}.np-ghost{background:#f3f4f6;color:#374151}.np-btn:disabled{opacity:.55;cursor:not-allowed}
 .np-statusbar{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin:14px 0}.np-status{font-weight:800}.np-status.running{color:#059669}.np-status.done{color:#2563eb}.np-status.fail{color:#dc2626}.np-status.queue{color:#d97706}
 .np-stat{padding:16px;border:1px solid #eee;border-radius:13px;background:#fafafa}.np-stat b{font-size:25px;display:block;margin-top:5px}.np-stat span{font-size:12px;color:#6b7280}.np-progress{height:9px;background:#ede9fe;border-radius:99px;overflow:hidden}.np-progress>i{display:block;height:100%;background:linear-gradient(90deg,#7c3aed,#a78bfa);width:0%;transition:width .4s}
 .np-kv{display:grid;grid-template-columns:150px 1fr;gap:8px;font-size:12px}.np-kv strong{color:#374151}.np-url{direction:ltr;text-align:left;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.np-log{max-height:280px;overflow:auto;border:1px solid #eee;border-radius:10px}.np-log-row{display:grid;grid-template-columns:90px 20px 1fr;gap:8px;padding:9px 11px;border-bottom:1px solid #f1f1f1;font-size:12px}.np-log-row:last-child{border-bottom:0}.np-good{color:#059669}.np-warn{color:#d97706}.np-bad{color:#dc2626}.np-tabs{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:12px}.np-tab{padding:8px 12px;border-radius:9px;background:#f3f4f6;border:0;cursor:pointer;font-weight:700}.np-tab.active{background:#ede9fe;color:#5b21b6}.np-panel{display:none}.np-panel.active{display:block}.np-checks{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:8px}.np-check{padding:9px;border:1px solid #e5e7eb;border-radius:9px;background:#fafafa;font-size:12px}.np-table{width:100%;border-collapse:collapse;font-size:11px}.np-table th,.np-table td{padding:8px;border-bottom:1px solid #eee;text-align:right;vertical-align:top}.np-table th{background:#fafafa}.np-table-wrap{overflow:auto;max-height:380px;border:1px solid #eee;border-radius:10px}
 @media(max-width:1000px){.np-span-8,.np-span-6,.np-span-4,.np-span-3{grid-column:span 12}.np-fields,.np-checks{grid-template-columns:1fr}}
 </style>
 <div class="wrap" id="numpo-app">
  <div class="np-hero"><div class="np-hero-top"><div><div class="np-brand">Numpo Discovery Control Center</div><div class="np-muted">داشبورد اجرای واقعی، پایش زنده، نتایج و Runtime در یک صفحه</div></div><div class="np-live"><i class="np-dot"></i><span id="np-runtime">Runtime checking…</span></div></div></div>
  <form id="numpo-form">
   <div class="np-grid">
    <div class="np-card np-span-8"><div class="np-section">Create discovery job</div><h2>شروع Crawl جدید</h2><div class="np-fields">
     <div class="np-field"><label>Project ID</label><input name="project_id" value="<?php echo esc_attr(Numpo_Settings::default_project());?>" required></div>
     <div class="np-field"><label>Mode</label><select name="mode"><option value="manual">Manual</option><option value="hybrid">Hybrid</option><option value="automatic">Automatic</option></select></div>
     <div class="np-field" style="grid-column:1/-1"><label>Seeds / Domains</label><textarea name="seeds" placeholder="https://example.com&#10;https://example.org"></textarea><div class="np-help">هر خط یک URL. در Manual حداقل یک Seed لازم است.</div></div>
    </div></div>
    <div class="np-card np-span-4"><div class="np-section">Sources</div><h2>منابع Discovery</h2><label class="np-check"><input type="checkbox" name="source_search"> Search provider</label><label class="np-check"><input type="checkbox" name="source_sitemap"> Sitemap</label><label class="np-check"><input type="checkbox" name="source_robots"> robots.txt</label><label class="np-check"><input type="checkbox" name="source_links" checked> Link discovery</label><label class="np-check"><input type="checkbox" name="source_subdomains"> Subdomain discovery</label></div>
    <div class="np-card np-span-6"><div class="np-section">Target & limits</div><h2>کنترل Crawl</h2><div class="np-fields">
      <div class="np-field"><label>Country / TLD</label><select name="target_country"><option value="">Any</option><option value="ir">Iran (.ir)</option><option value="nl">Netherlands (.nl)</option><option value="us">United States (.us)</option><option value="de">Germany (.de)</option><option value="uk">United Kingdom (.uk)</option><option value="fr">France (.fr)</option><option value="tr">Turkey (.tr)</option></select></div>
      <div class="np-field"><label>Max pages</label><input type="number" min="1" name="max_pages" value="<?php echo esc_attr(Numpo_Settings::int('numpo_max_pages',100));?>"></div>
      <div class="np-field"><label>Max URLs</label><input type="number" min="1" name="max_urls" value="<?php echo esc_attr(Numpo_Settings::int('numpo_max_urls',500));?>"></div>
      <div class="np-field"><label>Max depth</label><input type="number" min="0" name="max_depth" value="<?php echo esc_attr(Numpo_Settings::int('numpo_max_depth',3));?>"></div>
      <div class="np-field"><label>Candidates / page</label><input type="number" min="1" name="max_candidates_per_page" value="<?php echo esc_attr(Numpo_Settings::int('numpo_max_candidates_per_page',50));?>"></div>
    </div></div>
    <div class="np-card np-span-6"><div class="np-section">Capabilities</div><h2>داده‌هایی که استخراج می‌شوند</h2><div class="np-checks"><?php echo self::capChecks();?></div></div>
    <div class="np-card np-span-12"><div class="np-actions"><button class="np-btn np-primary" id="np-start">▶ شروع Crawl</button><button type="button" class="np-btn np-ghost" id="np-refresh">↻ بروزرسانی</button><span id="np-form-status" class="np-muted"></span></div></div>
   </div>
  </form>
  <div id="np-dashboard"></div>
 </div>
 <script>
 (function(){
 const root=document.getElementById('np-dashboard'),form=document.getElementById('numpo-form'),ajaxUrl=<?php echo wp_json_encode(admin_url('admin-ajax.php'));?>,nonce=<?php echo wp_json_encode(wp_create_nonce('numpo_admin'));?>,restBase=<?php echo wp_json_encode(trailingslashit(rest_url('numpo/v1')));?>,restNonce=<?php echo wp_json_encode(wp_create_nonce('wp_rest'));?>;
 let active=null,timer=null;
 const esc=v=>String(v??'').replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));
 const n=v=>Number(v||0).toLocaleString('en-US');
 async function api(path,opt={}){
  const o={...opt,credentials:'same-origin',headers:{'Accept':'application/json','X-WP-Nonce':restNonce,...(opt.headers||{})}};
  if(o.body&&typeof o.body!=='string'){o.headers['Content-Type']='application/json';o.body=JSON.stringify(o.body)}
  const r=await fetch(restBase+path.replace(/^\\//,''),o),t=await r.text();let d;try{d=JSON.parse(t)}catch(e){throw new Error('پاسخ JSON معتبر نبود: '+t.slice(0,300))}
  if(!r.ok)throw new Error(d?.message||d?.data?.message||d?.error?.message||'خطای API');return d;
 }
 function caps(f){const o={};['active_probe','deep_crawl','link_discovery','sitemap','robots','subdomain_from_crawl','wordpress','woocommerce','phone','email','business','social','page_classification'].forEach(k=>o[k]=f.has('cap_'+k));return o}
 function card(label,val,sub){return '<div class="np-stat"><span>'+esc(label)+'</span><b>'+esc(n(val))+'</b><small class="np-muted">'+esc(sub||'')+'</small></div>'}
 function render(j){
  const m=j.metrics||{},status=j.status||'unknown',max=Number(j.max_pages||m.pages||0),pct=max?Math.min(100,Math.round(Number(j.processed_pages||0)/max*100)):0;
  const running=status==='running',queued=status==='queued',paused=status==='paused',done=status==='completed',fail=status==='failed',cancel=status==='cancelled';
  const statusClass=running?'running':done?'done':fail?'fail':queued?'queue':paused?'queue':'';
  const statusText=running?'🟢 در حال اجرا':queued?'🟠 در صف شروع':paused?'⏸️ متوقف موقت':done?'✅ تکمیل شد':fail?'🔴 خطا':cancel?'⛔ لغو شد':status;
  let logs='';
  if(m.current_url)logs+='<div class="np-log-row"><span>اکنون</span><b class="np-good">●</b><div class="np-url">'+esc(m.current_url)+'</div></div>';
  if(m.last_error)logs+='<div class="np-log-row"><span>خطای اخیر</span><b class="np-bad">!</b><div>'+esc(m.last_error)+'</div></div>';
  if(m.last_candidate)logs+='<div class="np-log-row"><span>آخرین Candidate</span><b class="np-good">+</b><div class="np-url">'+esc(m.last_candidate)+'</div></div>';
  if(!logs)logs='<div class="np-log-row"><span>—</span><b>·</b><div>هنوز Activity ثبت نشده است.</div></div>';
  const dataRows=[['Domains',m.domains],['Pages',m.pages],['Technologies',m.technologies],['Contacts',m.contacts],['Business',m.business],['Social',m.social],['Classifications',m.classifications],['Probes',m.probes],['Errors',m.errors]];
  root.innerHTML='<div class="np-grid">'+
   '<div class="np-card np-span-12"><div class="np-statusbar"><div><div class="np-section">Live Job Monitor</div><h2>Job '+esc(j.job_id)+'</h2><div class="np-status '+statusClass+'">'+statusText+' · <span id="np-last-poll">live</span></div></div><div class="np-actions">'+(running||queued?'<button class="np-btn np-danger" id="np-cancel">■ لغو Job</button>':'')+'<a class="np-btn np-ghost" href="'+esc(<?php echo wp_json_encode(admin_url('admin-ajax.php'));?>)+'?action=numpo_admin_export&nonce='+encodeURIComponent(nonce)+'&job_id='+encodeURIComponent(j.job_id)+'">Export CSV</a></div></div><div class="np-progress"><i style="width:'+pct+'%"></i></div><div class="np-muted" style="margin-top:7px">پیشرفت صفحات: '+n(j.processed_pages)+' از '+n(max)+' · URLهای مصرف‌شده: '+n(j.processed_urls)+' از '+n(j.max_urls)+'</div></div>'+
   '<div class="np-card np-span-12"><div class="np-grid">'+card('سایت / Domain',m.domains,'Project total')+card('URL پیدا شده',m.candidates,'Candidates')+card('صفحات پردازش شده',j.processed_pages,'Processed pages')+card('داده‌های تکنولوژی',m.technologies,'Technologies')+card('مخاطبین',m.contacts,'Phone / Email')+card('Business profiles',m.business,'Extracted profiles')+card('صف در انتظار',m.queued,'Queue')+card('خطاها',m.errors,'Job errors')+'</div></div>'+
   '<div class="np-card np-span-4"><div class="np-section">Worker</div><h3>وضعیت Worker</h3><div class="np-kv"><strong>Status</strong><span>'+esc(statusText)+'</span><strong>در حال بررسی</strong><span class="np-url">'+esc(m.current_url||'—')+'</span><strong>صف باقی‌مانده</strong><span>'+n(m.queued)+'</span><strong>Retry</strong><span>'+n(m.retryable)+'</span><strong>Completed</strong><span>'+n(m.completed)+'</span></div></div>'+
   '<div class="np-card np-span-4"><div class="np-section">Runtime data</div><h3>داده‌های ذخیره‌شده</h3><div class="np-table-wrap"><table class="np-table"><tbody>'+dataRows.map(x=>'<tr><th>'+esc(x[0])+'</th><td>'+n(x[1])+'</td></tr>').join('')+'</tbody></table></div></div>'+
   '<div class="np-card np-span-4"><div class="np-section">Activity</div><h3>Live Activity</h3><div class="np-log">'+logs+'</div></div>'+
   '<div class="np-card np-span-12"><div class="np-tabs">'+['candidates','domains','pages','technologies','contacts','business','social','classifications','probes','errors','settings'].map((x,i)=>'<button class="np-tab '+(i===0?'active':'')+'" data-tab="'+x+'">'+x+'</button>').join('')+'</div><div id="np-tab-body"></div></div>'+
  '</div>';
  const cancel=document.getElementById('np-cancel');if(cancel)cancel.onclick=async()=>{cancel.disabled=true;try{await api('/jobs/'+encodeURIComponent(j.job_id)+'/cancel',{method:'POST'});await poll()}catch(e){alert(e.message)}};
  document.querySelectorAll('.np-tab').forEach(b=>b.onclick=()=>{document.querySelectorAll('.np-tab').forEach(x=>x.classList.remove('active'));b.classList.add('active');loadTab(b.dataset.tab,j)});
  loadTab('candidates',j);
 }
 async function loadTab(tab,j){
  const body=document.getElementById('np-tab-body');if(!body)return;
  if(tab==='settings'){body.innerHTML='<div class="np-card"><div class="np-section">Runtime configuration</div><h3>تنظیمات Numpo</h3><p>این بخش جایگزین صفحه Settings جداگانه است. تنظیمات Runtime و Crawl در همین پنل مدیریت می‌شوند.</p><form method="post" action="<?php echo esc_url(admin_url('admin-post.php'));?>"><input type="hidden" name="action" value="numpo_save"><?php wp_nonce_field('numpo_save');?><div class="np-fields"><div class="np-field"><label>Engine mode</label><select name="runtime_mode"><option value="bundled" <?php selected(Numpo_Settings::runtime_mode(),'bundled');?>>Bundled</option><option value="external" <?php selected(Numpo_Settings::runtime_mode(),'external');?>>External</option></select></div><div class="np-field"><label>Engine URL</label><input name="engine_url" value="<?php echo esc_attr(Numpo_Settings::engine_url());?>"></div><div class="np-field"><label>Browser binary</label><input name="browser_binary" value="<?php echo esc_attr(Numpo_Settings::browser_binary());?>"></div><div class="np-field"><label>API Key</label><input type="password" name="api_key" value="<?php echo esc_attr(Numpo_Settings::api_key());?>"></div><div class="np-field"><label>Default project</label><input name="default_project" value="<?php echo esc_attr(Numpo_Settings::default_project());?>"></div><div class="np-field"><label>Search URL template</label><input name="search_url_template" value="<?php echo esc_attr(Numpo_Settings::search_url_template());?>"></div><div class="np-field"><label>Domain rate limit (ms)</label><input type="number" name="domain_rate_limit_ms" value="<?php echo esc_attr(Numpo_Settings::int('numpo_domain_rate_limit_ms',250));?>"></div><div class="np-field"><label>Probe cache TTL (sec)</label><input type="number" name="probe_ttl_seconds" value="<?php echo esc_attr(Numpo_Settings::int('numpo_probe_ttl_seconds',3600));?>"></div><div class="np-field"><label>Max pages</label><input type="number" name="max_pages" value="<?php echo esc_attr(Numpo_Settings::int('numpo_max_pages',100));?>"></div><div class="np-field"><label>Max URLs</label><input type="number" name="max_urls" value="<?php echo esc_attr(Numpo_Settings::int('numpo_max_urls',500));?>"></div><div class="np-field"><label>Max depth</label><input type="number" name="max_depth" value="<?php echo esc_attr(Numpo_Settings::int('numpo_max_depth',3));?>"></div><div class="np-field"><label>Candidates/page</label><input type="number" name="max_candidates_per_page" value="<?php echo esc_attr(Numpo_Settings::int('numpo_max_candidates_per_page',50));?>"></div></div><p><button class="np-btn np-primary">ذخیره تنظیمات</button></p></form><hr><h3>Runtime diagnostics</h3><table class="np-table"><tbody><?php foreach($diag['checks'] as $c):?><tr><th><?php echo esc_html($c['label']);?></th><td><?php echo $c['ok']?'OK':($c['required']?'Required':'Optional');?></td><td><?php echo esc_html($c['value']);?></td></tr><?php endforeach;?></tbody></table></div>';return}
  if(tab==='errors'){try{const d=await api('/jobs/'+encodeURIComponent(j.job_id)+'/errors');body.innerHTML='<div class="np-table-wrap"><table class="np-table"><thead><tr><th>Category</th><th>Code</th><th>Message</th><th>Retry</th></tr></thead><tbody>'+(d.items||[]).map(x=>'<tr><td>'+esc(x.category)+'</td><td>'+esc(x.code)+'</td><td>'+esc(x.message)+'</td><td>'+esc(x.retryable)+'</td></tr>').join('')+'</tbody></table></div>'}catch(e){body.innerHTML='<p>'+esc(e.message)+'</p>'}return}
  try{const d=await api('/jobs/'+encodeURIComponent(j.job_id)+'/'+tab+'?per_page=50');const items=d.items||[];if(!items.length){body.innerHTML='<p class="np-muted">داده‌ای ثبت نشده.</p>';return}const keys=Object.keys(items[0]);body.innerHTML='<div class="np-table-wrap"><table class="np-table"><thead><tr>'+keys.map(k=>'<th>'+esc(k)+'</th>').join('')+'</tr></thead><tbody>'+items.map(x=>'<tr>'+keys.map(k=>'<td>'+esc(typeof x[k]==='object'?JSON.stringify(x[k]):x[k])+'</td>').join('')+'</tr>').join('')+'</tbody></table></div>'}catch(e){body.innerHTML='<p>'+esc(e.message)+'</p>'}
 }
 async function poll(){if(!active)return;try{const j=await api('/jobs/'+encodeURIComponent(active));render(j);document.getElementById('np-runtime').textContent='Live · '+new Date().toLocaleTimeString();if(j.status==='running'||j.status==='queued'||j.status==='paused'){clearTimeout(timer);timer=setTimeout(poll,5000)}else{clearTimeout(timer);timer=null}}catch(e){root.innerHTML='<div class="np-card np-bad">'+esc(e.message)+'</div>';clearTimeout(timer);timer=null}}
 form.addEventListener('submit',async e=>{e.preventDefault();const btn=document.getElementById('np-start');btn.disabled=true;btn.textContent='در حال ایجاد Job…';try{const f=new FormData(form),seeds=String(f.get('seeds')||'').split(/\\r?\\n/).map(x=>x.trim()).filter(Boolean),sources={search_provider:f.has('source_search'),sitemap:f.has('source_sitemap'),robots:f.has('source_robots'),link_discovery:f.has('source_links'),subdomain_from_crawl:f.has('source_subdomains')},target={},country=f.get('target_country');if(country)target.countries=[country];const limits={max_pages:Number(f.get('max_pages')),max_urls:Number(f.get('max_urls')),max_depth:Number(f.get('max_depth')),max_candidates_per_page:Number(f.get('max_candidates_per_page'))},fd=new URLSearchParams();fd.set('action','numpo_admin_create');fd.set('nonce',nonce);fd.set('project_id',f.get('project_id'));fd.set('mode',f.get('mode'));fd.set('seeds',JSON.stringify(seeds));fd.set('sources',JSON.stringify(sources));fd.set('target',JSON.stringify(target));fd.set('limits',JSON.stringify(limits));fd.set('capabilities',JSON.stringify(caps(f)));const r=await fetch(ajaxUrl,{method:'POST',credentials:'same-origin',headers:{'Accept':'application/json','Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'},body:fd.toString()}),d=await r.json();if(!d.success)throw new Error(d?.data?.message||'خطا در ایجاد Job');active=d.data.job_id;await poll()}catch(e){document.getElementById('np-form-status').textContent=e.message}finally{btn.disabled=false;btn.textContent='▶ شروع Crawl'}});document.getElementById('np-refresh').onclick=()=>{if(active)poll()};document.getElementById('np-runtime').textContent='Ready';})();
 </script><?php } public static function settings(){if(!current_user_can('manage_options'))return;?>
 <div class="wrap" id="numpo-app"><h1>Numpo Settings</h1>
  <?php $diag=Numpo_Diagnostics::check(); ?>
  <div class="postbox" style="padding:16px;max-width:1100px"><h2>Runtime diagnostics</h2>
   <p><strong><?php echo $diag['ok']?'Ready':'Blocked'; ?></strong></p>
   <table class="widefat striped"><thead><tr><th>Component</th><th>Status</th><th>Value</th></tr></thead><tbody>
   <?php foreach($diag['checks'] as $check): ?><tr><td><?php echo esc_html($check['label']); ?></td><td><?php echo $check['ok']?'OK':($check['required']?'Required':'Optional'); ?></td><td><?php echo esc_html($check['value']); ?></td></tr><?php endforeach; ?>
   </tbody></table>
   <p class="description">Linux amd64, PHP exec(), bundled engine and bundled PostgreSQL are required for bundled mode. Chromium is optional and is only required for browser rendering.</p>
  </div><form method="post" action="<?php echo esc_url(admin_url('admin-post.php'));?>"><?php wp_nonce_field('numpo_save');?><input type="hidden" name="action" value="numpo_save">
  <h2>Engine runtime</h2><table class="form-table">
   <tr><th>Engine mode</th><td><select name="runtime_mode"><option value="bundled" <?php selected(Numpo_Settings::runtime_mode(),'bundled');?>>Bundled (recommended)</option><option value="external" <?php selected(Numpo_Settings::runtime_mode(),'external');?>>External</option></select><p class="description">Bundled runs the Go engine and its PostgreSQL runtime from this plugin package. External keeps the same API contract for a separately managed engine.</p></td></tr>
   <tr><th>Engine URL</th><td><input class="regular-text" name="engine_url" value="<?php echo esc_attr(Numpo_Settings::engine_url());?>" placeholder="http://127.0.0.1:8080"><p class="description">Base URL of the Go engine.</p></td></tr>
   <tr><th>Browser binary</th><td><input class="regular-text" name="browser_binary" value="<?php echo esc_attr(Numpo_Settings::browser_binary());?>" placeholder="/usr/bin/chromium"><p class="description">Optional. Leave empty to auto-detect Chromium/Chrome. Only needed for browser rendering.</p></td></tr>
   <tr><th>API Key</th><td><input type="password" class="regular-text" name="api_key" value="<?php echo esc_attr(Numpo_Settings::api_key());?>"><p class="description">Used as Bearer authentication.</p></td></tr>
  </table>
  <h2>Discovery defaults</h2><table class="form-table">
   <tr><th>Default project</th><td><input class="regular-text" name="default_project" value="<?php echo esc_attr(Numpo_Settings::default_project());?>"></td></tr>
   <tr><th>Search provider template</th><td><input class="large-text" name="search_url_template" value="<?php echo esc_attr(Numpo_Settings::search_url_template());?>" placeholder="https://provider.example/search?q={query}"><p class="description">The engine replaces {query} and accepts newline-separated HTTP(S) URLs from the provider.</p></td></tr>
  </table>
  <h2>Safety & crawl limits</h2><table class="form-table">
   <tr><th>Limits</th><td>Max pages <input type="number" min="1" name="max_pages" value="<?php echo esc_attr(Numpo_Settings::int('numpo_max_pages',100));?>"> &nbsp; Max URLs <input type="number" min="1" name="max_urls" value="<?php echo esc_attr(Numpo_Settings::int('numpo_max_urls',500));?>"> &nbsp; Max depth <input type="number" min="0" name="max_depth" value="<?php echo esc_attr(Numpo_Settings::int('numpo_max_depth',3));?>"> &nbsp; Candidates/page <input type="number" min="1" name="max_candidates_per_page" value="<?php echo esc_attr(Numpo_Settings::int('numpo_max_candidates_per_page',50));?>"></td></tr>
   <tr><th>Rate limit</th><td><input type="number" min="1" name="domain_rate_limit_ms" value="<?php echo esc_attr(Numpo_Settings::int('numpo_domain_rate_limit_ms',250));?>"> ms per domain</td></tr>
   <tr><th>Probe cache TTL</th><td><input type="number" min="1" name="probe_ttl_seconds" value="<?php echo esc_attr(Numpo_Settings::int('numpo_probe_ttl_seconds',3600));?>"> seconds</td></tr>
   <tr><th>Scope</th><td><label><input type="checkbox" name="allow_subdomains" value="1" <?php checked(Numpo_Settings::bool('allow_subdomains',false),true);?>> Allow subdomains</label><br><label><input type="checkbox" name="allow_external_links" value="1" <?php checked(Numpo_Settings::bool('allow_external_links',false),true);?>> Allow external links</label></td></tr>
  </table>
  <h2>Default capabilities</h2><div class="numpo-checks"><?php echo self::capChecks();?></div>
  <p><button class="button button-primary">Save settings</button></p>
 </form></div><?php }
 public static function save(){
  if(!current_user_can('manage_options')||!check_admin_referer('numpo_save'))wp_die('Forbidden');
  update_option('numpo_runtime_mode',in_array($_POST['runtime_mode']??'bundled',['bundled','external'],true)?sanitize_text_field(wp_unslash($_POST['runtime_mode'])):'bundled');
  update_option('numpo_engine_url',esc_url_raw(wp_unslash($_POST['engine_url']??'')));
  update_option('numpo_api_key',sanitize_text_field(wp_unslash($_POST['api_key']??'')));
  update_option('numpo_browser_binary',sanitize_text_field(wp_unslash($_POST['browser_binary']??'')));
  update_option('numpo_default_project',sanitize_text_field(wp_unslash($_POST['default_project']??'default')));
  update_option('numpo_search_url_template',esc_url_raw(wp_unslash($_POST['search_url_template']??'')));
  foreach(['max_pages'=>100,'max_urls'=>500,'max_depth'=>3,'max_candidates_per_page'=>50,'domain_rate_limit_ms'=>250,'probe_ttl_seconds'=>3600] as $k=>$d)update_option('numpo_'.$k,max(1,absint($_POST[$k]??$d)));
  foreach(['active_probe','deep_crawl','link_discovery','sitemap','robots','subdomain_from_crawl','wordpress','woocommerce','phone','email','business','social','page_classification'] as $k=>$_)update_option('numpo_cap_'.$k,isset($_POST['cap_'.$k])?'1':'0');
  update_option('numpo_allow_subdomains',isset($_POST['allow_subdomains'])?'1':'0');
  update_option('numpo_allow_external_links',isset($_POST['allow_external_links'])?'1':'0');
  wp_safe_redirect(admin_url('admin.php?page=numpo-settings&updated=1'));exit;
 }
}
