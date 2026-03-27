#!/usr/bin/env bash

set -euo pipefail

docker compose -f docker/compose.yml -f docker/compose.dev.yml run --rm --entrypoint sh miserend -lc \
  'if [ ! -f vendor/bin/phpunit ]; then composer install --no-interaction --no-progress; fi; php vendor/bin/phpunit -c tests/phpunit.xml "$@"' -- "$@"
