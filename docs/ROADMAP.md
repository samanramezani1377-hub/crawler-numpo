# نومپو — نقشه راه و وضعیت

## هستهٔ MVP

- [x] Discovery با حالت‌های Manual / Automatic / Hybrid
- [x] Candidate Store و dedup اتمیک
- [x] Active Domain Probe
- [x] Seed / CSV Import
- [x] robots.txt و Sitemap Discovery
- [x] Link / Subdomain Discovery در Deep Crawl
- [x] Discovery Provider Interface
- [x] Search Provider صفحه‌بندی‌شده و قابل تعویض
- [x] Crawl Budget برای URL / Page / Depth
- [x] HTTP Crawl با redirect و DNS-aware SSRF protection
- [x] Timeout و Retry محدود
- [x] HTML Parser و URL normalization
- [x] Contact / Business / Social extraction
- [x] Technology Detection با Evidence
- [x] Page Classification و Technical Signals
- [x] PostgreSQL persistence
- [x] Job errors و Dead Letter handling
- [x] Metrics
- [x] Unit / Integration / Race / Vet tests

## WordPress Control Plane

- [x] تنظیم Engine URL و API Key
- [x] مدیریت پروژه و دامنه
- [x] Manual / Automatic / Hybrid Discovery UI
- [x] Job status و cancel
- [x] جدول Domains / Hosts / Pages / Technologies / Contacts / Business / Social / Classifications / Probes
- [x] CSV import / export
- [x] PHPUnit با WordPress واقعی و MySQL
- [x] ساخت ZIP نصب‌شدنی در CI

## Production Hardening

- [x] API نسخه‌بندی‌شده و authentication با fail-closed default
- [x] PostgreSQL migrations و runtime schema
- [x] Atomic queue leasing
- [x] Global cross-worker domain rate limiting
- [x] Central retry decision policy و Retry-After
- [x] Candidate dead-letter handling
- [x] Browser escalation با concurrency محدود
- [x] Browser egress SSRF proxy برای HTTP و HTTPS
- [x] Docker Compose health checks و secrets از environment
- [x] E2E: API → DB → queue → probe → routing → crawl → extract → persist → API
- [x] Concurrent Worker load test
- [x] CI build / test / race / vet / PHPUnit / package / container validation

## وضعیت Browser

Chromium فقط در صورت نیاز به Render فراخوانی می‌شود. هر Render یک proxy محلی با policy مشترک SSRF ایجاد می‌کند و Chromium تمام ترافیک خود را از آن عبور می‌دهد. مقصدهای private / loopback / reserved و DNSهایی که به چنین مقصدهایی resolve می‌شوند توسط policy رد می‌شوند.

## وضعیت Search

Search به یک interface مستقل تبدیل شده است. Provider فعلی HTTP از templateهای `{query}` و `{page}`، timeout، redirect validation، pagination، parsing JSON/line/HTML، normalization و dedup پشتیبانی می‌کند. اضافه‌کردن Provider اختصاصی بعدی بدون تغییر Crawl Core امکان‌پذیر است.

## وضعیت Plugin Source

تنها source رسمی پلاگین:

`wordpress-plugin/numpo`

tree قدیمی `numpo/` حذف شده تا source of truth دوگانه وجود نداشته باشد.

## Migration History

شماره‌گذاری قدیمی شامل دو migration با prefix `002` بود. migration queue lease به `007_queue_leases.sql` منتقل شده و SQL آن idempotent است؛ بنابراین دیتابیس‌های جدید تاریخچهٔ مرتب دارند و دیتابیس‌های قبلی با اجرای مجدد SQL به وضعیت صحیح می‌رسند.

## موارد توسعهٔ بعدی، خارج از شرط تکمیل MVP

- [ ] Providerهای جستجوی اختصاصی بیشتر
- [ ] Detectorهای فناوری بیشتر
- [ ] Benchmark گسترده و capacity planning
- [ ] Browser lifecycle / crash recovery / per-render resource telemetry
- [ ] Dashboard تحلیلی پیشرفته‌تر با نمودار و exploration عمیق نتایج
- [ ] Strict gofmt enforcement پس از یک commit مستقل formatting

این موارد feature/scale work هستند؛ هستهٔ MVP و سخت‌سازی فعلی به آن‌ها وابسته نیست.
