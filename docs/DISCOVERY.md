# نومپو — معماری Discovery

## هدف

Discovery از Crawl جداست.

Discovery وظیفه دارد **دامنه‌ها و URLهای کاندید** را پیدا کند؛ Crawl وظیفه دارد آن کاندید را بررسی، دریافت و تحلیل کند.

معماری اصلی:

```text
Discovery
   ↓
Candidate URL/Domain
   ↓
Normalize + Deduplicate
   ↓
Policy / Scope
   ↓
Candidate Queue
   ↓
Active Check یا Deep Search
   ↓
Crawl Engine
   ↓
Technology / Contact / Business Intelligence
```

## اصل مهم: Probe داده را حذف نمی‌کند

Active Domain Probe یک **فیلتر حذفی** نیست؛ یک مرحلهٔ اندازه‌گیری و Classification است. اگر دامنه Active باشد اما WordPress نباشد، دامنه همچنان در مجموعهٔ اصلی باقی می‌ماند و فقط Classificationهای مربوط به آن ثبت نمی‌شوند یا Classification دیگری دریافت می‌کند.

مثلاً یک دامنه می‌تواند هم‌زمان این وضعیت را داشته باشد:

```text
example.com
  ├── active
  ├── wordpress
  ├── woocommerce
  ├── cloudflare
  └── has_public_phone
```

قواعدی مانند «Active + WordPress» فقط برای تعیین **مسیر مرحلهٔ بعدی** هستند؛ حذف اطلاعات از Domain Store انجام نمی‌شود.

## دو موتور اصلی

### ۱. Active Domain Probe / Discovery

هدف: پیدا کردن دامنه‌هایی که واقعاً قابل دسترسی هستند.

این مرحله باید بسیار سبک و سریع باشد و وارد تحلیل عمیق صفحات نشود.

نمونه وضعیت‌ها:

- dns_resolved
- http_reachable
- https_reachable
- redirected
- parked
- under_construction
- server_error
- timeout
- inactive

Active Probe می‌تواند تعداد بسیار زیادی دامنه را با هزینهٔ کم بررسی کند و برای هر دامنه وضعیت و سیگنال‌های پایه را ثبت کند.

### ۲. Deep Search

هدف: پیدا کردن و تحلیل سایت‌هایی که معیار مشخصی دارند.

مثال:

- WordPress + WooCommerce
- WooCommerce + شماره عمومی
- سایت ایرانی + WooCommerce
- WooCommerce + Instagram
- سایت دارای صفحه محصول و اطلاعات تماس

Deep Search ابتدا Candidate تولید می‌کند و سپس Crawl عمیق را فقط روی کاندیدهای مناسب انجام می‌دهد.

## منابع Discovery

Providerها باید قابل تعویض باشند:

1. Manual Seeds
2. Seed List / CSV Import
3. Search Provider
4. Sitemap و robots.txt
5. لینک‌های کشف‌شده در Crawl
6. Providerهای آینده، در صورت نیاز و با رعایت شرایط استفاده و قوانین منبع

Search Engine نباید تنها وابستگی سیستم باشد. Provider abstraction اجازه می‌دهد APIهای مختلف یا منابع دیگر بدون تغییر Crawl Engine اضافه یا جایگزین شوند.

## Candidate

هر Candidate باید Provenance داشته باشد:

- source_type
- source_id یا source_query
- discovered_url
- normalized_domain
- discovered_at
- priority
- confidence
- status
- parent_url در صورت کشف از یک صفحه
- crawl_job_id در صورت ارتباط با Job

چرخه:

```text
Source
  ↓
Candidate
  ↓
Normalize
  ↓
Deduplicate
  ↓
Policy Check
  ↓
Queue
  ↓
Probe / Crawl
```

## Deduplication

در مرحلهٔ Domain Discovery کلید اصلی معمولاً دامنهٔ نرمال‌شده است.

در Deep Search علاوه بر دامنه، URL نرمال‌شده نیز برای جلوگیری از دریافت تکراری استفاده می‌شود.

نرمال‌سازی باید Scheme، Host، Port پیش‌فرض، Fragment و پارامترهای Tracking شناخته‌شده را مدیریت کند.

## فیلتر قبل از Crawl

هیچ Candidate نباید بدون Policy Check مستقیماً به Crawl نامحدود وارد شود.

حداقل کنترل‌ها:

- SSRF protection
- جلوگیری از localhost و شبکه‌های خصوصی
- محدودیت دامنه
- محدودیت URL
- Rate Limit
- Timeout
- Response Size Limit
- Redirect Limit
- Crawl Budget

