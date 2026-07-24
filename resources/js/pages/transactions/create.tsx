import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { TransactionForm } from '@/components/transactions/transaction-form';
import { Button } from '@/components/ui/button';
import type { StatusOption } from '@/types';

export default function CreateTransaction({
    statuses,
}: {
    statuses: StatusOption[];
}) {
    return (
        <>
            <Head title="Nova transação" />
            <main className="min-h-full bg-slate-50/70 p-4 sm:p-6 lg:p-8 dark:bg-slate-950/40">
                <div className="mx-auto max-w-4xl space-y-6">
                    <header>
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
                            Nova transação
                        </h1>
                        <p className="mt-2 text-sm text-slate-500 dark:text-slate-400">
                            Informe os dados e envie o documento comprobatório.
                        </p>
                    </header>
                    <TransactionForm statuses={statuses} />
                </div>
            </main>
        </>
    );
}
