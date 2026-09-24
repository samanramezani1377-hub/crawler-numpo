# نومپو — نقشه راه

## مرحله صفر — مستندسازی

- [x] تفکیک Discovery از Crawl
- [x] تعریف Active Domain Discovery
- [x] تعریف Deep Search
- [x] تعریف Candidate و Provenance
- [x] طراحی Discovery API اولیه
- [x] تعریف حالت‌های Discovery: Manual / Automatic / Hybrid
- [x] تفکیک Discovery Source از Capabilityهای داخلی Crawl
- [x] تعریف جریان مشترک Manual و Automatic به Candidate Store

- [x] هدف محصول
- [x] مرزبندی WordPress و Go
- [x] مدل‌های استقرار
- [x] قرارداد API
- [x] مدل داده
- [x] اصول کراول مسئولانه

## مرحله یک — Discovery و موتور MVP

- [ ] طراحی Candidate و Discovery Job با پشتیبانی از modeهای manual/automatic/hybrid
- [ ] Active Domain Check
- [ ] Seed List / CSV Import
- [ ] robots.txt و Sitemap Discovery
- [ ] Link Discovery
- [ ] Discovery Provider Interface
- [ ] اولین Search Provider
- [ ] Deep Search Pipeline

## مرحله یک — موتور MVP

- [ ] ساخت Go Module
- [ ] دریافت HTTP
- [ ] نرمال‌سازی URL
- [ ] محدود کردن دامنه
- [ ] Crawl Budget
- [ ] همزمانی کنترل‌شده
- [ ] Timeout و Retry
- [ ] Parser HTML
- [ ] اولویت صفحات تماس
- [ ] استخراج شماره
- [ ] نرمال‌سازی شماره
- [ ] تشخیص WordPress
- [ ] تشخیص WooCommerce
- [ ] ذخیره‌سازی ساختاریافته
- [ ] Metrics
- [ ] تست Unit و Integration

## مرحله دو — پنل WordPress

- [ ] اسکلت پلاگین
- [ ] مدیریت پروژه
- [ ] مدیریت دامنه‌های اولیه
- [ ] تنظیم اتصال به موتور
- [ ] شروع و توقف Job
- [ ] داشبورد پیشرفت
- [ ] جدول نتایج
- [ ] فیلتر فناوری
- [ ] فیلتر شماره
- [ ] Export CSV

## مرحله سه — جداسازی سرویس

- [ ] API نسخه‌بندی‌شده Go
- [ ] احراز هویت API
- [ ] PostgreSQL
- [ ] Docker
- [ ] تنظیم موتور Remote
- [ ] Health Check
- [ ] تست سازگاری API

## مرحله چهار — مقیاس

- [ ] صف پایدار
- [ ] چند Worker در Go
- [ ] زمان‌بندی برای هر دامنه
- [ ] کنترل Rate کلی
- [ ] سلامت Workerها
- [ ] Metrics صف
- [ ] Retry و Dead Letter
- [ ] افزایش افقی Workerها

## مرحله پنج — هوشمندی فناوری

- [ ] WordPress
- [ ] WooCommerce
- [ ] Shopify
- [ ] Joomla
- [ ] Magento
- [ ] Laravel
- [ ] تشخیص CDN و Proxy
- [ ] تشخیص سرویس‌های Analytics
- [ ] تشخیص فناوری‌های پرداخت

هر Detector باید مستقل تست شود و شواهد تشخیص را برگرداند.

## مرحله شش — اجرای مرورگر در مواقع لازم

- [ ] تشخیص صفحاتی که به Render نیاز دارند
- [ ] جداسازی Workerهای مرورگر
- [ ] Render فقط در صورت نیاز
- [ ] محدودیت منابع مرورگر
- [ ] مقایسه نتیجه HTTP با Render

## موارد خارج از MVP

