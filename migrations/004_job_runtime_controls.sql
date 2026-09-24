ALTER TABLE discovery_jobs
 ADD COLUMN IF NOT EXISTS config jsonb NOT NULL DEFAULT '{}'::jsonb,
 ADD COLUMN IF NOT EXISTS max_pages integer NOT NULL DEFAULT 100,
 ADD COLUMN IF NOT EXISTS max_urls integer NOT NULL DEFAULT 500,
 ADD COLUMN IF NOT EXISTS max_depth integer NOT NULL DEFAULT 3,
 ADD COLUMN IF NOT EXISTS max_candidates_per_page integer NOT NULL DEFAULT 50,
 ADD COLUMN IF NOT EXISTS processed_pages integer NOT NULL DEFAULT 0,
 ADD COLUMN IF NOT EXISTS processed_urls integer NOT NULL DEFAULT 0,
 ADD COLUMN IF NOT EXISTS cancelled_at timestamptz;

ALTER TABLE domains
 ADD COLUMN IF NOT EXISTS crawl_pages integer NOT NULL DEFAULT 0;

CREATE INDEX IF NOT EXISTS candidates_job_status_attempt_idx
 ON candidates(discovery_job_id,status,next_attempt_at,priority DESC);

CREATE INDEX IF NOT EXISTS candidates_job_domain_idx
 ON candidates(discovery_job_id,normalized_domain,status);

CREATE INDEX IF NOT EXISTS pages_domain_depth_idx
 ON pages(domain_id,depth);

CREATE INDEX IF NOT EXISTS domain_probes_expiry_idx
 ON domain_probes(last_probe_at);