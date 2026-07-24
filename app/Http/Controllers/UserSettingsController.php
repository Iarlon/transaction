<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateAvatarRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class UserSettingsController extends Controller
{
    public function edit(): InertiaResponse
    {
        return Inertia::render('settings/index');
    }

    public function updateAvatar(UpdateAvatarRequest $request): RedirectResponse
    {
        $file = $request->file('avatar');

        if (! $file instanceof UploadedFile) {
            throw new RuntimeException('A foto não foi recebida corretamente.');
        }

        $newPath = Storage::disk('local')->putFile('avatars', $file);

        if (! is_string($newPath)) {
            throw new RuntimeException('Não foi possível armazenar a foto.');
        }

        $user = $request->user();
        $oldPath = $user->avatar_path;

        try {
            $user->forceFill(['avatar_path' => $newPath])->save();
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($newPath);

            throw $exception;
        }

        if ($oldPath && $oldPath !== $newPath) {
            Storage::disk('local')->delete($oldPath);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Foto atualizada com sucesso.',
        ]);

        return to_route('settings.edit');
    }

    public function destroyAvatar(Request $request): RedirectResponse
    {
        $user = $request->user();
        $oldPath = $user->avatar_path;

        $user->forceFill(['avatar_path' => null])->save();

        if ($oldPath) {
            Storage::disk('local')->delete($oldPath);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Foto removida com sucesso.',
        ]);

        return to_route('settings.edit');
    }

    public function avatar(Request $request): StreamedResponse
    {
        $user = $request->user();

        abort_unless($user->avatar_path && Storage::disk('local')->exists($user->avatar_path), 404);

        return Storage::disk('local')->response(
            $user->avatar_path,
            basename($user->avatar_path),
            [
                'Cache-Control' => 'private, max-age=3600',
                'Content-Type' => Storage::disk('local')->mimeType($user->avatar_path)
                    ?: 'application/octet-stream',
            ],
        );
    }
}
