# کراولر نومپو

Numpo یک افزونه WordPress برای Discovery، Crawl و استخراج اطلاعات عمومی سایت‌هاست.

## معماری PHP-only

Numpo در شاخه `php-only` کاملاً داخل WordPress/PHP اجرا می‌شود و برای اجرای Crawl به Go Engine، Chromium یا PostgreSQL داخلی وابسته نیست.

```text
WordPress
   │
   ├── REST API
   ├── Discovery
   ├── Queue / Worker
   ├── HTTP Crawler
   ├── Detection / Extraction
   └── MySQL / $wpdb
```

## قابلیت‌ها

- Discovery از Seed، robots.txt و Sitemap/Sitemap Index
- Crawl با محدودیت URL، Page، Depth و Candidate
- رعایت robots.txt
- Rate limit برای هر Host
- Retry و Backoff
- کنترل Redirect
- SSRF protection برای IPv4/IPv6 و DNS
- محدودیت حجم پاسخ
- تشخیص CMS، Framework، Analytics، CDN/WAF و ابزارهای رایج
- استخراج Metadata
- استخراج Social
- استخراج Business و Contact
- Page Classification
- Host Probe
- ذخیره HTTP response headers
- REST API
- CSV Export
- Job recovery
- Candidate deduplication و lease برای پردازش

## امنیت

Numpo فقط برای Crawl کنترل‌شدهٔ اطلاعات عمومی طراحی شده است.

- مقصدهای private/local توسط SSRF policy مسدود می‌شوند.
- Redirectها دوباره اعتبارسنجی می‌شوند.
- حجم پاسخ محدود است.
- تعداد URL و عمق Crawl محدود است.
- Rate limit قابل تنظیم است.
- robots.txt قابل رعایت یا غیرفعال‌سازی توسط تنظیم Job است.
- احراز هویت، CAPTCHA یا محدودیت‌های سایت دور زده نمی‌شوند.

## ساختار

منبع رسمی افزونه:

`wordpress-plugin/numpo`

اجزای PHP اصلی:

- `class-numpo-api.php`
- `class-numpo-crawler.php`
- `class-numpo-db.php`
- `class-numpo-discovery.php`
- `class-numpo-worker.php`
- `class-numpo-diagnostics.php`
- `class-numpo-admin.php`

## نصب

فایل ZIP ساخته‌شده توسط CI را در WordPress از مسیر Plugins → Add New → Upload Plugin نصب کنید.

پیش‌نیازهای اصلی:

- WordPress
- PHP 7.4+
- WordPress HTTP API / cURL
- DOMDocument
- MySQL/MariaDB مورد نیاز WordPress

هیچ Go binary یا PostgreSQL داخلی برای اجرای PHP-only مورد نیاز نیست.

## وضعیت

شاخه `php-only` مسیر مهاجرت کامل Numpo از معماری Go/embedded PostgreSQL به اجرای مستقیم PHP در WordPress است. قبل از استفاده Production باید CI، تست End-to-End و نصب واقعی ZIP با موفقیت تأیید شوند.
