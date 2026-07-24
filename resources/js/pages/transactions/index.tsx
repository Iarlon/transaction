import { Head, Link, router } from '@inertiajs/react';
import {
    ChevronLeft,
    ChevronRight,
    Eye,
    MoreVertical,
    Pencil,
    Plus,
    ReceiptText,
    Trash2,
} from 'lucide-react';
import { useCallback, useState } from 'react';
import { DeleteTransactionDialog } from '@/components/transactions/delete-transaction-dialog';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { formatCurrency, formatDateTime } from '@/lib/transaction';
import type { TransactionListItem, TransactionPaginator } from '@/types';

type Props = {
    transactions: TransactionPaginator;
};

export default function TransactionsIndex({ transactions }: Props) {
    const [selected, setSelected] = useState<TransactionListItem | null>(null);
    const [deleting, setDeleting] = useState(false);
    const cancelDelete = useCallback(() => setSelected(null), []);

    function confirmDelete() {
        if (!selected) {
            return;
        }

        setDeleting(true);
        router.delete(`/transactions/${selected.id}`, {
            preserveScroll: true,
            onSuccess: () => setSelected(null),
            onFinish: () => setDeleting(false),
        });
    }

    return (
        <>
            <Head title="Transações" />

            <main className="min-h-full bg-slate-50/70 p-4 sm:p-6 lg:p-8 dark:bg-slate-950/40">
                <div className="mx-auto max-w-6xl space-y-6">
                    <header className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p className="text-sm font-medium text-emerald-600">
                                Gestão financeira
                            </p>
                            <h1 className="mt-1 text-2xl font-semibold tracking-tight text-slate-950 sm:text-3xl dark:text-white">
                                Transações
                            </h1>
                            <p className="mt-2 text-sm text-slate-500 dark:text-slate-400">
                                Consulte e gerencie as transações da sua
                                empresa.
                            </p>
                        </div>

                        <Button
                            asChild
                            className="bg-emerald-600 text-white hover:bg-emerald-700"
                        >
                            <Link href="/transactions/create">
                                <Plus />
                                Nova transação
                            </Link>
                        </Button>
                    </header>

                    <section className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <div className="border-b border-slate-100 px-5 py-4 dark:border-slate-800">
                            <h2 className="font-semibold text-slate-950 dark:text-white">
                                Transações cadastradas
                            </h2>
                            <p className="mt-1 text-xs text-slate-500">
                                {transactions.total}{' '}
                                {transactions.total === 1
                                    ? 'transação encontrada'
                                    : 'transações encontradas'}
                            </p>
                        </div>

                        {transactions.data.length === 0 ? (
                            <div className="flex min-h-80 flex-col items-center justify-center px-6 py-12 text-center">
                                <span className="mb-4 grid size-14 place-items-center rounded-2xl bg-emerald-50 text-emerald-600 ring-8 ring-emerald-50/60 dark:bg-emerald-950 dark:text-emerald-400 dark:ring-emerald-950/40">
                                    <ReceiptText className="size-6" />
                                </span>
                                <h3 className="font-semibold text-slate-900 dark:text-white">
                                    Nenhuma transação encontrada
                                </h3>
                                <p className="mt-2 max-w-sm text-sm leading-6 text-slate-500 dark:text-slate-400">
                                    Cadastre a primeira transação para começar a
                                    acompanhar as movimentações.
                                </p>
                                <Button asChild className="mt-5">
                                    <Link href="/transactions/create">
                                        <Plus />
                                        Cadastrar transação
                                    </Link>
                                </Button>
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-left text-sm">
                                    <thead className="bg-slate-50/80 text-xs tracking-wide text-slate-500 uppercase dark:bg-slate-950/40">
                                        <tr>
                                            <th className="px-5 py-3.5 font-medium">
                                                Data e hora
                                            </th>
                                            <th className="px-5 py-3.5 font-medium">
                                                Valor
                                            </th>
                                            <th className="w-16 px-5 py-3.5">
                                                <span className="sr-only">
                                                    Ações
                                                </span>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                                        {transactions.data.map(
                                            (transaction) => (
                                                <tr
                                                    key={transaction.id}
                                                    tabIndex={0}
                                                    role="link"
                                                    onClick={() =>
                                                        router.visit(
                                                            `/transactions/${transaction.id}`,
                                                        )
                                                    }
                                                    onKeyDown={(event) => {
                                                        if (
                                                            event.key ===
                                                                'Enter' ||
                                                            event.key === ' '
                                                        ) {
                                                            event.preventDefault();
                                                            router.visit(
                                                                `/transactions/${transaction.id}`,
                                                            );
                                                        }
                                                    }}
                                                    className="cursor-pointer transition-colors hover:bg-slate-50 focus:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-inset dark:hover:bg-slate-800/50 dark:focus:bg-slate-800/50"
                                                >
                                                    <td className="px-5 py-4 text-slate-600 dark:text-slate-300">
                                                        {formatDateTime(
                                                            transaction.created_at,
                                                        )}
                                                    </td>
                                                    <td className="px-5 py-4 font-semibold text-slate-950 dark:text-white">
                                                        {formatCurrency(
                                                            transaction.amount,
                                                        )}
                                                    </td>
                                                    <td
                                                        className="px-5 py-4 text-right"
                                                        onClick={(event) =>
                                                            event.stopPropagation()
                                                        }
                                                        onKeyDown={(event) =>
                                                            event.stopPropagation()
                                                        }
                                                    >
                                                        <DropdownMenu>
                                                            <DropdownMenuTrigger
                                                                asChild
                                                            >
                                                                <Button
                                                                    variant="ghost"
                                                                    size="icon"
                                                                    aria-label={`Ações da transação ${transaction.id}`}
                                                                >
                                                                    <MoreVertical />
                                                                </Button>
                                                            </DropdownMenuTrigger>
                                                            <DropdownMenuContent align="end">
                                                                <DropdownMenuItem
                                                                    onSelect={() =>
                                                                        router.visit(
                                                                            `/transactions/${transaction.id}`,
                                                                        )
                                                                    }
                                                                >
                                                                    <Eye /> Ver
                                                                </DropdownMenuItem>
                                                                <DropdownMenuItem
                                                                    onSelect={() =>
                                                                        router.visit(
                                                                            `/transactions/${transaction.id}/edit`,
                                                                        )
                                                                    }
                                                                >
                                                                    <Pencil />{' '}
                                                                    Editar
                                                                </DropdownMenuItem>
                                                                <DropdownMenuSeparator />
                                                                <DropdownMenuItem
                                                                    variant="destructive"
                                                                    onSelect={() =>
                                                                        setSelected(
                                                                            transaction,
                                                                        )
                                                                    }
                                                                >
                                                                    <Trash2 />{' '}
                                                                    Excluir
                                                                </DropdownMenuItem>
                                                            </DropdownMenuContent>
                                                        </DropdownMenu>
                                                    </td>
                                                </tr>
                                            ),
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        )}

                        {transactions.last_page > 1 && (
                            <footer className="flex items-center justify-between border-t border-slate-100 px-5 py-4 dark:border-slate-800">
                                <p className="text-xs text-slate-500">
                                    Página {transactions.current_page} de{' '}
                                    {transactions.last_page}
                                </p>
                                <div className="flex gap-2">
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        asChild={Boolean(
                                            transactions.prev_page_url,
                                        )}
                                        disabled={!transactions.prev_page_url}
                                    >
                                        {transactions.prev_page_url ? (
                                            <Link
                                                href={
                                                    transactions.prev_page_url
                                                }
                                                preserveScroll
                                            >
                                                <ChevronLeft /> Anterior
                                            </Link>
                                        ) : (
                                            <span>
                                                <ChevronLeft /> Anterior
                                            </span>
                                        )}
                                    </Button>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        asChild={Boolean(
                                            transactions.next_page_url,
                                        )}
                                        disabled={!transactions.next_page_url}
                                    >
                                        {transactions.next_page_url ? (
                                            <Link
                                                href={
                                                    transactions.next_page_url
                                                }
                                                preserveScroll
                                            >
                                                Próxima <ChevronRight />
                                            </Link>
                                        ) : (
                                            <span>
                                                Próxima <ChevronRight />
                                            </span>
                                        )}
                                    </Button>
                                </div>
                            </footer>
                        )}
                    </section>
                </div>
            </main>

            <DeleteTransactionDialog
                transaction={selected}
                processing={deleting}
                onCancel={cancelDelete}
                onConfirm={confirmDelete}
            />
        </>
    );
}
