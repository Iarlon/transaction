<?php

namespace App\Http\Requests;

use App\Enums\TransactionStatus;
use App\Rules\Cpf;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'cpf' => preg_replace('/\D/', '', (string) $this->input('cpf')),
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:9999999999999.99'],
            'cpf' => ['required', 'string', 'size:11', new Cpf],
            'status' => ['required', Rule::enum(TransactionStatus::class)],
            'document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'tenant_id' => ['prohibited'],
            'user_id' => ['prohibited'],
            'created_at' => ['prohibited'],
            'deleted_at' => ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'amount.required' => 'Informe o valor da transação.',
            'amount.numeric' => 'Informe um valor válido.',
            'amount.decimal' => 'O valor deve possuir no máximo duas casas decimais.',
            'amount.gt' => 'O valor deve ser maior que zero.',
            'cpf.required' => 'Informe o CPF do portador.',
            'cpf.size' => 'O CPF deve possuir 11 dígitos.',
            'status.required' => 'Selecione o status da transação.',
            'status.enum' => 'Selecione um status válido.',
            'document.required' => 'Envie o documento da transação.',
            'document.mimes' => 'O documento deve ser PDF, JPEG ou PNG.',
            'document.max' => 'O documento deve possuir no máximo 10 MB.',
            '*.prohibited' => 'Este campo é controlado pelo sistema.',
        ];
    }
}
