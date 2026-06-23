<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Models\Invoice;
use App\Services\InvoiceService;
use Illuminate\Http\JsonResponse;

class InvoiceController extends Controller
{
    public function __construct(private readonly InvoiceService $service) {}

    public function index(): JsonResponse
    {
        return response()->json($this->service->list());
    }

    public function show(Invoice $invoice): JsonResponse
    {
        return response()->json($invoice);
    }

    public function store(StoreInvoiceRequest $request): JsonResponse
    {
        $invoice = $this->service->create($request->validated());
        return response()->json($invoice, 201);
    }

    public function update(UpdateInvoiceRequest $request, Invoice $invoice): JsonResponse
    {
        if ($invoice->status !== 'pending') {
            return response()->json(
                ['message' => 'Only pending invoices can be updated.'],
                422
            );
        }

        $updated = $this->service->update($invoice, $request->validated());
        return response()->json($updated);
    }
}
