CREATE UNIQUE INDEX IF NOT EXISTS domains_project_domain_uq ON domains(project_id,normalized_domain);
CREATE UNIQUE INDEX IF NOT EXISTS pages_domain_url_uq ON pages(domain_id,normalized_url);
CREATE UNIQUE INDEX IF NOT EXISTS technologies_domain_name_uq ON technologies(domain_id,name);
CREATE UNIQUE INDEX IF NOT EXISTS contacts_domain_type_value_uq ON contacts(domain_id,type,normalized_value);
CREATE UNIQUE INDEX IF NOT EXISTS domain_probes_host_uq ON domain_probes(host_id);

ALTER TABLE candidates ADD COLUMN IF NOT EXISTS depth integer NOT NULL DEFAULT 0;
ALTER TABLE candidates ADD COLUMN IF NOT EXISTS discovery_page_count integer NOT NULL DEFAULT 0;
