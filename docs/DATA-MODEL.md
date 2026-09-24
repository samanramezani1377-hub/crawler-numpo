# نومپو — مدل داده

مدل داده مستقل از WordPress است.

گزینهٔ اصلی Production: PostgreSQL.

## پروژه

- id
- name
- description
- created_at
- updated_at

## دامنه

- id
- project_id
- domain
- normalized_domain
- status
- first_seen_at
- last_crawled_at

## Job کراول

- id
- project_id
- status
- started_at
- completed_at
- created_at
- error_count
- page_count
- contact_count

## صفحه

- id
- domain_id
- url
- canonical_url
- status_code
- content_type
- depth
- fetch_status
- fetched_at
- response_time_ms
- error_code

HTML کامل به‌صورت پیش‌فرض ذخیره نمی‌شود. اگر بعداً ذخیرهٔ محتوای خام اضافه شد باید سیاست نگهداری مشخص داشته باشد.

## فناوری

- id
- domain_id
- name
- version
- confidence
- evidence
- source_url
- detected_at

## اطلاعات تماس

- id
- domain_id
- type
- raw_value
- normalized_value
- confidence
- source_url
- first_seen_at
- last_seen_at

انواع شماره:

- موبایل
- تلفن ثابت
- فکس
- نامشخص

## خطای Job

- id
- job_id
- domain_id
- url
- category
- message
- retryable
- created_at

## روابط

~~~text
پروژه
  |
  +-- دامنه‌ها
  |     +-- صفحات
  |     +-- فناوری‌ها
  |     +-- اطلاعات تماس
  |
  +-- Jobهای Crawl
        +-- خطاها
~~~

## نرمال‌سازی

دامنه:

- حروف کوچک
- نرمال‌سازی Port پیش‌فرض
- حذف نقطهٔ انتهایی
- جدا کردن Scheme از Host
- نگهداری ورودی اولیه در صورت نیاز برای Audit

URL:

- نرمال‌سازی Scheme و Host
- حذف Fragment
- امکان حذف پارامترهای Tracking شناخته‌شده
- جلوگیری از دریافت تکراری

شماره:

- نگهداری شکل اصلی نمایش‌داده‌شده
- ساخت مقدار نرمال‌شده بر اساس کشور
- نرمال‌سازی به‌تنهایی نباید اثبات کند دو رشتهٔ مبهم یک شماره هستند.

## اولویت Indexها

احتمالاً برای این موارد Index لازم خواهد بود:

- دامنهٔ نرمال‌شده
- شناسهٔ پروژه و Job
- نام فناوری
- میزان اطمینان فناوری
- شمارهٔ نرمال‌شده
- نوع اطلاعات تماس
- URL منبع
- وضعیت Crawl

Index نهایی بعد از مشخص شدن Queryهای واقعی تعیین می‌شود.\n\n## توسعه مدل برای Schema خروجی\n\nمدل داده باید علاوه بر موجودیت‌های فعلی، امکان نگهداری ساختاریافتهٔ موارد زیر را داشته باشد:\n\n- مشخصات پایهٔ سایت و Crawl Metadata\n- Email و Social Profile\n- Business Identity عمومی\n- نوع و طبقه‌بندی Page\n- شواهد و Provenance برای تشخیص‌های مهم\n- سیگنال‌های WordPress و WooCommerce\n- سیگنال‌های فنی مانند CDN، Sitemap، Canonical و Open Graph\n\nمرجع دقیق فیلدها و تفکیک MVP از قابلیت‌های بعدی در [OUTPUT-SCHEMA](OUTPUT-SCHEMA.md) است.\n\n### Queryability\n\nطراحی باید امکان Query مستقیم برای مواردی مانند WordPress + WooCommerce، داشتن شماره، کشور، شهر، Email و فناوری را فراهم کند؛ بنابراین مقادیر نرمال‌شده و Detectionهای فناوری باید ساختاریافته و قابل Index باشند.\n