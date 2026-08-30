<?php $__env->startSection('title', 'Invoice - ERP TADCO'); ?>
<?php $__env->startSection('page_title', 'Daftar Invoice'); ?>
<?php $__env->startSection('page_subtitle', 'Invoice diterbitkan dari DO shipped dan mencatat piutang berjalan'); ?>

<?php $__env->startSection('page_action'); ?>
<a href="<?php echo e(route('invoices.create')); ?>" class="btn btn-primary px-4 rounded-pill fw-semibold shadow-sm">
    <i class="bi bi-plus-lg me-1"></i> Buat Invoice
</a>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

<?php if(session('success')): ?>
    <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm border-0 mb-4" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i><?php echo e(session('success')); ?>

        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if(session('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm border-0 mb-4" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo e(session('error')); ?>

        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="<?php echo e(route('invoices.index')); ?>" class="row g-2">
            <div class="col-md-8">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control bg-light border-start-0" placeholder="Cari nomor invoice, DO, customer..." value="<?php echo e($search ?? ''); ?>">
                </div>
            </div>
            <div class="col-md-2 d-grid">
                <div class="dropdown d-grid">
                    <button class="btn btn-outline-secondary rounded-pill fw-semibold dropdown-toggle" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                        <i class="bi bi-layout-three-columns me-1"></i> Kolom
                    </button>
                    <ul class="dropdown-menu shadow-sm p-2">
                        <li><label class="dropdown-item rounded"><input type="checkbox" class="form-check-input me-2 toggle-column" data-col="col-cust" checked> Customer</label></li>
                        <li><label class="dropdown-item rounded"><input type="checkbox" class="form-check-input me-2 toggle-column" data-col="col-inv" checked> No. Invoice</label></li>
                        <li><label class="dropdown-item rounded"><input type="checkbox" class="form-check-input me-2 toggle-column" data-col="col-do" checked> No. DO</label></li>
                        <li><label class="dropdown-item rounded"><input type="checkbox" class="form-check-input me-2 toggle-column" data-col="col-date" checked> Tanggal</label></li>
                        <li><label class="dropdown-item rounded"><input type="checkbox" class="form-check-input me-2 toggle-column" data-col="col-due" checked> Jatuh Tempo</label></li>
                        <li><label class="dropdown-item rounded"><input type="checkbox" class="form-check-input me-2 toggle-column" data-col="col-paid" checked> Telah Dibayar</label></li>
                        <li><label class="dropdown-item rounded"><input type="checkbox" class="form-check-input me-2 toggle-column" data-col="col-bill" checked> Total Tagihan</label></li>
                        <li><label class="dropdown-item rounded"><input type="checkbox" class="form-check-input me-2 toggle-column" data-col="col-status" checked> Status</label></li>
                    </ul>
                </div>
            </div>
            <div class="col-md-2 d-grid">
                <button type="submit" class="btn btn-dark rounded-pill fw-semibold">Cari Invoice</button>
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
                    <th class="col-cust">Customer</th>
                    <th class="col-inv">No. Invoice</th>
                    <th class="col-do">No. DO</th>
                    <th class="col-date">Tanggal</th>
                    <th class="col-due">Jatuh Tempo</th>
                    <th class="col-paid text-end">Telah Dibayar</th>
                    <th class="col-bill text-end">Total Tagihan</th>
                    <th class="col-status text-center">Status</th>
                    <th style="width: 140px;" class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $invoices; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $invoice): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td class="text-center fw-medium text-secondary text-nowrap"><?php echo e($invoices->firstItem() + $loop->index); ?></td>
                        <td class="fw-semibold text-dark text-truncate col-cust" style="max-width: 180px;" title="<?php echo e($invoice->customer->customer_name ?? '-'); ?>"><?php echo e($invoice->customer->customer_name ?? '-'); ?></td>
                        <td class="fw-bold text-dark text-nowrap col-inv"><?php echo e($invoice->invoice_number); ?></td>
                        <td class="fw-medium text-primary text-nowrap col-do"><?php echo e($invoice->deliveryOrder->do_number ?? '-'); ?></td>
                        <td class="text-nowrap col-date"><?php echo e($invoice->invoice_date->format('d/m/Y')); ?></td>
                        <td class="text-nowrap col-due"><?php echo e($invoice->due_date ? $invoice->due_date->format('d/m/Y') : '-'); ?></td>
                        <td class="text-end fw-bold text-success text-nowrap col-paid">Rp <?php echo e(number_format($invoice->paid_total, 0, ',', '.')); ?></td>
                        <td class="text-end fw-bold text-danger text-nowrap col-bill">Rp <?php echo e(number_format($invoice->receivable_amount, 0, ',', '.')); ?></td>
                        <td class="text-center text-nowrap col-status">
                            <?php if($invoice->status === 'unpaid'): ?>
                                <span class="badge bg-warning-subtle text-warning-emphasis px-3 py-1 rounded-pill">Unpaid</span>
                            <?php elseif($invoice->status === 'paid'): ?>
                                <span class="badge bg-success-subtle text-success px-3 py-1 rounded-pill">Paid</span>
                            <?php elseif($invoice->status === 'cancelled'): ?>
                                <span class="badge bg-secondary-subtle text-secondary px-3 py-1 rounded-pill">Cancelled</span>
                            <?php else: ?>
                                <span class="badge bg-dark px-3 py-1 rounded-pill"><?php echo e($invoice->status); ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center text-nowrap">
                            <div class="d-flex justify-content-center gap-2">
                                <a href="<?php echo e(route('invoices.show', $invoice)); ?>" class="btn btn-sm btn-light border text-info rounded d-inline-flex align-items-center justify-content-center shadow-sm" style="width: 32px; height: 32px;" title="Detail"><i class="bi bi-eye"></i></a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="11" class="text-center text-muted py-4">
                            <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                            Data invoice belum tersedia.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if($invoices->hasPages()): ?>
        <div class="card-footer bg-white py-3 px-4 border-top border-light">
            <?php echo e($invoices->links()); ?>

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

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\ERP-Tadco\resources\views/invoices/index.blade.php ENDPATH**/ ?>