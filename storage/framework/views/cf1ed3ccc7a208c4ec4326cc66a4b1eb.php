<?php $__env->startSection('title', 'Dashboard - ERP TADCO'); ?>
<?php $__env->startSection('page_title', 'Dashboard'); ?>
<?php $__env->startSection('page_subtitle', 'Ringkasan operasional distribusi, penjualan, stok, dan pembayaran'); ?>

<?php $__env->startSection('page_action'); ?>
<a href="<?php echo e(route('products.create')); ?>" class="btn btn-primary px-4 rounded-pill fw-semibold shadow-sm">
    <i class="bi bi-plus-lg me-1"></i> Tambah Produk
</a>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <?php
        $totalSlots = max((int) $totalStockAvailable + (int) $totalStockReserved, 1);
        $usedPercent = min(100, round(((int) $totalStockAvailable / $totalSlots) * 100));
        $availableSlots = max((int) $totalStockAvailable, 0);
        $lowStockCount = $lowStocks->count();
        $outOfStockCount = $lowStocks->where('qty_available', '<=', 0)->count();
    ?>

    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6 col-xl-3">
            <a href="<?php echo e(route('products.index')); ?>" class="quick-action" style="--metric-bg:#eff6ff;--metric-color:#2563eb;">
                <span class="quick-action-icon"><i class="bi bi-box-seam-fill"></i></span>
                <span>
                    <span class="d-block fw-semibold">Kelola Produk</span>
                    <small class="text-muted"><?php echo e(number_format($totalProducts, 0, ',', '.')); ?> produk aktif</small>
                </span>
            </a>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <a href="<?php echo e(route('customers.index')); ?>" class="quick-action"
                style="--metric-bg:#ecfdf5;--metric-color:#047857;">
                <span class="quick-action-icon"><i class="bi bi-people-fill"></i></span>
                <span>
                    <span class="d-block fw-semibold">Customer</span>
                    <small class="text-muted"><?php echo e(number_format($totalCustomers, 0, ',', '.')); ?> akun terdaftar</small>
                </span>
            </a>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <a href="<?php echo e(route('inventory.index')); ?>" class="quick-action"
                style="--metric-bg:#fff7ed;--metric-color:#bc4800;">
                <span class="quick-action-icon"><i class="bi bi-boxes"></i></span>
                <span>
                    <span class="d-block fw-semibold">Inventory</span>
                    <small class="text-muted">Monitor stok gudang</small>
                </span>
            </a>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <a href="<?php echo e(route('reports.index')); ?>" class="quick-action" style="--metric-bg:#f5f3ff;--metric-color:#6d28d9;">
                <span class="quick-action-icon"><i class="bi bi-bar-chart-fill"></i></span>
                <span>
                    <span class="d-block fw-semibold">Laporan</span>
                    <small class="text-muted">Export performa bisnis</small>
                </span>
            </a>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-12 col-md-6 col-xl-4">
            <div class="card metric-card" style="--metric-bg:#eff6ff;--metric-soft:#eff6ff;--metric-color:#2563eb;">
                <div class="metric-icon">
                    <i class="bi bi-box-seam-fill"></i>
                </div>
                <div>
                    <div class="metric-label">Stok Tersedia</div>
                    <div class="d-flex align-items-center gap-2">
                        <div class="metric-value"><?php echo e(number_format((float) $totalStockAvailable, 0, ',', '.')); ?></div>
                        <span class="metric-trend bg-success">Ready</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl-4">
            <div class="card metric-card" style="--metric-bg:#fef3c7;--metric-soft:#fefce8;--metric-color:#b45309;">
                <div class="metric-icon">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                </div>
                <div>
                    <div class="metric-label">Peringatan Stok Rendah</div>
                    <div class="d-flex align-items-center gap-2">
                        <div class="metric-value"><?php echo e(number_format($lowStockCount, 0, ',', '.')); ?></div>
                        <span class="metric-trend bg-warning">Review</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl-4">
            <div class="card metric-card" style="--metric-bg:#fee2e2;--metric-soft:#fef2f2;--metric-color:#b91c1c;">
                <div class="metric-icon">
                    <i class="bi bi-cart-x-fill"></i>
                </div>
                <div>
                    <div class="metric-label">Stok Kosong</div>
                    <div class="d-flex align-items-baseline gap-2">
                        <div class="metric-value"><?php echo e(number_format($outOfStockCount, 0, ',', '.')); ?></div>
                        <small class="text-muted fw-semibold">Perlu restock</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4 align-items-stretch">
        <div class="col-12 col-xl-5">
            <div class="card chart-card h-100">
                <h5 class="section-title mb-4">Kapasitas Gudang</h5>

                <div class="donut" style="--percent: <?php echo e($usedPercent); ?>;">
                    <div class="donut-inner">
                        <div class="fs-5 fw-semibold"><?php echo e($usedPercent); ?>%</div>
                        <small class="text-muted">Terisi</small>
                    </div>
                </div>

                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Total Slot</span>
                    <span><?php echo e(number_format($totalSlots, 0, ',', '.')); ?></span>
                </div>
                <div class="d-flex justify-content-between">
                    <span class="text-muted">Slot Tersedia</span>
                    <span class="text-primary"><?php echo e(number_format($availableSlots, 0, ',', '.')); ?></span>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-7">
            <div class="card chart-card h-100">
                <div class="d-flex justify-content-between align-items-center gap-3 mb-4">
                    <h5 class="section-title">Volume Pergerakan Barang</h5>
                    <div class="d-flex gap-3 small text-muted">
                        <span><span class="legend-dot me-2" style="background:#2563eb;"></span>Inbound</span>
                        <span><span class="legend-dot me-2" style="background:#bc4800;"></span>Outbound</span>
                    </div>
                </div>

                <div class="bar-chart">
                    <?php $__empty_1 = true; $__currentLoopData = $weeklyBars; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $bar): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <div class="bar-day">
                            <div class="bar-pair">
                                <span class="bar inbound" style="height: <?php echo e($bar['in_height'] ?? 0); ?>%;"></span>
                                <span class="bar outbound" style="height: <?php echo e($bar['out_height'] ?? 0); ?>%;"></span>
                            </div>
                            <span class="bar-label"><?php echo e(strtoupper($bar['day'])); ?></span>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <div class="text-muted">Belum ada data pergerakan stok pada minggu ini.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12 col-xl-7">
            <div class="card overflow-hidden">
                <div class="card-body border-bottom d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="section-title mb-1">Delivery Order Terbaru</h5>
                        <small class="text-muted">Transaksi delivery order terbaru</small>
                    </div>
                    <a href="<?php echo e(route('delivery_orders.index')); ?>" class="btn btn-sm btn-dark">Lihat Semua</a>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>DO Number</th>
                                <th>Customer</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__empty_1 = true; $__currentLoopData = $recentDeliveryOrders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $deliveryOrder): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <tr>
                                    <td class="fw-semibold"><?php echo e($deliveryOrder->do_number ?? $deliveryOrder->id); ?></td>
                                    <td><?php echo e($deliveryOrder->customer->customer_name ?? '-'); ?></td>
                                    <td><?php echo e(optional($deliveryOrder->created_at)->format('d/m/Y')); ?></td>
                                    <td><span
                                            class="badge bg-success"><?php echo e(ucfirst(str_replace('_', ' ', $deliveryOrder->status ?? 'open'))); ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">Belum ada delivery order
                                        terbaru.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-5">
            <div class="card overflow-hidden">
                <div class="card-body border-bottom">
                    <h5 class="section-title mb-1">Status Invoice</h5>
                    <small class="text-muted">Posisi tagihan dan pembayaran</small>
                </div>

                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center py-2">
                        <span class="text-muted">Belum dibayar</span>
                        <span class="badge bg-warning"><?php echo e(number_format($unpaidInvoices, 0, ',', '.')); ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-2">
                        <span class="text-muted">Lunas</span>
                        <span class="badge bg-success"><?php echo e(number_format($paidInvoices, 0, ',', '.')); ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-2">
                        <span class="text-muted">Jatuh tempo</span>
                        <span class="badge bg-danger"><?php echo e(number_format($overdueInvoices, 0, ',', '.')); ?></span>
                    </div>

                    <hr class="my-3">

                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted">Piutang berjalan</span>
                        <strong>Rp <?php echo e(number_format((float) $totalReceivable, 0, ',', '.')); ?></strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-2">
                        <span class="text-muted">Pembayaran masuk</span>
                        <strong class="text-primary">Rp <?php echo e(number_format((float) $totalPayments, 0, ',', '.')); ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mt-1">
        <div class="col-12 col-xl-6">
            <div class="card overflow-hidden">
                <div class="card-body border-bottom d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="section-title mb-1">Invoice Terbaru</h5>
                        <small class="text-muted">Tagihan yang baru dibuat</small>
                    </div>
                    <a href="<?php echo e(route('invoices.index')); ?>" class="btn btn-sm btn-dark">Lihat Semua</a>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Invoice</th>
                                <th>Customer</th>
                                <th>Jumlah</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__empty_1 = true; $__currentLoopData = $recentInvoices; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $invoice): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <tr>
                                    <td class="fw-semibold"><?php echo e($invoice->invoice_number ?? $invoice->id); ?></td>
                                    <td><?php echo e($invoice->customer->customer_name ?? '-'); ?></td>
                                    <td>Rp <?php echo e(number_format((float) ($invoice->grand_total ?? 0), 0, ',', '.')); ?></td>
                                    <td><span
                                            class="badge bg-<?php echo e($invoice->status === 'paid' ? 'success' : 'warning'); ?>"><?php echo e(ucfirst(str_replace('_', ' ', $invoice->status ?? 'unpaid'))); ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">Belum ada invoice terbaru.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-6">
            <div class="card overflow-hidden">
                <div class="card-body border-bottom d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="section-title mb-1">Pembayaran Terbaru</h5>
                        <small class="text-muted">Pembayaran masuk terbaru</small>
                    </div>
                    <a href="<?php echo e(route('payments.index')); ?>" class="btn btn-sm btn-dark">Lihat Semua</a>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Payment</th>
                                <th>Invoice</th>
                                <th>Jumlah</th>
                                <th>Tanggal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__empty_1 = true; $__currentLoopData = $recentPayments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $payment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <tr>
                                    <td class="fw-semibold"><?php echo e($payment->payment_number ?? $payment->id); ?></td>
                                    <td><?php echo e($payment->invoice->invoice_number ?? '-'); ?></td>
                                    <td>Rp <?php echo e(number_format((float) ($payment->amount ?? 0), 0, ',', '.')); ?></td>
                                    <td><?php echo e(optional($payment->payment_date)->format('d/m/Y') ?? '-'); ?></td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">Belum ada pembayaran terbaru.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\ERP-Tadco\resources\views/dashboard/index.blade.php ENDPATH**/ ?>