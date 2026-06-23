<?php

namespace App\Services;

use App\Models\Invoice;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class InvoiceService
{
    public function list(int $perPage = 15): LengthAwarePaginator
    {
        return Invoice::orderByDesc('created_at')->paginate($perPage);
    }

    public function create(array $data): Invoice
    {
        return Invoice::create($data);
    }

    public function update(Invoice $invoice, array $data): Invoice
    {
        $invoice->update($data);
        return $invoice->fresh();
    }
}
