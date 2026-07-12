<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductPrice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search');

        $products = Product::with('prices')
            ->when($search, function ($query, $search) {
                $query->where('product_code', 'like', "%{$search}%")
                    ->orWhere('product_name', 'like', "%{$search}%")
                    ->orWhere('segment', 'like', "%{$search}%");
            })
            ->orderBy('product_code')
            ->paginate(10)
            ->withQueryString();

        return view('products.index', compact('products', 'search'));
    }

    public function create()
    {
        return view('products.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_code' => ['required', 'string', 'max:50', 'unique:products,product_code'],
            'product_name' => ['required', 'string', 'max:200'],
            'uom' => ['nullable', 'string', 'max:30'],
            'uom_secondary' => ['nullable', 'string', 'max:30'],
            'segment' => ['nullable', 'string', 'max:150'],
            'is_active' => ['nullable', 'boolean'],

            'prices.S1.price' => ['nullable', 'numeric', 'min:0'],
            'prices.S1.discount_rate' => ['nullable', 'numeric', 'min:0'],
            'prices.S2.price' => ['nullable', 'numeric', 'min:0'],
            'prices.S2.discount_rate' => ['nullable', 'numeric', 'min:0'],
            'prices.S3.price' => ['nullable', 'numeric', 'min:0'],
            'prices.S3.discount_rate' => ['nullable', 'numeric', 'min:0'],
            'prices.S4.price' => ['nullable', 'numeric', 'min:0'],
            'prices.S4.discount_rate' => ['nullable', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($request, $validated) {
            $product = Product::create([
                'product_code' => $validated['product_code'],
                'product_name' => $validated['product_name'],
                'uom' => $validated['uom'] ?? null,
                'uom_secondary' => $validated['uom_secondary'] ?? null,
                'segment' => $validated['segment'] ?? null,
                'is_active' => $request->has('is_active'),
            ]);

            foreach (['S1', 'S2', 'S3', 'S4'] as $tier) {
                ProductPrice::create([
                    'product_id' => $product->id,
                    'tier_code' => $tier,
                    'price' => $request->input("prices.{$tier}.price", 0) ?? 0,
                    'discount_rate' => $request->input("prices.{$tier}.discount_rate", 0) ?? 0,
                    'valid_from' => null,
                    'valid_until' => null,
                ]);
            }
        });

        return redirect()
            ->route('products.index')
            ->with('success', 'Data produk berhasil ditambahkan.');
    }

    public function show(Product $product)
    {
        $product->load('prices');

        return view('products.show', compact('product'));
    }

    public function edit(Product $product)
    {
        $product->load('prices');

        $prices = $product->prices->keyBy('tier_code');

        return view('products.edit', compact('product', 'prices'));
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'product_code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('products', 'product_code')->ignore($product->id),
            ],
            'product_name' => ['required', 'string', 'max:200'],
            'uom' => ['nullable', 'string', 'max:30'],
            'uom_secondary' => ['nullable', 'string', 'max:30'],
            'segment' => ['nullable', 'string', 'max:150'],
            'is_active' => ['nullable', 'boolean'],

            'prices.S1.price' => ['nullable', 'numeric', 'min:0'],
            'prices.S1.discount_rate' => ['nullable', 'numeric', 'min:0'],
            'prices.S2.price' => ['nullable', 'numeric', 'min:0'],
            'prices.S2.discount_rate' => ['nullable', 'numeric', 'min:0'],
            'prices.S3.price' => ['nullable', 'numeric', 'min:0'],
            'prices.S3.discount_rate' => ['nullable', 'numeric', 'min:0'],
            'prices.S4.price' => ['nullable', 'numeric', 'min:0'],
            'prices.S4.discount_rate' => ['nullable', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($request, $validated, $product) {
            $product->update([
                'product_code' => $validated['product_code'],
                'product_name' => $validated['product_name'],
                'uom' => $validated['uom'] ?? null,
                'uom_secondary' => $validated['uom_secondary'] ?? null,
                'segment' => $validated['segment'] ?? null,
                'is_active' => $request->has('is_active'),
            ]);

            foreach (['S1', 'S2', 'S3', 'S4'] as $tier) {
                ProductPrice::updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'tier_code' => $tier,
                    ],
                    [
                        'price' => $request->input("prices.{$tier}.price", 0) ?? 0,
                        'discount_rate' => $request->input("prices.{$tier}.discount_rate", 0) ?? 0,
                        'valid_from' => null,
                        'valid_until' => null,
                    ]
                );
            }
        });

        return redirect()
            ->route('products.index')
            ->with('success', 'Data produk berhasil diperbarui.');
    }

    public function destroy(Product $product)
    {
        Product::destroy($product->id);

        return redirect()
            ->route('products.index')
            ->with('success', 'Data produk berhasil dihapus.');
    }
}
