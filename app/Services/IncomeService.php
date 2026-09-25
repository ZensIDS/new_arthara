<?php

namespace App\Services;

use App\Models\Cash;
use App\Models\CashFlow;
use App\Models\Income;
use Illuminate\Support\Facades\DB;

class IncomeService
{
    public function __construct(
        protected CashFlowService $cashFlowService,
        protected CashService $cashService,
    ) {}

    // Simpan pemasukan lain (mis. modal, pinjaman, dll), tambahkan saldo kas
    // yang dipilih sebesar 'amount', lalu catat sebagai kas masuk di ledger
    // cash_flows. Sama seperti ExpenseService::create(), cuma arah kasnya
    // kebalikan (increase & recordIn, bukan decrease & recordOut).
    public function create(array $data): Income
    {
        return DB::transaction(function () use ($data) {
            $cash = ! empty($data['cash_id']) ? Cash::findOrFail($data['cash_id']) : null;

            $income = Income::create($data);

            if ($cash) {
                $this->cashService->increase($cash, (float) $income->amount);
            }

            $this->cashFlowService->recordIn(
                $income->income_date,
                $income->amount,
                $income,
                $income->description ?? "Pemasukan lain: {$income->category->name}",
                $cash
            );

            return $income;
        });
    }

    // Update income + sinkronkan saldo kas & cash_flow terkait, biar ledger
    // dan saldo kas tetap konsisten. Kalau kas atau nominalnya berubah, saldo
    // kas lama dikembalikan (dikurangi) dulu baru kas baru ditambah ulang.
    public function update(Income $income, array $data): Income
    {
        return DB::transaction(function () use ($income, $data) {
            $oldCash   = $income->cash_id ? Cash::find($income->cash_id) : null;
            $oldAmount = (float) $income->amount;

            $income->update($data);
            $income->refresh();

            $newCash = $income->cash_id ? Cash::findOrFail($income->cash_id) : null;

            if ($oldCash) {
                $this->cashService->decrease($oldCash, $oldAmount);
            }
            if ($newCash) {
                $this->cashService->increase($newCash, (float) $income->amount);
            }

            $cashFlow = $this->findCashFlow($income);

            if ($cashFlow) {
                $cashFlow->update([
                    'transaction_date' => $income->income_date,
                    'amount'           => $income->amount,
                    'cash_id'          => $newCash?->id,
                    'description'      => $income->description ?? "Pemasukan lain: {$income->category->name}",
                ]);
            }

            return $income;
        });
    }

    // Hapus income + kembalikan (kurangi) saldo kas terkait + cash_flow
    // terkait sekaligus, supaya tidak ada ledger nyangkut ataupun saldo kas
    // yang keliru.
    public function delete(Income $income): void
    {
        DB::transaction(function () use ($income) {
            if ($income->cash_id) {
                $cash = Cash::find($income->cash_id);
                if ($cash) {
                    $this->cashService->decrease($cash, (float) $income->amount);
                }
            }

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