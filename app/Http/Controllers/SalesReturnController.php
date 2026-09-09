<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSalesReturnRequest;
use App\Models\SalesOrder;
use App\Models\SalesReturn;
use App\Services\SalesReturnService;

class SalesReturnController extends Controller
{
    public function __construct(protected SalesReturnService $service) {}

    public function store(StoreSalesReturnRequest $request, SalesOrder $salesOrder)
    {
        $validated = $request->validated();

        try {
            $return = $this->service->create(
                so: $salesOrder,
                data: [
                    'return_date' => $validated['return_date'],
                    'note'        => $validated['note'] ?? null,
                ],
                items: $validated['items'],
            );
        } catch (\RuntimeException $e) {
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }

        return redirect()
            ->route('sales-orders.show', $salesOrder)
            ->with('success', "Retur {$return->return_number} berhasil dicatat, stok sudah dikembalikan.");
    }

    public function destroy(SalesOrder $salesOrder, SalesReturn $return)
    {
        abort_unless($return->sales_order_id === $salesOrder->id, 404);

        $returnNumber = $return->return_number;

        try {
            $this->service->delete($return);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return redirect()
            ->route('sales-orders.show', $salesOrder)
            ->with('success', "Retur {$returnNumber} berhasil dihapus, stok & total transaksi sudah dikembalikan.");
    }
}
