<?php
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\ProductController;
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
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::middleware('auth')->group(function () {
    // Global search route
    Route::get('/search/global', [SearchController::class, 'global'])->name('search.global');

    Route::resource('users', UserController::class);

    // Dashboard route
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Resource routes
    Route::resource('customers', CustomerController::class);
    Route::post('/customers/ajax', [App\Http\Controllers\CustomerController::class, 'storeAjax'])->name('customers.store_ajax');
    Route::resource('products', ProductController::class);

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
