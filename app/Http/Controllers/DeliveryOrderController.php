<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDeliveryOrderRequest;
use App\Models\Customer;
use App\Models\DeliveryOrder;
use App\Models\Product;
use App\Services\DeliveryOrderService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DeliveryOrderController extends Controller
{
    public function __construct(
        protected DeliveryOrderService $deliveryOrderService
    ) {}

    public function index(Request $request)
    {
        $search = $request->get('search');

        $deliveryOrders = DeliveryOrder::query()
            ->with(['customer'])
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('do_number', 'like', "%{$search}%")
                        ->orWhereHas('customer', function ($customerQuery) use ($search) {
                            $customerQuery->where('customer_name', 'like', "%{$search}%")
                                ->orWhere('customer_code', 'like', "%{$search}%");
                        });
                });
            })
            ->orderByDesc('do_date')
            ->paginate(10)
            ->withQueryString();

        return view('delivery_orders.index', compact('deliveryOrders', 'search'));
    }

    public function create()
    {
        $customers = Customer::query()
            ->where('is_active', '=', true)
            ->orderBy('customer_name')
            ->get();

        $products = Product::query()
            ->with(['discounts', 'stockBalances'])
            ->where('is_active', '=', true)
            ->whereHas('stockBalances', function ($q) {
                $q->whereRaw('(qty_available - qty_reserved) > 0');
            })
            ->orderBy('product_name')
            ->get();

        return view('delivery_orders.create', compact('customers', 'products'));
    }

    public function store(StoreDeliveryOrderRequest $request)
    {
        try {
            $this->deliveryOrderService->createDeliveryOrder(
                $request->validated(),
                $request->items(),
                auth()->id()
            );

            return redirect()
                ->route('delivery_orders.index')
                ->with('success', 'Delivery Order berhasil dibuat dan stok berhasil di-reserve.');
        } catch (ValidationException $e) {
            return back()
                ->withErrors($e->errors())
                ->withInput();
        }
    }

    public function show(DeliveryOrder $deliveryOrder)
    {
        $deliveryOrder->load([
            'customer',
            'items.product',
        ]);

        return view('delivery_orders.show', compact('deliveryOrder'));
    }

    public function edit(DeliveryOrder $deliveryOrder)
    {
        return redirect()
            ->route('delivery_orders.show', $deliveryOrder)
            ->with('success', 'DO yang sudah dibuat tidak diedit langsung. Batalkan DO lalu buat ulang jika ada kesalahan.');
    }

    public function update(Request $request, DeliveryOrder $deliveryOrder)
    {
        return redirect()
            ->route('delivery_orders.show', $deliveryOrder)
            ->with('success', 'Update DO belum diaktifkan. Gunakan pembatalan DO jika diperlukan.');
    }

    public function destroy(DeliveryOrder $deliveryOrder)
    {
        try {
            $this->deliveryOrderService->cancelDeliveryOrder($deliveryOrder);

            return redirect()
                ->route('delivery_orders.index')
                ->with('success', 'Delivery Order berhasil dibatalkan dan stok reserved dikembalikan.');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }
    }
}
