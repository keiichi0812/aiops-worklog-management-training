# Claude Codeで実際に生成した出力：この教材の追加指針なし

Claude Code 2.1.287 / Sonnet / 新規セッション / 同一の依頼・開始ファイル。下記は生成結果です。AIが実行していないテストは、講師が別途実行して照合しています。毎回同じ差が出ることを保証する見本ではありません。

ファイルは変更していません。修正案は次のとおりです。

```php
<?php

namespace Workshop;

final class ExpirySelection
{
    private const WINDOW_DAYS = 30;

    /**
     * @param list<array{id:int,user_id:int,starts_on:string,ends_on:string,stopped:bool}> $licenses
     * @return list<int>
     */
    public function select(array $licenses, int $ownerId, string $today): array
    {
        // 入力は正規化済みのYYYY-MM-DDなので、文字列比較で日付の前後を判定できる。
        $limit = (new \DateTimeImmutable($today, new \DateTimeZone('UTC')))
            ->modify('+' . self::WINDOW_DAYS . ' days')
            ->format('Y-m-d');

        $targets = array_filter(
            $licenses,
            static fn (array $license): bool =>
                $license['user_id'] === $ownerId
                && !$license['stopped']
                && $license['starts_on'] <= $today
                && $license['ends_on'] >= $today
                && $license['ends_on'] <= $limit
        );

        // 終了日の昇順、同じ終了日はIDの昇順。
        usort(
            $targets,
            static fn (array $a, array $b): int =>
                [$a['ends_on'], $a['id']] <=> [$b['ends_on'], $b['id']]
        );

        // usortで添字は0から振り直される。
        return array_map(static fn (array $license): int => $license['id'], $targets);
    }
}
```

**実装のポイント**

- 30日後の日付は `DateTimeImmutable` をUTCで使って計算します。夏時間などの影響を避けるためです。現在時刻には依存しません。
- 基準日当日(`ends_on >= $today`)と30日後(`<= $limit`)は含めます。期限切れ、31日後、開始前(`starts_on > $today`)、停止中、他人の契約は除外します。
- `usort` で並べ替えると添字が0から振り直されます。そのため、返り値は `list<int>` になります。

**確認方法**

1. 構文チェックとして、`php -l ExpirySelection.php` を実行します。
2. 基準日を `2026-10-26`、`ownerId` を `1` として、次のような契約を用意し、`select()` を呼びます。期待値は `[3, 2, 1]` です。

```php
require 'ExpirySelection.php';
$l = fn($id,$u,$s,$e,$st=false)=>['id'=>$id,'user_id'=>$u,'starts_on'=>$s,'ends_on'=>$e,'stopped'=>$st];
$r = (new Workshop\ExpirySelection())->select([
    $l(1, 1, '2026-01-01', '2026-11-25'),        // 30日後: 含む
    $l(2, 1, '2026-01-01', '2026-10-30'),        // 含む
    $l(3, 1, '2026-01-01', '2026-10-26'),        // 当日: 含む
    $l(4, 1, '2026-01-01', '2026-11-26'),        // 31日後: 除外
    $l(5, 1, '2026-01-01', '2026-10-25'),        // 期限切れ: 除外
    $l(6, 1, '2026-10-27', '2026-11-01'),        // 開始前: 除外
    $l(7, 2, '2026-01-01', '2026-10-30'),        // 他人: 除外
    $l(8, 1, '2026-01-01', '2026-10-30', true),  // 停止中: 除外
], 1, '2026-10-26');
var_dump($r === [3, 2, 1]);
```

3. 同じ終了日のときにIDの昇順になることも確認します。たとえば `id=20` と `id=10` を同じ `ends_on` で入れ、`[10, 20]` の順になることを見ます。
4. 月またぎや年またぎ(基準日 `2026-12-15` なら上限は `2027-01-14`)と、うるう日(基準日 `2028-02-10`)も確認します。
5. 該当なしのときは `[]` が返ることを確認します。

このセッションでは PHP を実行していません。上の確認は未実施です。
