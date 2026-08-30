<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductDiscount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search');

        $products = Product::with('discounts')
            ->when($search, function ($query, $search) {
                $query->where('product_code', 'like', "%{$search}%")
                    ->orWhere('product_name', 'like', "%{$search}%")
                    ->orWhere('segment', 'like', "%{$search}%")
                    ->orWhere('uom', 'like', "%{$search}%")
                    ->orWhere('net_weight', 'like', "%{$search}%");
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
        $request->merge([
            'is_active' => $request->has('is_active') ? true : false,
        ]);

        $validated = $request->validate([
            'product_code' => ['required', 'string', 'max:50', 'unique:products,product_code'],
            'product_name' => ['required', 'string', 'max:200'],
            'base_price' => ['required', 'numeric', 'min:0'],
            'uom' => ['required', 'string', 'in:BOX,SACK'],
            'net_weight' => ['nullable', 'string', 'max:50'],
            'segment' => ['nullable', 'string', 'max:150'],
            'is_active' => ['nullable', 'boolean'],

            'discounts' => ['nullable', 'array'],
            'discounts.*.min_qty' => ['nullable', 'numeric', 'min:0'],
            'discounts.*.max_qty' => ['nullable', 'numeric', 'min:0'],
            'discounts.*.discount_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $uom = strtoupper($validated['uom']);

        DB::transaction(function () use ($request, $validated, $uom) {
            $product = Product::create([
                'product_code' => $validated['product_code'],
                'product_name' => $validated['product_name'],
                'base_price' => $validated['base_price'],
                'uom' => $uom,
                'net_weight' => $validated['net_weight'] ?? null,
                'segment' => $validated['segment'] ?? null,
                'is_active' => $request->boolean('is_active'),
            ]);

            $tiers = $uom === 'SACK'
                ? ['S1', 'S2', 'S3']
                : ['S1', 'S2', 'S3', 'S4', 'S5'];

            foreach ($tiers as $tier) {
                ProductDiscount::create([
                    'product_id' => $product->id,
                    'strata_level' => $tier,
                    'min_qty' => $request->input("discounts.{$tier}.min_qty", 0) ?? 0,
                    'max_qty' => $request->input("discounts.{$tier}.max_qty") ?: null,
                    'discount_percentage' => $request->input("discounts.{$tier}.discount_percentage", 0) ?? 0,
                ]);
            }
        });

        return redirect()
            ->route('products.index')
            ->with('success', 'Data produk berhasil ditambahkan.');
    }

    public function show(Product $product)
    {
        $product->load('discounts');

        return view('products.show', compact('product'));
    }

    public function edit(Product $product)
    {
        $product->load('discounts');

        $discounts = $product->discounts->keyBy('strata_level');

        return view('products.edit', compact('product', 'discounts'));
    }

    public function update(Request $request, Product $product)
    {
        $request->merge([
            'is_active' => $request->has('is_active') ? true : false,
        ]);

        $validated = $request->validate([
            'product_code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('products', 'product_code')->ignore($product->id),
            ],
            'product_name' => ['required', 'string', 'max:200'],
            'base_price' => ['required', 'numeric', 'min:0'],
            'uom' => ['required', 'string', 'in:BOX,SACK'],
            'net_weight' => ['nullable', 'string', 'max:50'],
            'segment' => ['nullable', 'string', 'max:150'],
            'is_active' => ['nullable', 'boolean'],

            'discounts' => ['nullable', 'array'],
            'discounts.*.min_qty' => ['nullable', 'numeric', 'min:0'],
            'discounts.*.max_qty' => ['nullable', 'numeric', 'min:0'],
            'discounts.*.discount_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $uom = strtoupper($validated['uom']);

        DB::transaction(function () use ($request, $validated, $product, $uom) {
            $product->update([
                'product_code' => $validated['product_code'],
                'product_name' => $validated['product_name'],
                'base_price' => $validated['base_price'],
                'uom' => $uom,
                'net_weight' => $validated['net_weight'] ?? null,
                'segment' => $validated['segment'] ?? null,
                'is_active' => $request->boolean('is_active'),
            ]);

            $tiers = $uom === 'SACK'
                ? ['S1', 'S2', 'S3']
                : ['S1', 'S2', 'S3', 'S4', 'S5'];

            // Remove discounts for tiers no longer allowed (e.g. S4 & S5 if changed to SACK)
            $product->discounts()->whereNotIn('strata_level', $tiers)->delete();

            foreach ($tiers as $tier) {
                ProductDiscount::updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'strata_level' => $tier,
                    ],
                    [
                        'min_qty' => $request->input("discounts.{$tier}.min_qty", 0) ?? 0,
                        'max_qty' => $request->input("discounts.{$tier}.max_qty") ?: null,
                        'discount_percentage' => $request->input("discounts.{$tier}.discount_percentage", 0) ?? 0,
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
