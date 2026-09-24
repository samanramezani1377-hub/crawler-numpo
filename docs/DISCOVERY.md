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

## دو موتور اصلی

### ۱. Active Domain Discovery

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

Active Discovery می‌تواند تعداد بسیار زیادی دامنه را با هزینهٔ کم غربال کند.

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

## Pipeline پیشنهادی

```text
Seed / Search Provider
        ↓
Candidate Store
        ↓
Deduplication
        ↓
Active Check
        ↓
Technology Filter
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
