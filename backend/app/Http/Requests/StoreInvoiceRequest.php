<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'number'          => 'required|string|unique:invoices,number',
            'supplier_name'   => 'required|string|max:255',
            'supplier_tax_id' => 'required|string|max:50',
            'net_amount'      => 'required|numeric|gt:0',
            'vat_amount'      => 'required|numeric|gte:0',
            'gross_amount'    => 'required|numeric',
            'currency'        => 'sometimes|string|size:3',
            'status'          => 'sometimes|in:pending,approved,rejected',
            'issue_date'      => 'required|date',
            'due_date'        => 'required|date|after_or_equal:issue_date',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $data = $this->only(['net_amount', 'vat_amount', 'gross_amount']);
            if (isset($data['net_amount'], $data['vat_amount'], $data['gross_amount'])) {
                $expected = round((float) $data['net_amount'] + (float) $data['vat_amount'], 2);
                if (abs((float) $data['gross_amount'] - $expected) > 0.01) {
                    $validator->errors()->add('gross_amount', 'gross_amount must equal net_amount + vat_amount.');
                }
            }
        });
    }
}
