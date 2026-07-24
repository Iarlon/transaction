import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { TransactionForm } from '@/components/transactions/transaction-form';
import { Button } from '@/components/ui/button';
import type { StatusOption, TransactionDetail } from '@/types';

type Props = {
    transaction: TransactionDetail;
    statuses: StatusOption[];
};

export default function EditTransaction({ transaction, statuses }: Props) {
    return (
        <>
            <Head title={`Editar transação #${transaction.id}`} />
            <main className="min-h-full bg-slate-50/70 p-4 sm:p-6 lg:p-8 dark:bg-slate-950/40">
                <div className="mx-auto max-w-4xl space-y-6">
                    <header>
                        <Button
                            variant="ghost"
                            size="sm"
                            asChild
                            className="-ml-3"
                        >
                            <Link href={`/transactions/${transaction.id}`}>
                                <ArrowLeft /> Voltar para a transação
                            </Link>
                        </Button>
                        <h1 className="mt-4 text-2xl font-semibold tracking-tight text-slate-950 dark:text-white">
                            Editar transação #{transaction.id}
                        </h1>
                        <p className="mt-2 text-sm text-slate-500 dark:text-slate-400">
                            Atualize somente as informações necessárias.
                        </p>
                    </header>
                    <TransactionForm
                        statuses={statuses}
                        transaction={transaction}
                    />
                </div>
            </main>
        </>
    );
}
