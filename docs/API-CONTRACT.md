# نومپو — قرارداد API

API مرز دائمی بین WordPress و Go است.

مسیر پایه:

/api/v1

## احراز هویت

در حالت Remote سرویس باید احراز هویت شود.

نمونه:

~~~text
Authorization: Bearer ENGINE_API_KEY
~~~

کلیدها نباید داخل Git ذخیره شوند.

## ایجاد Job

POST /api/v1/crawl/jobs

نمونه درخواست:

~~~json
{
  "project_id": "project-123",
  "seeds": ["https://example.com"],
  "options": {
    "max_pages_per_domain": 20,
    "max_depth": 2,
    "render_javascript": false
  }
}
~~~

نمونه پاسخ:

~~~json
{
  "job_id": "job_abc123",
  "status": "queued"
}
~~~

این Endpoint نباید تا پایان Crawl منتظر بماند.

## وضعیت Job

GET /api/v1/crawl/jobs/{job_id}

وضعیت‌ها:

- queued — در صف
- running — در حال اجرا
- completed — کامل‌شده
- completed_with_errors — کامل‌شده همراه خطا
- failed — شکست‌خورده
- cancelled — لغوشده

فیلدهای پیشرفت شامل تعداد دامنه‌ها، دامنه‌های کامل‌شده، صفحات دریافت‌شده، اطلاعات تماس و فناوری‌های شناسایی‌شده هستند.

## لغو Job

POST /api/v1/crawl/jobs/{job_id}/cancel

لغو باید جلوی کار جدید را بگیرد و اجازه دهد درخواست‌های در حال اجرا به‌صورت امن تمام شوند.

## نتایج

GET /api/v1/crawl/jobs/{job_id}/results

فیلترهای اولیه:

- فناوری
- میزان اطمینان فناوری
- دارای شماره
- نوع شماره
- کشور
- دامنه
- وضعیت Crawl

نمونه:

~~~json
{
  "domain": "example.com",
  "technologies": [
    {"name": "wordpress", "confidence": 0.96},
    {"name": "woocommerce", "confidence": 0.91}
  ],
  "contacts": [
    {
      "type": "phone",
      "value": "+982112345678",
      "source_url": "https://example.com/contact"
    }
  ]
}
~~~

## سلامت سرویس

GET /api/v1/health

~~~json
{
  "status": "ok",
  "version": "0.1.0"
}
~~~

## سازگاری

- مسیرهای API نسخه‌بندی می‌شوند.
- اضافه شدن فیلد اختیاری نباید Client قبلی را خراب کند.
- معنی فیلدهای قبلی نباید بی‌سر و صدا تغییر کند.
- تغییرات ناسازگار نیازمند نسخهٔ جدید API هستند.
- WordPress باید قطع بودن موتور را به‌درستی مدیریت کند.
- Go نباید به ساختار Request یا Database اختصاصی WordPress وابسته باشد.\n\n## Schema نتایج\n\nقرارداد Results باید با [OUTPUT-SCHEMA](OUTPUT-SCHEMA.md) هم‌راستا باشد. هر Result حداقل باید بتواند Domain، Technologies، Contacts و Provenance را برگرداند و در نسخه‌های بعدی Business، Social، Important Pages و Technical Signals را بدون شکستن Client قبلی اضافه کند.\n\nنمونهٔ نتیجهٔ کامل‌تر:\n\n~~~json\n{\n  "domain": {\n    "domain": "example.com",\n    "normalized_domain": "example.com",\n    "main_url": "https://example.com",\n    "https": true\n  },\n  "technologies": [\n    {"name": "wordpress", "confidence": 0.96, "source_url": "https://example.com/"},\n    {"name": "woocommerce", "confidence": 0.91, "source_url": "https://example.com/shop/"}\n  ],\n  "contacts": [\n    {\n      "type": "phone",\n      "raw_value": "021-12345678",\n      "normalized_value": "+982112345678",\n      "source_url": "https://example.com/contact",\n      "confidence": 0.97\n    }\n  ]\n}\n~~~\n\nفیلدهای جدید باید تا حد امکان Optional باشند تا Clientهای قبلی بدون تغییر کار کنند.\n

## Discovery API

Discovery از Crawl جداست و API مستقل دارد:

- `POST /api/v1/discovery/jobs`
- `GET /api/v1/discovery/jobs/{job_id}`
- `GET /api/v1/discovery/jobs/{job_id}/candidates`
- `POST /api/v1/discovery/jobs/{job_id}/cancel`

### حالت Discovery

هر Discovery Job یکی از این حالت‌ها را دارد:

- `manual` — دریافت دامنه/URL اولیه از کاربر
- `automatic` — تولید Candidate از منابع Discovery فعال
- `hybrid` — ترکیب ورودی دستی و منابع خودکار

نمونهٔ درخواست پیشنهادی:

```json
{
  "project_id": "project-123",
  "mode": "hybrid",
  "seeds": ["example.com", "https://shop.example.com"],
  "sources": {
    "search_provider": true,
    "sitemap": true,
    "robots": true,
    "link_discovery": true,
    "subdomain_from_crawl": true
  },
  "target": {
    "technologies": ["wordpress", "woocommerce"],
    "country": "IR",
    "has_public_phone": true
  }
}
```

در حالت `manual`، `seeds` مستقیماً وارد Candidate Store می‌شوند و فعال بودن Search Provider الزامی نیست. در حالت `automatic`، منابع فعال Candidate تولید می‌کنند. در حالت `hybrid` هر دو جریان وارد Candidate Store مشترک می‌شوند و Deduplication مشترک دارند.

`mode` نحوهٔ ورود Candidate را مشخص می‌کند؛ Queue type مانند `discovery`، `active_probe` و `deep_crawl` مرحلهٔ پردازش را مشخص می‌کند و این دو مفهوم نباید در API یکی شوند.

### Capabilityهای پردازشی

Capabilityهای سطح محصول باید جدا از منابع Discovery مدیریت شوند، از جمله:

- `active_probe.enabled`
- `deep_crawl.enabled`
- `detection.wordpress.enabled`
- `detection.woocommerce.enabled`
- `extraction.phone.enabled`
- `extraction.email.enabled`
- `extraction.business.enabled`
- `extraction.social.enabled`
- `page_classification.enabled`
- `browser_render.enabled`

جزئیات معماری و مرز Capabilityها در [DISCOVERY](DISCOVERY.md) و [ARCHITECTURE](ARCHITECTURE.md) تعریف شده است.

Candidateهای Discovery باید Source و Provenance خود را حفظ کنند. Search Provider فقط Candidate تولید می‌کند و تشخیص نهایی فناوری یا اطلاعات تماس باید توسط Crawl/Detector انجام شود.
