# نومپو — معماری

## ۱. تصمیم معماری

موتور اصلی Crawl با Go پیاده‌سازی می‌شود.

WordPress فقط نقش پنل مدیریت و رابط کاربری را دارد و نباید به وابستگی اجباری موتور Crawl تبدیل شود.

### چرا Go؟

بخش اصلی کار شامل عملیات شبکه‌ای است:

- DNS
- اتصال TCP/TLS
- درخواست HTTP
- انتظار برای پاسخ
- پردازش HTML
- استخراج سبک اطلاعات

Go برای همزمانی بالا و مصرف نسبتاً پایین منابع مناسب است.

### چرا Crawl داخل PHP/WordPress نباشد؟

پیاده‌سازی کامل داخل WordPress، Crawl را به محدودیت‌های PHP، Bootstrap وردپرس، WP-Cron، Queueهای وردپرس، افزونه‌ها و محدودیت‌های هاست وابسته می‌کند.

WordPress همچنان پنل مدیریتی خوبی است، اما اجرای Crawl باید بر عهدهٔ Go باشد.

## ۲. مرزبندی اجزا

### پلاگین WordPress

مسئول:

- احراز هویت و سطح دسترسی مدیر
- پروژه‌ها
- دامنه‌های اولیه
- تنظیمات Crawl
- ایجاد Job
- نمایش وضعیت
- نمایش نتایج
- فیلترها
- Export
- تنظیم اتصال به موتور

مسئول نیست:

- اجرای HTTP Crawl
- مدیریت DNS و همزمانی
- حلقه‌های Retry
- اجرای Workerها
- اجرای مرورگر برای Crawl

### موتور Go

مسئول:

- دریافت Job
- زمان‌بندی
- صف
- دریافت صفحات
- Redirect
- Timeout
- نرمال‌سازی URL
- کشف لینک‌ها
- محدودیت Crawl
- تشخیص فناوری
- استخراج اطلاعات تماس
- نرمال‌سازی
- حذف داده‌های تکراری
- ذخیره‌سازی
- Retry
- Rate Limit
- Metrics و Logging

### پایگاه داده

مدل دادهٔ Crawl مستقل از جداول WordPress خواهد بود.

گزینهٔ اصلی Production: PostgreSQL.

## ۳. حالت‌های استقرار

### حالت اول — همان VPS

~~~text
WordPress -> API محلی -> Go -> Database
~~~

این اولین حالت هدف است.

### حالت دوم — سرویس‌های جدا

~~~text
مرورگر
   |
   v
WordPress
   |
 HTTPS + API Key
   |
   v
API موتور Crawl
   |
   v
صف
   |
   +-- Worker
   +-- Worker
   +-- Worker
   |
   v
PostgreSQL
~~~

پلاگین نباید بداند موتور روی همان سرور است یا سرور دیگری.

### حالت سوم — مقیاس‌پذیری

API درخواست را دریافت می‌کند، صف کار را تقسیم می‌کند و چند Worker دامنه‌ها را پردازش می‌کنند.

در شروع نباید Kubernetes یا زیرساخت توزیع‌شدهٔ پیچیده اضافه شود؛ ابتدا باید ظرفیت واقعی اندازه‌گیری شود.

## ۴. چرخهٔ Crawl

~~~text
ساخت پروژه
   ↓
افزودن دامنه‌های اولیه
   ↓
ساخت Job
   ↓
ورود به صف
   ↓
Resolve دامنه
   ↓
دریافت صفحه اصلی
   ↓
تشخیص فناوری
   ↓
کشف صفحات مهم
   ↓
دریافت صفحات
   ↓
استخراج اطلاعات تماس عمومی
   ↓
نرمال‌سازی + حذف تکراری
   ↓
ذخیره
   ↓
پایان Job
~~~

صفحات تماس، درباره ما و لینک‌های موجود در منو و Footer باید اولویت بالاتری داشته باشند.

## ۵. روش دریافت صفحات

### سطح ۱
HTTP غیرهمزمان برای اکثر صفحات.

### سطح ۲
پردازش HTML بدون اجرای مرورگر.

### سطح ۳
Headless Browser فقط زمانی که HTTP معمولی اطلاعات کافی ندهد.

Browser نباید روش پیش‌فرض برای تمام صفحات باشد.

## ۶. همزمانی

همزمانی باید هم در سطح کل سیستم و هم برای هر دامنه کنترل شود.

موارد ضروری:

- حداکثر درخواست همزمان کلی
- حداکثر درخواست همزمان برای هر دامنه
- Timeout
- محدودیت Redirect
- تعداد Retry محدود
- Backoff

همزمانی زیاد نباید باعث ارسال تعداد نامحدود درخواست به یک دامنه شود.

## ۷. تشخیص فناوری

تشخیص باید مبتنی بر شواهد باشد.

نمونهٔ شواهد WordPress:

- مسیر wp-content
- مسیر wp-includes
- مسیر wp-json
- متادیتای Generator
- Assetها و Endpointهای اختصاصی WordPress

WooCommerce نیز Detector مستقل خود را خواهد داشت.

