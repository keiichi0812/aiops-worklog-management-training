<?php

$root = dirname(__DIR__, 2);
$stage = $argv[1] ?? 'bad';
if (! in_array($stage, ['bad', 'good', 'cleanup'], true)) {
    fwrite(STDERR, "指定はbad・good・cleanupです。\n");
    exit(1);
}
$target = $root.'/app/Services/LessonTwoDemoService.php';
$runtime = __DIR__.'/runtime';
if (! is_dir($runtime)) {
    mkdir($runtime, 0775, true);
}
if (is_file($target)) {
    $current = file_get_contents($target);
    if (! str_contains($current, 'LESSON2_OWNED_FIXTURE')) {
        fwrite(STDERR, "既存の本人ファイルは変更しません。別名に保存して確認してください。\n");
        exit(1);
    }
    $backup = tempnam($runtime, 'before-'.$stage.'-');
    copy($target, $backup);
}
if ($stage === 'cleanup') {
    if (is_file($target)) {
        $known = false;
        foreach (['bad', 'good'] as $name) {
            if (file_get_contents($target) === file_get_contents(__DIR__.'/fixtures/LessonTwoDemoService.'.$name.'.php.stub')) {
                $known = true;
            }
        }
        if (! $known) {
            fwrite(STDERR, "本人が変更した内容を保持しました。自動削除しません。\n");
            exit(1);
        }
        unlink($target);
    }
    echo "デモの既知ファイルだけを片付けました。Gitのステージや本人の差分は変更しません。\n";
    exit(0);
}
copy(__DIR__.'/fixtures/LessonTwoDemoService.'.$stage.'.php.stub', $target);
file_put_contents($runtime.'/dummy-secret.txt', "LESSON2_DUMMY_ONLY_NOT_A_REAL_SECRET\n");
echo 'デモ状態：'.$stage."。ダミー秘密ファイルと対象PHPを準備しました。既存の対象はruntimeに退避しています。\n";
