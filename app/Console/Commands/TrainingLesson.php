<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TrainingLesson extends Command
{
    protected $signature = 'training:lesson {stage : lesson4, reference or complete}';

    protected $description = '現在の演習対象ファイルをバックアップして、授業用の開始版または確認済み版へ切り替える';

    public function handle(): int
    {
        if (! app()->environment('local', 'testing')) {
            $this->error('研修用のlocal/testing環境だけで使用できます。');

            return self::FAILURE;
        }
        $stage = $this->argument('stage');
        if (! in_array($stage, ['lesson4', 'reference', 'complete'], true)) {
            $this->error('stageにはlesson4・reference・completeを指定してください。');

            return self::FAILURE;
        }
        if ($stage === 'complete') {
            $marker = storage_path('app/training-exercise');
            if (is_file($marker)) {
                unlink($marker);
            }
            $this->info('自分の実装を保持して演習中表示を解除しました。対象テストの結果も記録してください。');

            return self::SUCCESS;
        }
        $name = config('training.topic') === 'license' ? 'LicenseQuery' : 'WorkLogReport';
        $target = app_path('Services/'.$name.'.php');
        $source = base_path('training/'.$stage.'/'.$name.'.php.stub');
        if (! is_file($target) || ! is_file($source)) {
            $this->error('対象ファイルまたは切替元がありません。変更しませんでした。');

            return self::FAILURE;
        }
        $directory = storage_path('app/training-backups');
        if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
            $this->error('バックアップ先を作成できません。変更しませんでした。');

            return self::FAILURE;
        }
        $backup = tempnam($directory, $name.'-');
        if ($backup === false || ! copy($target, $backup)) {
            $this->error('バックアップに失敗したため、変更しませんでした。');

            return self::FAILURE;
        }
        if (! copy($source, $target)) {
            $this->error('切替に失敗しました。元の内容は '.$backup.' に保存されています。');

            return self::FAILURE;
        }
        $marker = storage_path('app/training-exercise');
        if ($stage === 'lesson4') {
            file_put_contents($marker, 'lesson4');
        } elseif (is_file($marker)) {
            unlink($marker);
        }
        $this->info('切替完了：'.$stage);
        $this->line('変更前のファイル：'.$backup);
        $this->line('DB・他のソース・Gitの作業履歴は変更していません。');

        return self::SUCCESS;
    }
}
