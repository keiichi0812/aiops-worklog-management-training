#!/usr/bin/env bash
# Regenerate Laravel Boost files (AGENTS.md, skills, MCP configs) so they call
# PHP/Composer/npm through Docker (PHP is not required on the host).
# Usage: ./bin/boost.sh [update|install]   (default: update)
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

EXEC="docker compose exec -T php"

# Only set while generating files: inside the container Boost must keep using the real php binary.
docker compose exec -T \
    -e BOOST_PHP_EXECUTABLE_PATH="$EXEC php" \
    -e BOOST_COMPOSER_EXECUTABLE_PATH="$EXEC composer" \
    -e BOOST_NPM_EXECUTABLE_PATH="$EXEC npm" \
    -e BOOST_VENDOR_BIN_EXECUTABLE_PATH="$EXEC vendor/bin/" \
    php php artisan "boost:${1:-update}" --no-interaction

# Share one set of guidelines/skills between Claude Code and Codex.
ln -sfn AGENTS.md CLAUDE.md
if [ -d .agents/skills ] && [ ! -L .claude/skills ]; then
    rm -rf .claude/skills
    mkdir -p .claude
    ln -s ../.agents/skills .claude/skills
fi
