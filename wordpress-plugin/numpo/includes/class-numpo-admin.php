<?php
if(!defined('ABSPATH')) exit;
class Numpo_Admin {
 public static function init(){add_action('admin_menu',[__CLASS__,'menu']);add_action('admin_post_numpo_save',[__CLASS__,'save']);require_once NUMPO_DIR.'includes/class-numpo-ajax.php';}
 public static function menu(){add_menu_page('Numpo','Numpo','manage_options','numpo',[__CLASS__,'page'],'dashicons-search',58);}
 private static function capChecks(){
  $caps=['active_probe'=>'Active Probe','deep_crawl'=>'Deep Crawl','link_discovery'=>'Link discovery','sitemap'=>'Sitemap','robots'=>'robots.txt','subdomain_from_crawl'=>'Subdomain discovery','wordpress'=>'WordPress detection','woocommerce'=>'WooCommerce detection','phone'=>'Phone extraction','email'=>'Email extraction','business'=>'Business extraction','social'=>'Social extraction','page_classification'=>'Page classification'];
  $html='';foreach($caps as $key=>$label){$html.='<label class="np-check"><input type="checkbox" name="cap_'.$key.'" value="1" '.checked(Numpo_Settings::cap($key,true),true,false).'><span>'.esc_html($label).'</span></label>';}return $html;
 }
 public static function page(){if(!current_user_can('manage_options'))return; $diag=Numpo_Diagnostics::check();?>
<style>
#numpo-app{--p:#6657e8;--p2:#5144c7;--ink:#171923;--muted:#6b7280;--line:#e4e7ef;--soft:#f7f8fb;max-width:1380px;margin:20px 20px 40px 0;color:var(--ink)}
#numpo-app *{box-sizing:border-box}.np-top{background:linear-gradient(135deg,#171923 0%,#29244e 58%,#6657e8 100%);color:#fff;border-radius:20px;padding:28px 30px;margin-bottom:14px}.np-top h1{color:#fff;margin:0 0 6px;font-size:27px}.np-top p{margin:0;color:#e8e7f3}.np-nav{display:flex;gap:8px;flex-wrap:wrap;background:#fff;border:1px solid var(--line);padding:9px;border-radius:14px;position:sticky;top:32px;z-index:20;box-shadow:0 4px 18px rgba(20,24,40,.05)}.np-nav a{color:#3f4554;text-decoration:none;padding:9px 13px;border-radius:9px;font-weight:600;font-size:13px}.np-nav a:hover{background:#f1efff;color:var(--p2)}
.np-section{margin-top:24px;scroll-margin-top:90px}.np-view{display:none}.np-view.np-view-active{display:block}.np-nav a.active{background:#f1efff;color:var(--p2);box-shadow:inset 0 0 0 1px #d8d3ff}.np-heading{display:flex;align-items:end;justify-content:space-between;gap:15px;margin:0 0 10px}.np-heading h2{font-size:20px;margin:0}.np-heading p{margin:4px 0 0;color:var(--muted);font-size:13px}.np-badge{background:#efedff;color:var(--p2);padding:5px 10px;border-radius:999px;font-size:12px;font-weight:700}
.np-card{background:#fff;border:1px solid var(--line);border-radius:16px;padding:20px;box-shadow:0 2px 12px rgba(20,24,40,.035)}.np-grid{display:grid;grid-template-columns:repeat(12,minmax(0,1fr));gap:14px}.np-span-12{grid-column:span 12}.np-span-8{grid-column:span 8}.np-span-6{grid-column:span 6}.np-span-4{grid-column:span 4}.np-span-3{grid-column:span 3}
.np-label{display:block;font-weight:700;font-size:13px;margin-bottom:7px}.np-input,#numpo-app select,#numpo-app textarea{width:100%;border:1px solid #d6dae4;border-radius:10px;background:#fff;padding:10px 12px;min-height:42px}.np-input:focus,#numpo-app select:focus,#numpo-app textarea:focus{border-color:var(--p);box-shadow:0 0 0 3px rgba(102,87,232,.12);outline:0}.np-help{color:var(--muted);font-size:12px;line-height:1.7}.np-actions{display:flex;gap:8px;flex-wrap:wrap;align-items:center}.np-btn{display:inline-flex!important;align-items:center;justify-content:center;min-height:40px;padding:8px 13px;border:1px solid #d4d8e2;border-radius:9px;background:#fff;color:#303542;text-decoration:none!important;font-weight:700;cursor:pointer}.np-btn:hover{border-color:#bdb7f5;background:#faf9ff}.np-primary{background:var(--p)!important;border-color:var(--p)!important;color:#fff!important}.np-danger{background:#fff1f1!important;border-color:#efb4b4!important;color:#a52222!important}
.np-section-label{font-size:12px;text-transform:uppercase;letter-spacing:.06em;color:var(--p2);font-weight:800;margin-bottom:8px}.np-mode{display:grid;grid-template-columns:repeat(3,1fr);gap:9px}.np-mode label{border:1px solid var(--line);border-radius:11px;padding:12px;cursor:pointer;background:var(--soft)}.np-mode label:has(input:checked){border-color:#bcb5f8;background:#f3f1ff}.np-mode input{margin-left:7px}
.np-checks{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:9px}.np-check{display:flex;align-items:center;gap:8px;border:1px solid var(--line);background:var(--soft);border-radius:10px;padding:10px;cursor:pointer;font-size:13px}.np-check input{margin:0}.np-limits{display:grid;grid-template-columns:repeat(4,1fr);gap:10px}
.np-statusline{display:flex;justify-content:space-between;gap:12px;align-items:center;border-bottom:1px solid var(--line);padding-bottom:13px;margin-bottom:14px}.np-status{font-weight:800}.np-muted{color:var(--muted);font-size:12px}.np-progress{height:10px;background:#eceef4;border-radius:999px;overflow:hidden;margin:13px 0 8px}.np-progress i{display:block;height:100%;background:var(--p);border-radius:999px}.np-stat{border:1px solid var(--line);border-radius:12px;padding:13px;background:var(--soft)}.np-stat span{display:block;color:var(--muted);font-size:11px;margin-bottom:5px}.np-stat b{font-size:20px}.np-kv{display:grid;grid-template-columns:120px 1fr;gap:9px;font-size:12px}.np-kv strong{color:#555}.np-url{word-break:break-all}.np-good{color:#17834d}.np-bad{color:#bd2929}.np-log{max-height:220px;overflow:auto}.np-log-row{display:grid;grid-template-columns:80px 22px 1fr;gap:7px;padding:9px 0;border-bottom:1px solid #f0f1f5;font-size:12px}.np-tabs{display:flex;gap:6px;overflow:auto;border-bottom:1px solid var(--line);margin-bottom:12px}.np-tab{border:0;background:none;padding:10px 12px;cursor:pointer;color:#666;font-weight:700;white-space:nowrap}.np-tab.active{color:var(--p2);border-bottom:2px solid var(--p)}.np-table-wrap{overflow:auto;border:1px solid var(--line);border-radius:10px}.np-table{width:100%;border-collapse:collapse;font-size:12px}.np-table th,.np-table td{padding:9px;border-bottom:1px solid #edf0f4;text-align:right;white-space:nowrap}.np-table th{background:var(--soft)}.np-history-list{display:grid;gap:8px}.np-history-row{display:grid;grid-template-columns:minmax(0,1fr) auto auto;gap:14px;align-items:center;width:100%;text-align:right;border:1px solid var(--line);background:#fff;border-radius:12px;padding:12px 14px;cursor:pointer}.np-history-row:hover,.np-history-row.active{border-color:#bcb5f8;background:#f8f7ff}.np-history-main{display:flex;flex-direction:column;gap:4px}.np-history-main small{color:var(--muted);font-size:11px}.np-history-metrics{display:flex;gap:12px;color:#555;font-size:12px}.np-history-live{display:flex;flex-wrap:wrap;gap:6px 12px;margin-top:6px;color:#555;font-size:11px}.np-history-live span{white-space:nowrap}.np-history-live b{color:#303542}.np-history-status{white-space:nowrap;font-size:12px;font-weight:800}.np-runtime{display:grid;grid-template-columns:repeat(3,1fr);gap:10px}.np-runtime-item{padding:13px;border:1px solid var(--line);border-radius:11px;background:var(--soft)}.np-ok{color:#16834b}.np-warn{color:#a96800}.np-savebar{display:flex;justify-content:space-between;gap:12px;align-items:center;padding-top:16px;margin-top:16px;border-top:1px solid var(--line)}
@media(max-width:900px){#numpo-app{margin-right:10px}.np-span-8,.np-span-6,.np-span-4{grid-column:span 12}.np-span-3{grid-column:span 6}.np-checks,.np-limits,.np-runtime{grid-template-columns:repeat(2,1fr)}.np-nav{top:10px}}@media(max-width:600px){#numpo-app{margin:10px 8px 30px 0}.np-grid{grid-template-columns:1fr}.np-span-12,.np-span-8,.np-span-6,.np-span-4,.np-span-3{grid-column:span 1}.np-checks,.np-limits,.np-runtime,.np-mode{grid-template-columns:1fr}.np-top{padding:20px;border-radius:14px}.np-nav{position:static}.np-heading{display:block}.np-savebar{display:block}.np-savebar .np-actions{margin-top:10px}}
</style>
<div class="wrap" id="numpo-app">
 <header class="np-top"><h1>Numpo Discovery</h1><p>مرکز مدیریت کامل Crawl، کشف سایت، مانیتورینگ، نتایج و خروجی‌ها — همه در یک صفحه.</p></header>
 <nav class="np-nav" id="numpo-section-nav" aria-label="Numpo management sections">
  <a href="#np-start" data-view="np-start">شروع Crawl</a><a href="#np-history" data-view="np-history">Job History</a><a href="#np-monitor" data-view="np-monitor">مانیتورینگ</a><a href="#np-results" data-view="np-results">نتایج</a><a href="#np-export" data-view="np-export">خروجی‌ها</a><a href="#np-settings" data-view="np-settings">تنظیمات</a><a href="#np-runtime" data-view="np-runtime">وضعیت سیستم</a>
 </nav>

 <section id="np-start" class="np-monitor-label">
  <div class="np-heading"><div><h2>۱. شروع Crawl</h2><p>Job جدید را بساز، محدوده را مشخص کن و Crawl را شروع کن.</p></div><span class="np-badge">Job Setup</span></div>
  <form id="numpo-form" method="post" action="" onsubmit="return false;">
   <div class="np-grid">
    <div class="np-card np-span-8"><div class="np-section-label">ورودی Crawl</div>
     <label class="np-label">Project ID<input class="np-input" name="project_id" value="<?php echo esc_attr(Numpo_Settings::default_project());?>" required></label>
     <div style="height:10px"></div>
     <label class="np-label">Seed URLs / Domains<textarea name="seeds" rows="7" placeholder="هر URL یا دامنه در یک خط&#10;https://example.com&#10;https://example.org"></textarea></label>
     <p class="np-help">این‌ها نقطه شروع Job هستند. در حالت Manual باید حداقل یک Seed وارد شود.</p>
    </div>
    <div class="np-card np-span-4"><div class="np-section-label">حالت اجرا</div>
     <div class="np-mode"><label><input type="radio" name="mode" value="manual" checked><b>Manual</b><br><small>فقط Seedهای داده‌شده</small></label><label><input type="radio" name="mode" value="hybrid"><b>Hybrid</b><br><small>Seed + منابع کشف</small></label><label><input type="radio" name="mode" value="automatic"><b>Automatic</b><br><small>کشف خودکار</small></label></div>
    </div>
    <div class="np-card np-span-6"><div class="np-section-label">منابع کشف</div><div class="np-checks"><label class="np-check"><input type="checkbox" name="source_search">Search provider</label><label class="np-check"><input type="checkbox" name="source_sitemap">Sitemap</label><label class="np-check"><input type="checkbox" name="source_robots">robots.txt</label><label class="np-check"><input type="checkbox" name="source_links" checked>Link discovery</label><label class="np-check"><input type="checkbox" name="source_subdomains">Subdomain discovery</label></div></div>
    <div class="np-card np-span-6"><div class="np-section-label">محدوده و فیلتر</div><label class="np-label">Country / TLD<select name="target_country"><option value="">بدون محدودیت</option><option value="ir">Iran (.ir)</option><option value="nl">Netherlands (.nl)</option><option value="us">United States (.us)</option><option value="de">Germany (.de)</option><option value="uk">United Kingdom (.uk)</option><option value="fr">France (.fr)</option><option value="tr">Turkey (.tr)</option></select></label><p class="np-help">فیلتر دامنه و سیگنال زبان/کشور، در صورت فعال بودن.</p></div>
    <div class="np-card np-span-12"><div class="np-section-label">محدودیت Crawl</div><div class="np-limits"><label class="np-label">Max pages<input class="np-input" type="number" min="1" name="max_pages" value="<?php echo esc_attr(Numpo_Settings::int('numpo_max_pages',100));?>"></label><label class="np-label">Max URLs<input class="np-input" type="number" min="1" name="max_urls" value="<?php echo esc_attr(Numpo_Settings::int('numpo_max_urls',500));?>"></label><label class="np-label">Max depth<input class="np-input" type="number" min="0" name="max_depth" value="<?php echo esc_attr(Numpo_Settings::int('numpo_max_depth',3));?>"></label><label class="np-label">Candidates / page<input class="np-input" type="number" min="1" name="max_candidates_per_page" value="<?php echo esc_attr(Numpo_Settings::int('numpo_max_candidates_per_page',50));?>"></label><label class="np-label">Revisit after<select class="np-input" name="revisit_after"><?php $rv=Numpo_Settings::int('numpo_revisit_after',0,0);?><option value="0" <?php selected($rv,0);?>>فقط یک‌بار</option><option value="86400" <?php selected($rv,86400);?>>هر 24 ساعت</option><option value="604800" <?php selected($rv,604800);?>>هر 7 روز</option><option value="2592000" <?php selected($rv,2592000);?>>هر 30 روز</option></select></label></div></div>
    <div class="np-card np-span-12"><div class="np-section-label">قابلیت‌های استخراج</div><div class="np-checks"><?php echo self::capChecks();?></div></div>
    <div class="np-card np-span-12"><div class="np-actions"><button type="submit" class="np-btn np-primary">🟢 شروع Crawl</button><button type="button" class="np-btn np-ghost" id="numpo-pause-start" disabled>⏸️ توقف موقت</button><button type="button" class="np-btn np-danger" id="numpo-cancel-start" disabled>⛔ لغو Job</button><button type="button" class="np-btn np-primary" id="numpo-resume-start" disabled>▶️ ادامه</button><button type="button" class="np-btn" id="numpo-refresh">🔄 بروزرسانی Job فعال</button><span id="numpo-status" class="np-muted"></span></div></div>
   </div>
  </form>
 </section>

 <section id="np-history" class="np-monitor-label"><div class="np-heading"><div><h2>۲. تاریخچه Jobها</h2><p>هیچ Crawl قبلی با شروع Job جدید حذف نمی‌شود. یک Job را انتخاب کن تا مانیتورینگ، نتایج و خروجی همان Job نمایش داده شود.</p></div><span class="np-badge">Persistent History</span><button type="button" class="np-btn np-ghost" id="np-all-project">همه نتایج Project</button></div><div class="np-card"><div id="numpo-job-history"><div class="np-muted">در حال بارگذاری Jobها…</div></div><div id="numpo-project-summary" class="np-project-summary"></div></div></section>

 <section id="np-monitor" class="np-monitor-label"><div class="np-heading"><div><h2>۳. مانیتورینگ و کنترل Job</h2><p>وضعیت لحظه‌ای و کنترل‌های Job انتخاب‌شده.</p></div><span class="np-badge">Live Monitor</span></div><div id="numpo-dashboard"></div></section>

 <section id="np-results" class="np-monitor-label"><div class="np-heading"><div><h2>۴. نتایج Crawl</h2><p>نتایج دقیق Job انتخاب‌شده؛ تغییر Job در تاریخچه این بخش را هم تغییر می‌دهد.</p></div><span class="np-badge">Results</span></div><div id="numpo-results-panel" class="np-card"><div class="np-muted">یک Job را از تاریخچه انتخاب کن.</div></div></section>

 <section id="np-export" class="np-monitor-label"><div class="np-heading"><div><h2>۵. Export Center</h2><p>خروجی همیشه به Job انتخاب‌شده متصل است؛ Job جدید، خروجی قبلی را جایگزین نمی‌کند.</p></div><span class="np-badge">Exports</span></div><div id="numpo-export-panel" class="np-card"><div class="np-muted">یک Job را از تاریخچه انتخاب کن.</div></div></section>

 <section id="np-settings" class="np-monitor-label"><div class="np-heading"><div><h2>۶. تنظیمات Numpo</h2><p>تنظیمات پیش‌فرض Jobهای بعدی را همین‌جا مدیریت کن.</p></div><span class="np-badge">Settings</span></div>
  <form method="post" action="<?php echo esc_url(admin_url('admin-post.php'));?>">
   <?php wp_nonce_field('numpo_save');?><input type="hidden" name="action" value="numpo_save">
   <div class="np-grid">
    <div class="np-card np-span-6"><div class="np-section-label">پیش‌فرض‌ها</div><label class="np-label">Default project<input class="np-input" name="default_project" value="<?php echo esc_attr(Numpo_Settings::default_project());?>"></label><div style="height:10px"></div><label class="np-label">Search provider template<input class="np-input" name="search_url_template" value="<?php echo esc_attr(Numpo_Settings::search_url_template());?>" placeholder="https://provider.example/search?q={query}"></label></div>
    <div class="np-card np-span-6"><div class="np-section-label">رفتار Crawl</div><label class="np-label">Rate limit (ms)<input class="np-input" type="number" min="0" name="domain_rate_limit_ms" value="<?php echo esc_attr(Numpo_Settings::int('numpo_domain_rate_limit_ms',250,0));?>"></label><div style="height:10px"></div><label class="np-label">Probe cache TTL (seconds)<input class="np-input" type="number" min="1" name="probe_ttl_seconds" value="<?php echo esc_attr(Numpo_Settings::int('numpo_probe_ttl_seconds',3600));?>"></label></div>
    <div class="np-card np-span-12"><div class="np-section-label">پیش‌فرض محدودیت‌ها</div><div class="np-limits"><label class="np-label">Max pages<input class="np-input" type="number" min="1" name="max_pages" value="<?php echo esc_attr(Numpo_Settings::int('numpo_max_pages',100));?>"></label><label class="np-label">Max URLs<input class="np-input" type="number" min="1" name="max_urls" value="<?php echo esc_attr(Numpo_Settings::int('numpo_max_urls',500));?>"></label><label class="np-label">Max depth<input class="np-input" type="number" min="0" name="max_depth" value="<?php echo esc_attr(Numpo_Settings::int('numpo_max_depth',3));?>"></label><label class="np-label">Candidates / page<input class="np-input" type="number" min="1" name="max_candidates_per_page" value="<?php echo esc_attr(Numpo_Settings::int('numpo_max_candidates_per_page',50));?>"></label><label class="np-label">Revisit after<select class="np-input" name="revisit_after"><?php $rv=Numpo_Settings::int('numpo_revisit_after',0,0);?><option value="0" <?php selected($rv,0);?>>فقط یک‌بار</option><option value="86400" <?php selected($rv,86400);?>>هر 24 ساعت</option><option value="604800" <?php selected($rv,604800);?>>هر 7 روز</option><option value="2592000" <?php selected($rv,2592000);?>>هر 30 روز</option></select></label></div></div>
    <div class="np-card np-span-12"><div class="np-section-label">Scope</div><label class="np-check"><input type="checkbox" name="allow_subdomains" value="1" <?php checked(Numpo_Settings::bool('allow_subdomains',false),true);?>>Allow subdomains</label><label class="np-check"><input type="checkbox" name="allow_external_links" value="1" <?php checked(Numpo_Settings::bool('allow_external_links',false),true);?>>Allow external links</label></div>
    <div class="np-card np-span-12"><div class="np-section-label">Default capabilities</div><div class="np-checks"><?php echo self::capChecks();?></div><div class="np-savebar"><span class="np-help">این تنظیمات فقط روی Jobهای جدید اعمال می‌شوند.</span><button class="np-btn np-primary">ذخیره تنظیمات</button></div></div>
   </div>
  </form>
 </section>

 <section id="np-runtime" class="np-monitor-label"><div class="np-heading"><div><h2>۷. وضعیت سیستم</h2><p>وضعیت Runtime فعلی Numpo را قبل از Crawl بررسی کن.</p></div><span class="np-badge">PHP-only</span></div><div class="np-runtime"><div class="np-runtime-item"><b>PHP Runtime</b><br><span class="<?php echo $diag['ok']?'np-ok':'np-warn';?>"><?php echo $diag['ok']?'Ready':'Blocked';?></span></div><div class="np-runtime-item"><b>Architecture</b><br><span class="np-ok">PHP-only</span></div><div class="np-runtime-item"><b>External Engine</b><br><span class="np-ok">Not required</span></div></div><div class="np-card" style="margin-top:10px"><table class="np-table"><thead><tr><th>Component</th><th>Status</th><th>Value</th></tr></thead><tbody><?php foreach($diag['checks'] as $check):?><tr><td><?php echo esc_html($check['label']);?></td><td><?php echo $check['ok']?'OK':($check['required']?'Required':'Optional');?></td><td><?php echo esc_html($check['value']);?></td></tr><?php endforeach;?></tbody></table></div></section>
</div>
<script>
(function(){
 const root=document.getElementById('numpo-dashboard'), form=document.getElementById('numpo-form');
 const sectionNav=document.getElementById('numpo-section-nav');
 function showSection(id,updateHash){
  const target=document.getElementById(id)||document.getElementById('np-start');
  document.querySelectorAll('#numpo-app .np-view').forEach(v=>v.classList.toggle('np-view-active',v===target));
  if(sectionNav)sectionNav.querySelectorAll('a[data-view]').forEach(a=>a.classList.toggle('active',a.dataset.view===target.id));
  if(updateHash&&history.replaceState)history.replaceState(null,'','#'+target.id);
 }
 if(sectionNav){
  sectionNav.querySelectorAll('a[data-view]').forEach(a=>a.addEventListener('click',e=>{e.preventDefault();showSection(a.dataset.view,true);}));
  showSection((location.hash||'').replace('#',''),false);
  if(!document.querySelector('#numpo-app .np-view-active'))showSection('np-start',false);
  window.addEventListener('hashchange',()=>showSection((location.hash||'').replace('#',''),false));
 }
 const ajaxUrl=<?php echo wp_json_encode(admin_url('admin-ajax.php')); ?>;
 const adminNonce=<?php echo wp_json_encode(wp_create_nonce('numpo_admin')); ?>;
 const restBase=<?php echo wp_json_encode(trailingslashit(rest_url('numpo/v1'))); ?>;
 const restNonce=<?php echo wp_json_encode(wp_create_nonce('wp_rest')); ?>;
 let activeJobId=localStorage.getItem('numpo_active_job_id')||null;
 const historyRoot=document.getElementById('numpo-job-history'),resultsRoot=document.getElementById('numpo-results-panel'),exportRoot=document.getElementById('numpo-export-panel');
 function jobStatusLabel(s){return {running:'🟢 در حال اجرا',queued:'🟠 در صف',paused:'⏸️ متوقف موقت',completed:'✅ تکمیل',failed:'🔴 خطا',cancelled:'⛔ لغو'}[s]||s||'—';}
 function fmtDate(v){if(!v)return '—';const d=new Date(String(v).replace(' ','T')+'Z');return isNaN(d)?v:d.toLocaleString('fa-IR');}
 async function loadHistory(){
  if(!historyRoot)return;
  try{const response=await fetch(restBase+'jobs?limit=50',{headers:{'X-WP-Nonce':restNonce}});const data=await parseResponse(response);const items=data.items||[];
   if(!items.length){historyRoot.innerHTML='<div class="np-muted">هنوز هیچ Jobای ساخته نشده است.</div>';return;}
   historyRoot.innerHTML='<div class="np-history-list">'+items.map(j=>{const m=j.metrics||{},stale=j.status==='running'&&j.updated_at&&(Date.now()-new Date(String(j.updated_at).replace(' ','T')+'Z').getTime()>15*60*1000),label=stale?'⚠️ قطع شده — آماده ادامه':jobStatusLabel(j.status);return '<button type="button" class="np-history-row '+(String(j.job_id)===String(activeJobId)?'active':'')+'" data-job-id="'+esc(j.job_id)+'"><span class="np-history-main"><b>Job '+esc(j.job_id)+'</b><small>'+esc(j.project_id)+' · '+esc(j.mode)+' · '+esc(fmtDate(j.created_at))+'</small><span class="np-history-live"><span><b>Found</b> '+esc(m.candidates||0)+'</span><span><b>Queued</b> '+esc(m.queued||0)+'</span><span><b>Deep crawl</b> '+esc(m.completed||0)+'</span><span><b>Retry</b> '+esc(m.retryable||0)+'</span><span><b>Pages</b> '+esc(m.pages||j.processed_pages||0)+'</span><span><b>Last activity</b> '+esc(fmtDate(m.last_heartbeat||j.updated_at))+'</span></span></span><span class="np-history-status">'+label+'</span></button>';}).join('')+'</div>';
   historyRoot.querySelectorAll('.np-history-row').forEach(b=>b.onclick=()=>selectJob(b.dataset.jobId));
  }catch(e){historyRoot.innerHTML='<div class="notice notice-error"><p>'+esc(formatError(e))+'</p></div>';}
 }
 function syncStartControls(status){
  const p=document.getElementById('numpo-pause-start'),r=document.getElementById('numpo-resume-start'),x=document.getElementById('numpo-cancel-start');
  if(!p||!r||!x)return;
  p.disabled=!(status==='running'||status==='queued'); r.disabled=!(status==='paused'); x.disabled=!(status==='running'||status==='queued'||status==='paused');
  const s=document.getElementById('numpo-status'); if(s)s.textContent=activeJobId?('Job #'+activeJobId+' · '+jobStatusLabel(status)):'';
}
function selectJob(id){if(!id)return;activeJobId=String(id);localStorage.setItem('numpo_active_job_id',activeJobId);load(activeJobId);loadHistory();} 
 async function loadProjectAggregate(){if(!activeJobId)return;try{const j=await fetch(restBase+'jobs/'+encodeURIComponent(activeJobId),{headers:{'X-WP-Nonce':restNonce}}).then(parseResponse);const p=await fetch(restBase+'project/'+encodeURIComponent(j.project_id)+'/metrics',{headers:{'X-WP-Nonce':restNonce}}).then(parseResponse);const box=document.getElementById('numpo-project-summary');if(box)box.innerHTML='<div class="np-card"><h3>مجموع Project · '+esc(j.project_id)+'</h3><div class="np-stats"><div><b>'+esc(p.jobs||0)+'</b><small>Jobs</small></div><div><b>'+esc(p.urls||0)+'</b><small>Unique URLs</small></div><div><b>'+esc(p.crawled_urls||0)+'</b><small>Crawled URLs</small></div><div><b>'+esc(p.domains||0)+'</b><small>Domains</small></div></div></div>';}catch(e){}} 
 document.getElementById('np-all-project')?.addEventListener('click',loadProjectAggregate);


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
  activeJobId=id;localStorage.setItem('numpo_active_job_id',id);
  try{
   const j=await api('/jobs/'+encodeURIComponent(id)),m=j.metrics||{},status=j.status;
   const max=Math.max(1,Number(j.max_pages||1)),pct=Math.min(100,Math.round(Number(j.processed_pages||0)/max*100));
   const stale=status==='running' && j.updated_at && (Date.now()-new Date(j.updated_at.replace(' ','T')+'Z').getTime()>15*60*1000); const textStatus=stale?'⚠️ قطع شده — آماده ادامه':({running:'🟢 در حال اجرا',queued:'🟠 در صف',paused:'⏸️ متوقف موقت',completed:'✅ تکمیل',failed:'🔴 خطا',cancelled:'⛔ لغو'}[status]||status);
   const exportUrl=x=>'<?php echo esc_url(admin_url('admin-ajax.php')); ?>?action=numpo_admin_export&nonce=<?php echo esc_js(wp_create_nonce('numpo_admin')); ?>&job_id='+encodeURIComponent(j.job_id)+'&resource='+x;
   const cards=[['Domains',m.domains],['Found URLs',m.candidates],['Deep crawled',m.completed],['Queued',m.queued],['Processing',m.processing],['Retry',m.retryable],['Pages',m.pages||j.processed_pages],['Technologies',m.technologies],['Contacts',m.contacts],['Business',m.business],['Social',m.social],['Classifications',m.classifications],['Probes',m.probes] ,['Errors',m.errors]];
   const workerLine=m.worker_scheduled?'<div class="np-log-row"><span>Worker</span><b class="np-good">●</b><div>برای اجرای پس‌زمینه زمان‌بندی شده</div></div>':'<div class="np-log-row"><span>Worker</span><b>·</b><div>'+esc(m.worker_state||'—')+'</div></div>';const logs=(workerLine+(m.last_heartbeat?'<div class="np-log-row"><span>Heartbeat</span><b class="np-good">♥</b><div>'+esc(fmtDate(m.last_heartbeat))+'</div></div>':'')+(m.current_url?'<div class="np-log-row"><span>اکنون</span><b class="np-good">●</b><div class="np-url">'+esc(m.current_url)+'</div></div>':'')+(m.last_candidate?'<div class="np-log-row"><span>آخرین Candidate</span><b class="np-good">+</b><div class="np-url">'+esc(m.last_candidate)+'</div></div>':'')+(m.last_error?'<div class="np-log-row"><span>خطای اخیر</span><b class="np-bad">!</b><div>'+esc(m.last_error)+'</div></div>':'')||'<div class="np-log-row"><span>—</span><b>·</b><div>هنوز Activity ثبت نشده است.</div></div>';
   // Keep the monitor DOM stable during 5-second polling. Only live values are patched in place.
   if(root.dataset.rendered==='1' && root.dataset.renderStatus===String(status)){
    const pill=root.querySelector('.np-pill'); if(pill) pill.textContent=textStatus;
    const progress=root.querySelector('.np-progress i'); if(progress) progress.style.width=pct+'%';
    const progressText=root.querySelector('.np-progress')?.nextElementSibling; if(progressText) progressText.textContent='صفحات: '+String(j.processed_pages||0)+' / '+String(j.max_pages||0)+' · URLها: '+String(j.processed_urls||0)+' / '+String(j.max_urls||0)+' · '+pct+'%';
    const values=root.querySelectorAll('.np-stat b'); const live=[m.domains,m.candidates,m.completed,m.queued,m.processing,m.retryable,m.pages||j.processed_pages,m.technologies,m.contacts,m.business,m.social,m.classifications,m.probes,m.errors]; values.forEach((el,i)=>{if(i<live.length)el.textContent=String(live[i]||0)});
    const kv=root.querySelectorAll('.np-kv span'); const kvValues=[textStatus,m.current_url||'—',m.queued||0,m.retryable||0,m.completed||0,fmtDate(m.last_heartbeat),m.next_worker_at?fmtDate(new Date(Number(m.next_worker_at)*1000).toISOString()):'در صف زمان‌بندی']; kv.forEach((el,i)=>{if(i<kvValues.length)el.textContent=String(kvValues[i]??'—')});
    const log=root.querySelector('.np-log'); if(log) log.innerHTML=(m.worker_scheduled?'<div class="np-log-row"><span>Worker</span><b class="np-good">●</b><div>برای اجرای پس‌زمینه زمان‌بندی شده</div></div>':'<div class="np-log-row"><span>Worker</span><b>·</b><div>'+esc(m.worker_state||'—')+'</div></div>')+(m.last_heartbeat?'<div class="np-log-row"><span>Heartbeat</span><b class="np-good">♥</b><div>'+esc(fmtDate(m.last_heartbeat))+'</div></div>':'')+(m.current_url?'<div class="np-log-row"><span>اکنون</span><b class="np-good">●</b><div class="np-url">'+esc(m.current_url)+'</div></div>':'')+(m.last_candidate?'<div class="np-log-row"><span>آخرین Candidate</span><b class="np-good">+</b><div class="np-url">'+esc(m.last_candidate)+'</div></div>':'')+(m.last_error?'<div class="np-log-row"><span>خطای اخیر</span><b class="np-bad">!</b><div>'+esc(m.last_error)+'</div></div>':'')||'<div class="np-log-row"><span>—</span><b>·</b><div>هنوز Activity ثبت نشده است.</div></div>';
    syncStartControls(status);
    clearTimeout(window.numpoMonitorTimer); window.numpoMonitorTimer=setTimeout(()=>load(id),5000);
    return;
   }
   root.innerHTML='<div class="np-grid"><div class="np-card np-span-12"><div class="np-section-title"><h2>Control</h2><span class="np-pill">'+textStatus+'</span></div><div class="np-actions"><button class="np-btn np-primary" id="np-start-label" disabled>🟢 شروع Crawl</button>'+(status==='running'||status==='queued'?'<button class="np-btn np-ghost" id="np-pause">⏸️ توقف موقت</button>':'')+(status==='paused'||stale?'<button class="np-btn np-primary" id="np-resume">▶️ ادامه Crawl</button>':'')+(status==='running'||status==='queued'||status==='paused'?'<button class="np-btn np-danger" id="np-cancel">⛔ لغو Job</button>':'')+'<button class="np-btn np-ghost" id="np-monitor-refresh">🔄 بروزرسانی</button></div></div><div class="np-card np-span-12"><div class="np-monitor-label">Live Monitor</div><h2>مانیتورینگ زنده · Job '+esc(j.job_id)+'</h2><div class="np-progress"><i style="width:'+pct+'%"></i></div><div class="np-muted">صفحات: '+esc(j.processed_pages||0)+' / '+esc(j.max_pages||0)+' · URLها: '+esc(j.processed_urls||0)+' / '+esc(j.max_urls||0)+' · '+pct+'%</div><div class="np-grid" style="margin-top:14px">'+cards.map(x=>'<div class="np-stat np-span-3"><span>'+esc(x[0])+'</span><b>'+esc(x[1]||0)+'</b></div>').join('')+'</div></div><div class="np-card np-span-6"><div class="np-monitor-label">Worker</div><h3>وضعیت Worker</h3><div class="np-kv"><strong>Status</strong><span>'+textStatus+'</span><strong>در حال بررسی</strong><span class="np-url">'+esc(m.current_url||'—')+'</span><strong>صف باقی‌مانده</strong><span>'+esc(m.queued||0)+'</span><strong>Retry</strong><span>'+esc(m.retryable||0)+'</span><strong>Deep crawled</strong><span>'+esc(m.completed||0)+'</span><strong>Last heartbeat</strong><span>'+esc(fmtDate(m.last_heartbeat))+'</span><strong>اجرای بعدی Worker</strong><span>'+esc(m.next_worker_at?fmtDate(new Date(Number(m.next_worker_at)*1000).toISOString()):"در صف زمان‌بندی")+'</span></div></div><div class="np-card np-span-6"><div class="np-monitor-label">Activity</div><h3>فعالیت زنده</h3><div class="np-log">'+logs+'</div></div><div class="np-card np-span-12"><div class="np-monitor-label">Completed</div><h2>✅ Crawl completed</h2><div class="np-grid"><div class="np-stat np-span-3"><span>سایت</span><b>'+esc(m.domains||0)+'</b></div><div class="np-stat np-span-3"><span>صفحه</span><b>'+esc(j.processed_pages||0)+'</b></div><div class="np-stat np-span-3"><span>URL / داده</span><b>'+esc(m.candidates||j.processed_urls||0)+'</b></div></div><div class="np-actions"><a class="np-btn np-ghost" href="'+exportUrl('urls')+'">دانلود URLها</a><a class="np-btn np-ghost" href="'+exportUrl('domains')+'">دانلود سایت‌ها</a><a class="np-btn np-primary" href="'+exportUrl('zip')+'">دانلود نتایج کامل</a></div></div>':'')+'</div>';
   const resultsCard='<div class="np-card np-span-12"><div class="np-monitor-label">Results</div><h2>نتایج Crawl · Job '+esc(j.job_id)+'</h2><div class="np-tabs">'+['candidates','domains','pages','technologies','contacts','business','social','classifications','probes','errors'].map((x,i)=>'<button class="np-tab '+(i===0?'active':'')+'" data-tab="'+x+'">'+x+'</button>').join('')+'</div><div id="np-tab-body"></div></div>';
   const exportCard='<div class="np-card np-span-12"><div class="np-monitor-label">Export Center</div><h2>دانلود نتایج Job '+esc(j.job_id)+'</h2><div class="np-actions"><a class="np-btn np-ghost" href="'+exportUrl('urls')+'">🔗 URLهای پیدا شده · CSV</a><a class="np-btn np-ghost" href="'+exportUrl('txt')+'">🔗 URLها · TXT</a><a class="np-btn np-ghost" href="'+exportUrl('domains')+'">🌐 سایت‌های بررسی‌شده · CSV</a><a class="np-btn np-ghost" href="'+exportUrl('pages')+'">📄 صفحات پردازش‌شده · CSV</a><a class="np-btn np-ghost" href="'+exportUrl('technologies')+'">🧩 تکنولوژی‌ها · CSV</a><a class="np-btn np-ghost" href="'+exportUrl('contacts')+'">📞 شماره‌ها و ایمیل‌ها · CSV</a><a class="np-btn np-ghost" href="'+exportUrl('business')+'">🏢 اطلاعات کسب‌وکار · CSV</a><a class="np-btn np-ghost" href="'+exportUrl('social')+'">📱 Social Profiles · CSV</a><a class="np-btn np-ghost" href="'+exportUrl('errors')+'">❌ خطاهای Crawl · CSV</a><a class="np-btn np-primary" href="'+exportUrl('zip')+'">📦 کل نتایج Job · ZIP</a></div></div>';
   if(resultsRoot)resultsRoot.innerHTML=resultsCard;
   if(exportRoot)exportRoot.innerHTML=exportCard;
   if(resultsRoot){const tabs=resultsRoot.querySelectorAll('.np-tab');tabs.forEach(b=>b.onclick=()=>{tabs.forEach(x=>x.classList.remove('active'));b.classList.add('active');loadTab(b.dataset.tab,j)});}
   loadTab('candidates',j);
   const cancel=document.getElementById('np-cancel');if(cancel)cancel.onclick=async()=>{if(!confirm('Job لغو شود؟'))return;try{await api('/jobs/'+encodeURIComponent(id)+'/cancel',{method:'POST'});await load(id)}catch(e){showError(e)}};
   const pause=document.getElementById('np-pause');if(pause)pause.onclick=async()=>{try{await api('/jobs/'+encodeURIComponent(id)+'/pause',{method:'POST'});await load(id)}catch(e){showError(e)}};
   const resume=document.getElementById('np-resume');if(resume)resume.onclick=async()=>{try{await api('/jobs/'+encodeURIComponent(id)+'/resume',{method:'POST'});await load(id)}catch(e){showError(e)}};
   root.dataset.rendered='1'; root.dataset.renderStatus=String(status);
   syncStartControls(status);
   if(status==='running'||status==='queued'||status==='paused'){clearTimeout(window.numpoMonitorTimer);window.numpoMonitorTimer=setTimeout(()=>load(id),5000)}
  }catch(e){showError(e)}
 }
 async function loadTab(tab,j){
  const body=document.getElementById('np-tab-body');if(!body)return;
  try{const d=await api('/jobs/'+encodeURIComponent(j.job_id)+'/'+tab+'?per_page=50');const items=d.items||[];if(!items.length){body.innerHTML='<p class="np-muted">داده‌ای ثبت نشده.</p>';return}const keys=Object.keys(items[0]);body.innerHTML='<div class="np-table-wrap"><table class="np-table"><thead><tr>'+keys.map(k=>'<th>'+esc(k)+'</th>').join('')+'</tr></thead><tbody>'+items.map(x=>'<tr>'+keys.map(k=>'<td>'+esc(typeof x[k]==='object'?JSON.stringify(x[k]):x[k])+'</td>').join('')+'</tr>').join('')+'</tbody></table></div>'}catch(e){body.innerHTML='<p class="np-bad">'+esc(e.message)+'</p>'}
 }
 // Never allow a normal browser form submission: Start must always stay on the Numpo admin page.
 form.addEventListener('submit',async e=>{
  e.preventDefault();
  const button=form.querySelector('button[type="submit"], button:not([type])'),oldText=button?button.textContent:'';
  if(button){button.disabled=true;button.textContent='Starting…';}
  try{
   const f=new FormData(form),seeds=String(f.get('seeds')||'').split(/\r?\n/).map(x=>x.trim()).filter(Boolean);
   const sources={search_provider:f.has('source_search'),sitemap:f.has('source_sitemap'),robots:f.has('source_robots'),link_discovery:f.has('source_links'),subdomain_from_crawl:f.has('source_subdomains')};
   const target={},country=f.get('target_country');if(country)target.countries=[country];
   const limits={max_pages:Number(f.get('max_pages')),max_urls:Number(f.get('max_urls')),max_depth:Number(f.get('max_depth')),max_candidates_per_page:Number(f.get('max_candidates_per_page')),revisit_after:Number(f.get('revisit_after')||0)};
   const body={project_id:String(f.get('project_id')||''),mode:String(f.get('mode')||'manual'),seeds,sources,target,limits,capabilities:caps(f)};
   const result=await api('/jobs',{method:'POST',numpoStart:true,body});
   if(!result||!result.job_id)throw new Error('Engine job ID در پاسخ ایجاد Job وجود ندارد.');
   activeJobId=String(result.job_id);localStorage.setItem('numpo_active_job_id',activeJobId);
   const s=document.getElementById('numpo-status');if(s)s.textContent='Job #'+activeJobId+' · '+jobStatusLabel(result.status||'queued');
   syncStartControls(result.status||'queued'); await load(result.job_id);
  }catch(e){showError(e);}
  finally{if(button){button.disabled=false;button.textContent=oldText;}}
 });
 document.getElementById('numpo-refresh').onclick=()=>{if(activeJobId)load(activeJobId);loadHistory();};
 document.getElementById('numpo-pause-start').onclick=async()=>{if(!activeJobId)return;try{await api('/jobs/'+encodeURIComponent(activeJobId)+'/pause',{method:'POST'});await load(activeJobId);}catch(e){showError(e)}};
 document.getElementById('numpo-resume-start').onclick=async()=>{if(!activeJobId)return;try{await api('/jobs/'+encodeURIComponent(activeJobId)+'/resume',{method:'POST'});await load(activeJobId);}catch(e){showError(e)}};
 document.getElementById('numpo-cancel-start').onclick=async()=>{if(!activeJobId||!confirm('Job لغو شود؟'))return;try{await api('/jobs/'+encodeURIComponent(activeJobId)+'/cancel',{method:'POST'});await load(activeJobId);}catch(e){showError(e)}};
 syncStartControls('');loadHistory();if(activeJobId)load(activeJobId);document.addEventListener('click',e=>{if(e.target&&e.target.id==='np-monitor-refresh'&&activeJobId)load(activeJobId);});
})();
 </script>
<?php }
 public static function save(){
  if(!current_user_can('manage_options')||!check_admin_referer('numpo_save'))wp_die('Forbidden');
  update_option('numpo_default_project',sanitize_text_field(wp_unslash($_POST['default_project']??'default')));
  update_option('numpo_search_url_template',esc_url_raw(wp_unslash($_POST['search_url_template']??'')));
  foreach(['max_pages'=>100,'max_urls'=>500,'max_depth'=>3,'max_candidates_per_page'=>50,'domain_rate_limit_ms'=>250,'probe_ttl_seconds'=>3600] as $k=>$d)update_option('numpo_'.$k,max(1,absint($_POST[$k]??$d)));
  update_option('numpo_revisit_after',max(0,absint($_POST['revisit_after']??0)));
  foreach(['active_probe','deep_crawl','link_discovery','sitemap','robots','subdomain_from_crawl','wordpress','woocommerce','phone','email','business','social','page_classification'] as $k=>$_)update_option('numpo_cap_'.$k,isset($_POST['cap_'.$k])?'1':'0');
  update_option('numpo_allow_subdomains',isset($_POST['allow_subdomains'])?'1':'0');
  update_option('numpo_allow_external_links',isset($_POST['allow_external_links'])?'1':'0');
  wp_safe_redirect(admin_url('admin.php?page=numpo#np-settings'));exit;
 }
}