هر تشخیص شامل موارد زیر است:

- نام فناوری
- میزان اطمینان
- شواهد
- URL منبع
- زمان تشخیص

## ۸. استخراج اطلاعات تماس

نسخهٔ اول روی اطلاعات تماس تجاریِ عمومی تمرکز دارد.

روند:

~~~text
HTML
 ↓
متن و Attributeهای مرتبط
 ↓
شماره‌های احتمالی
 ↓
اعتبارسنجی
 ↓
نرمال‌سازی بر اساس کشور
 ↓
حذف تکراری
 ↓
ذخیره URL منبع
~~~

لینک‌های tel نیز باید پشتیبانی شوند.

## ۹. محدودهٔ دامنه

هر Job باید محدودهٔ مشخص داشته باشد.

حالت پیش‌فرض:

- ماندن داخل دامنهٔ هدف
- دنبال نکردن کورکورانهٔ لینک‌های خارجی
- امکان فعال کردن Subdomain به‌صورت صریح
- امکان ثبت لینک‌های خارجی بدون Crawl کردن آن‌ها

## ۱۰. مدیریت خطا

خطاها باید به‌عنوان داده ثبت شوند.

نمونه‌ها:

- خطای DNS
- خطای TLS
- Timeout
- HTTP 403/429/5xx
- محتوای نامعتبر
- پاسخ بیش از حد بزرگ
- حلقهٔ Redirect
- خطای Parser

خطاهای موقت می‌توانند با Backoff محدود دوباره امتحان شوند؛ خطاهای دائمی نباید بی‌نهایت Retry شوند.

## ۱۱. قرارداد جداسازی

قانون اصلی:

~~~text
WordPress -> API نسخه‌بندی‌شده -> Go
~~~

پلاگین نباید مستقیماً به Packageها یا جزئیات داخلی Go وابسته باشد.

این اصل باعث می‌شود موتور Go بعداً به سرور دیگری منتقل شود بدون اینکه منطق اصلی محصول بازنویسی شود.

## ۱۲. امنیت

حالت Remote باید HTTPS و احراز هویت سرویس داشته باشد.

موارد ضروری:

- احراز هویت
- اعتبارسنجی ورودی
- کنترل دسترسی پروژه و Job
- محدودیت تعداد URL و دامنه
- محدودیت حجم پاسخ
- محافظت SSRF
- محدودیت شبکهٔ خروجی در صورت امکان

موتور نباید به Proxy نامحدود یا ابزار دسترسی به شبکهٔ داخلی تبدیل شود.

## ۱۳. مانیتورینگ

هر Job باید حداقل این اطلاعات را داشته باشد:

- تعداد در صف
- تعداد در حال اجرا
- تعداد تکمیل‌شده
- تعداد شکست‌خورده
- تعداد Retry
- تعداد صفحات دریافت‌شده
- تعداد دامنه‌های تکمیل‌شده
- تعداد اطلاعات تماس
- تعداد فناوری‌های شناسایی‌شده
- زمان پاسخ
- همزمانی فعلی
- آخرین خطاها

## ۱۴. مسیر توسعه

شروع:

~~~text
یک موتور Go
یک صف
یک Database
~~~

بعد:

~~~text
API
 ↓
Queue
 ↓
N Worker
 ↓
PostgreSQL
~~~

تعداد Workerها بر اساس Queue و ظرفیت واقعی شبکه افزایش داده می‌شود.


## ۱۴. جداسازی Discovery از Crawl

Discovery یک لایهٔ مستقل قبل از Crawl است.

```text
Discovery
  ├── Active Domain Discovery
  └── Deep Search
          ↓
     Candidate Queue
          ↓
       Crawl Engine
          ↓
   Detection / Extraction
```

**Active Domain Probe** برای بررسی سریع دامنه‌ها طراحی می‌شود و نباید به‌عنوان فیلتر حذفی عمل کند. Probe وضعیت Active و Signalهای فناوری را ثبت می‌کند. اگر دامنه Active باشد ولی WordPress نباشد، دامنه همچنان در Store باقی می‌ماند و فقط Classification مناسب برای آن ثبت می‌شود. **Deep Search** برای تحلیل عمیق کاندیدهایی که Routing Rule آن‌ها را انتخاب کرده است استفاده می‌شود.

منابع Discovery می‌توانند Manual Seed، Seed List/CSV، Search Provider، Sitemap/robots.txt و لینک‌های کشف‌شده در Crawl باشند. Search Providerها باید پشت Interface مستقل قرار بگیرند تا وابستگی به یک سرویس خاص ایجاد نشود.

هر Candidate باید Provenance داشته باشد و پیش از ورود به Queue از Normalize، Deduplicate و Policy/SSRF checks عبور کند. Classificationها باید مستقل و چندگانه باشند و Routing Rule فقط مسیر پردازش بعدی را تعیین کند، نه اینکه Candidate را از داده‌های اصلی حذف کند. جزئیات در [DISCOVERY](DISCOVERY.md) آمده است.


## ۱۵. حلقهٔ کنترل‌شدهٔ Discovery و Deep Crawl