## Query-driven Discovery

کاربر می‌تواند به‌جای دادن URL، هدف تعریف کند.

نمونه:

```text
Technology:
  WooCommerce

Country:
  IR

Has:
  Public Phone

Optional:
  Instagram
```

Discovery Candidate تولید می‌کند؛ اما نتیجهٔ نهایی Technology و Contact باید توسط Crawl و Detectorهای Numpo تأیید شود.

بنابراین Search Result یا هر Discovery Hint به‌تنهایی اثبات فناوری نیست.

## API پیشنهادی

در نسخهٔ بعدی:

- `POST /api/v1/discovery/jobs`
- `GET /api/v1/discovery/jobs/{job_id}`
- `GET /api/v1/discovery/jobs/{job_id}/candidates`
- `POST /api/v1/discovery/jobs/{job_id}/cancel`

می‌توان در آینده Job Type را یکپارچه کرد:

```text
job.type = discovery | active_check | crawl | deep_search
```

اما در MVP بهتر است مرزها واضح باقی بمانند.

## Interface موتور Discovery

پیاده‌سازی Go باید Providerها را پشت یک Interface قرار دهد:

```go
type DiscoveryProvider interface {
    Discover(ctx context.Context, req DiscoveryRequest) ([]Candidate, error)
}
```

Provider نباید به Database یا WordPress وابستگی مستقیم داشته باشد.

## Classification و Routing

Classification باید مستقل از Domain باشد. یک دامنه می‌تواند در چند Technology/Signal هم‌زمان قرار بگیرد و نباید فقط به یک دسته محدود شود.

نمونه:

```text
All Domains
   ├── Active
   ├── WordPress
   ├── WooCommerce
   ├── Shopify
   ├── Has Public Phone
   └── Unknown Technology
```

Routing می‌تواند بر اساس شرط‌ها انجام شود:

```text
ACTIVE
AND WORDPRESS
AND WOOCOMMERCE
        ↓
   Deep Search
```

اما دامنه‌ای که شرط را ندارد همچنان در Domain Store و Classificationهای خودش باقی می‌ماند.

## Pipeline پیشنهادی

```text
Seed / Search Provider
        ↓
Candidate Store
        ↓
Deduplication
        ↓
Active Probe
        ↓
Classification / Signals
        ↓
Routing Rules
        ↓
Deep Search
        ↓
Contact / Business Extraction
        ↓
Final Intelligence Result
```

برای مقیاس بالا، هر مرحله باید Queue مستقل یا قابل صف‌بندی داشته باشد تا یک مرحلهٔ سنگین باعث توقف کل Pipeline نشود.

## MVP Discovery

اولین نسخهٔ Discovery بهتر است با این ترتیب ساخته شود:

1. Manual Seeds
2. CSV/Seed List Import
3. robots.txt و Sitemap Discovery
4. Link Discovery داخل Crawl
5. Active Domain Check
6. یک Search Provider با Interface قابل تعویض
7. Deep Search

این ترتیب باعث می‌شود موتور قبل از وابستگی به Search API قابل تست و استفاده باشد.

## اصل معماری

**Discovery پیدا می‌کند؛ Crawl تأیید و تحلیل می‌کند.**

هیچ Search Provider یا منبع Discovery نباید مستقیماً نتیجهٔ نهایی Numpo را تعیین کند.


## چرخهٔ کشف مجدد از داخل Deep Crawler

Deep Crawler می‌تواند هنگام پردازش یک صفحه، URL یا Host جدید پیدا کند. این مورد باید دوباره وارد لایهٔ Discovery شود و نباید Deep Crawler مستقیماً یک Crawl جدید و بدون کنترل ایجاد کند.

قاعدهٔ اصلی:

```text
Deep Crawler
     ↓
New URL / Host
     ↓
Normalize + Deduplicate
     ↓
Policy / Scope
     ↓
Was this Domain Probed?
     ├── YES ──► Reuse Probe/Classification
     │             ↓
     │          Routing
     │             ↓
     │         Deep Queue
     │
     └── NO ───► Active Queue
                    ↓
                Active Probe
                    ↓
              Classification
                    ↓
                 Routing
                    ↓
                Deep Queue
```

بنابراین Deep Crawler یک **Discovery Source** نیز محسوب می‌شود.

### اگر دامنه قبلاً Probe شده باشد

فرض کنیم Deep Crawler به این لینک برسد:

```text
https://shop.example.com/products
```

ابتدا دامنهٔ نرمال‌شده استخراج می‌شود:

