import { Head, Link } from '@inertiajs/react';
import { login } from '@/routes';
export default function Welcome() {
    return (
        <>
            <Head title="AI開発研修" />
            <main className="mx-auto grid min-h-screen max-w-2xl content-center gap-6 px-6">
                <p className="font-semibold text-muted-foreground">
                    開発部門 AI活用研修
                </p>
                <h1 className="text-3xl font-semibold">
                    業務アプリで開発工程を一周する
                </h1>
                <p>
                    架空データを使い、AIと要件・設計・実装・レビュー・テスト・文書化を進めます。
                </p>
                <Link
                    className="w-fit rounded-md bg-primary px-5 py-3 text-primary-foreground"
                    href={login()}
                >
                    研修用アカウントでログイン
                </Link>
                <p className="text-sm text-muted-foreground">
                    ログイン情報とセットアップ手順は、このリポジトリのREADMEに記載しています。
                </p>
            </main>
        </>
    );
}
