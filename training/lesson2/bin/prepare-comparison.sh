#!/usr/bin/env bash
set -euo pipefail
root="$(cd "$(dirname "${BASH_SOURCE[0]}")/../../.." && pwd)"
lesson2_dir="$(mktemp -d "${TMPDIR:-/tmp}/aiops-lesson2-compare.XXXXXX")"
for kind in without with; do
    mkdir -p "$lesson2_dir/$kind"
    cp "$root/training/lesson2/comparison/start/ExpirySelection.php.stub" "$lesson2_dir/$kind/ExpirySelection.php"
    cp "$root/training/lesson2/comparison/project-spec.txt" "$lesson2_dir/$kind/project-spec.txt"
    cp "$root/training/lesson2/comparison/prompt.txt" "$lesson2_dir/$kind/prompt.txt"
done
cp "$root/training/lesson2/comparison/AGENTS.md" "$lesson2_dir/with/AGENTS.md"
printf '@AGENTS.md\n' > "$lesson2_dir/with/CLAUDE.md"
printf '比較用フォルダ：%s\n' "$lesson2_dir"
printf 'withoutとwithを別の新規Claudeセッションで開き、同じprompt.txtを使ってください。\n'
printf '会社・ユーザー共通の規則は保持します。「なし」はこの教材で追加する指針がない状態です。\n'
