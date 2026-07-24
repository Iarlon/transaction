import { Loader2, Trash2, X } from 'lucide-react';
import { useEffect } from 'react';
import { Button } from '@/components/ui/button';
import type { TransactionListItem } from '@/types';

type Props = {
    transaction: TransactionListItem | null;
    processing: boolean;
    onCancel: () => void;
    onConfirm: () => void;
};

export function DeleteTransactionDialog({
    transaction,
    processing,
    onCancel,
    onConfirm,
}: Props) {
    useEffect(() => {
        if (!transaction) {
            return;
        }

        const closeOnEscape = (event: KeyboardEvent) => {
            if (event.key === 'Escape' && !processing) {
                onCancel();
            }
        };

        document.addEventListener('keydown', closeOnEscape);

        return () => document.removeEventListener('keydown', closeOnEscape);
    }, [onCancel, processing, transaction]);

    if (!transaction) {
        return null;
    }

    return (
        <div
            className="fixed inset-0 z-50 grid place-items-center bg-slate-950/55 p-4 backdrop-blur-sm"
            role="presentation"
            onMouseDown={(event) => {
                if (event.currentTarget === event.target && !processing) {
                    onCancel();
                }
            }}
        >
            <section
                role="alertdialog"
                aria-modal="true"
                aria-labelledby="delete-transaction-title"
                aria-describedby="delete-transaction-description"
                className="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl dark:border-slate-800 dark:bg-slate-900"
            >
                <div className="flex items-start justify-between gap-4">
                    <span className="grid size-11 shrink-0 place-items-center rounded-xl bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300">
                        <Trash2 className="size-5" />
                    </span>
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        aria-label="Fechar confirmação"
                        disabled={processing}
                        onClick={onCancel}
                    >
                        <X />
                    </Button>
                </div>

                <h2
                    id="delete-transaction-title"
                    className="mt-5 text-lg font-semibold text-slate-950 dark:text-white"
                >
                    Excluir transação?
                </h2>
                <p
                    id="delete-transaction-description"
                    className="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400"
                >
                    A transação deixará de aparecer na lista. O registro será
                    preservado internamente por meio de exclusão lógica.
                </p>

                <div className="mt-6 flex justify-end gap-3">
                    <Button
                        type="button"
                        variant="outline"
                        disabled={processing}
                        onClick={onCancel}
                    >
                        Cancelar
                    </Button>
                    <Button
                        type="button"
                        variant="destructive"
                        disabled={processing}
                        onClick={onConfirm}
                        autoFocus
                    >
                        {processing ? (
                            <Loader2 className="animate-spin" />
                        ) : (
                            <Trash2 />
                        )}
                        {processing ? 'Excluindo...' : 'Excluir'}
                    </Button>
                </div>
            </section>
        </div>
    );
}
