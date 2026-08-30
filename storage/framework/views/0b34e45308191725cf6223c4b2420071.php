<?php $__env->startSection('title', 'Payment - ERP TADCO'); ?>
<?php $__env->startSection('page_title', 'Daftar Pembayaran'); ?>
<?php $__env->startSection('page_subtitle', 'Pencatatan penerimaan uang (tunai & transfer) dari customer'); ?>

<?php $__env->startSection('page_action'); ?>
<a href="<?php echo e(route('payments.create')); ?>" class="btn btn-success px-4 rounded-pill fw-semibold shadow-sm">
    <i class="bi bi-plus-lg me-1"></i> Catat Pembayaran
</a>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

<?php if(session('success')): ?>
    <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm border-0 mb-4" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i><?php echo e(session('success')); ?>

        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="<?php echo e(route('payments.index')); ?>" class="row g-2">
            <div class="col-md-8">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input
                        type="text"
                        name="search"
                        class="form-control bg-light border-start-0"
                        placeholder="Cari nomor payment, referensi, invoice, customer..."
                        value="<?php echo e($search ?? ''); ?>"
                    >
                </div>
            </div>
            <div class="col-md-2 d-grid">
                <div class="dropdown d-grid">
                    <button class="btn btn-outline-secondary rounded-pill fw-semibold dropdown-toggle" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                        <i class="bi bi-layout-three-columns me-1"></i> Kolom
                    </button>
                    <ul class="dropdown-menu shadow-sm p-2" id="columnToggleMenu">
                        <li><label class="dropdown-item rounded"><input type="checkbox" class="form-check-input me-2 toggle-column" data-col="col-pay-cust" checked> Customer</label></li>
                        <li><label class="dropdown-item rounded"><input type="checkbox" class="form-check-input me-2 toggle-column" data-col="col-pay-no" checked> No. Payment</label></li>
                        <li><label class="dropdown-item rounded"><input type="checkbox" class="form-check-input me-2 toggle-column" data-col="col-pay-inv" checked> No. Invoice</label></li>
                        <li><label class="dropdown-item rounded"><input type="checkbox" class="form-check-input me-2 toggle-column" data-col="col-pay-date" checked> Tanggal</label></li>
                        <li><label class="dropdown-item rounded"><input type="checkbox" class="form-check-input me-2 toggle-column" data-col="col-pay-method" checked> Metode</label></li>
                        <li><label class="dropdown-item rounded"><input type="checkbox" class="form-check-input me-2 toggle-column" data-col="col-pay-amount" checked> Nominal Pembayaran</label></li>
                    </ul>
                </div>
            </div>
            <div class="col-md-2 d-grid">
                <button type="submit" class="btn btn-dark rounded-pill fw-semibold">
                    Cari Payment
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
    <div class="table-responsive">
        <table class="table table-hover table-bordered border-light align-middle mb-0">
            <thead class="table-light">
                <tr class="text-nowrap">
                    <th style="width: 50px;" class="text-center">No</th>
                    <th class="col-pay-cust">Customer</th>
                    <th class="col-pay-no">No. Payment</th>
                    <th class="col-pay-inv">No. Invoice</th>
                    <th class="col-pay-date">Tanggal</th>
                    <th class="col-pay-method">Metode</th>
                    <th class="col-pay-amount text-end">Nominal Pembayaran</th>
                    <th style="width: 120px;" class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $payments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $payment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td class="text-center fw-medium text-secondary text-nowrap"><?php echo e($payments->firstItem() + $loop->index); ?></td>
                        <td class="fw-semibold text-dark col-pay-cust"><?php echo e($payment->invoice->customer->customer_name ?? '-'); ?></td>
                        <td class="fw-bold text-dark text-nowrap col-pay-no"><?php echo e($payment->payment_number); ?></td>
                        <td class="fw-medium text-primary text-nowrap col-pay-inv"><?php echo e($payment->invoice->invoice_number ?? '-'); ?></td>
                        <td class="text-nowrap col-pay-date"><?php echo e($payment->payment_date->format('d/m/Y')); ?></td>
                        <td class="text-nowrap col-pay-method">
                            <?php if($payment->payment_method === 'cash'): ?>
                                <span class="badge bg-success-subtle text-success px-3 py-1 rounded-pill"><i class="bi bi-cash me-1"></i> Cash</span>
                            <?php else: ?>
                                <span class="badge bg-primary-subtle text-primary px-3 py-1 rounded-pill"><i class="bi bi-bank me-1"></i> Transfer</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end fw-bold text-success text-nowrap col-pay-amount">Rp <?php echo e(number_format($payment->amount, 0, ',', '.')); ?></td>
                        <td class="text-center text-nowrap">
                            <div class="d-flex justify-content-center gap-2">
                                <a href="<?php echo e(route('payments.show', $payment)); ?>" class="btn btn-sm btn-light border text-info rounded d-inline-flex align-items-center justify-content-center shadow-sm" style="width: 32px; height: 32px;" title="Detail"><i class="bi bi-eye"></i></a>
                                <form action="<?php echo e(route('payments.destroy', $payment)); ?>" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data ini?')" class="d-inline">
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('DELETE'); ?>
                                    <button type="submit" class="btn btn-sm btn-light border text-danger rounded d-inline-flex align-items-center justify-content-center shadow-sm" style="width: 32px; height: 32px;" title="Hapus"><i class="bi bi-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                            Data pembayaran belum tersedia.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if($payments->hasPages()): ?>
        <div class="card-footer bg-white py-3 px-4 border-top border-light">
            <?php echo e($payments->links()); ?>

        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const pageKey = 'toggle_cols_' + window.location.pathname.replace(/\//g, '_');
    const checkboxes = document.querySelectorAll('.toggle-column');
    const savedState = JSON.parse(localStorage.getItem(pageKey)) || {};

    checkboxes.forEach(cb => {
        const colClass = cb.getAttribute('data-col');
        if (savedState[colClass] !== undefined) {
            cb.checked = savedState[colClass];
        }
        toggleVisibility(colClass, cb.checked);

        cb.addEventListener('change', function() {
            toggleVisibility(colClass, this.checked);
            saveState();
        });
    });

    function toggleVisibility(colClass, isVisible) {
        document.querySelectorAll('.' + colClass).forEach(el => {
            el.style.display = isVisible ? '' : 'none';
        });
    }

    function saveState() {
        const state = {};
        checkboxes.forEach(cb => {
            state[cb.getAttribute('data-col')] = cb.checked;
        });
        localStorage.setItem(pageKey, JSON.stringify(state));
    }
});
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\ERP-Tadco\resources\views/payments/index.blade.php ENDPATH**/ ?>