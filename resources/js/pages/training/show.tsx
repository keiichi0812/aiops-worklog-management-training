import { Head, Link } from '@inertiajs/react';

export default function TrainingShow({
    title,
    record,
    backUrl,
}: {
    title: string;
    record: Record<string, string | number | boolean | null>;
    backUrl: string;
}) {
    return (
        <>
            <Head title={title} />
            <main className="grid gap-6 p-6">
                <h1 className="text-2xl font-semibold">{title}</h1>
                <dl className="grid gap-3 rounded-lg border p-5">
                    {Object.entries(record).map(([key, value]) => (
                        <div
                            key={key}
                            className="grid gap-1 md:grid-cols-[180px_1fr]"
                        >
                            <dt className="text-muted-foreground">{key}</dt>
                            <dd>{String(value ?? '')}</dd>
                        </div>
                    ))}
                </dl>
                <Link className="underline" href={backUrl}>
                    一覧へ戻る
                </Link>
            </main>
        </>
    );
}
