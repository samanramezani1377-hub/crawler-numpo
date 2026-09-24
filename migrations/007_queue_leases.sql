ALTER TABLE candidates ADD COLUMN IF NOT EXISTS next_attempt_at timestamptz NOT NULL DEFAULT now();
ALTER TABLE candidates ADD COLUMN IF NOT EXISTS lease_until timestamptz;
ALTER TABLE candidates ADD COLUMN IF NOT EXISTS processing_started_at timestamptz;
ALTER TABLE candidates ADD COLUMN IF NOT EXISTS completed_at timestamptz;
CREATE INDEX IF NOT EXISTS candidates_retry_idx ON candidates(discovery_job_id,status,next_attempt_at);

ALTER TABLE discovery_jobs ADD COLUMN IF NOT EXISTS cancelled_at timestamptz;
CREATE INDEX IF NOT EXISTS discovery_jobs_status_idx ON discovery_jobs(status);
