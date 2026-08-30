<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /**
     * Handle global live autocomplete search for Master Data (Customers & Products).
     */
    public function global(Request $request): JsonResponse
    {
        $q = trim($request->input('q', ''));

        if (mb_strlen($q) < 2) {
            return response()->json([
                'query' => $q,
                'total' => 0,
                'customers' => [],
                'products' => [],
            ]);
        }

        // Search Customers
        $customers = Customer::query()
            ->where(function ($query) use ($q) {
                $query->where('customer_name', 'like', "%{$q}%")
                    ->orWhere('customer_code', 'like', "%{$q}%")
                    ->orWhere('city', 'like', "%{$q}%")
                    ->orWhere('province', 'like', "%{$q}%")
                    ->orWhere('address', 'like', "%{$q}%");
            })
            ->limit(5)
            ->get()
            ->map(function ($item) {
                $location = array_filter([$item->city, $item->province]);
                return [
                    'id' => $item->id,
                    'code' => $item->customer_code,
                    'name' => $item->customer_name,
                    'subtitle' => !empty($location) ? implode(', ', $location) : ($item->address ?? 'Customer'),
                    'url' => route('customers.show', $item->id),
                    'is_active' => (bool) $item->is_active,
                ];
            });

        // Search Products
        $products = Product::query()
            ->where(function ($query) use ($q) {
                $query->where('product_name', 'like', "%{$q}%")
                    ->orWhere('product_code', 'like', "%{$q}%")
                    ->orWhere('segment', 'like', "%{$q}%")
                    ->orWhere('uom', 'like', "%{$q}%");
            })
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'code' => $item->product_code,
                    'name' => $item->product_name,
                    'subtitle' => 'Rp ' . number_format($item->base_price, 0, ',', '.') . ($item->uom ? ' / ' . $item->uom : ''),
                    'url' => route('products.show', $item->id),
                    'is_active' => (bool) $item->is_active,
                ];
            });

        $totalCount = $customers->count() + $products->count();

        return response()->json([
            'query' => $q,
            'total' => $totalCount,
            'customers' => $customers,
            'products' => $products,
        ]);
    }
}
