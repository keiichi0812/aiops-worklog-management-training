#!/usr/bin/env bash
set -uo pipefail
cd "${CLAUDE_PROJECT_DIR:?プロジェクトルートでClaudeを開始してください}" || exit 2
docker compose exec -T php php training/lesson2/hooks/guard.php
lesson2_status=$?
if [ "$lesson2_status" -ne 0 ] && [ "$lesson2_status" -ne 2 ]; then
    echo "LESSON2_HOOK_ERROR: Dockerまたは検査処理が失敗しました。検証未完了のため停止します。" >&2
    exit 2
fi
exit "$lesson2_status"
