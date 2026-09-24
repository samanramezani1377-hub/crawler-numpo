# Numpo Crawler — Data Model

The model is independent of WordPress. Production direction: PostgreSQL.

## Project

id, name, description, created_at, updated_at.

## Domain

id, project_id, domain, normalized_domain, status, first_seen_at, last_crawled_at.

## Crawl Job

id, project_id, status, started_at, completed_at, created_at, error_count, page_count, contact_count.

## Page

id, domain_id, url, canonical_url, status_code, content_type, depth, fetch_status, fetched_at, response_time_ms, error_code.

Full HTML should not be stored by default. Any future raw-content retention needs an explicit retention policy.

## Technology

id, domain_id, name, version, confidence, evidence, source_url, detected_at.

## Contact

id, domain_id, type, raw_value, normalized_value, confidence, source_url, first_seen_at, last_seen_at.

Phone types may include mobile, landline, fax, and unknown.

## Job Error

id, job_id, domain_id, url, category, message, retryable, created_at.

## Relationships

~~~text
Project
  |
  +-- Domains
  |     +-- Pages
  |     +-- Technologies
  |     +-- Contacts
  |
  +-- Crawl Jobs
        +-- Job Errors
~~~

## Normalization

Domains: lowercase, normalize default ports and trailing dots, separate scheme from host, retain original input where useful.

URLs: normalize scheme/host, remove fragments, optionally remove known tracking parameters, and prevent duplicate fetches.

Phones: retain original display value and derive a country-aware normalized value. Normalization is not proof that ambiguous strings are identical.

## Indexing priorities

Likely indexes include normalized domain, project/job IDs, technology name, technology confidence, normalized phone, contact type, source URL, and crawl status. Exact indexes should be validated after real query patterns exist.
