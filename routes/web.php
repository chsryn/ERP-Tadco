<?php
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AiChatController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\PurchaseReceiptController;
use App\Http\Controllers\ReorderController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\CustomerController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\DeliveryOrderController;
use App\Http\Controllers\ShipmentController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SearchController;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard.index');
    }
    return redirect()->route('login');
});

// Authentication routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware('auth')->group(function () {
    // Global search route
    Route::get('/search/global', [SearchController::class, 'global'])->name('search.global');

    Route::resource('users', UserController::class)->middleware('role:admin');

    // Dashboard route
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');

    // AI Assistant read-only (admin, sales, finance)
    Route::post('/ai/chat', AiChatController::class)
        ->middleware(['role:admin|sales|finance', 'throttle:' . config('ai.rate_limit', '20,1')])
        ->name('ai.chat');

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Resource routes
    Route::resource('customers', CustomerController::class);
    Route::post('/customers/ajax', [App\Http\Controllers\CustomerController::class, 'storeAjax'])->name('customers.store_ajax');
    Route::resource('products', ProductController::class);

    // Purchasing (SCM) — granular permission per operasi (SCM-09).
    // Urutan resource dipisah per aksi; create/store harus didaftarkan sebelum {model} agar tidak tertangkap binding.
    Route::resource('suppliers', SupplierController::class)->only(['create', 'store'])
        ->middleware('permission:suppliers.create');
    Route::resource('suppliers', SupplierController::class)->only(['index', 'show'])
        ->middleware('permission:suppliers.view');
    Route::resource('suppliers', SupplierController::class)->only(['edit', 'update'])
        ->middleware('permission:suppliers.update');
    Route::resource('suppliers', SupplierController::class)->only(['destroy'])
        ->middleware('permission:suppliers.delete');

    // Auto PO / reorder harus didaftarkan sebelum resource agar tidak tertangkap {purchaseOrder}
    Route::get('/purchase-orders/reorder', [ReorderController::class, 'index'])
        ->middleware('permission:auto-po.view')
        ->name('purchase_orders.reorder');
    Route::post('/purchase-orders/reorder', [ReorderController::class, 'generate'])
        ->middleware('permission:auto-po.create')
        ->name('purchase_orders.reorder.generate');

    Route::resource('purchase-orders', PurchaseOrderController::class)
        ->only(['create', 'store'])
        ->parameters(['purchase-orders' => 'purchaseOrder'])
        ->names('purchase_orders')
        ->middleware('permission:purchase-orders.create');

    Route::resource('purchase-orders', PurchaseOrderController::class)
        ->only(['index', 'show'])
        ->parameters(['purchase-orders' => 'purchaseOrder'])
        ->names('purchase_orders')
        ->middleware('permission:purchase-orders.view');

    Route::resource('purchase-orders', PurchaseOrderController::class)
        ->only(['edit', 'update'])
        ->parameters(['purchase-orders' => 'purchaseOrder'])
        ->names('purchase_orders')
        ->middleware('permission:purchase-orders.update');

    Route::post('/purchase-orders/{purchaseOrder}/confirm', [PurchaseOrderController::class, 'confirm'])
        ->middleware('permission:purchase-orders.confirm')
        ->name('purchase_orders.confirm');
    Route::get('/purchase-orders/{purchaseOrder}/print', [PurchaseOrderController::class, 'printOrder'])
        ->middleware('permission:purchase-orders.print')
        ->name('purchase_orders.print');
    Route::post('/purchase-orders/{purchaseOrder}/cancel', [PurchaseOrderController::class, 'cancel'])
        ->middleware('permission:purchase-orders.cancel')
        ->name('purchase_orders.cancel');

    // Purchase Receipt (SCM-06) — catat penerimaan, Stock IN terpisah (SCM-07)
    Route::get('/purchase-receipts', [PurchaseReceiptController::class, 'index'])
        ->middleware('permission:purchase-receipts.view')
        ->name('purchase_receipts.index');
    Route::get('/purchase-receipts/create/{purchaseOrder}', [PurchaseReceiptController::class, 'create'])
        ->middleware('permission:purchase-receipts.create')
        ->name('purchase_receipts.create');
    Route::post('/purchase-receipts/{purchaseOrder}', [PurchaseReceiptController::class, 'store'])
        ->middleware('permission:purchase-receipts.create')
        ->name('purchase_receipts.store');
    Route::post('/purchase-receipts/{purchaseReceipt}/stock-in', [PurchaseReceiptController::class, 'processStockIn'])
        ->middleware('permission:purchase-receipts.process-stock-in')
        ->name('purchase_receipts.stock_in');
    Route::get('/purchase-receipts/{purchaseReceipt}', [PurchaseReceiptController::class, 'show'])
        ->middleware('permission:purchase-receipts.view')
        ->name('purchase_receipts.show');

    // Import routes
    Route::get('/imports', [ImportController::class, 'index'])->name('imports.index');
    Route::post('/imports/master', [ImportController::class, 'importMaster'])
        ->name('imports.master');

    // Inventory routes
    Route::get('/inventory', [InventoryController::class, 'index'])
        ->name('inventory.index');
    Route::get('/inventory/adjustment', [InventoryController::class, 'createAdjustment'])
        ->name('inventory.adjustment.create');
    Route::post('/inventory/adjustment', [InventoryController::class, 'storeAdjustment'])
        ->name('inventory.adjustment.store');
    Route::get('/inventory/{stockBalance}', [InventoryController::class, 'show'])
        ->name('inventory.show');

    // Delivery Order routes
    Route::get('/delivery-orders', [DeliveryOrderController::class, 'index'])
        ->name('delivery_orders.index');
    Route::get('/delivery-orders/create', [DeliveryOrderController::class, 'create'])
        ->name('delivery_orders.create');
    Route::post('/delivery-orders', [DeliveryOrderController::class, 'store'])
        ->name('delivery_orders.store');
    Route::get('/delivery-orders/{deliveryOrder}', [DeliveryOrderController::class, 'show'])
        ->name('delivery_orders.show');
    Route::get('/delivery-orders/{deliveryOrder}/edit', [DeliveryOrderController::class, 'edit'])
        ->name('delivery_orders.edit');
    Route::put('/delivery-orders/{deliveryOrder}', [DeliveryOrderController::class, 'update'])
        ->name('delivery_orders.update');
    Route::delete('/delivery-orders/{deliveryOrder}', [DeliveryOrderController::class, 'destroy'])
        ->name('delivery_orders.destroy');

    Route::post('/shipments/{shipment}/mark-received', [\App\Http\Controllers\ShipmentController::class, 'markReceived'])->name('shipments.mark_received');
    Route::resource('shipments', ShipmentController::class);

    // Invoice routes
    Route::get('/invoices/{invoice}/export-excel', [InvoiceController::class, 'exportExcel'])->name('invoices.export_excel');
    Route::post('/invoices/{invoice}/quick-pay', [App\Http\Controllers\InvoiceController::class, 'quickPay'])->name('invoices.quick_pay');
    Route::resource('invoices', InvoiceController::class);
    // Payment routes
    Route::resource('payments', PaymentController::class);

    // Reports routes
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');

        Route::get('/stock', [ReportController::class, 'stock'])->name('stock');
        Route::get('/stock/export', [ReportController::class, 'exportStock'])->name('stock.export');

        Route::get('/sales', [ReportController::class, 'sales'])->name('sales');
        Route::get('/sales/export', [ReportController::class, 'exportSales'])->name('sales.export');

        Route::get('/receivables', [ReportController::class, 'receivables'])->name('receivables');
        Route::get('/receivables/export', [ReportController::class, 'exportReceivables'])->name('receivables.export');

        Route::get('/payments', [ReportController::class, 'payments'])->name('payments');
        Route::get('/payments/export', [ReportController::class, 'exportPayments'])->name('payments.export');
    });
});
