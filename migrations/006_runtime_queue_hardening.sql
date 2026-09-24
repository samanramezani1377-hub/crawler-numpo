CREATE TABLE IF NOT EXISTS domain_rate_limits(
 normalized_domain text PRIMARY KEY,
 last_started_at timestamptz NOT NULL DEFAULT 'epoch'
);
CREATE TABLE IF NOT EXISTS candidate_dead_letters(
 candidate_id uuid PRIMARY KEY REFERENCES candidates(id) ON DELETE CASCADE,
 discovery_job_id uuid NOT NULL REFERENCES discovery_jobs(id) ON DELETE CASCADE,
 failed_at timestamptz NOT NULL DEFAULT now(),
 attempts integer NOT NULL,
 error text NOT NULL DEFAULT ''
);
CREATE INDEX IF NOT EXISTS candidate_dead_letters_job_idx ON candidate_dead_letters(discovery_job_id,failed_at DESC);
