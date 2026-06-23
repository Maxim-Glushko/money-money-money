<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $invoice = $this->route('invoice');
        $minDueDate = $invoice ? $invoice->issue_date->toDateString() : 'today';

        return [
            'net_amount'   => 'required|numeric|gt:0',
            'vat_amount'   => 'required|numeric|gte:0',
            'gross_amount' => 'required|numeric',
            'due_date'     => ['required', 'date', 'after_or_equal:' . $minDueDate],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $invoice = $this->route('invoice');

            if ($invoice && $invoice->status !== 'pending') {
                $validator->errors()->add('status', 'Only pending invoices can be updated.');
                return;
            }

            $net   = (float) $this->input('net_amount');
            $vat   = (float) $this->input('vat_amount');
            $gross = (float) $this->input('gross_amount');

            if (abs($gross - round($net + $vat, 2)) > 0.01) {
                $validator->errors()->add('gross_amount', 'gross_amount must equal net_amount + vat_amount.');
            }
        });
    }
}