```text
shop.example.com
```

اگر برای این Domain یک Probe معتبر وجود داشته باشد، Active Probe دوباره اجرا نمی‌شود و نتیجهٔ قبلی برای Routing استفاده می‌شود.

مثلاً:

```text
Active = true
WordPress = true
WooCommerce = true
```

سپس URL مناسب مستقیماً وارد Deep Queue می‌شود.

### اگر دامنه قبلاً Probe نشده باشد

URL جدید ابتدا به Active Queue می‌رود:

```text
New URL
  ↓
New Domain
  ↓
Active Queue
  ↓
Active Probe
  ↓
Signals / Classification
  ↓
Routing
  ↓
Deep Queue
```

این کار باعث می‌شود Deep Crawler مجبور نباشد خودش منطق Active Check را پیاده کند.

## تفکیک URL Queue و Domain Probe

Domain و URL دو مفهوم متفاوت هستند.

برای مثال:

```text
https://example.com/about
https://example.com/contact
https://example.com/products
```

همه به یک Domain مربوط هستند:

```text
example.com
```

اما URLهای Deep Crawl باید در سطح URL Deduplicate شوند، در حالی که Active Probe عمدتاً در سطح Domain/Host انجام می‌شود.

در نتیجه:

- **Domain/Host Queue** برای Active Probe
- **URL Queue** برای Deep Crawl

استفاده می‌شود.

یک Domain ممکن است یک‌بار Probe شود ولی تعداد زیادی URL برای Deep Crawl داشته باشد.

## وضعیت Probe

برای جلوگیری از اجرای تکراری Probe، وضعیت Probe باید قابل Query باشد:

```text
NOT_CHECKED
QUEUED
CHECKING
ACTIVE
INACTIVE
ERROR
```

همراه با:

- last_probe_at
- probe_result
- probe_error
- probe_version

اگر Probe قبلی منقضی شده باشد، Routing می‌تواند دوباره آن را به Active Queue برگرداند.

## بازگشت URLهای جدید به Discovery

Deep Crawler هنگام کشف لینک جدید باید آن را با Provenance ثبت کند:

- source_type = deep_crawl
- parent_url
- source_domain
- discovered_url
- discovered_at
- crawl_job_id

بعد از Deduplication مشخص می‌شود که:

1. URL قبلاً Deep Crawl شده است؛
2. URL جدید است ولی Domain قبلاً Probe شده؛
3. هم URL و هم Domain جدید هستند.

این سه حالت نباید با یک Queue رفتار شوند.

## جلوگیری از حلقه و انفجار Queue

از آنجا که Deep Crawler می‌تواند Candidate جدید تولید کند، باید محدودیت‌های زیر اعمال شوند:

- Crawl Budget برای هر Job
- حداکثر Candidate جدید برای هر صفحه
- حداکثر URL برای هر Domain
- حداکثر عمق
- Deduplication اتمیک قبل از Queue
- Rate Limit مستقل برای هر Domain
- جلوگیری از تولید بی‌نهایت Candidate از Query Stringهای تکراری
- TTL یا انقضای Probe در صورت نیاز

هدف این است که:

```text
Deep Crawl → Discovery → Active Probe → Deep Crawl
```

یک حلقهٔ کنترل‌شده باشد، نه یک حلقهٔ نامحدود.

## معماری Queue

در مقیاس اولیه می‌توان Queueها را در یک سیستم صف واحد با نوع Job متفاوت پیاده کرد:

```text
Queue
 ├── active_probe
 ├── deep_crawl
 └── discovery
```

یا در صورت نیاز به مقیاس بالاتر به Queueهای مستقل تقسیم کرد:

```text
Active Queue      → Active Workers
Deep Queue        → Deep Workers
Discovery Queue   → Discovery Workers
```

منطق تولید Candidate نباید به انتخاب تکنولوژی Queue وابسته باشد.

## اصل نهایی

**Deep Crawler هر لینک جدید را مستقیماً Crawl نمی‌کند.**

ابتدا آن را به Discovery Pipeline برمی‌گرداند.

اگر Domain قبلاً بررسی شده باشد، نتیجهٔ Probe دوباره استفاده می‌شود و URL می‌تواند وارد Deep Queue شود.

اگر Domain جدید باشد، ابتدا Active Probe انجام می‌شود و بعد بر اساس Classification و Routing Rule تصمیم گرفته می‌شود که آیا وارد Deep Search شود یا فقط به‌عنوان یک Domain کشف‌شده نگهداری شود.
