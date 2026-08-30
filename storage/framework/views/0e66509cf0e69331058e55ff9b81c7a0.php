<?php $__env->startSection('title', 'Reports - ERP TADCO'); ?>
<?php $__env->startSection('page_title', 'Reporting Dashboard'); ?>
<?php $__env->startSection('page_subtitle', 'Visualisasi data operasional, penjualan, piutang, dan pembayaran'); ?>

<?php $__env->startSection('content'); ?>
    <style>
        .report-card {
            transition: all 0.2s ease-in-out;
            border-left: 4px solid transparent;
        }

        .report-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 .5rem 1rem rgba(0, 0, 0, .15) !important;
            border-left-color: #0d6efd;
        }

        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }
    </style>

    <!-- Tombol Navigasi Laporan -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <a href="<?php echo e(route('reports.stock')); ?>" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100 report-card">
                    <div class="card-body p-3">
                        <div class="mb-2 text-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="currentColor"
                                viewBox="0 0 16 16">
                                <path
                                    d="M8.186 1.113a.5.5 0 0 0-.372 0L1.846 3.5 8 5.961 14.154 3.5 8.186 1.113zM15 4.239l-6.5 2.6v7.922l6.5-2.6V4.24zM7.5 14.762V6.838L1 4.239v7.923l6.5 2.6z" />
                            </svg>
                        </div>
                        <h6 class="text-dark fw-bold mb-1">Detail Stok</h6>
                        <small class="text-muted">Lihat data mutasi stok</small>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="<?php echo e(route('reports.sales')); ?>" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100 report-card">
                    <div class="card-body p-3">
                        <div class="mb-2 text-success">
                            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="currentColor"
                                viewBox="0 0 16 16">
                                <path
                                    d="M1.5 0A1.5 1.5 0 0 0 0 1.5v13A1.5 1.5 0 0 0 1.5 16h13a1.5 1.5 0 0 0 1.5-1.5v-13A1.5 1.5 0 0 0 14.5 0h-13zM2 1.5a.5.5 0 0 1 .5-.5h11a.5.5 0 0 1 .5.5v13a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-13z" />
                                <path
                                    d="M8 3.5a.5.5 0 0 1 .5.5v2.5H11a.5.5 0 0 1 0 1H8.5V10a.5.5 0 0 1-1 0V7.5H5a.5.5 0 0 1 0-1h2.5V4a.5.5 0 0 1 .5-.5z" />
                            </svg>
                        </div>
                        <h6 class="text-dark fw-bold mb-1">Detail Penjualan</h6>
                        <small class="text-muted">Lihat histori invoice</small>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="<?php echo e(route('reports.receivables')); ?>" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100 report-card">
                    <div class="card-body p-3">
                        <div class="mb-2 text-danger">
                            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="currentColor"
                                viewBox="0 0 16 16">
                                <path d="M1 3a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1H1zm7 8a2 2 0 1 0 0-4 2 2 0 0 0 0 4z" />
                                <path
                                    d="M0 5a1 1 0 0 1 1-1h14a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H1a1 1 0 0 1-1-1V5zm3 0a2 2 0 0 1-2 2v4a2 2 0 0 1 2 2h10a2 2 0 0 1 2-2V7a2 2 0 0 1-2-2H3z" />
                            </svg>
                        </div>
                        <h6 class="text-dark fw-bold mb-1">Detail Piutang</h6>
                        <small class="text-muted">Lihat tagihan pelanggan</small>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="<?php echo e(route('reports.payments')); ?>" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100 report-card">
                    <div class="card-body p-3">
                        <div class="mb-2 text-info">
                            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="currentColor"
                                viewBox="0 0 16 16">
                                <path
                                    d="M11 5.5a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1-.5-.5v-1z" />
                                <path
                                    d="M2 2a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H2zm13 2v5H1V4a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1zm-1 9H2a1 1 0 0 1-1-1v-1h14v1a1 1 0 0 1-1 1z" />
                            </svg>
                        </div>
                        <h6 class="text-dark fw-bold mb-1">Detail Pembayaran</h6>
                        <small class="text-muted">Lihat arus kas masuk</small>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <!-- Area Grafik / Charts -->
    <div class="row g-4">

        <!-- Chart 1: Stok -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold">Grafik Laporan Stok</h6>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="stockChart"></canvas>
                    </div>
                    <div class="text-center mt-3 small text-muted">
                        Total <strong class="text-danger"><?php echo e($lowStockCount); ?></strong> Produk berstatus Low Stock (Sisa ≤
                        10)
                    </div>
                </div>
            </div>
        </div>

        <!-- Chart 2: Penjualan -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold">Grafik Total Penjualan (Rupiah)</h6>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="salesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Chart 3: Piutang -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold">Kondisi Piutang Berjalan (Rupiah)</h6>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="receivablesChart"></canvas>
                    </div>
                    <div class="text-center mt-3 small text-muted">
                        *Overdue adalah bagian dari total piutang yang telah lewat jatuh tempo.
                    </div>
                </div>
            </div>
        </div>

        <!-- Chart 4: Pembayaran -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold">Metode Pembayaran (Rupiah)</h6>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="paymentsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Script Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const rupiahFormatter = new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0
            });

            // 1. Chart Stok
            new Chart(document.getElementById('stockChart'), {
                type: 'doughnut',
                data: {
                    labels: ['Stok Available', 'Stok Reserved'],
                    datasets: [{
                        data: [<?php echo e($totalAvailable); ?>, <?php echo e($totalReserved); ?>],
                        backgroundColor: ['#0d6efd', '#ffc107'],
                        borderWidth: 1
                    }]
                },
                options: {
                    maintainAspectRatio: false
                }
            });

            // 2. Chart Penjualan
            new Chart(document.getElementById('salesChart'), {
                type: 'bar',
                data: {
                    labels: ['Gross Total', 'Sudah Dibayar', 'Sisa Piutang'],
                    datasets: [{
                        label: 'Nominal Penjualan',
                        data: [<?php echo e($totalSales); ?>, <?php echo e($totalPaid); ?>, <?php echo e($totalReceivable); ?>],
                        backgroundColor: ['#6c757d', '#198754', '#dc3545'],
                        borderRadius: 4
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    },
                    tooltips: {
                        callbacks: {
                            label: function(context) {
                                return rupiahFormatter.format(context.raw);
                            }
                        }
                    }
                }
            });

            // 3. Chart Piutang
            new Chart(document.getElementById('receivablesChart'), {
                type: 'bar',
                data: {
                    labels: ['Piutang Unpaid', 'Piutang Partial', 'Lewat Jatuh Tempo (Overdue)'],
                    datasets: [{
                        label: 'Nominal Piutang',
                        data: [<?php echo e($unpaidAmount); ?>, <?php echo e($partialPaidAmount); ?>,
                            <?php echo e($overdueAmount); ?>

                        ],
                        backgroundColor: ['#dc3545', '#fd7e14', '#842029'],
                        borderRadius: 4
                    }]
                },
                options: {
                    indexAxis: 'y',
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true
                        }
                    }
                }
            });

            // 4. Chart Pembayaran
            new Chart(document.getElementById('paymentsChart'), {
                type: 'pie',
                data: {
                    labels: ['Cash (Tunai)', 'Transfer Bank'],
                    datasets: [{
                        data: [<?php echo e($cashPayments); ?>, <?php echo e($transferPayments); ?>],
                        backgroundColor: ['#198754', '#0dcaf0'],
                        borderWidth: 1
                    }]
                },
                options: {
                    maintainAspectRatio: false
                }
            });
        });
    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\ERP-Tadco\resources\views/reports/index.blade.php ENDPATH**/ ?>