# Claude Codeで実際に生成した出力：指針あり

Claude Code 2.1.287 / Sonnet / 新規セッション / 同一の依頼・開始ファイル。下記は生成結果です。AIが実行していないテストは、講師が別途実行して照合しています。毎回同じ差が出ることを保証する見本ではありません。

## 変更前の整理

- **対象ファイル**: `ExpirySelection.php`。変更するのは `select` の中身だけで、シグネチャは保持します。
- **理由**: TODO のまま `[]` を返しているためです。
- **確認方法**: 下の境界値ケースを手元で実行します。今回は何も実行していません。

## 仮定

- 「利用開始済み」は `starts_on <= 基準日` とし、開始日当日は対象にします。
- 日付は正規化済みの `YYYY-MM-DD` なので、文字列比較で日付順になります。
- 30日後の算出は `DateTimeImmutable` を UTC で使います。現在時刻やタイムゾーンには依存しません。
- 入力の検証は追加しません。

## 修正案(クラス全体)

```php
<?php

namespace Workshop;

use DateTimeImmutable;
use DateTimeZone;

final class ExpirySelection
{
    /**
     * @param list<array{id:int,user_id:int,starts_on:string,ends_on:string,stopped:bool}> $licenses
     * @return list<int>
     */
    public function select(array $licenses, int $ownerId, string $today): array
    {
        $limit = (new DateTimeImmutable($today, new DateTimeZone('UTC')))
            ->modify('+30 days')
            ->format('Y-m-d');

        $selected = [];
        foreach ($licenses as $license) {
            if ($license['user_id'] !== $ownerId || $license['stopped']) {
                continue;
            }
            if ($license['starts_on'] > $today) {
                continue;
            }
            if ($license['ends_on'] < $today || $license['ends_on'] > $limit) {
                continue;
            }
            $selected[] = $license;
        }

        usort(
            $selected,
            static fn (array $a, array $b): int =>
                [$a['ends_on'], $a['id']] <=> [$b['ends_on'], $b['id']]
        );

        return array_map(static fn (array $l): int => $l['id'], $selected);
    }
}
```

## 確認方法

基準日 `2026-10-26`、本人 ID を 1 として、次のケースを用意します。

| ケース         | ends_on      | 他の条件              | 期待       |
| -------------- | ------------ | --------------------- | ---------- |
| 当日           | 2026-10-26   | -                     | 含む       |
| 30日後         | 2026-11-25   | -                     | 含む       |
| 31日後         | 2026-11-26   | -                     | 除く       |
| 期限切れ       | 2026-10-25   | -                     | 除く       |
| 停止中         | 範囲内       | stopped=true          | 除く       |
| 開始前         | 範囲内       | starts_on=2026-10-27  | 除く       |
| 開始日が当日   | 範囲内       | starts_on=2026-10-26  | 含む       |
| 他人の契約     | 範囲内       | user_id=2             | 除く       |
| 同日の並び順   | 同じ終了日   | ID を 9, 3 の順で入力 | `[3, 9]`   |
| 終了日の並び順 | 異なる終了日 | 遅い順で入力          | 昇順で返る |
| 該当なし       | -            | -                     | `[]`       |

返り値の添字が 0 から連番になっていることも、`array_is_list` で確認します。

## 報告

- **変更**: ファイルは変更していません。上記は提案のみです。
- **確認したこと**: `project-spec.txt` と `ExpirySelection.php` を読み、仕様とコードの対応を机上で確認しました。
- **未実行・未確認**: PHP の構文チェックとテストは実行していません。上の表のケースも未実行です。
