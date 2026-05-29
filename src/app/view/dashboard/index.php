<div class="content">
    <div class="page-heading">
        <h3><?= $page_title ?></h3>
        <p class="text-muted mb-0">Selamat datang kembali, <strong><?= htmlspecialchars($user_name ?? 'User') ?></strong>.</p>
    </div>

    <div class="page-content mt-3">
        <div class="row g-3">
            <div class="col-12 col-md-6">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <div class="text-uppercase text-muted small">Total User Aktif</div>
                                <div class="display-6 fw-semibold mt-2"><?= (int) ($active_users ?? 0) ?></div>
                            </div>
                            <span class="badge bg-success-subtle text-success-emphasis px-3 py-2">
                                <i class="bi bi-person-check-fill me-1"></i> Active
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <div class="text-uppercase text-muted small">Total User Inactive</div>
                                <div class="display-6 fw-semibold mt-2"><?= (int) ($inactive_users ?? 0) ?></div>
                            </div>
                            <span class="badge bg-warning-subtle text-warning-emphasis px-3 py-2">
                                <i class="bi bi-person-dash-fill me-1"></i> Inactive
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
