<?php

namespace App\Services;

use App\Models\Cash;
use App\Models\CashFlow;
use App\Models\Expense;
use Illuminate\Support\Facades\DB;

class ExpenseService
{
    public function __construct(
        protected CashFlowService $cashFlowService,
        protected CashService $cashService,
    ) {}

    /**
     * @param array $data ['expense_category_id', 'purchase_order_id'?, 'sales_order_id'?,
     *                     'cash_id'?, 'expense_date', 'amount', 'description'?] — 'cash_id'
     *                     opsional supaya tetap kompatibel dengan pemanggil lama (mis. biaya
     *                     lainnya dari PO/SO yang belum memilih kas), tapi kalau diisi maka
     *                     saldo kas terkait langsung dikurangi sebesar 'amount'.
     *
     * @throws \RuntimeException kalau saldo kas yang dipilih tidak cukup
     */
    public function create(array $data): Expense
    {
        return DB::transaction(function () use ($data) {
            $cash = ! empty($data['cash_id']) ? Cash::findOrFail($data['cash_id']) : null;

            $expense = Expense::create($data);

            if ($cash) {
                $this->cashService->decrease($cash, (float) $expense->amount);
            }

            $this->cashFlowService->recordOut(
                $expense->expense_date,
                $expense->amount,
                $expense,
                $expense->description ?? "Biaya operasional: {$expense->category->name}",
                $cash
            );

            return $expense;
        });
    }

    // Update expense + sinkronkan saldo kas & cash_flow terkait, biar ledger
    // dan saldo kas tetap konsisten. Kalau kas atau nominalnya berubah, saldo
    // kas lama dikembalikan dulu baru kas baru dipotong ulang.
    public function update(Expense $expense, array $data): Expense
    {
        return DB::transaction(function () use ($expense, $data) {
            $oldCash   = $expense->cash_id ? Cash::find($expense->cash_id) : null;
            $oldAmount = (float) $expense->amount;

            $expense->update($data);
            $expense->refresh();

            $newCash = $expense->cash_id ? Cash::findOrFail($expense->cash_id) : null;

            if ($oldCash) {
                $this->cashService->increase($oldCash, $oldAmount);
            }
            if ($newCash) {
                $this->cashService->decrease($newCash, (float) $expense->amount);
            }

            $cashFlow = $this->findCashFlow($expense);

            if ($cashFlow) {
                $cashFlow->update([
                    'transaction_date' => $expense->expense_date,
                    'amount'           => $expense->amount,
                    'cash_id'          => $newCash?->id,
                    'description'      => $expense->description ?? "Biaya operasional: {$expense->category->name}",
                ]);
            }

            return $expense;
        });
    }

    // Hapus expense + kembalikan saldo kas terkait + cash_flow terkait
    // sekaligus, supaya tidak ada ledger nyangkut ataupun saldo kas yang keliru.
    public function delete(Expense $expense): void
    {
        DB::transaction(function () use ($expense) {
            if ($expense->cash_id) {
                $cash = Cash::find($expense->cash_id);
                if ($cash) {
                    $this->cashService->increase($cash, (float) $expense->amount);
                }
            }

            $this->findCashFlow($expense)?->delete();
            $expense->delete();
        });
    }

    protected function findCashFlow(Expense $expense): ?CashFlow
    {
        return CashFlow::where('source_type', Expense::class)
            ->where('source_id', $expense->id)
            ->first();
    }
}