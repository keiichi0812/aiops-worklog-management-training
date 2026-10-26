#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "${BASH_SOURCE[0]}")/../../.."
for lesson2_solution in reference/ExpirySelection.php.stub examples/without.php.stub examples/with.php.stub; do
    echo "Checking $lesson2_solution"
    docker compose exec -T -e "LESSON2_SOLUTION=/var/www/html/training/lesson2/comparison/$lesson2_solution" php vendor/bin/phpunit --no-configuration training/lesson2/comparison/ExpirySelectionTest.php
done
