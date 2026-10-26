# 工数管理 — 開発部門AI研修用テンプレート

Laravel 13 / React 19 / Inertia 3 / PHP 8.4 / MySQL 8.4を使用する、架空データ専用の教材です。
初期配布は、業務画面とテストが動作する確認済み版です。第4回の対象処理だけを演習開始版へ切り替えられます。

先方指定の[webimpact-dev/laravel-react-ai-training](https://github.com/webimpact-dev/laravel-react-ai-training)（基準コミット：`6ad5aef17b6084f4ca8a5b3caccf6bb90b31a563`）をベースに、題材の業務機能・架空データ・研修用設定とテストを追加しています。

## 1. 自分用リポジトリを作る

講師が用意した2題のうち、取り組む方を1つ選びます。GitHubで「Use this template」から自分用の**非公開**リポジトリを作成し、ローカルへcloneしてください。
ZIPで受け取った場合は展開し、フォルダ全体をリポジトリのルートへ配置します。公開設定にしないでください。
本リポジトリはパブリックの配布用テンプレートです。閲覧・取得のための個別招待は不要です。本人用の複製先は、社内ルールに従い非公開にしてください。

## 2. セットアップ

必要なものはDocker Desktop、Git、ターミナルです。WindowsではWSL2のLinux側で作業します。PHP・Composer・Nodeをホストへ入れる必要はありません。

```bash
./bin/setup.sh
```

このスクリプトがコンテナ起動、依存のインストール、キー生成、Migration、架空データ投入、画面ビルドを行います。
初回はネット接続とダウンロード時間が必要です。既存データを消す初期化は実行しません。再実行してもサンプルを重複追加しません。
ポート番号は`.env.example`に記載しています。競合時は`.env`のAPP_PORT・APP_URL等を変更してください。

## 3. ログイン

アプリのURLは`.env`のAPP_URLを参照してください。初期値のアプリ用ポートは8082です。

| 用途          | メール              | パスワード    |
| ------------- | ------------------- | ------------- |
| 管理者        | admin@example.test  | Training!2026 |
| 一般ユーザーA | user-a@example.test | Training!2026 |
| 一般ユーザーB | user-b@example.test | Training!2026 |

上記はローカル研修用の架空アカウントです。自己登録は無効です。アカウントは管理者が作成します。
一般ユーザーは自分の業務データだけを扱い、管理者は全体・マスタ・アカウントを扱います。
メール送信はMailpitへ捕捉されます。実在する顧客・給与・APIキー等は入れないでください。

## 4. 土台に含まれる機能

- 管理者と一般ユーザー、アカウント管理、マスタの登録・編集・削除と削除制約
- 業務データの一覧・詳細・登録・編集・削除・検索・集計
- サーバー側の入力検証と本人範囲の制御
- Migration、Factory、繰り返し投入可能なSeeder、業務のFeatureテスト、CI設定
- Asia/Tokyoでの日付判定、架空ユーザー2名と境界値を含むデータ

工数は案件と作業日、0.5時間刻みの時間を登録し、作業日の年・月で集計します。時間は内部で半時間単位の整数として保持し、固定の研修単価から概算コストを計算します。工数登録後は単価変更を拒否します。

入力の保存上限は契約数・時間・単価とも100万、文字列は画面に対応する保存上限を設定しています。業務上の時間上限・未来日禁止等は今回の土台で追加していません。
発展機能（通知・タイマー・勤怠等）は含みません。

## 5. 開発・確認コマンド

```bash
docker compose exec -T php php artisan test --compact
docker compose exec -T php composer test
docker compose exec -T php npm run types:check
docker compose exec -T php npm run check
docker compose exec -T php npm run build
```

PHP・npmコマンドはコンテナ内で実行します。通常の画面確認にはビルド済みアセットを使います。
HMRを使う場合だけ`docker compose --profile dev up -d node`を実行します。終了は`docker compose --profile dev down`です。DBボリューム削除は不要です。
AIの接続情報は個人の承認済み環境で設定し、リポジトリへ保存しません。MCPは`.mcp.json`の相対的なDockerコマンドを使用します。

## 6. 授業中の対象処理の切替

本人が変更する対象は`app/Services/WorkLogReport.php`の小さな処理です。

```bash
# 第4回の未実装状態へ切替（講師の案内があるときだけ）
docker compose exec -T php php artisan training:lesson lesson4

# 自分の実装でテストを通した後、コードを保持して演習中表示を解除
docker compose exec -T php php artisan training:lesson complete

# 確認済みの動作へ切替
docker compose exec -T php php artisan training:lesson reference
```

lesson4・referenceは変更前の対象ファイルを`storage/app/training-backups/`へ自動保存します。DB・他のコード・Git履歴は変更しません。
演習開始版では対象テストが失敗し、画面に演習中の表示が出ます。未実装部分の出力を正しい業務結果として扱わないでください。
自分の修正で完了させる場合は対象テストが通ることを確認し、講師の指示でcompleteを実行します。`reference`は自分の修正をバックアップしたうえで見本へ置き換える操作です。

研修時間外に機能実装を進める課題はありません。本人の変更と、提供版で確認した部分を分けて記録してください。

## 7. GitHub Actionsと配布時の注意

CIはDraftではないPRで実行します。`composer ci:check`で画面側チェック・型・PHP整形・解析・テストを行います。
CIのDBはSQLite、開発時はMySQLです。依存はlockファイルで固定しています。
`.env`、vendor、node_modules、DBファイル、ログ、個人のAI設定は配布しません。環境の詳細は[README_ENVIRONMENT.md](README_ENVIRONMENT.md)を参照してください。
