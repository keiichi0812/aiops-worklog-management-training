<?php

$payload = json_decode(stream_get_contents(STDIN), true, 512, JSON_THROW_ON_ERROR);
$root = dirname(__DIR__, 3);
$event = $payload['hook_event_name'] ?? '';
$tool = $payload['tool_name'] ?? '';
if ($event === 'PreToolUse' && $tool === 'Bash') {
    $command = $payload['tool_input']['command'] ?? '';
    if (preg_match('/\bgit\s+commit\b/', $command)) {
        passthru(escapeshellarg(PHP_BINARY).' '.escapeshellarg(__DIR__.'/check-naming.php'), $status);
        exit($status);
    }
}
if ($event === 'PostToolUse' && in_array($tool, ['Write', 'Edit'], true)) {
    $path = str_replace('\\', '/', $payload['tool_input']['file_path'] ?? '');
    // Host and container paths differ; the allowlisted relative suffix is identical.
    if (str_ends_with($path, '/app/Services/LessonTwoDemoService.php') || $path === 'app/Services/LessonTwoDemoService.php') {
        $file = $root.'/app/Services/LessonTwoDemoService.php';
        if (is_file($file)) {
            exec(escapeshellarg(PHP_BINARY).' -l '.escapeshellarg($file).' 2>&1', $lines, $status);
            if ($status !== 0) {
                fwrite(STDERR, "LESSON2_SYNTAX_FAIL: 編集後の構文エラーです。編集自体は実行済みです。\n");
                exit(2);
            }
            echo json_encode(['hookSpecificOutput' => ['hookEventName' => 'PostToolUse', 'additionalContext' => 'LESSON2_SYNTAX_PASS: 編集後のPHP構文確認が成功しました。業務テストは別途実行してください。']], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR).PHP_EOL;
        }
    }
}
