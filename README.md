# Numpo Crawler

Numpo is a planned web-crawling and website-intelligence system for discovering public website information, detecting technologies, and extracting publicly displayed business contact information.

## Architecture

~~~text
WordPress Plugin
      |
 Versioned API
      |
      v
Go Crawler
  |   |   |
Queue Fetcher Detection/Extraction
      |
      v
 PostgreSQL
~~~

The key decision is that Go is the crawler engine and WordPress is the control plane/UI. The plugin talks to a versioned API, never to Go internals. This allows the Go engine to move from the same VPS to a separate crawler server later without a rewrite.

## Deployment path

Phase 1: WordPress -> local API -> Go -> database

Phase 2: WordPress -> HTTPS API -> Go -> database

Phase 3: WordPress -> API -> Queue -> multiple Go workers -> PostgreSQL

## Product capabilities

1. Seed domains/URLs.
2. Bounded domain crawling.
3. Priority discovery of useful pages such as contact/about pages.
4. Technology detection.
5. Public business contact extraction.
6. Phone normalization and deduplication.
7. Source URL/provenance for every result.
8. Structured persistence.
9. Job progress, errors, retries, and metrics.
10. Filtering and export.

## Initial technologies

- WordPress
- WooCommerce

Detection is evidence-based and returns technology, confidence, evidence, source URL, and detection time.

## Fetching

Asynchronous HTTP is the default. Headless browser rendering is an escalation path only when normal HTTP does not expose enough information.

## Performance

The workload is mostly network I/O, so controlled concurrency is more important than heavy per-site processes. Global and per-domain concurrency, timeouts, redirect limits, bounded retries, and backoff are mandatory.

A small VPS around 4 vCPU / 8 GB RAM is a reasonable MVP starting point; actual throughput must be benchmarked.

## Data

Core entities:

- Project
- Domain
- Crawl Job
- Page
- Technology
- Contact
- Job Error

The crawler model is independent of WordPress tables. PostgreSQL is the production direction.

## Security and responsible crawling

The system is intended for public website information and controlled crawling.

Required protections include HTTPS/service authentication in remote mode, SSRF protection, bounded URL/domain counts, response-size limits, per-domain rate limits, timeouts, bounded retries, explicit crawl scope, and no authentication/CAPTCHA bypass or stealth/evasion design.

## Current status

Architecture/specification phase. Implementation starts after the architecture is agreed.

## Documentation

- [Architecture](docs/ARCHITECTURE.md)
- [API Contract](docs/API-CONTRACT.md)
- [Data Model](docs/DATA-MODEL.md)
- [Roadmap](docs/ROADMAP.md)

## MVP definition

A seed domain can be submitted, crawled asynchronously, classified for supported technologies, scanned for public business contact information, normalized, stored with provenance, and viewed/exported through the control plane.

The Go engine must also run independently of WordPress before the architecture is considered complete.
