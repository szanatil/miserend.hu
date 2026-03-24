#!/usr/bin/env bash

set -euo pipefail

docker compose -f docker/compose.yml -f docker/compose.dev.yml run --rm --entrypoint sh miserend -lc \
  'php vendor/bin/phpunit -c tests/phpunit.xml "$@"' -- "$@"
