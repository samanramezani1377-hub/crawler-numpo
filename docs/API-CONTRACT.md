# Numpo API Contract

Base path: `/api/v1`

## Authentication

All endpoints except `GET /health` require:
```
Authorization: Bearer ENGINE_API_KEY
```

Anonymous API access is disabled by default. It is only allowed when the engine is explicitly started with `NUMPO_ALLOW_ANONYMOUS_API=true`.

Never store the API key in Git, client-side JavaScript, or API responses.

## Health

`GET /api/v1/health`

Health is intentionally unauthenticated so infrastructure can probe it.

Response:
```json
{"status":"ok","service":"numpo-engine"}
```

A database failure returns HTTP 503 with a stable error code.

## Metrics

`GET /api/v1/metrics`

Returns Prometheus-compatible text metrics. Metrics are operational data and should normally be protected at the network/ingress layer if the engine is internet reachable.

## Discovery Jobs

### Create

`POST /api/v1/discovery/jobs`

Request:
```json
{
  "project_id":"project-123",
  "mode":"manual|automatic|hybrid",
  "seeds":["https://example.com"],
  "sources":{
    "search_provider":true,
    "sitemap":true,
    "robots":true,
    "link_discovery":true,
    "subdomain_from_crawl":true
  },
  "target":{
    "technologies":["wordpress","woocommerce"],
    "country":"IR",
    "has_public_phone":true
  },
  "limits":{
    "max_urls":500,
    "max_pages":100,
    "max_depth":3,
    "max_candidates_per_page":50
  },
  "capabilities":{}
}
```

The engine clamps client limits to configured server ceilings. Security policy cannot be disabled by a job request.

Modes:
- `manual`: at least one seed is required.
- `automatic`: at least one automatic discovery source must be enabled.
- `hybrid`: manual seeds or an automatic source must be present.

Response: HTTP 202.
```json
{"job_id":"...","status":"queued","mode":"manual","created_at":"..."}
```

### Status

`GET /api/v1/discovery/jobs/{job_id}`

Returns job status, configuration snapshot, counters, and timestamps.

States:
`queued`, `running`, `completed`, `completed_with_errors`, `failed`, `cancelled`.

### Cancel

`POST /api/v1/discovery/jobs/{job_id}/cancel`

Cancellation stops scheduling new work and lets in-flight operations exit through context cancellation.

### Candidates

`GET /api/v1/discovery/jobs/{job_id}/candidates?page=1&per_page=50`

Maximum `per_page` is 200.

### Resources

The following paginated resources are available:
- `domains`
- `hosts`
- `pages`
- `technologies`
- `contacts`
- `business`
- `social`
- `classifications`
- `probes`

Example:
`GET /api/v1/discovery/jobs/{job_id}/domains?page=1&per_page=50`

### Errors

`GET /api/v1/discovery/jobs/{job_id}/errors?page=1&per_page=50`

### Candidate CSV

Export:
`GET /api/v1/discovery/jobs/{job_id}/candidates.csv`

Import:
`POST /api/v1/discovery/jobs/{job_id}/csv`

CSV import accepts one URL per row and applies the same URL normalization and SSRF policy as other discovery inputs.

## Error Contract

All API errors use:
```json
{
  "error":{
    "code":"stable_machine_code",
    "message":"Human-readable message.",
    "retryable":false
  }
}
```

Clients must use `code`, not `message`, for program logic.

Common codes include:
- `unauthorized`
- `not_found`
- `invalid_json`
- `invalid_project_id`
- `invalid_discovery_mode`
- `manual_seeds_required`
- `automatic_source_required`
- `invalid_seed`
- `csv_too_large`
- `invalid_csv`
- `database_unavailable`

## Security Contract

Every crawl URL is checked before use. HTTP crawling uses a protected transport that validates DNS results and redirect targets against private/local/reserved networks. Browser escalation performs an initial URL policy check; browser networking should additionally be restricted at the deployment/network boundary.

The engine must not be exposed directly to the public internet without authentication and network-level protection for metrics and database access.

## Compatibility

The supported API is the Discovery API under `/api/v1/discovery`. Older `/api/v1/crawl/*` examples are not active engine routes and must not be used by clients.

Adding optional response fields is backward compatible. Removing or changing the meaning of existing fields requires a new API version.
