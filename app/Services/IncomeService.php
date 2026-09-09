<?php

namespace App\Services;

use App\Models\CashFlow;
use App\Models\Income;
use Illuminate\Support\Facades\DB;

class IncomeService
{
    public function __construct(protected CashFlowService $cashFlowService) {}

    // Simpan pemasukan lain (mis. modal, pinjaman, dll) + catat sebagai kas masuk
    // di ledger cash_flows. Sama seperti ExpenseService::create(), cuma arah
    // kasnya kebalikan (recordIn, bukan recordOut).
    public function create(array $data): Income
    {
        return DB::transaction(function () use ($data) {
            $income = Income::create($data);

            $this->cashFlowService->recordIn(
                $data['income_date'],
                $data['amount'],
                $income,
                $income->description ?? "Pemasukan lain: {$income->category->name}"
            );

            return $income;
        });
    }

    // Update income + sinkronkan cash_flow terkait (biar ledger kas tetap konsisten).
    public function update(Income $income, array $data): Income
    {
        return DB::transaction(function () use ($income, $data) {
            $income->update($data);
            $income->refresh();

            $cashFlow = $this->findCashFlow($income);

            if ($cashFlow) {
                $cashFlow->update([
                    'transaction_date' => $income->income_date,
                    'amount'           => $income->amount,
                    'description'      => $income->description ?? "Pemasukan lain: {$income->category->name}",
                ]);
            }

            return $income;
        });
    }

    // Hapus income + cash_flow terkait sekaligus, supaya tidak ada ledger nyangkut.
    public function delete(Income $income): void
    {
        DB::transaction(function () use ($income) {
            $this->findCashFlow($income)?->delete();
            $income->delete();
        });
    }

    protected function findCashFlow(Income $income): ?CashFlow
    {
        return CashFlow::where('source_type', Income::class)
            ->where('source_id', $income->id)
            ->first();
    }
}
