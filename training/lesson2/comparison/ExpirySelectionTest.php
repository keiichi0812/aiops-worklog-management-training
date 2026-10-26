<?php

namespace WorkshopTests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Workshop\ExpirySelection;

final class ExpirySelectionTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        $solution = getenv('LESSON2_SOLUTION') ?: __DIR__.'/reference/ExpirySelection.php.stub';
        require_once $solution;
    }

    /** @return iterable<string, array{list<array{id:int,user_id:int,starts_on:string,ends_on:string,stopped:bool}>,list<int>}> */
    public static function cases(): iterable
    {
        $base = ['id' => 10, 'user_id' => 1, 'starts_on' => '2026-10-01', 'ends_on' => '2026-10-27', 'stopped' => false];
        yield '当日を含む' => [[$base], [10]];
        yield '30日後を含む' => [[array_replace($base, ['ends_on' => '2026-11-26'])], [10]];
        yield '31日後を除外' => [[array_replace($base, ['ends_on' => '2026-11-27'])], []];
        yield '期限切れを除外' => [[array_replace($base, ['ends_on' => '2026-10-26'])], []];
        yield '停止中を除外' => [[array_replace($base, ['stopped' => true])], []];
        yield '開始前を除外' => [[array_replace($base, ['starts_on' => '2026-10-28'])], []];
        yield '他人の契約を除外' => [[array_replace($base, ['user_id' => 2])], []];
        yield '終了日とIDの順に並び添字を詰める' => [[
            array_replace($base, ['id' => 30, 'ends_on' => '2026-11-01']),
            array_replace($base, ['id' => 20]),
            array_replace($base, ['id' => 11]),
        ], [11, 20, 30]];
        yield '空配列' => [[], []];
    }

    /** @param list<array{id:int,user_id:int,starts_on:string,ends_on:string,stopped:bool}> $licenses @param list<int> $expected */
    #[DataProvider('cases')]
    public function test_selects_only_the_expected_ids(array $licenses, array $expected): void
    {
        $filter = new ExpirySelection;

        $actual = $filter->select($licenses, 1, '2026-10-27');

        $this->assertSame($expected, $actual);
    }
}
