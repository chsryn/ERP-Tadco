<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator; // <-- Wajib ditambahkan untuk memanggil Validator manual

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search');

        $customers = Customer::query()
            ->when($search, function ($query, $search) {
                $query->where('customer_code', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('district', 'like', "%{$search}%");
            })
            ->orderBy('customer_code')
            ->paginate(10)
            ->withQueryString();

        return view('customers.index', compact('customers', 'search'));
    }

    public function create()
    {
        $nextCustomerCode = $this->generateCustomerCode();

        return view('customers.create', compact('nextCustomerCode'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_code' => ['nullable', 'string', 'max:30', 'unique:customers,customer_code'],
            'customer_name' => ['required', 'string', 'max:150'],
            'province' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'sub_district' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if (empty($validated['customer_code'])) {
            $validated['customer_code'] = $this->generateCustomerCode();
        }

        $validated['is_active'] = $request->has('is_active');

        Customer::create($validated);

        return redirect()
            ->route('customers.index')
            ->with('success', 'Data customer berhasil ditambahkan.');
    }

    public function show(Customer $customer)
    {
        return view('customers.show', compact('customer'));
    }

    public function edit(Customer $customer)
    {
        return view('customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'customer_code' => [
                'required',
                'string',
                'max:30',
                Rule::unique('customers', 'customer_code')->ignore($customer->id),
            ],
            'customer_name' => ['required', 'string', 'max:150'],
            'province' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'sub_district' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->has('is_active');

        $customer->update($validated);

        return redirect()
            ->route('customers.index')
            ->with('success', 'Data customer berhasil diperbarui.');
    }

    public function destroy(Customer $customer)
    {
        Customer::destroy($customer->id);

        return redirect()
            ->route('customers.index')
            ->with('success', 'Data customer berhasil dihapus.');
    }

    // --- FUNGSI BARU UNTUK MENANGANI MODAL/POP-UP DARI HALAMAN DO ---
    public function storeAjax(Request $request)
    {
        // 1. Gunakan Validator manual untuk mencegah redirect otomatis Laravel
        $validator = Validator::make($request->all(), [
            'customer_code' => ['nullable', 'string', 'max:50', 'unique:customers,customer_code'],
            'customer_name' => ['required', 'string', 'max:255'],
        ], [
            'customer_code.unique' => 'Kode Customer ini sudah digunakan. Silakan gunakan kode lain.'
        ]);

        // 2. Jika validasi gagal, PAKSA kembalikan format JSON dengan kode error 422
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors()
            ], 422);
        }

        $customerCode = $request->customer_code;
        if (empty($customerCode)) {
            $customerCode = $this->generateCustomerCode();
        }

        // 3. Jika berhasil dan kode unik, simpan data ke database
        $customer = Customer::create([
            'customer_code' => $customerCode,
            'customer_name' => $request->customer_name,
            'is_active'     => true,
        ]);

        // 4. Kembalikan data customer baru dalam format JSON (Sukses)
        return response()->json([
            'message'  => 'Customer berhasil ditambahkan',
            'customer' => $customer
        ]);
    }

    /**
     * Auto-generate customer code based on the latest customer ID.
     */
    private function generateCustomerCode(): string
    {
        $latestCustomer = Customer::query()->orderByDesc('id')->first();

        if (!$latestCustomer || empty($latestCustomer->customer_code)) {
            return 'CUST-01';
        }

        if (preg_match('/^(.*?)-(\d+)$/', $latestCustomer->customer_code, $matches)) {
            $prefix = $matches[1];
            $digits = $matches[2];
            $nextNumber = (int) $digits + 1;
            $paddedNumber = str_pad((string) $nextNumber, strlen($digits), '0', STR_PAD_LEFT);

            return $prefix . '-' . $paddedNumber;
        }

        return 'CUST-' . str_pad((string) ($latestCustomer->id + 1), 2, '0', STR_PAD_LEFT);
    }
}