- Crawl بدون محدودیت کل اینترنت
- دور زدن احراز هویت
- دور زدن CAPTCHA
- سیستم‌های پنهان‌کاری یا فرار از محدودیت
- همزمانی نامحدود
- ذخیرهٔ پیش‌فرض کل سایت‌ها
- اضافه کردن زیرساخت توزیع‌شده قبل از نیاز واقعی

## تعریف پایان MVP

کاربر باید بتواند یک دامنهٔ اولیه ثبت کند، Crawl را غیرهمزمان اجرا کند، فناوری‌های پشتیبانی‌شده را تشخیص دهد، اطلاعات تماس تجاریِ عمومی را استخراج و نرمال کند، منبع هر نتیجه را نگهداری کند و نتایج را از طریق پنل مشاهده و Export کند.

موتور Go باید در پایان MVP مستقل از WordPress نیز قابل اجرا باشد.\n\n## خروجی داده و Queryability\n\n- [x] تعریف Schema اولیهٔ خروجی\n- [x] پیاده‌سازی مدل Contact و Email\n- [x] پیاده‌سازی Technology Detection با Evidence\n- [x] پیاده‌سازی Page Classification\n- [x] پیاده‌سازی Business و Social extraction\n- [x] پیاده‌سازی Technical Signals\n- [x] طراحی Indexهای واقعی بر اساس Queryهای MVP\n\nمرجع: [OUTPUT-SCHEMA](OUTPUT-SCHEMA.md).\n

## تصمیمات تکمیلی Discovery

- [x] مستقل بودن قابلیت‌های Discovery و Crawl
- [x] قابل فعال/غیرفعال بودن هر Discovery Source
- [x] قابل فعال/غیرفعال بودن Subdomain Discovery
- [x] قرار گرفتن Subdomain Discovery اولیه داخل Deep Crawler
- [x] قابل فعال/غیرفعال بودن Active Probe
- [x] قابل فعال/غیرفعال بودن Deep Search
- [x] قابل فعال/غیرفعال بودن Routing Ruleها
- [x] ثبت Snapshot تنظیمات مؤثر در Job
- [x] پیاده‌سازی Feature/Capability Settings
- [x] پیاده‌سازی Routing Rule Engine
- [x] پیاده‌سازی Subdomain Discovery داخل Deep Crawler


## مرحله صفر — قراردادهای اجرایی تکمیل‌شده

- [x] قرارداد دقیق Discovery Job
- [x] State Machine برای Candidate / Probe / Deep Crawl
- [x] Crawl Scope و Budget
- [x] PostgreSQL Physical Schema اولیه
- [x] Retry و Failure Policy

از این نقطه، قراردادهای اصلی لازم برای شروع پیاده‌سازی MVP مشخص شده‌اند.

### ترتیب پیاده‌سازی بعدی

1. Go Module و ساختار Packageها
2. PostgreSQL migrations و Repositoryها
3. URL/Domain/Host normalization
4. Candidate Store و atomic dedup
5. Active Probe
6. Discovery Providers
7. Routing Engine
8. Deep Crawl
9. Detection و Extraction
10. API و Integration با WordPress

هیچ Search Provider یا Browser Worker نباید قبل از تثبیت Interfaceهای مربوطه به Crawl Core وابستگی مستقیم پیدا کند.


## وضعیت پیاده‌سازی جاری

- [x] Atomic page/domain budget transaction
- [x] Job error API
- [x] Candidate pagination API
- [x] Candidate CSV import/export
- [x] Effective capability snapshot
- [x] Extraction persistence for page/business/social data
- [ ] Full E2E API-to-persistence test
- [ ] Security hardening suite for DNS rebinding/IPv6/redirects
- [ ] Global cross-worker domain rate limiter
- [ ] HTTP-date Retry-After support
- [ ] Production queue metrics and dead-letter handling
- [ ] Browser worker / JS rendering
- [ ] Full WordPress project/domain/results dashboard
