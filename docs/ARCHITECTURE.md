# Numpo Crawler — Architecture

## Decision

The crawler engine is implemented in Go. WordPress is a control plane and UI, not a crawler dependency.

## Boundaries

### WordPress Plugin
Owns projects, seeds, configuration, job creation, status, results, filters, export, and engine connection settings.

It must not own HTTP crawling, DNS/concurrency management, retry loops, or browser workers.

### Go Engine
Owns scheduling, HTTP fetching, redirects, timeouts, URL canonicalization, link discovery, crawl budget, technology detection, contact extraction, normalization, deduplication, persistence, retries, rate limiting, and metrics.

### Database
The crawler model is independent of WordPress tables. PostgreSQL is the production direction.

## Deployment modes

Same VPS: WordPress -> local API -> Go -> database.

Separate: WordPress -> HTTPS API -> Go -> queue -> workers -> PostgreSQL.

Scaled: API -> queue -> N workers -> PostgreSQL.

The plugin must not care whether the engine is local or remote.

## Crawl lifecycle

Create project -> add seeds -> create job -> queue -> resolve -> fetch homepage -> detect technology -> discover relevant pages -> fetch -> extract public contacts -> normalize/deduplicate -> persist -> complete.

Contact/about pages and navigation/footer links should receive higher priority.

## Fetching tiers

1. Asynchronous normal HTTP.
2. HTML parsing.
3. Headless browser only when normal HTTP does not expose enough information.

Browser rendering is an escalation path, not the default.

## Concurrency

Control global concurrency and per-domain concurrency. Use bounded retries, timeouts, redirect limits, and backoff. High global concurrency must never mean unlimited requests to one domain.

## Technology detection

Detection is evidence-based. WordPress signals may include wp-content, wp-includes, wp-json, generator metadata, and WordPress-specific assets/endpoints. WooCommerce has its own detector.

Each detection returns technology, confidence, evidence, source_url, and detected_at.

## Contact extraction

The first version focuses on publicly displayed business contact information.

HTML -> visible text/attributes -> phone candidates -> validation -> country-aware normalization -> deduplication -> source URL -> persistence.

Common tel links should also be supported.

## Domain isolation

Default scope stays within the target registrable domain. External links are not blindly followed. Subdomains can be explicitly allowed.

## Error handling

Normalize DNS, TLS, timeout, HTTP 403/429/5xx, invalid content, oversized response, redirect loop, and parser failures. Retry transient failures with bounded backoff; do not retry permanent failures forever.

## Separation contract

The key rule is: WordPress -> versioned HTTP API -> Go service.

The plugin talks to an API, never to Go internals. This allows the Go engine to move to another server without rewriting the product.

## Security

Remote mode requires HTTPS and service authentication. Validate inputs, authorize jobs/projects, limit URL/domain counts, limit response size, protect against SSRF, and restrict outbound networking where possible. The engine must not become an unrestricted proxy or internal-network fetcher.

## Observability

Expose queued/running/completed/failed counts, retries, pages fetched, domains completed, contacts found, technologies detected, timing, current concurrency/rate, and recent errors.

## Scaling path

Start with one Go process, one queue, and one database. Then move to API + queue + N workers + PostgreSQL. Scale according to measured queue depth and network throughput.
