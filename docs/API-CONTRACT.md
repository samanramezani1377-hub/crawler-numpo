# Numpo Crawler — API Contract

The API is the permanent boundary between WordPress and Go.

Base path: /api/v1

## Authentication

Remote deployments require service authentication, for example:

Authorization: Bearer ENGINE_API_KEY

Secrets must never be committed.

## Create crawl job

POST /api/v1/crawl/jobs

Request:

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

Response:

~~~json
{
  "job_id": "job_abc123",
  "status": "queued"
}
~~~

The endpoint is asynchronous.

## Job status

GET /api/v1/crawl/jobs/{job_id}

Statuses: queued, running, completed, completed_with_errors, failed, cancelled.

Example progress fields: domains_total, domains_completed, pages_fetched, contacts_found, technologies_detected.

## Cancel

POST /api/v1/crawl/jobs/{job_id}/cancel

## Results

GET /api/v1/crawl/jobs/{job_id}/results

Initial filters: technology, technology confidence, has_phone, phone type, country, domain, crawl status.

Example result:

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

## Health

GET /api/v1/health

~~~json
{
  "status": "ok",
  "version": "0.1.0"
}
~~~

## Compatibility

- API paths are versioned.
- New optional fields may be added without breaking old clients.
- Existing field meanings must not silently change.
- Breaking changes require a new API version.
- WordPress must handle an unavailable engine gracefully.
- The Go engine must not depend on WordPress-specific request objects or database schemas.