Deep Crawler یکی از منابع Discovery است. هر URL یا Host جدیدی که در Deep Crawl کشف شود نباید مستقیماً یک Crawl مستقل ایجاد کند.

جریان استاندارد:

```text
Deep Crawler
     ↓
Discovered URL
     ↓
Normalize / Deduplicate / Policy
     ↓
Probe State
   ┌─┴──────────────┐
   │                │
Already Probed   Not Probed
   │                │
   ▼                ▼
Routing         Active Queue
   │                │
   │                ▼
   │            Active Probe
   │                │
   └───────┬────────┘
           ▼
   Classification
           ↓
      Routing Rule
           ↓
       Deep Queue
           ↓
      Deep Crawler
```

### Queueهای منطقی

در معماری، حداقل سه نوع کار باید از هم قابل تشخیص باشند:

- `discovery`
- `active_probe`
- `deep_crawl`

در MVP می‌توان این‌ها را روی یک Queue backend اجرا کرد، اما Worker و Scheduler باید نوع کار را بشناسند تا بعداً امکان جداسازی Queueها بدون بازطراحی Pipeline وجود داشته باشد.

### استفادهٔ مجدد از Probe

اگر Deep Crawler به Hostی برسد که Probe معتبر دارد، Probe نباید دوباره اجرا شود. نتیجهٔ آخرین Probe و Signalهای معتبر برای Routing استفاده می‌شوند.

اگر Probe وجود نداشته باشد یا TTL آن منقضی شده باشد، Host وارد Active Queue می‌شود.

### Domain، Host و URL

معماری باید این سه سطح را از هم جدا نگه دارد:

```text
Domain
  ↓
Host
  ↓
URL
```

Active Probe عمدتاً در سطح Host انجام می‌شود؛ Deep Crawl در سطح URL انجام می‌شود و Domain موجودیت پایدار برای گزارش و Query است.

این تفکیک به‌خصوص برای Subdomainها ضروری است.

### جلوگیری از حلقهٔ نامحدود

چون Deep Crawl می‌تواند Discovery جدید تولید کند، Crawl Budget و Deduplication باید روی حلقهٔ زیر اعمال شوند:

```text
Deep Crawl
   ↓
Discovery
   ↓
Active Probe
   ↓
Routing
   ↓
Deep Crawl
```

این حلقه باید **کنترل‌شده و محدود** باشد و هر Job دارای سقف URL، عمق، Candidate و زمان اجرای مشخص باشد.


## ۱۶. قابلیت‌های مستقل و قابل فعال/غیرفعال شدن

تمام مسیرهای Discovery، Probe و Deep Crawl باید Feature/Capability مستقل داشته باشند. هیچ Worker یا Provider نباید صرفاً با وجود یک قابلیت دیگر، قابلیت وابسته‌ای را به‌صورت ضمنی فعال کند.

نمونهٔ تنظیمات:

```text
discovery.enabled
discovery.manual_seeds.enabled
discovery.csv_import.enabled
discovery.search_provider.enabled
discovery.sitemap.enabled
discovery.robots.enabled
discovery.link_discovery.enabled
discovery.subdomain_from_crawl.enabled

active_probe.enabled

deep_search.enabled
deep_search.external_links.enabled
deep_search.subdomains.enabled
```

### Subdomain Discovery در Deep Crawler

در MVP، Deep Crawler مسئول کشف ساب‌دامین از URLها و لینک‌های مشاهده‌شده است؛ این قابلیت مستقل است:

```text
Deep Crawler
    ↓
[Subdomain Discovery ON?]
    ├── NO  → continue normal crawl
    └── YES → Host Candidate
                  ↓
             Normalize / Dedup / Policy
                  ↓
             Active Probe [ON/OFF]
                  ↓
             Classification / Routing
                  ↓
             Deep Queue
```

خاموش بودن Subdomain Discovery فقط مانع تولید Candidate جدید از این مسیر می‌شود. ساب‌دامین‌هایی که از منابع دیگر Discovery به دست آمده‌اند همچنان می‌توانند پردازش شوند.

خاموش بودن Active Probe نیز نباید Candidate را حذف کند؛ فقط مرحلهٔ Probe را غیرفعال می‌کند و رفتار بعدی باید طبق Policy و Routing تنظیم‌شده تعیین شود.

### Snapshot تنظیمات

در شروع هر Job، تنظیمات مؤثر باید Snapshot شوند. بنابراین یک Job در حال اجرا با تغییر تنظیمات پروژه به‌صورت ناگهانی رفتار خود را عوض نمی‌کند، مگر اینکه در آینده قابلیت Dynamic Policy صراحتاً اضافه شود.

### اصل استقلال

```text
Sitemap OFF ≠ Robots OFF
Robots OFF ≠ Link Discovery OFF
Subdomain Discovery OFF ≠ Deep Crawl OFF
Active Probe OFF ≠ Discovery OFF
Search Provider OFF ≠ Manual Seeds OFF
```

این استقلال باید در طراحی API، Scheduler و Workerها نیز حفظ شود.
