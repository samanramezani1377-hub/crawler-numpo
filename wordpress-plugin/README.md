# Numpo WordPress Plugin

## Install
Upload the directory `wordpress-plugin/numpo` as a plugin to WordPress and activate it.

After activation:
1. Open **Numpo → Settings**.
2. Set the Go Engine URL, for example `http://127.0.0.1:8080`.
3. Set the same API key configured in `NUMPO_API_KEY` when authentication is enabled.
4. Open **Numpo** and create a discovery job.

The WordPress plugin is the control plane only. Crawling, probing, parsing, SSRF policy and persistence remain in the Go engine.

## Engine
The repository root contains the Go engine and PostgreSQL deployment files:
- `Dockerfile`
- `docker-compose.yml`
- `migrations/`

Do not expose PostgreSQL or the Go engine directly to the public Internet without an authenticated and appropriately restricted network boundary.
