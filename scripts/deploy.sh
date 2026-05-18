#!/usr/bin/env bash
# Déploiement vers le VPS — pattern identique à dim89.
# Pré-requis :
#  - alias SSH "vps" configuré dans ~/.ssh/config pointant sur le serveur cible
#  - réseau Docker externe "web" déjà créé côté VPS (`docker network create web`)
#  - reverse-proxy externe (Caddy/Traefik) sur le réseau "web" qui route
#    sutom-commune.com vers le container nginx exposé par compose.prod.yaml
#  - .env.prod (non versionné) présent côté VPS dans REMOTE_BASE, dérivé de
#    .env.prod.example (APP_SECRET généré, mot de passe DB fort, VITE_UMAMI_WEBSITE_ID rempli…)
set -euo pipefail

REMOTE_HOST="vps"
REMOTE_BASE="/opt/projects/sutom-commune"

rsync -az --delete \
  --exclude 'vendor/' \
  --exclude 'node_modules/' \
  --exclude 'var/' \
  --exclude 'frontend/dist/' \
  --exclude '.git/' \
  --exclude '.env' \
  --exclude '.env.local' \
  --exclude '.env.prod' \
  --exclude '.env.prod.local' \
  ./ "${REMOTE_HOST}:${REMOTE_BASE}/"

ssh "${REMOTE_HOST}" "cd ${REMOTE_BASE} && docker compose -f compose.prod.yaml --env-file .env.prod build && docker compose -f compose.prod.yaml --env-file .env.prod up -d"

ssh "${REMOTE_HOST}" "cd ${REMOTE_BASE} && docker compose -f compose.prod.yaml --env-file .env.prod exec -T backend php bin/console doctrine:migrations:migrate --no-interaction"

ssh "${REMOTE_HOST}" "cd ${REMOTE_BASE} && docker compose -f compose.prod.yaml --env-file .env.prod exec -T backend php bin/console cache:clear --env=prod --no-warmup"

ssh "${REMOTE_HOST}" "cd ${REMOTE_BASE} && docker compose -f compose.prod.yaml --env-file .env.prod exec -T backend php bin/console cache:warmup --env=prod"

ssh "${REMOTE_HOST}" "cd ${REMOTE_BASE} && docker compose -f compose.prod.yaml --env-file .env.prod ps"
