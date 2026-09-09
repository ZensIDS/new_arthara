<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePurchaseReturnRequest;
use App\Models\PurchaseOrder;
use App\Models\PurchaseReturn;
use App\Services\PurchaseReturnService;

class PurchaseReturnController extends Controller
{
    public function __construct(protected PurchaseReturnService $service) {}

    public function store(StorePurchaseReturnRequest $request, PurchaseOrder $purchaseOrder)
    {
        $validated = $request->validated();

        try {
            $return = $this->service->create(
                po: $purchaseOrder,
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
            ->route('purchase-orders.show', $purchaseOrder)
            ->with('success', "Retur {$return->return_number} berhasil dicatat, stok sudah dikurangi.");
    }

    public function destroy(PurchaseOrder $purchaseOrder, PurchaseReturn $return)
    {
        abort_unless($return->purchase_order_id === $purchaseOrder->id, 404);

        $returnNumber = $return->return_number;

        try {
            $this->service->delete($return);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return redirect()
            ->route('purchase-orders.show', $purchaseOrder)
            ->with('success', "Retur {$returnNumber} berhasil dihapus, stok & total PO sudah dikembalikan.");
    }
}
