import { Head, Link, usePage } from '@inertiajs/react';
type Training = { title: string; homeUrl: string };

export default function Dashboard() {
    const { training } = usePage<{ training: Training }>().props;
    return (
        <>
            <Head title="研修の案内" />
            <main className="grid max-w-4xl gap-6 p-6">
                <header>
                    <p className="text-sm font-semibold text-muted-foreground">
                        開発部門 AI活用研修
                    </p>
                    <h1 className="mt-2 text-3xl font-semibold">
                        {training.title}の研修用テンプレート
                    </h1>
                </header>
                <section className="grid gap-3 rounded-lg border p-5">
                    <h2 className="text-xl font-semibold">
                        同じ題材で、開発工程を一周する
                    </h2>
                    <p>
                        このアプリは架空データを使用する演習用の土台です。管理者と一般ユーザーを切り替えて、登録・検索・集計と閲覧範囲を確認できます。
                    </p>
                    <p>
                        第2回は指針と設定、第3回は要件と設計、第4回は小さな実装、第5回はテストとCI、第6回は根拠付きの文書化を行います。
                    </p>
                    <p>
                        通し課題の開発作業は原則として研修時間内。提供された部分と、自分が変更・確認した部分を分けて記録してください。
                    </p>
                    <Link
                        className="mt-2 w-fit rounded-md bg-primary px-4 py-2 text-primary-foreground"
                        href={training.homeUrl}
                    >
                        業務画面を開く
                    </Link>
                </section>
                <section className="rounded-lg border p-5">
                    <h2 className="mb-2 font-semibold">開始前の確認</h2>
                    <ul className="list-inside list-disc space-y-2">
                        <li>管理者：admin@example.test</li>
                        <li>
                            一般ユーザー：user-a@example.test ／
                            user-b@example.test
                        </li>
                        <li>初期パスワード：Training!2026（ローカル研修用）</li>
                        <li>単価・顧客・契約・案件はすべて架空の値です。</li>
                    </ul>
                </section>
            </main>
        </>
    );
}
