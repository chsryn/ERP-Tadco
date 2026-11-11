<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductDiscount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search');

        $products = Product::with('discounts')
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('product_code', 'like', "%{$search}%")
                        ->orWhere('product_name', 'like', "%{$search}%")
                        ->orWhere('segment', 'like', "%{$search}%")
                        ->orWhere('uom', 'like', "%{$search}%")
                        ->orWhere('net_weight', 'like', "%{$search}%")
                        ->orWhere('dynamics_code', 'like', "%{$search}%");
                });
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

            // SCM-02 purchasing data
            'dynamics_code' => ['nullable', 'string', 'max:50', 'unique:products,dynamics_code'],
            'purchase_price' => ['nullable', 'numeric', 'min:0'],
            'weight_kg' => ['nullable', 'numeric', 'gt:0'],
            'target_stock' => ['nullable', 'numeric', 'min:0'],

            'discounts' => ['nullable', 'array'],
            // min_qty/max_qty bertipe integer di database.
            'discounts.*.min_qty' => ['nullable', 'integer', 'min:0'],
            'discounts.*.max_qty' => ['nullable', 'integer', 'min:0'],
            'discounts.*.discount_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $uom = strtoupper($validated['uom']);
        $tiers = $this->discountTiers($uom);
        $discountRows = $request->has('discounts')
            ? $this->normalizeDiscountRows($request, $tiers)
            : [];

        $this->validateDiscountRows($discountRows);

        DB::transaction(function () use ($request, $validated, $uom, $discountRows) {
            $product = Product::create([
                'product_code' => $validated['product_code'],
                'product_name' => $validated['product_name'],
                'base_price' => $validated['base_price'],
                'uom' => $uom,
                'net_weight' => $validated['net_weight'] ?? null,
                'segment' => $validated['segment'] ?? null,
                'is_active' => $request->boolean('is_active'),
                'dynamics_code' => $validated['dynamics_code'] ?? null,
                'purchase_price' => $validated['purchase_price'] ?? null,
                'weight_kg' => $validated['weight_kg'] ?? null,
                'target_stock' => $validated['target_stock'] ?? null,
            ]);

            foreach ($discountRows as $discountRow) {
                ProductDiscount::create([
                    'product_id' => $product->id,
                    ...$discountRow,
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

            // SCM-02 purchasing data
            'dynamics_code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('products', 'dynamics_code')->ignore($product->id),
            ],
            'purchase_price' => ['nullable', 'numeric', 'min:0'],
            'weight_kg' => ['nullable', 'numeric', 'gt:0'],
            'target_stock' => ['nullable', 'numeric', 'min:0'],

            'discounts' => ['nullable', 'array'],
            // min_qty/max_qty bertipe integer di database.
            'discounts.*.min_qty' => ['nullable', 'integer', 'min:0'],
            'discounts.*.max_qty' => ['nullable', 'integer', 'min:0'],
            'discounts.*.discount_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $uom = strtoupper($validated['uom']);
        $tiers = $this->discountTiers($uom);

        // Jika form tidak mengirim data diskon sama sekali, jangan sentuh
        // product_discounts yang sudah ada.
        $discountRows = null;
        if ($request->has('discounts')) {
            $discountRows = $this->normalizeDiscountRows($request, $tiers);
            $this->validateDiscountRows($discountRows);
        }

        DB::transaction(function () use ($request, $validated, $product, $uom, $tiers, $discountRows) {
            $product->update([
                'product_code' => $validated['product_code'],
                'product_name' => $validated['product_name'],
                'base_price' => $validated['base_price'],
                'uom' => $uom,
                'net_weight' => $validated['net_weight'] ?? null,
                'segment' => $validated['segment'] ?? null,
                'is_active' => $request->boolean('is_active'),
                'dynamics_code' => $validated['dynamics_code'] ?? null,
                'purchase_price' => $validated['purchase_price'] ?? null,
                'weight_kg' => $validated['weight_kg'] ?? null,
                'target_stock' => $validated['target_stock'] ?? null,
            ]);

            // Remove discounts for tiers no longer allowed (e.g. S4 & S5 if changed to SACK)
            $product->discounts()->whereNotIn('strata_level', $tiers)->delete();

            if ($discountRows === null) {
                return;
            }

            $configured = collect($discountRows)->keyBy('strata_level');

            foreach ($tiers as $tier) {
                if ($configured->has($tier)) {
                    // Duplikasi product_id + strata_level tidak dicegah oleh
                    // unique constraint. Jangan update salah satu secara acak.
                    if ($product->discounts()->where('strata_level', $tier)->count() > 1) {
                        throw ValidationException::withMessages([
                            'discounts' => "Produk ini memiliki lebih dari satu baris diskon untuk strata {$tier}. "
                                . 'Hapus duplikat terlebih dahulu sebelum menyimpan.',
                        ]);
                    }

                    ProductDiscount::updateOrCreate(
                        [
                            'product_id' => $product->id,
                            'strata_level' => $tier,
                        ],
                        $configured->get($tier)
                    );
                } else {
                    $product->discounts()->where('strata_level', $tier)->delete();
                }
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

    /**
     * Strata yang diizinkan berdasarkan UOM.
     *
     * @return string[]
     */
    private function discountTiers(string $uom): array
    {
        return $uom === 'SACK'
            ? ['S1', 'S2', 'S3']
            : ['S1', 'S2', 'S3', 'S4', 'S5'];
    }

    /**
     * Ambil hanya strata yang benar-benar dikonfigurasi (punya min/max qty).
     * Diskon tanpa rentang qty tidak boleh menjadi strata tak terbatas.
     *
     * @param  string[]  $tiers
     * @return array<int, array{strata_level: string, min_qty: int, max_qty: int|null, discount_percentage: float}>
     */
    private function normalizeDiscountRows(Request $request, array $tiers): array
    {
        $rows = [];

        foreach ($tiers as $tier) {
            $minQty = $request->input("discounts.{$tier}.min_qty");
            $maxQty = $request->input("discounts.{$tier}.max_qty");
            $discount = $request->input("discounts.{$tier}.discount_percentage");

            $hasMin = $minQty !== null && $minQty !== '';
            $hasMax = $maxQty !== null && $maxQty !== '';

            if (!$hasMin && !$hasMax) {
                continue;
            }

            $rows[] = [
                'strata_level' => $tier,
                'min_qty' => $hasMin ? (int) $minQty : 0,
                'max_qty' => $hasMax ? (int) $maxQty : null,
                'discount_percentage' => ($discount !== null && $discount !== '')
                    ? (float) $discount
                    : 0.0,
            ];
        }

        return $rows;
    }

    /**
     * Tolak rentang yang max < min atau tumpang tindih.
     *
     * @param  array<int, array{strata_level: string, min_qty: int, max_qty: int|null, discount_percentage: float}>  $rows
     *
     * @throws ValidationException
     */
    private function validateDiscountRows(array $rows): void
    {
        foreach ($rows as $row) {
            if ($row['max_qty'] !== null && $row['max_qty'] < $row['min_qty']) {
                throw ValidationException::withMessages([
                    'discounts' => "Rentang qty strata {$row['strata_level']} tidak valid: "
                        . 'max_qty tidak boleh lebih kecil dari min_qty.',
                ]);
            }
        }

        usort($rows, fn (array $a, array $b) => $a['min_qty'] <=> $b['min_qty']);

        for ($i = 1; $i < count($rows); $i++) {
            $previous = $rows[$i - 1];
            $current = $rows[$i];

            if ($previous['max_qty'] === null || $current['min_qty'] <= $previous['max_qty']) {
                throw ValidationException::withMessages([
                    'discounts' => "Rentang qty strata {$previous['strata_level']} dan {$current['strata_level']} "
                        . 'saling tumpang tindih. Pastikan setiap qty hanya cocok dengan satu strata.',
                ]);
            }
        }
    }
}
