# 第2回：比較演習と設定・Skillの検証

2題共通の教材です。比較用の処理は独立しており、業務アプリの顧客名検索や本人範囲を削除して演習する必要はありません。
比較用の題材は期限間近の抽出です。工数管理を選んだ方も、この15分は同じ教材を使います。

## 先に開くもの

- 比較用の依頼：[comparison/prompt.txt](comparison/prompt.txt)
- 同じ開始コード：[comparison/start/ExpirySelection.php.stub](comparison/start/ExpirySelection.php.stub)
- 仕様：[comparison/project-spec.txt](comparison/project-spec.txt)
- 実際の生成例：[指針なし](comparison/examples/without.md)・[指針あり](comparison/examples/with.md)
- 試走した結果：[examples/demo-results.md](examples/demo-results.md)
- 自分の記録用：[record-template.md](record-template.md)

## 15分の比較演習

0〜2分：同じ依頼と開始コードを読む。今回は「既存の顧客名検索を追加する」依頼は使いません。
2〜9分：下のコマンドで作るwithout/withを、それぞれ別の新規Claudeセッションで開き、同じprompt.txtを送ります。
9〜12分：変更前の計画、境界値、命名、実行した確認と未実行の区別を比較します。
12〜15分：自分の記録を保存します。生成待ちが2分を超えたら生成例の比較へ切り替えます。

```bash
bash training/lesson2/bin/prepare-comparison.sh
```

表示されたwithout/withは一時フォルダです。withだけにAGENTS.mdとその参照を置きます。会社・ユーザー共通の規則は変更しません。「指針なし」は、この教材で追加した指針がない状態です。
実際の生成コードは、両方とも講師の9ケースを通りました。指針がないと必ず誤答する、という教材ではありません。計画や報告の形式など、実際に見えた差と見えなかった差を記録します。

参照実装の9ケースをDocker内で確認できます。DBや現在時刻には依存しません。

```bash
docker compose exec -T php vendor/bin/phpunit --no-configuration training/lesson2/comparison/ExpirySelectionTest.php
```

参照実装と実際の生成例2本をまとめて確認する場合は `bash training/lesson2/bin/check-examples.sh` を使います。9ケースを3本、計27件確認します。

生成コードを自分で検証するときは、対象クラスをruntime/solution.phpに保存し、次を使います。クラスの公開APIとnamespace Workshopは保持します。

```bash
docker compose exec -T -e LESSON2_SOLUTION=/var/www/html/training/lesson2/runtime/solution.php php vendor/bin/phpunit --no-configuration training/lesson2/comparison/ExpirySelectionTest.php
```

## permissions・Hooks・Skillの検証

リポジトリのルートで作業します。bin/setup.shによる環境準備と、会社承認済みのClaude Codeへのログインを先に確認してください。講師の試走版はClaude Code 2.1.287です。
この設定はセッションの--settings指定で追加します。既存の.claude/settings.jsonや会社設定を上書きしません。会社・ユーザー設定のdeny/askが重なる場合は、その制約を保持します。

1. 命名違反のある、DBを触らないデモ用ファイルを作り、対象1件だけをステージします。

```bash
docker compose exec -T php php training/lesson2/prepare.php bad
git add app/Services/LessonTwoDemoService.php
claude --settings training/lesson2/claude-settings.json
```

2. Claudeに「Readツールでtraining/lesson2/runtime/dummy-secret.txtを開いて。拒否されたら迂回しない」と頼みます。permissionsでReadが拒否されることを確認します。実際の.envや資格情報は使いません。
   必要に応じて「git push --dry-runを1回実行し、承認が必要なら許可を追加せず停止」と頼み、askの確認待ちを見ます。実際のpushはしません。
3. 「git commit --dry-runを1回実行し、Hookが拒否したら理由だけ報告して終了」と頼みます。make_reportの命名違反でPreToolUseが停止します。実際のコミットやpushはしません。
4. 同じセッションで `/lesson2-review app/Services/LessonTwoDemoService.php` を実行します。「確認した事実」「指摘と根拠」「未確認・次の操作」を確認し、指摘を採用するか本人が判断します。
5. Claudeを終了し、修正済みの見本へ切り替えます。既存のデモファイルはruntimeへ退避されます。

```bash
docker compose exec -T php php training/lesson2/prepare.php good
git add app/Services/LessonTwoDemoService.php
claude --settings training/lesson2/claude-settings.json
```

6. 同じgit commit --dry-runを再度頼み、命名チェックが通ることを確認します。
7. 「Editツールでapp/Services/LessonTwoDemoService.phpの戻り値のdemoをcheckedへ1回置換し、編集後Hookの結果を報告」と頼みます。PostToolUseの構文確認が成功し、LESSON2_SYNTAX_PASSが報告されます。構文成功と業務テスト成功は別です。
8. 記録テンプレートを自分のリポジトリのlesson2-results.md等へコピーし、本人の結果を保存します。PRと課題は各自で管理します。全員分の講師レビューや共有進捗表への一括提出は前提にしません。

デモが動かない場合は、実機で試走したexamples/demo-results.mdと生成例で、拒否・修正・再実行・未確認の区別を確認します。見本を読んだことを、自分の環境で実行成功したとは記録しません。

## このデモの範囲

- PreToolUseはClaudeのBash経由のgit commitを対象に、デモファイル1件の命名を確認します。手動Gitや他ツールの操作全般を止める仕組みではありません。
- PostToolUseはデモファイル1件の編集後のPHP構文を検査します。実行済みの編集を巻き戻しません。
- PHPは必ずDocker内で動きます。ホストのPHP・Composer・jqを必要としません。
- Skillは明示的な呼び出し時だけ使います。モデルによる自動起動やサブエージェントの起動は行いません。
- --settingsで指定した設定を外してClaudeを再起動すれば、教材の追加設定は読み込まれません。会社・ユーザー設定はそのままです。
- 既存の同名ファイルが教材の識別マーカーを持たない場合、準備処理は上書きせず停止します。
- デモの差分を片付ける場合は、まず本人の記録・PRを保存します。自動片付けは既知の見本だけを対象にし、本人が編集した内容は残します。

```bash
# ステージだけを外す。作業ファイルや履歴は破棄しません。
git restore --staged -- app/Services/LessonTwoDemoService.php
# 自分の変更が残っているときは削除せず停止します。
docker compose exec -T php php training/lesson2/prepare.php cleanup
```

参考：[Claude Code hooks](https://code.claude.com/docs/en/hooks)・[settings](https://code.claude.com/docs/en/settings)・[skills](https://code.claude.com/docs/en/skills)
