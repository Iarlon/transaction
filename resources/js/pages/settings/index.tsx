import { Head, useForm, usePage } from '@inertiajs/react';
import { Camera, Loader2, Save, Trash2, Upload } from 'lucide-react';
import { useEffect, useState } from 'react';
import InputError from '@/components/input-error';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useInitials } from '@/hooks/use-initials';

type FormData = {
    avatar: File | null;
};

export default function SettingsIndex() {
    const { auth } = usePage().props;
    const getInitials = useInitials();
    const form = useForm<FormData>({ avatar: null });
    const [preview, setPreview] = useState<string | null>(null);

    useEffect(() => {
        return () => {
            if (preview) {
                URL.revokeObjectURL(preview);
            }
        };
    }, [preview]);

    function selectAvatar(file: File | null) {
        if (preview) {
            URL.revokeObjectURL(preview);
        }

        form.setData('avatar', file);
        setPreview(file ? URL.createObjectURL(file) : null);
    }

    function submit(event: React.FormEvent<HTMLFormElement>) {
        event.preventDefault();

        form.post('/settings/avatar', {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                setPreview(null);
            },
        });
    }

    function removeAvatar() {
        form.delete('/settings/avatar', {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                setPreview(null);
            },
        });
    }

    return (
        <>
            <Head title="Configurações" />

            <main className="min-h-full bg-slate-50/70 p-4 sm:p-6 lg:p-8 dark:bg-slate-950/40">
                <div className="mx-auto max-w-4xl space-y-6">
                    <header>
                        <p className="text-sm font-medium text-emerald-600">
                            Sua conta
                        </p>
                        <h1 className="mt-1 text-2xl font-semibold tracking-tight text-slate-950 sm:text-3xl dark:text-white">
                            Configurações
                        </h1>
                        <p className="mt-2 text-sm text-slate-500 dark:text-slate-400">
                            Gerencie as informações visuais do seu perfil.
                        </p>
                    </header>

                    <section className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <div className="border-b border-slate-100 px-5 py-4 sm:px-7 dark:border-slate-800">
                            <h2 className="font-semibold text-slate-950 dark:text-white">
                                Perfil
                            </h2>
                            <p className="mt-1 text-sm text-slate-500">
                                Sua foto será exibida no menu da aplicação.
                            </p>
                        </div>

                        <form
                            onSubmit={submit}
                            className="space-y-7 p-5 sm:p-7"
                        >
                            <div className="flex flex-col gap-6 sm:flex-row sm:items-center">
                                <div className="relative w-fit">
                                    <Avatar className="size-24 border-4 border-white shadow-lg dark:border-slate-800">
                                        <AvatarImage
                                            src={
                                                preview ??
                                                auth.user.avatar ??
                                                undefined
                                            }
                                            alt={auth.user.name}
                                        />
                                        <AvatarFallback className="bg-emerald-100 text-xl font-semibold text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">
                                            {getInitials(auth.user.name)}
                                        </AvatarFallback>
                                    </Avatar>
                                    <span className="absolute -right-1 -bottom-1 grid size-8 place-items-center rounded-full bg-emerald-600 text-white ring-4 ring-white dark:ring-slate-900">
                                        <Camera className="size-4" />
                                    </span>
                                </div>

                                <div className="flex-1 space-y-3">
                                    <div>
                                        <p className="font-medium text-slate-950 dark:text-white">
                                            {auth.user.name}
                                        </p>
                                        <p className="text-sm text-slate-500">
                                            {auth.user.email}
                                        </p>
                                    </div>

                                    <div className="flex flex-wrap gap-3">
                                        <Label
                                            htmlFor="avatar"
                                            className="inline-flex h-9 cursor-pointer items-center justify-center gap-2 rounded-md border border-slate-200 bg-white px-4 text-sm font-medium shadow-xs transition-colors hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:hover:bg-slate-800"
                                        >
                                            <Upload className="size-4" />
                                            Selecionar foto
                                        </Label>
                                        <Input
                                            id="avatar"
                                            type="file"
                                            accept="image/jpeg,image/png,image/webp"
                                            className="sr-only"
                                            onChange={(event) =>
                                                selectAvatar(
                                                    event.target.files?.[0] ??
                                                        null,
                                                )
                                            }
                                        />

                                        {auth.user.avatar && (
                                            <Button
                                                type="button"
                                                variant="outline"
                                                onClick={removeAvatar}
                                                disabled={form.processing}
                                                className="text-rose-600 hover:text-rose-700"
                                            >
                                                <Trash2 />
                                                Remover foto
                                            </Button>
                                        )}
                                    </div>

                                    <p className="text-xs text-slate-500">
                                        JPEG, PNG ou WebP, com no máximo 5 MB.
                                    </p>
                                    <InputError message={form.errors.avatar} />
                                </div>
                            </div>

                            <div className="flex justify-end border-t border-slate-100 pt-6 dark:border-slate-800">
                                <Button
                                    type="submit"
                                    disabled={
                                        !form.data.avatar || form.processing
                                    }
                                    className="bg-emerald-600 text-white hover:bg-emerald-700"
                                >
                                    {form.processing ? (
                                        <Loader2 className="animate-spin" />
                                    ) : (
                                        <Save />
                                    )}
                                    {form.processing
                                        ? 'Salvando...'
                                        : 'Salvar foto'}
                                </Button>
                            </div>
                        </form>
                    </section>
                </div>
            </main>
        </>
    );
}
