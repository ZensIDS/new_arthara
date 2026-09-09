<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');
        $search    = trim((string) $request->input('search', ''));

        $customers = Customer::query()
            ->when($startDate, fn($q) => $q->whereDate('created_at', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('created_at', '<=', $endDate))
            ->when($search !== '', function ($q) use ($search) {
                // Cari di semua kolom yang tampil di tabel: Nama, Telepon, Email.
                $q->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        if ($request->ajax()) {
            return view('customers._table', compact('customers'));
        }

        return view('customers.index', compact('customers', 'startDate', 'endDate', 'search'));
    }

    public function store(StoreCustomerRequest $request)
    {
        $customer = Customer::create($request->validated());

        return response()->json([
            'message' => 'Customer berhasil ditambahkan.',
            'data'    => $customer,
        ]);
    }

    public function update(UpdateCustomerRequest $request, Customer $customer)
    {
        $customer->update($request->validated());

        return response()->json([
            'message' => 'Customer berhasil diperbarui.',
            'data'    => $customer,
        ]);
    }

    public function destroy(Customer $customer)
    {
        if ($customer->salesOrders()->exists()) {
            return response()->json([
                'message' => 'Customer tidak bisa dihapus karena masih punya riwayat SO.',
            ], 422);
        }

        $customer->delete();

        return response()->json([
            'message' => 'Customer berhasil dihapus.',
        ]);
    }
}
