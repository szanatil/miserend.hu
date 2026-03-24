#!/usr/bin/env bash

set -euo pipefail

# Some WSL2 / Docker Desktop setups do not have the buildx plugin available.
# Force the legacy builder to avoid requiring buildx.
export DOCKER_BUILDKIT=0
export COMPOSE_DOCKER_CLI_BUILD=0

docker compose -f docker/compose.yml -f docker/compose.dev.yml -f docker/compose.coverage.yml build miserend

docker compose -f docker/compose.yml -f docker/compose.dev.yml -f docker/compose.coverage.yml run --rm --entrypoint sh -u root miserend -lc \
  'mkdir -p tests/coverage/html \
    && chown -R www-data:www-data tests/coverage \
    && su -s /bin/sh www-data -c "php vendor/bin/phpunit -c tests/phpunit.xml --coverage-html tests/coverage/html $*"' -- "$@"
