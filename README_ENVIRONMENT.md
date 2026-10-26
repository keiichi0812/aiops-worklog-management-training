# 研修用Docker環境

題材・起動手順・架空アカウント・授業用の切替手順はREADME.mdを参照してください。

## 前提

Docker DesktopまたはDocker EngineとCompose v2、Git、ターミナルを使用します。
WindowsはWSL2のLinuxファイルシステム上へcloneし、そのターミナルから実行します。
ホストへのPHP・Composer・Nodeのインストールは不要です。
初回はDockerイメージ、Composer、npmのダウンロード先へ接続できる必要があります。
会社PCへのDocker導入・ネットワーク・AI接続は会社承認の構成を利用してください。

## 起動

```bash
./bin/setup.sh
```

.envがなければ.env.exampleから作成します。APP_KEYは利用者の環境で生成し、架空データをMigrationとSeederで投入します。
セットアップは既存テーブルを削除しません。データを保存したまま再起動する場合は次を使います。

```bash
docker compose up -d
docker compose ps
```

| サービス | 用途・接続先                                       |
| -------- | -------------------------------------------------- |
| nginx    | アプリ。ホスト側の初期ポート：8082                 |
| php      | PHP-FPM、Composer、Node。ソースをマウント          |
| mysql    | MySQL 8.4。コンテナ内はmysql:3306、ホスト側は33062 |
| mailpit  | 送信メールの確認。ホスト側の初期ポート：8027       |
| node     | 任意のVite HMR。初期ポート：5175                   |

ブラウザのアプリURLは.envのAPP_URLです。自己登録は無効で、READMEの架空アカウントでログインします。
DBはComposeプロジェクトごとのmysql_dataボリュームに保持します。受講者それぞれのローカル環境で独立します。
公開ポートは127.0.0.1へバインドされます。

## 開発と確認

```bash
docker compose exec -T php php artisan test --compact
docker compose exec -T php composer ci:check
docker compose exec -T php npm run build
docker compose logs --tail=100 php nginx mysql
```

PHP・Composer・npmはphpコンテナ内で実行します。DB_HOSTはmysqlです。
resources/js/actions・routes・wayfinderは自動生成されるため、手で変更しません。
通常はセットアップ時のビルド済み画面を使います。HMRが必要なときだけ次を実行します。

```bash
docker compose --profile dev up -d node
```

画面は引き続きAPP_URLで開きます。phpコンテナでnpm run devを直接起動しないでください。
終了時は次を使います。DBボリュームは保持されます。

```bash
docker compose --profile dev down
```

## AI向けの共通ファイル

AGENTS.mdに共通指針、.agents/skillsに既存の開発Skillを置いています。
CLAUDE.mdはAGENTS.mdを参照し、.claude/skillsにも同内容を同梱しています。
.mcp.jsonは相対的なdocker composeコマンドでLaravel Boostを起動します。先にコンテナと依存を準備してください。
個人のAI接続情報や会社の資格情報は同梱していません。授業で作るpermissions・Hooks等の設定は講師の案内に従ってください。

## 困ったとき

- ポート競合：.envのAPP_PORTとAPP_URL、必要なVITE_PORT・MYSQL_EXPOSE_PORT・MAILPIT_PORTを変更して再起動します。
- 同じ題材を別フォルダでも同時起動する場合：COMPOSE_PROJECT_NAMEとポートも別にします。
- DB接続エラー：docker compose psでmysqlがhealthyか確認します。コンテナ内からlocalhostには接続しません。
- 502：phpの起動状態とログを確認します。
- 画面アセットのエラー：npm run buildをコンテナ内で実行します。HMRを使わない場合はnodeを含めて停止して再起動します。
- 実行権限エラー：リポジトリのルートでbash bin/setup.shを実行できます。
- 依存取得のエラー：社内プロキシ・許可済みレジストリ・Dockerのネットワークを技術窓口へ確認します。

.env、DBファイル、ログ、vendor、node_modules、個人AI設定はリポジトリへ追加しません。
