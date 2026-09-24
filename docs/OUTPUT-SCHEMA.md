# نومپو — Schema اطلاعات خروجی

این سند قرارداد مفهومی داده‌هایی است که نومپو از هر سایت استخراج و برای جست‌وجو، فیلتر، مشاهده و Export نگهداری می‌کند.

اصل مهم: نومپو فقط داده‌های عمومی قابل مشاهده از سایت را جمع‌آوری می‌کند. هر مقدار قابل استخراج باید تا حد امکان **منبع، زمان، روش/شاهد و میزان اطمینان** داشته باشد.

## 1. شناسنامه سایت

هر Domain Result می‌تواند شامل این فیلدها باشد:

- `domain`
- `normalized_domain`
- `main_url`
- `title`
- `language`
- `probable_country`
- `status`
- `http_status`
- `https`
- `response_time_ms`
- `first_seen_at`
- `last_crawled_at`

این بخش وضعیت فنی Crawl را از هویت تجاری سایت جدا نگه می‌دارد.

## 2. فناوری‌ها

هر Technology Detection:

- `name`
- `version` در صورت قابل تشخیص بودن
- `confidence`
- `evidence`
- `source_url`
- `detected_at`

فناوری‌های اولیه:

- WordPress
- WooCommerce

فناوری‌های توسعه‌ای:

- Shopify
- Joomla
- Magento
- Laravel
- Next.js
- React
- Vue
- Bootstrap
- Tailwind
- Elementor
- WPBakery
- Yoast
- Rank Math
- Cloudflare
- Google Analytics
- Google Tag Manager
- سرویس‌های پرداخت قابل تشخیص

وجود فناوری نباید صرفاً بر اساس یک حدس ثبت شود؛ Detector باید شاهد قابل توضیح برگرداند.

## 3. شماره تلفن

هر Phone Contact:

- `raw_value`
- `normalized_value`
- `country`
- `type`: mobile / landline / fax / unknown
- `confidence`
- `source_url`
- `location`: header / footer / contact / tel / body / unknown
- `first_seen_at`
- `last_seen_at`

شمارهٔ اصلی نمایش‌داده‌شده حفظ می‌شود و مقدار نرمال‌شده برای جست‌وجو و Deduplication استفاده می‌شود.

## 4. ایمیل

هر Email Contact:

- `raw_value`
- `normalized_value`
- `source_url`
- `location`
- `confidence`
- `first_seen_at`
- `last_seen_at`

فقط ایمیل عمومی قابل مشاهده در سایت در Scope این محصول است.

## 5. اطلاعات تجاری عمومی

در صورت مشاهده:

- `business_name`
- `brand_name`
- `address`
- `city`
- `country`
- `postal_code`
- `public_email`
- `public_phone`

این اطلاعات باید به منبع قابل مشاهده متصل باشند و در صورت نبود شاهد کافی به‌عنوان مقدار قطعی ثبت نشوند.

## 6. شبکه‌های اجتماعی

هر Social Profile:

- `type`: instagram / telegram / whatsapp / linkedin / facebook / x / youtube / other
- `url`
- `source_url`
- `confidence`

## 7. صفحات مهم

هر Page Record:

- `url`
- `canonical_url`
- `type`
- `title`
- `status_code`
- `content_type`
- `depth`
- `fetch_status`
- `fetched_at`
- `response_time_ms`

Page Typeهای اولیه:

- home
- contact
- about
- products
- product
- services
- blog
- pricing
- terms
- privacy
- faq
- other

## 8. اطلاعات WordPress

در صورت تشخیص WordPress:

- نسخهٔ احتمالی WordPress
- نام Theme
- Child Theme در صورت قابل تشخیص بودن
- Plugins قابل تشخیص از سطح عمومی سایت
- Elementor
- WooCommerce
- SEO Plugin
- Cache Plugin

نسخه یا Plugin فقط زمانی ثبت شود که از شواهد عمومی قابل استنباط باشد.

