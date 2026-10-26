<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Project;
use App\Models\User;
use App\Models\WorkLog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('Training!2026');
        $users = [];
        foreach ([['admin', '管理者', 'admin'], ['user-a', '受講者A', 'user'], ['user-b', '受講者B', 'user']] as [$key, $name, $role]) {
            $users[$key] = User::firstOrNew(['email' => $key.'@example.test']);
            if (! $users[$key]->exists) {
                $users[$key]->forceFill(['name' => $name, 'password' => $password, 'role' => $role, 'email_verified_at' => now()])->save();
            }
        }

        foreach ([['WI', '自社'], ['ALP', 'アルファ商事'], ['BTA', 'ベータ製作所']] as [$code, $name]) {
            Customer::firstOrCreate(['code' => $code], ['name' => $name]);
        }
        $projects = [];
        foreach ([['WI-PM', 'PM業務', 'WI', '共通'], ['WI-STUDY', '学習・教育', 'WI', '共通'], ['ALP-WEB', 'Webサイト改修', 'ALP', '東京'], ['ALP-MAINT', '保守・運用', 'ALP', '東京'], ['BTA-APP', '業務アプリ開発', 'BTA', '豊橋'], ['BTA-SUPPORT', 'サポート対応', 'BTA', '豊橋']] as [$code, $name, $customerCode, $division]) {
            $projects[$code] = Project::firstOrCreate(['code' => $code], ['name' => $name, 'customer_id' => Customer::where('code', $customerCode)->firstOrFail()->id, 'division' => $division]);
        }
        foreach ([['user-a', 'ALP-WEB', 4, today(), '画面の入力チェック修正'], ['user-b', 'ALP-WEB', 3, today(), '他人の実績・権限確認'], ['user-a', 'WI-STUDY', 1, today()->startOfMonth(), '月初の学習'], ['user-a', 'ALP-MAINT', 2, today()->startOfMonth()->subDay(), '前月末の実績'], ['user-a', 'BTA-APP', 2, today()->startOfMonth()->addMonth(), '翌月初の実績'], ['user-a', 'ALP-WEB', 2, today()->subYear(), '前年同月の実績']] as [$key, $project, $halfHours, $date, $description]) {
            WorkLog::firstOrCreate(['user_id' => User::where('email', $key.'@example.test')->firstOrFail()->id, 'project_id' => Project::where('code', $project)->firstOrFail()->id, 'description' => $description], ['worked_on' => $date, 'half_hours' => $halfHours]);
        }

    }
}
