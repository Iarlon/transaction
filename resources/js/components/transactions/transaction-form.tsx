import { Link, useForm } from '@inertiajs/react';
import { FileText, Loader2, Save, Upload } from 'lucide-react';
import type { ChangeEvent, FormEvent } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatCpf } from '@/lib/transaction';
import type {
    StatusOption,
    TransactionDetail,
    TransactionStatus,
} from '@/types';

type FormData = {
    amount: string;
    cpf: string;
    status: TransactionStatus;
    document: File | null;
};

type Props = {
    statuses: StatusOption[];
    transaction?: TransactionDetail;
};

export function TransactionForm({ statuses, transaction }: Props) {
    const form = useForm<FormData>({
        amount: transaction?.amount ?? '',
        cpf: transaction ? formatCpf(transaction.cpf) : '',
        status: transaction?.status ?? 'processing',
        document: null,
    });
    const editing = Boolean(transaction);

    function handleFile(event: ChangeEvent<HTMLInputElement>) {
        form.setData('document', event.target.files?.[0] ?? null);
    }

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();

        if (transaction) {
            form.transform((data) => ({ ...data, _method: 'put' }));
            form.post(`/transactions/${transaction.id}`, {
                forceFormData: true,
                preserveScroll: true,
            });

            return;
        }

        form.post('/transactions', {
            forceFormData: true,
            preserveScroll: true,
        });
    }

    return (
        <form
            onSubmit={submit}
            className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7 dark:border-slate-800 dark:bg-slate-900"
        >
            <div className="grid gap-6 md:grid-cols-2">
                <div className="space-y-2">
                    <Label htmlFor="amount">Valor da transação</Label>
                    <div className="relative">
                        <span className="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-sm text-slate-500">
                            R$
                        </span>
                        <Input
                            id="amount"
                            name="amount"
                            type="number"
                            min="0.01"
                            max="9999999999999.99"
                            step="0.01"
                            inputMode="decimal"
                            value={form.data.amount}
                            onChange={(event) =>
                                form.setData('amount', event.target.value)
                            }
                            className="h-11 pl-10"
                            aria-invalid={Boolean(form.errors.amount)}
                            required
                            autoFocus
                        />
                    </div>
                    <InputError message={form.errors.amount} />
                </div>

                <div className="space-y-2">
                    <Label htmlFor="cpf">CPF do portador</Label>
                    <Input
                        id="cpf"
                        name="cpf"
                        inputMode="numeric"
                        autoComplete="off"
                        value={form.data.cpf}
                        onChange={(event) =>
                            form.setData('cpf', formatCpf(event.target.value))
                        }
                        placeholder="000.000.000-00"
                        className="h-11"
                        aria-invalid={Boolean(form.errors.cpf)}
                        required
                    />
                    <InputError message={form.errors.cpf} />
                </div>

                <div className="space-y-2">
                    <Label htmlFor="status">Status</Label>
                    <select
                        id="status"
                        name="status"
                        value={form.data.status}
                        onChange={(event) =>
                            form.setData(
                                'status',
                                event.target.value as TransactionStatus,
                            )
                        }
                        className="flex h-11 w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        aria-invalid={Boolean(form.errors.status)}
                        required
                    >
                        {statuses.map((status) => (
                            <option key={status.value} value={status.value}>
                                {status.label}
                            </option>
                        ))}
                    </select>
                    <InputError message={form.errors.status} />
                </div>

                <div className="space-y-2">
                    <Label htmlFor="document">
                        Documento {editing && '(opcional)'}
                    </Label>
                    <label
                        htmlFor="document"
                        className="flex min-h-11 cursor-pointer items-center gap-3 rounded-md border border-dashed border-slate-300 px-3 py-2 text-sm text-slate-600 transition-colors hover:border-emerald-500 hover:bg-emerald-50/60 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-emerald-950/30"
                    >
                        <Upload className="size-4 shrink-0 text-emerald-600" />
                        <span className="truncate">
                            {form.data.document?.name ??
                                'Selecionar PDF, JPEG ou PNG'}
                        </span>
                    </label>
                    <input
                        id="document"
                        name="document"
                        type="file"
                        accept="application/pdf,image/jpeg,image/png"
                        onChange={handleFile}
                        className="sr-only"
                        required={!editing}
                    />
                    <p className="text-xs text-slate-500">
                        Tamanho máximo: 10 MB.
                    </p>
                    <InputError message={form.errors.document} />
                </div>
            </div>

            {transaction?.document && (
                <div className="mt-6 flex items-center gap-3 rounded-xl bg-slate-50 p-4 dark:bg-slate-950/60">
                    <FileText className="size-5 text-emerald-600" />
                    <div className="min-w-0 flex-1">
                        <p className="text-xs text-slate-500">
                            Documento atual
                        </p>
                        <a
                            href={transaction.document.download_url}
                            className="block truncate text-sm font-medium text-emerald-700 hover:underline dark:text-emerald-400"
                        >
                            {transaction.document.name}
                        </a>
                    </div>
                </div>
            )}

            {form.progress && (
                <div className="mt-6" aria-live="polite">
                    <div className="mb-1 flex justify-between text-xs text-slate-500">
                        <span>Enviando documento</span>
                        <span>{form.progress.percentage}%</span>
                    </div>
                    <div className="h-1.5 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                        <div
                            className="h-full bg-emerald-600 transition-[width]"
                            style={{ width: `${form.progress.percentage}%` }}
                        />
                    </div>
                </div>
            )}

            <div className="mt-8 flex flex-col-reverse gap-3 border-t border-slate-100 pt-6 sm:flex-row sm:justify-end dark:border-slate-800">
                <Button variant="outline" asChild>
                    <Link href="/transactions">Cancelar</Link>
                </Button>
                <Button
                    type="submit"
                    disabled={form.processing}
                    className="bg-emerald-600 text-white hover:bg-emerald-700"
                >
                    {form.processing ? (
                        <Loader2 className="animate-spin" />
                    ) : (
                        <Save />
                    )}
                    {form.processing ? 'Salvando...' : 'Salvar transação'}
                </Button>
            </div>
        </form>
    );
}
