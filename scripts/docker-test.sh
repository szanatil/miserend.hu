#!/usr/bin/env bash

set -euo pipefail

COMPOSE=(docker compose -f docker/compose.yml -f docker/compose.dev.yml)

PHPUNIT_CMD='if [ ! -f vendor/bin/phpunit ]; then composer install --no-interaction --no-progress; fi; php vendor/bin/phpunit -c tests/phpunit.xml "$@"'

# Néhány integrációs teszt valódi HTTP-hívást intéz az apphoz, ezért futó webszerver kell.
# Ha a stack áll, a CI-hoz hasonlóan a futó konténerben futtatunk — ott a 127.0.0.1:8000 él.
# Különben egyszer használatos konténert indítunk (abban nincs Apache), és a compose-hálózaton
# lévő app-konténert adjuk meg címként.
if [ -n "$("${COMPOSE[@]}" ps --status running -q miserend 2>/dev/null)" ]; then
  "${COMPOSE[@]}" exec -T miserend sh -lc "$PHPUNIT_CMD" -- "$@"
else
  "${COMPOSE[@]}" run --rm --entrypoint sh \
    -e PANTHER_EXTERNAL_BASE_URI=http://miserend:8000 \
    miserend -lc "$PHPUNIT_CMD" -- "$@"
fi
