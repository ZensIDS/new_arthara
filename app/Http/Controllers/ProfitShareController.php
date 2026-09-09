<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProfitShareRequest;
use App\Http\Requests\UpdateProfitShareRequest;
use App\Models\ProfitShare;
use Illuminate\Http\Request;

class ProfitShareController extends Controller
{
    public function index(Request $request)
    {
        $profitShares = ProfitShare::orderByDesc('is_active')
            ->orderByDesc('percentage')
            ->paginate(10)
            ->withQueryString();

        $totalActivePercentage = ProfitShare::where('is_active', true)->sum('percentage');

        return view('profit-shares.index', compact('profitShares', 'totalActivePercentage'));
    }

    public function store(StoreProfitShareRequest $request)
    {
        $profitShare = ProfitShare::create($request->validated());

        return response()->json([
            'message' => 'Bagi hasil berhasil ditambahkan.',
            'data'    => $profitShare,
        ]);
    }

    public function update(UpdateProfitShareRequest $request, ProfitShare $profitShare)
    {
        $profitShare->update($request->validated());

        return response()->json([
            'message' => 'Bagi hasil berhasil diperbarui.',
            'data'    => $profitShare,
        ]);
    }

    public function destroy(ProfitShare $profitShare)
    {
        $profitShare->delete();

        return response()->json([
            'message' => 'Bagi hasil berhasil dihapus.',
        ]);
    }
}
