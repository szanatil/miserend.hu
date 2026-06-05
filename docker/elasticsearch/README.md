# Elasticsearch Data Container

This directory contains the configuration for the Elasticsearch data initialization container.

## Overview

The `miserend-elasticsearch-data` image is automatically built weekly from the production Elasticsearch instance and published to `ghcr.io/szentjozsefhackathon/miserend-elasticsearch-data`.

## Weekly Automatic Updates

The `.github/workflows/elasticsearch-data.yaml` workflow:

- **Schedule:** Every Monday at 02:00 UTC (04:00 CET/CEST)
- **Process:**
  1. Connects to the production server via SSH
  2. Exports the Elasticsearch data using snapshots or direct tar export
  3. Builds a new Docker image with the latest data
  4. Pushes the image to GHCR with:
    - Date-based tag (e.g., `2026.06.05`)
    - `latest` tag for automatic updates in production
  5. Creates a GitHub Release for tracking

## Image Usage

The image is used in `docker/compose.yml` as the `data-init` service, which:

1. Runs before the Elasticsearch container
2. Extracts the pre-packaged data into the `elasticsearch_data` volume
3. Sets correct ownership and permissions
4. Exits, allowing Elasticsearch to start with pre-loaded data

## Building Manually

To manually trigger a build:

```bash
gh workflow run .github/workflows/elasticsearch-data.yaml
```

Or use the GitHub Actions UI to trigger with a custom tag.

## Required Secrets

The workflow requires these GitHub secrets for production access:

- `DEPLOY_KEY`: SSH private key for production server access
- `DEPLOY_HOST`: Hostname/IP of the production server
- `DEPLOY_USER`: SSH username for production server

## Troubleshooting

If the workflow fails:

1. Check the workflow logs in GitHub Actions
2. Verify the production server is accessible
3. Ensure Elasticsearch is running on the production server
4. If data export fails, the workflow creates an empty backup to prevent deployment failures

## Files

- `Dockerfile` - Container image definition with fallback handling
- `entrypoint.sh` - Entry script for the Elasticsearch container during startup
- `README.md` - This file
- `initdb.d/` - Initial data files (imported during setup)
- `mappings/` - Elasticsearch index mappings
