import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import type { Auth } from '@/types';

type Field = {
    key: string;
    label: string;
    type: string;
    choices?: Record<string, string>;
    step?: number;
    default?: string | number;
};
type Row = { id: number; [key: string]: string | number | boolean | null };
type Option = { id: number; name: string };
type Props = {
    title: string;
    baseUrl: string;
    fields: Field[];
    columns: Record<string, string>;
    records: {
        data: Row[];
        links: { url: string | null; label: string; active: boolean }[];
    };
    options: Record<string, Option[]>;
    filters: Record<string, string>;
    canManage: boolean;
    summary: {
        label: string;
        count?: number;
        quantity?: number;
        hours?: number;
        cost?: number;
    }[];
    topic?: string;
    today?: string;
    ownRate?: number;
};

const inputClass =
    'w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-xs focus-visible:outline-2 focus-visible:outline-ring';
const buttonClass =
    'rounded-md border border-input px-3 py-2 text-sm font-medium hover:bg-accent disabled:opacity-50';

export default function TrainingIndex({
    title,
    baseUrl,
    fields,
    columns,
    records,
    options,
    filters,
    canManage,
    summary,
    topic,
    today,
    ownRate,
}: Props) {
    const page = usePage<{ auth: Auth; training: { exercise: boolean } }>();
    const { auth } = page.props;
    const [editing, setEditing] = useState<number | null>(null);
    const [selected, setSelected] = useState<Row | null>(null);
    const [search, setSearch] = useState<Record<string, string>>(filters);
    const defaults: Record<string, string> = Object.fromEntries(
        fields.map((field) => [
            field.key,
            String(
                field.default ?? (field.type === 'date' ? (today ?? '') : ''),
            ),
        ]),
    );
    const form = useForm<Record<string, string>>(defaults);

    function edit(row: Row) {
        setSelected(row);
        setEditing(row.id);
        form.clearErrors();
        form.setData(
            Object.fromEntries(
                fields.map((field) => {
                    const value =
                        field.type === 'password' ? '' : row[field.key];
                    return [
                        field.key,
                        typeof value === 'boolean'
                            ? value
                                ? '1'
                                : '0'
                            : String(value ?? ''),
                    ];
                }),
            ),
        );
    }
    function fresh() {
        setEditing(null);
        setSelected(null);
        form.setData(defaults);
        form.clearErrors();
    }
    function save(event: FormEvent) {
        event.preventDefault();
        const opts = { preserveScroll: true, onSuccess: fresh };
        if (editing === null) form.post(baseUrl, opts);
        else form.patch(`${baseUrl}/${editing}`, opts);
    }
    function remove(row: Row) {
        if (window.confirm('このデータを削除しますか？'))
            router.delete(`${baseUrl}/${row.id}`, { preserveScroll: true });
    }
    function filter() {
        router.get(baseUrl, search, {
            preserveState: true,
            preserveScroll: true,
        });
    }
    function filterInput(
        key: string,
        label: string,
        type = 'text',
        choices?: Record<string, string>,
    ) {
        return (
            <label className="grid gap-1 text-sm" key={key}>
                {label}
                {choices ? (
                    <select
                        className={inputClass}
                        value={search[key] ?? ''}
                        onChange={(e) =>
                            setSearch({ ...search, [key]: e.target.value })
                        }
                    >
                        <option value="">すべて</option>
                        {Object.entries(choices).map(([value, text]) => (
                            <option value={value} key={value}>
                                {text}
                            </option>
                        ))}
                    </select>
                ) : (
                    <input
                        className={inputClass}
                        type={type}
                        value={search[key] ?? ''}
                        onChange={(e) =>
                            setSearch({ ...search, [key]: e.target.value })
                        }
                    />
                )}
            </label>
        );
    }
    const optionChoices = (key: string) =>
        Object.fromEntries(
            (options[key] ?? []).map((item) => [String(item.id), item.name]),
        );

    return (
        <>
            <Head title={title} />
            <main className="grid gap-6 p-4 md:p-6">
                {page.props.training.exercise && (
                    <p
                        role="status"
                        className="rounded-lg border border-amber-500 bg-amber-50 p-3 text-amber-950 dark:bg-amber-950 dark:text-amber-100"
                    >
                        第4回の演習開始版です。対象の抽出・計算は未実装で、確認テストが失敗する状態です。
                    </p>
                )}
                <header className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h1 className="text-2xl font-semibold">{title}</h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            架空データによる研修用アプリ ／ {auth.user.name}（
                            {auth.user.role === 'admin'
                                ? '管理者・全体を表示'
                                : '一般ユーザー・本人の業務データのみ'}
                            ）
                        </p>
                    </div>
                    {ownRate !== undefined && (
                        <p className="text-sm">
                            自分の研修用単価：{ownRate.toLocaleString()}円／h
                        </p>
                    )}
                </header>
                <section
                    aria-label="検索と絞り込み"
                    className="flex flex-wrap items-end gap-3 rounded-lg border p-4"
                >
                    {topic !== 'worklog' &&
                        filterInput(
                            'q',
                            topic === 'license'
                                ? '顧客名・管理番号'
                                : '名前の部分一致',
                        )}
                    {topic === 'license' && (
                        <>
                            {filterInput(
                                'product_id',
                                '製品',
                                'text',
                                optionChoices('product_id'),
                            )}
                            {filterInput('status', '状態', 'text', {
                                before: '利用開始前',
                                active: '有効',
                                expired: '期限切れ',
                                stopped: '停止中',
                            })}
                            {filterInput(
                                'ending_from',
                                '終了日（開始）',
                                'date',
                            )}
                            {filterInput('ending_to', '終了日（終了）', 'date')}
                            <label className="flex gap-2 py-2 text-sm">
                                <input
                                    type="checkbox"
                                    checked={
                                        String(search.expiring ?? '') === '1'
                                    }
                                    onChange={(e) =>
                                        setSearch({
                                            ...search,
                                            expiring: e.target.checked
                                                ? '1'
                                                : '0',
                                        })
                                    }
                                />
                                期限30日前〜当日の有効な契約
                            </label>
                        </>
                    )}
                    {topic === 'worklog' && (
                        <>
                            {filterInput(
                                'month',
                                '対象年月（作業日）',
                                'month',
                            )}
                            {filterInput(
                                'project_id',
                                '案件',
                                'text',
                                optionChoices('project_id'),
                            )}
                            {filterInput(
                                'customer_id',
                                '顧客',
                                'text',
                                optionChoices('customer_id'),
                            )}
                            {filterInput('division', '区分', 'text', {
                                共通: '共通',
                                東京: '東京',
                                豊橋: '豊橋',
                            })}
                        </>
                    )}
                    {topic &&
                        auth.user.role === 'admin' &&
                        filterInput(
                            'user_id',
                            '担当者・作業者',
                            'text',
                            optionChoices('user_id'),
                        )}
                    <button className={buttonClass} onClick={filter}>
                        検索
                    </button>
                    <button
                        className={buttonClass}
                        onClick={() => {
                            setSearch({});
                            router.get(baseUrl);
                        }}
                    >
                        条件を解除
                    </button>
                </section>
                {summary.length > 0 && (
                    <section aria-label="検索対象の集計" className="grid gap-3">
                        <h2 className="text-lg font-semibold">
                            検索対象の集計
                        </h2>
                        <div className="grid gap-3 md:grid-cols-3">
                            {summary.map((item) => (
                                <div
                                    className="rounded-lg border bg-muted/30 p-4"
                                    key={item.label}
                                >
                                    <h3 className="font-medium">
                                        {item.label}
                                    </h3>
                                    <p className="mt-2 text-sm">
                                        {item.count !== undefined
                                            ? `${item.count}件 ／ 契約数 ${item.quantity}`
                                            : `${item.hours}h ／ ${item.cost?.toLocaleString()}円`}
                                    </p>
                                </div>
                            ))}
                        </div>
                    </section>
                )}
                <section className="overflow-x-auto rounded-lg border">
                    <table className="w-full text-left text-sm">
                        <thead className="bg-muted/50">
                            <tr>
                                {Object.entries(columns).map(([key, label]) => (
                                    <th
                                        className="px-4 py-3 whitespace-nowrap"
                                        key={key}
                                    >
                                        {label}
                                    </th>
                                ))}
                                <th className="px-4 py-3">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            {records.data.map((row) => (
                                <tr className="border-t" key={row.id}>
                                    {Object.keys(columns).map((key) => (
                                        <td className="px-4 py-3" key={key}>
                                            {String(row[key] ?? '')}
                                        </td>
                                    ))}
                                    <td className="flex flex-wrap gap-2 px-4 py-3">
                                        <button
                                            className={buttonClass}
                                            onClick={() => {
                                                if (canManage) edit(row);
                                                else setSelected(row);
                                            }}
                                        >
                                            {canManage ? '編集・詳細' : '詳細'}
                                        </button>
                                        {topic && (
                                            <Link
                                                className={buttonClass}
                                                href={`${baseUrl}/${row.id}`}
                                            >
                                                詳細ページ
                                            </Link>
                                        )}
                                        {canManage && (
                                            <button
                                                className={buttonClass}
                                                onClick={() => remove(row)}
                                            >
                                                削除
                                            </button>
                                        )}
                                    </td>
                                </tr>
                            ))}
                            {records.data.length === 0 && (
                                <tr>
                                    <td
                                        className="px-4 py-8 text-muted-foreground"
                                        colSpan={
                                            Object.keys(columns).length + 1
                                        }
                                    >
                                        条件に一致するデータはありません。
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </section>
                <nav aria-label="ページ送り" className="flex flex-wrap gap-2">
                    {records.links.map((link, i) =>
                        link.url ? (
                            <Link
                                key={i}
                                href={link.url}
                                className={`${buttonClass} ${link.active ? 'bg-muted' : ''}`}
                            >
                                {link.label
                                    .replace(/&laquo;/g, '«')
                                    .replace(/&raquo;/g, '»')}
                            </Link>
                        ) : (
                            <span
                                key={i}
                                className="px-3 py-2 text-sm text-muted-foreground"
                            >
                                {link.label
                                    .replace(/&laquo;/g, '«')
                                    .replace(/&raquo;/g, '»')}
                            </span>
                        ),
                    )}
                </nav>
                {canManage ? (
                    <section className="rounded-lg border p-4">
                        <div className="mb-4 flex items-center justify-between">
                            <h2 className="text-lg font-semibold">
                                {editing === null
                                    ? '新規登録'
                                    : `編集 #${editing}`}
                            </h2>
                            <button className={buttonClass} onClick={fresh}>
                                新規入力へ戻る
                            </button>
                        </div>
                        <form
                            className="grid gap-4 md:grid-cols-2"
                            onSubmit={save}
                        >
                            {fields.map((field) => (
                                <label
                                    className="grid gap-1 text-sm"
                                    key={field.key}
                                >
                                    {field.label}
                                    {field.type === 'select' ? (
                                        <select
                                            className={inputClass}
                                            value={form.data[field.key] ?? ''}
                                            disabled={
                                                field.key === 'user_id' &&
                                                auth.user.role !== 'admin'
                                            }
                                            onChange={(e) =>
                                                form.setData(
                                                    field.key,
                                                    e.target.value,
                                                )
                                            }
                                        >
                                            <option value="">
                                                選択してください
                                            </option>
                                            {Object.entries(
                                                field.choices ??
                                                    optionChoices(field.key),
                                            ).map(([value, label]) => (
                                                <option
                                                    value={value}
                                                    key={value}
                                                >
                                                    {label}
                                                </option>
                                            ))}
                                        </select>
                                    ) : field.type === 'textarea' ? (
                                        <textarea
                                            className={inputClass}
                                            value={form.data[field.key] ?? ''}
                                            onChange={(e) =>
                                                form.setData(
                                                    field.key,
                                                    e.target.value,
                                                )
                                            }
                                        />
                                    ) : (
                                        <input
                                            className={inputClass}
                                            type={field.type}
                                            step={field.step ?? 1}
                                            autoComplete={
                                                field.type === 'password'
                                                    ? 'new-password'
                                                    : undefined
                                            }
                                            value={form.data[field.key] ?? ''}
                                            onChange={(e) =>
                                                form.setData(
                                                    field.key,
                                                    e.target.value,
                                                )
                                            }
                                        />
                                    )}
                                    {form.errors[field.key] && (
                                        <span className="text-destructive">
                                            {form.errors[field.key]}
                                        </span>
                                    )}
                                </label>
                            ))}
                            <div className="md:col-span-2">
                                <button
                                    className={`${buttonClass} bg-primary text-primary-foreground hover:bg-primary/90`}
                                    disabled={form.processing}
                                >
                                    保存
                                </button>
                                {Object.values(form.errors).length > 0 && (
                                    <p
                                        role="alert"
                                        className="mt-3 text-destructive"
                                    >
                                        {Object.values(form.errors).join(
                                            ' ／ ',
                                        )}
                                    </p>
                                )}
                            </div>
                        </form>
                    </section>
                ) : (
                    selected && (
                        <section className="rounded-lg border p-4">
                            <h2 className="mb-3 text-lg font-semibold">
                                詳細 #{selected.id}
                            </h2>
                            <dl className="grid gap-3">
                                {fields.map((field) => (
                                    <div key={field.key}>
                                        <dt className="text-muted-foreground">
                                            {field.label}
                                        </dt>
                                        <dd>
                                            {String(selected[field.key] ?? '')}
                                        </dd>
                                    </div>
                                ))}
                            </dl>
                        </section>
                    )
                )}
                {page.props.errors?.resource && (
                    <p role="alert" className="text-destructive">
                        {String(page.props.errors.resource)}
                    </p>
                )}
            </main>
        </>
    );
}
