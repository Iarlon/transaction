import { CheckCircle2, LockKeyhole, ReceiptText } from 'lucide-react';
import type { AuthLayoutProps } from '@/types';

export default function AuthSimpleLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    return (
        <main className="grid min-h-svh bg-slate-50 lg:grid-cols-[1.05fr_0.95fr] dark:bg-slate-950">
            <section className="relative hidden overflow-hidden bg-emerald-950 p-12 text-white lg:flex lg:flex-col lg:justify-between">
                <div className="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(52,211,153,0.22),transparent_34%),radial-gradient(circle_at_bottom_left,rgba(16,185,129,0.16),transparent_38%)]" />
                <div className="absolute top-24 -right-24 size-80 rounded-full border border-emerald-400/10" />
                <div className="absolute top-10 -right-10 size-80 rounded-full border border-emerald-400/10" />

                <div className="relative flex items-center gap-3">
                    <span className="grid size-11 place-items-center rounded-2xl bg-emerald-400 text-emerald-950 shadow-lg shadow-emerald-950/30">
                        <ReceiptText className="size-6" />
                    </span>
                    <div>
                        <p className="text-lg font-semibold tracking-tight">
                            Transaction
                        </p>
                        <p className="text-xs text-emerald-200/70">
                            Gestão financeira
                        </p>
                    </div>
                </div>

                <div className="relative max-w-lg space-y-8">
                    <div className="space-y-4">
                        <p className="text-sm font-medium tracking-widest text-emerald-300 uppercase">
                            Simples. Seguro. Organizado.
                        </p>
                        <h2 className="text-4xl leading-tight font-semibold tracking-tight xl:text-5xl">
                            Suas transações em um só lugar.
                        </h2>
                        <p className="max-w-md text-base leading-7 text-emerald-100/70">
                            Acompanhe movimentações financeiras com segurança e
                            isolamento completo dos dados da sua empresa.
                        </p>
                    </div>

                    <div className="grid gap-4 text-sm text-emerald-100/80 sm:grid-cols-2">
                        <div className="flex items-center gap-3">
                            <CheckCircle2 className="size-5 text-emerald-400" />
                            Controle centralizado
                        </div>
                        <div className="flex items-center gap-3">
                            <LockKeyhole className="size-5 text-emerald-400" />
                            Ambiente protegido
                        </div>
                    </div>
                </div>

                <p className="relative text-xs text-emerald-100/45">
                    © {new Date().getFullYear()} Transaction. Ambiente seguro.
                </p>
            </section>

            <section className="flex items-center justify-center px-6 py-10 sm:px-10 lg:px-16">
                <div className="w-full max-w-md">
                    <div className="mb-10 flex items-center gap-3 lg:hidden">
                        <span className="grid size-10 place-items-center rounded-xl bg-emerald-600 text-white">
                            <ReceiptText className="size-5" />
                        </span>
                        <span className="font-semibold tracking-tight">
                            Transaction
                        </span>
                    </div>

                    <div className="rounded-3xl border border-slate-200 bg-white p-6 shadow-xl shadow-slate-200/60 sm:p-9 dark:border-slate-800 dark:bg-slate-900 dark:shadow-none">
                        <div className="mb-8 space-y-2">
                            <h1 className="text-2xl font-semibold tracking-tight text-slate-950 dark:text-white">
                                {title}
                            </h1>
                            <p className="text-sm leading-6 text-slate-500 dark:text-slate-400">
                                {description}
                            </p>
                        </div>
                        {children}
                    </div>

                    <p className="mt-6 flex items-center justify-center gap-2 text-xs text-slate-400">
                        <LockKeyhole className="size-3.5" />
                        Seus dados são protegidos e criptografados
                    </p>
                </div>
            </section>
        </main>
    );
}
