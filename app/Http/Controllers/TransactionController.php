<?php

namespace App\Http\Controllers;

use App\Enums\TransactionStatus;
use App\Http\Requests\StoreTransactionRequest;
use App\Http\Requests\UpdateTransactionRequest;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class TransactionController extends Controller
{
    public function index(Request $request): InertiaResponse|JsonResponse
    {
        $transactions = Transaction::query()
            ->where('tenant_id', $request->user()->tenant_id)
            ->latest('created_at')
            ->latest('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Transaction $transaction): array => $this->listData($transaction));

        if ($request->is('api/*')) {
            return response()->json([
                'data' => $transactions->items(),
                'meta' => [
                    'current_page' => $transactions->currentPage(),
                    'last_page' => $transactions->lastPage(),
                    'per_page' => $transactions->perPage(),
                    'total' => $transactions->total(),
                ],
            ]);
        }

        return Inertia::render('transactions/index', [
            'transactions' => $transactions,
        ]);
    }

    public function create(): InertiaResponse
    {
        return Inertia::render('transactions/create', [
            'statuses' => $this->statuses(),
        ]);
    }

    public function store(StoreTransactionRequest $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validated();
        $file = $request->file('document');

        if (! $file instanceof UploadedFile) {
            throw new RuntimeException('O documento da transação não foi recebido corretamente.');
        }

        $path = $this->storeDocument($file);

        try {
            $transaction = DB::transaction(function () use ($request, $validated, $file, $path): Transaction {
                $transaction = new Transaction;
                $transaction->tenant_id = (string) $request->user()->tenant_id;
                $transaction->user_id = $request->user()->id;
                $transaction->amount = (string) $validated['amount'];
                $transaction->cpf = (string) $validated['cpf'];
                $transaction->status = $validated['status'];
                $transaction->document_path = $path;
                $transaction->document_original_name = $this->safeOriginalName($file);
                $transaction->document_mime_type = $file->getMimeType() ?: 'application/octet-stream';
                $transaction->save();

                return $transaction;
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($path);

            throw $exception;
        }

        if ($request->is('api/*')) {
            return response()->json([
                'message' => 'Transação cadastrada com sucesso.',
                'data' => ['id' => $transaction->id],
                'redirect_to' => route('transactions.index', absolute: false),
            ], 201);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Transação cadastrada com sucesso.',
        ]);

        return to_route('transactions.index');
    }

    public function show(Request $request, int $transaction): InertiaResponse|JsonResponse
    {
        $model = $this->findTransaction($request, $transaction);
        Gate::authorize('view', $model);
        $data = $this->detailData($model, $request->is('api/*'));

        if ($request->is('api/*')) {
            return response()->json(['data' => $data]);
        }

        return Inertia::render('transactions/show', [
            'transaction' => $data,
        ]);
    }

    public function edit(Request $request, int $transaction): InertiaResponse
    {
        $model = $this->findTransaction($request, $transaction);
        Gate::authorize('update', $model);

        return Inertia::render('transactions/edit', [
            'transaction' => $this->detailData($model),
            'statuses' => $this->statuses(),
        ]);
    }

    public function update(UpdateTransactionRequest $request, int $transaction): RedirectResponse|JsonResponse
    {
        $model = $this->findTransaction($request, $transaction);
        Gate::authorize('update', $model);
        $validated = $request->validated();
        $file = $request->file('document');

        if ($file !== null && ! $file instanceof UploadedFile) {
            throw new RuntimeException('O documento da transação não foi recebido corretamente.');
        }

        $newPath = $file ? $this->storeDocument($file) : null;
        $oldPath = $model->document_path;

        try {
            DB::transaction(function () use ($model, $validated, $file, $newPath): void {
                $model->amount = (string) $validated['amount'];
                $model->cpf = (string) $validated['cpf'];
                $model->status = $validated['status'];

                if ($file && $newPath) {
                    $model->document_path = $newPath;
                    $model->document_original_name = $this->safeOriginalName($file);
                    $model->document_mime_type = $file->getMimeType() ?: 'application/octet-stream';
                }

                $model->save();
            });
        } catch (Throwable $exception) {
            if ($newPath) {
                Storage::disk('local')->delete($newPath);
            }

            throw $exception;
        }

        if ($newPath && $oldPath && $newPath !== $oldPath) {
            Storage::disk('local')->delete($oldPath);
        }

        if ($request->is('api/*')) {
            return response()->json([
                'message' => 'Transação atualizada com sucesso.',
                'data' => ['id' => $model->id],
                'redirect_to' => route('transactions.index', absolute: false),
            ]);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Transação atualizada com sucesso.',
        ]);

        return to_route('transactions.index');
    }

    public function destroy(Request $request, int $transaction): RedirectResponse|JsonResponse
    {
        $model = $this->findTransaction($request, $transaction);
        Gate::authorize('delete', $model);
        $model->delete();

        if ($request->is('api/*')) {
            return response()->json([
                'message' => 'Transação excluída com sucesso.',
            ]);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Transação excluída com sucesso.',
        ]);

        return to_route('transactions.index');
    }

    public function document(Request $request, int $transaction): StreamedResponse
    {
        $model = $this->findTransaction($request, $transaction);
        Gate::authorize('view', $model);

        abort_unless($model->document_path && Storage::disk('local')->exists($model->document_path), 404);

        return Storage::disk('local')->download(
            $model->document_path,
            $model->document_original_name ?: 'documento',
            ['Content-Type' => $model->document_mime_type ?: 'application/octet-stream'],
        );
    }

    private function findTransaction(Request $request, int $id): Transaction
    {
        return Transaction::query()
            ->where('tenant_id', $request->user()->tenant_id)
            ->findOrFail($id);
    }

    /**
     * @return array{id: int, created_at: string|null, amount: string}
     */
    private function listData(Transaction $transaction): array
    {
        return [
            'id' => $transaction->id,
            'created_at' => $transaction->created_at?->toIso8601String(),
            'amount' => $transaction->amount,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function detailData(Transaction $transaction, bool $api = false): array
    {
        $transaction->loadMissing('creator:id,tenant_id,name');
        $documentRoute = $api ? 'api.transactions.document' : 'transactions.document';

        return [
            ...$this->listData($transaction),
            'cpf' => $transaction->cpf,
            'status' => $transaction->status->value,
            'status_label' => $transaction->status->label(),
            'document' => $transaction->document_path ? [
                'name' => $transaction->document_original_name ?: 'Documento',
                'download_url' => route($documentRoute, $transaction->id, absolute: false),
            ] : null,
            'created_by' => [
                'id' => $transaction->creator->id,
                'name' => $transaction->creator->name,
            ],
        ];
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function statuses(): array
    {
        return array_map(
            fn (TransactionStatus $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
            ],
            TransactionStatus::cases(),
        );
    }

    private function storeDocument(UploadedFile $file): string
    {
        $sourcePath = $file->getRealPath() ?: $file->getPathname();
        $stream = $sourcePath !== '' ? @fopen($sourcePath, 'rb') : false;

        if (! is_resource($stream)) {
            throw new RuntimeException('O arquivo temporário do documento não está disponível para leitura.');
        }

        $path = $file->hashName('transactions');

        try {
            $stored = Storage::disk('local')->put($path, $stream);
        } finally {
            fclose($stream);
        }

        if (! $stored) {
            throw new RuntimeException('Não foi possível armazenar o documento da transação.');
        }

        return $path;
    }

    private function safeOriginalName(UploadedFile $file): string
    {
        return basename(str_replace('\\', '/', $file->getClientOriginalName()));
    }
}
