import { Form, Head } from '@inertiajs/react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/login';

export default function Login() {
    return (
        <>
            <Head title="Entrar" />

            <Form
                {...store.form()}
                resetOnSuccess={['password']}
                className="flex flex-col gap-5"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-5">
                            <div className="grid gap-2.5">
                                <Label
                                    htmlFor="email"
                                    className="text-sm font-medium text-slate-700 dark:text-slate-200"
                                >
                                    E-mail
                                </Label>
                                <Input
                                    id="email"
                                    type="email"
                                    name="email"
                                    required
                                    autoFocus
                                    tabIndex={1}
                                    autoComplete="email"
                                    placeholder="usuario@empresa.com"
                                    className="h-11 rounded-xl border-slate-200 bg-slate-50/70 px-3.5 shadow-none transition-colors placeholder:text-slate-400 focus-visible:border-emerald-500 focus-visible:ring-emerald-500/15 dark:border-slate-700 dark:bg-slate-950/60"
                                />
                                <InputError message={errors.email} />
                            </div>

                            <div className="grid gap-2.5">
                                <div className="flex items-center">
                                    <Label
                                        htmlFor="password"
                                        className="text-sm font-medium text-slate-700 dark:text-slate-200"
                                    >
                                        Senha
                                    </Label>
                                </div>
                                <PasswordInput
                                    id="password"
                                    name="password"
                                    required
                                    tabIndex={2}
                                    autoComplete="current-password"
                                    placeholder="Sua senha"
                                    className="h-11 rounded-xl border-slate-200 bg-slate-50/70 px-3.5 shadow-none transition-colors placeholder:text-slate-400 focus-visible:border-emerald-500 focus-visible:ring-emerald-500/15 dark:border-slate-700 dark:bg-slate-950/60"
                                />
                                <InputError message={errors.password} />
                            </div>

                            <Button
                                type="submit"
                                className="mt-2 h-11 w-full rounded-xl bg-emerald-600 font-semibold text-white shadow-lg shadow-emerald-600/20 transition-all hover:-translate-y-0.5 hover:bg-emerald-700 hover:shadow-emerald-600/30 focus-visible:ring-emerald-500/30"
                                tabIndex={3}
                                disabled={processing}
                                data-test="login-button"
                            >
                                {processing && <Spinner />}
                                {processing ? 'Entrando...' : 'Entrar'}
                            </Button>
                        </div>
                    </>
                )}
            </Form>
        </>
    );
}

Login.layout = {
    title: 'Acesse sua conta',
    description: 'Informe seu e-mail e sua senha para continuar',
};