## 9. اطلاعات WooCommerce

در صورت تشخیص WooCommerce:

- `detected`
- `version` در صورت تشخیص
- وجود احتمالی Shop
- وجود Cart
- وجود Checkout
- وجود My Account
- الگوی URL محصول
- نشانه‌های Payment Gateway
- WooCommerce-related plugins قابل تشخیص

اطلاعات داخلی فروشگاه، حساب مشتری، سفارش‌ها یا داده‌های نیازمند احراز هویت در Scope خروجی نیستند.

## 10. اطلاعات فنی

در صورت نیاز و با حجم محدود:

- Server Headers منتخب
- Content-Type
- Redirect Chain محدود
- TLS/HTTPS وضعیت
- CDN/Proxy signals
- Cache Headers
- وجود Sitemap
- وجود robots.txt
- Canonical
- Open Graph
- Schema/Structured Data قابل مشاهده

هدرها یا محتوای خام نباید بدون نیاز و بدون محدودیت ذخیره شوند.

## 11. Evidence و Provenance

هر تشخیص مهم باید بتواند به یک شاهد متصل شود.

ساختار مفهومی:

~~~json
{
  "source_url": "https://example.com/contact",
  "evidence": "mailto:info@example.com",
  "confidence": 0.98,
  "detected_at": "2026-09-24T10:00:00Z"
}
~~~

Evidence نباید شامل Secret، Credential یا دادهٔ خصوصی غیرضروری باشد.

## 12. خروجی ترکیبی نمونه

~~~json
{
  "domain": {
    "domain": "example.com",
    "normalized_domain": "example.com",
    "main_url": "https://example.com",
    "title": "Example Store",
    "https": true
  },
  "technologies": [
    {
      "name": "wordpress",
      "version": null,
      "confidence": 0.96,
      "source_url": "https://example.com/"
    },
    {
      "name": "woocommerce",
      "version": null,
      "confidence": 0.91,
      "source_url": "https://example.com/shop/"
    }
  ],
  "business": {
    "business_name": "Example Store",
    "city": "Tehran",
    "country": "IR"
  },
  "contacts": [
    {
      "type": "phone",
      "raw_value": "021-12345678",
      "normalized_value": "+982112345678",
      "country": "IR",
      "confidence": 0.97,
      "source_url": "https://example.com/contact"
    },
    {
      "type": "email",
      "raw_value": "info@example.com",
      "normalized_value": "info@example.com",
      "confidence": 0.99,
      "source_url": "https://example.com/contact"
    }
  ],
  "socials": [
    {
      "type": "instagram",
      "url": "https://instagram.com/example",
      "source_url": "https://example.com/"
    }
  ],
  "important_pages": [
    {
      "type": "contact",
      "url": "https://example.com/contact",
      "status_code": 200
    }
  ]
}
~~~

## 13. MVP در برابر نسخه‌های بعدی

### MVP

- Domain
- Crawl metadata
- Page
- WordPress
- WooCommerce
- Phone
- Email
- Source URL
- Confidence
- Normalized values
- خطاهای Crawl

### بعد از MVP

- Business identity
- Address و city
- Social profiles
- Important page classification
- WordPress theme/plugin signals
- WooCommerce commerce signals
- CDN/Analytics/Payment technology
- Structured Data analysis
- Browser-rendered evidence

## 14. اصل Queryability

Schema باید طوری طراحی شود که Queryهای کاربردی بدون Parse کردن دوبارهٔ صفحات انجام شوند، برای مثال:

- WordPress + WooCommerce
- WordPress + شماره تلفن
- کشور مشخص + فناوری مشخص
- دارای Email
- دارای Instagram
- شهر مشخص + WooCommerce
- دامنه‌هایی که در Crawl اخیر تغییر فناوری داشته‌اند

به همین دلیل فیلدهای نرمال‌شده، Technology Detection و Contact باید ساختاریافته و قابل Index باشند.
