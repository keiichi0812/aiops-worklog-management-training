<?php

$root = dirname(__DIR__, 3);
$path = $root.'/app/Services/LessonTwoDemoService.php';
if (! is_file($path)) {
    echo "デモ対象なし。既存アプリは検査対象にしていません。\n";
    exit(0);
}
$code = file_get_contents($path);
if (preg_match('/function\s+(?!__)[a-zA-Z][a-zA-Z0-9]*_[a-zA-Z0-9_]+\s*\(/', $code)) {
    fwrite(STDERR, "LESSON2_NAMING_BLOCK: メソッド名をcamelCaseにしてください（make_report → makeReport）。\n");
    exit(2);
}
echo "LESSON2_NAMING_PASS: デモ対象の命名規則は合格です。\n";
