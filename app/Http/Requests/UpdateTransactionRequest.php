<?php

namespace App\Http\Requests;

class UpdateTransactionRequest extends StoreTransactionRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['document'] = ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'];

        return $rules;
    }
}
