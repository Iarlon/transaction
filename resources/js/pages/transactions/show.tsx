import { Head, Link } from '@inertiajs/react';
import {
    ArrowLeft,
    CalendarClock,
    Download,
    FileText,
    Pencil,
    ReceiptText,
    UserRound,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { formatCpf, formatCurrency, formatDateTime } from '@/lib/transaction';
import type { TransactionDetail, TransactionStatus } from '@/types';

const statusClasses: Record<TransactionStatus, string> = {
    processing:
        'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300',
    approved:
        'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300',
    denied: 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300',
};

export default function ShowTransaction({
    transaction,
}: {
    transaction: TransactionDetail;
}) {
    return (
        <>
            <Head title={`Transação #${transaction.id}`} />
            <main className="min-h-full bg-slate-50/70 p-4 sm:p-6 lg:p-8 dark:bg-slate-950/40">
                <div className="mx-auto max-w-4xl space-y-6">
                    <header className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <Button
                                variant="ghost"
                                size="sm"
                                asChild
                                className="-ml-3"
                            >
                                <Link href="/transactions">
                                    <ArrowLeft /> Voltar para transações
                                </Link>
                            </Button>
                            <h1 className="mt-4 text-2xl font-semibold tracking-tight text-slate-950 dark:text-white">
                                Transação #{transaction.id}
                            </h1>
                            <p className="mt-2 text-sm text-slate-500 dark:text-slate-400">
                                Dados completos da movimentação.
                            </p>
                        </div>
                        <Button asChild>
                            <Link href={`/transactions/${transaction.id}/edit`}>
                                <Pencil /> Editar
                            </Link>
                        </Button>
                    </header>

                    <section className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <div className="flex flex-col gap-4 border-b border-slate-100 p-6 sm:flex-row sm:items-center sm:justify-between dark:border-slate-800">
                            <div className="flex items-center gap-4">
                                <span className="grid size-12 place-items-center rounded-xl bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">
                                    <ReceiptText className="size-6" />
                                </span>
                                <div>
                                    <p className="text-xs text-slate-500">
                                        Valor da transação
                                    </p>
                                    <p className="mt-1 text-2xl font-semibold text-slate-950 dark:text-white">
                                        {formatCurrency(transaction.amount)}
                                    </p>
                                </div>
                            </div>
                            <span
                                className={`w-fit rounded-full px-3 py-1 text-xs font-semibold ${statusClasses[transaction.status]}`}
                            >
                                {transaction.status_label}
                            </span>
                        </div>

                        <dl className="grid gap-px bg-slate-100 sm:grid-cols-2 dark:bg-slate-800">
                            <div className="bg-white p-6 dark:bg-slate-900">
                                <dt className="flex items-center gap-2 text-xs font-medium text-slate-500 uppercase">
                                    <CalendarClock className="size-4" /> Data e
                                    hora
                                </dt>
                                <dd className="mt-2 text-sm font-medium text-slate-900 dark:text-white">
                                    {formatDateTime(transaction.created_at)}
                                </dd>
                            </div>
                            <div className="bg-white p-6 dark:bg-slate-900">
                                <dt className="flex items-center gap-2 text-xs font-medium text-slate-500 uppercase">
                                    <UserRound className="size-4" /> CPF do
                                    portador
                                </dt>
                                <dd className="mt-2 text-sm font-medium text-slate-900 dark:text-white">
                                    {formatCpf(transaction.cpf)}
                                </dd>
                            </div>
                            <div className="bg-white p-6 dark:bg-slate-900">
                                <dt className="flex items-center gap-2 text-xs font-medium text-slate-500 uppercase">
                                    <UserRound className="size-4" /> Criada por
                                </dt>
                                <dd className="mt-2 text-sm font-medium text-slate-900 dark:text-white">
                                    {transaction.created_by.name}
                                </dd>
                            </div>
                            <div className="bg-white p-6 dark:bg-slate-900">
                                <dt className="flex items-center gap-2 text-xs font-medium text-slate-500 uppercase">
                                    <FileText className="size-4" /> Documento
                                </dt>
                                <dd className="mt-2">
                                    {transaction.document ? (
                                        <a
                                            href={
                                                transaction.document
                                                    .download_url
                                            }
                                            className="inline-flex max-w-full items-center gap-2 text-sm font-medium text-emerald-700 hover:underline dark:text-emerald-400"
                                        >
                                            <Download className="size-4 shrink-0" />
                                            <span className="truncate">
                                                {transaction.document.name}
                                            </span>
                                        </a>
                                    ) : (
                                        <span className="text-sm text-slate-500">
                                            Nenhum documento disponível
                                        </span>
                                    )}
                                </dd>
                            </div>
                        </dl>
                    </section>
                </div>
            </main>
        </>
    );
}
