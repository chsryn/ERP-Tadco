<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
        return view('customers.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_code' => ['required', 'string', 'max:30', 'unique:customers,customer_code'],
            'customer_name' => ['required', 'string', 'max:150'],
            'province' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'sub_district' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string'],
            'type_of_business' => ['nullable', 'string', 'max:100'],
            'market' => ['nullable', 'string', 'max:100'],
            'customer_type' => ['nullable', 'string', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
        ]);

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
            'type_of_business' => ['nullable', 'string', 'max:100'],
            'market' => ['nullable', 'string', 'max:100'],
            'customer_type' => ['nullable', 'string', 'max:100'],
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
}
