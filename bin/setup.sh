#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

if ! docker compose version >/dev/null 2>&1; then
    echo "Docker and Docker Compose v2 are required. Install Docker Desktop / Docker Engine first." >&2
    exit 1
fi

if [ ! -f .env ]; then
    cp .env.example .env
    echo "Created .env from .env.example"
fi

# Run containers as the host user so generated files stay editable on Linux/WSL2.
grep -q '^HOST_UID=' .env || printf 'HOST_UID=%s\nHOST_GID=%s\n' "$(id -u)" "$(id -g)" >> .env

echo "[1/5] Building the PHP image..."
docker compose build php

echo "[2/5] Starting containers (nginx / PHP-FPM / MySQL / Mailpit)..."
docker compose up -d

echo "[3/5] Installing PHP dependencies..."
docker compose exec -T php composer install --no-interaction
grep -q '^APP_KEY=.\+' .env || docker compose exec -T php php artisan key:generate

echo "[4/5] Migrating the database..."
docker compose exec -T php php artisan migrate --seed --force

echo "[5/5] Installing frontend dependencies and building assets..."
docker compose exec -T php npm ci
docker compose exec -T php npm run build

port() { grep "^$1=" .env | cut -d= -f2; }
echo
echo "Setup complete."
echo "  App:     http://localhost:$(port APP_PORT)"
echo "  Mailpit: http://localhost:$(port MAILPIT_PORT)"
echo
echo "Vite HMR: docker compose --profile dev up -d node"
echo "Stop:     docker compose --profile dev down"
