# Numpo Crawler — Roadmap

## Phase 0 — Documentation

- [x] Product goal
- [x] WordPress/Go boundary
- [x] Deployment modes
- [x] API contract
- [x] Data model
- [x] Responsible crawling constraints

## Phase 1 — MVP engine

- [ ] Go module
- [ ] HTTP fetcher
- [ ] URL normalization
- [ ] Domain scope enforcement
- [ ] Crawl budget
- [ ] Bounded concurrency
- [ ] Timeout/retry policy
- [ ] HTML parser
- [ ] Contact-page prioritization
- [ ] Phone extraction
- [ ] Phone normalization
- [ ] WordPress detector
- [ ] WooCommerce detector
- [ ] Structured persistence
- [ ] Metrics
- [ ] Unit/integration tests

## Phase 2 — WordPress control plane

- [ ] Plugin skeleton
- [ ] Project management
- [ ] Seed management
- [ ] Engine connection settings
- [ ] Start/stop jobs
- [ ] Progress dashboard
- [ ] Result table
- [ ] Technology filters
- [ ] Phone filters
- [ ] CSV export

## Phase 3 — Service separation

- [ ] Versioned Go HTTP API
- [ ] API authentication
- [ ] PostgreSQL
- [ ] Docker deployment
- [ ] Remote engine configuration
- [ ] Health checks
- [ ] API compatibility tests

## Phase 4 — Scale

- [ ] Durable queue
- [ ] Multiple Go workers
- [ ] Per-domain scheduling
- [ ] Global rate control
- [ ] Worker health
- [ ] Queue metrics
- [ ] Retry/dead-letter strategy
- [ ] Horizontal worker scaling

## Phase 5 — Technology intelligence

- [ ] WordPress
- [ ] WooCommerce
- [ ] Shopify
- [ ] Joomla
- [ ] Magento
- [ ] Laravel
- [ ] CDN/proxy signals
- [ ] Analytics signals
- [ ] Payment technology signals

Each detector must be independently testable and return evidence.

## Phase 6 — Browser escalation

- [ ] Detect pages needing rendering
- [ ] Isolate browser workers
- [ ] Render only when required
- [ ] Enforce browser resource/time limits
- [ ] Compare HTTP and rendered extraction

## MVP non-goals

- Internet-wide unrestricted crawling
- Bypassing authentication or access controls
- CAPTCHA bypass
- Stealth/evasion systems
- Unlimited concurrency
- Storing entire websites by default
- Premature distributed infrastructure

## MVP definition of done

A seed domain can be submitted, crawled asynchronously, classified for supported technologies, scanned for public business contact information, normalized, stored with provenance, and viewed/exported through the control plane.

The Go engine must also run without WordPress before the architecture is considered complete.
