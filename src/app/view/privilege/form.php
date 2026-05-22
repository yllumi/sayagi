<?php
$isEdit  = !empty($privilege);
$action  = $isEdit ? site_url('/panel/privilege/update') : site_url('/panel/privilege/store');
$btnText = $isEdit ? 'Simpan Perubahan' : 'Tambah Privilege';
?>

<div class="content" x-data="privilegeForm()">

    <div class="page-heading">
        <div class="d-flex justify-content-between align-items-center">
            <h3><?= $page_title ?></h3>
        </div>
    </div>

    <div class="page-content">
        <div class="row">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header fw-semibold">Informasi Privilege</div>
                    <div class="card-body">

                        <div class="alert d-none" id="formAlert" role="alert"></div>

                        <form id="privilegeForm" @submit.prevent="submit('<?= $action ?>')">

                            <?php if ($isEdit): ?>
                                <input type="hidden" name="key" value="<?= htmlspecialchars($privilege->key ?? '') ?>">
                            <?php endif; ?>

                            <div class="mb-3">
                                <label class="form-label fw-semibold" for="feature">
                                    Feature <span class="text-danger">*</span>
                                </label>
                                <input
                                    type="text"
                                    id="feature"
                                    name="feature"
                                    class="form-control"
                                    placeholder="Contoh: user, santri, iuran..."
                                    maxlength="20"
                                    list="featureList"
                                    value="<?= htmlspecialchars($privilege->feature ?? '') ?>"
                                    required>
                                <datalist id="featureList">
                                    <?php foreach ($features ?? [] as $f): ?>
                                        <option value="<?= htmlspecialchars($f) ?>"></option>
                                    <?php endforeach; ?>
                                </datalist>
                                <div class="form-text">Nama fitur (maks. 20 karakter).</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold" for="privilege">
                                    Privilege <span class="text-danger">*</span>
                                </label>
                                <input
                                    type="text"
                                    id="privilege"
                                    name="privilege"
                                    class="form-control"
                                    placeholder="Contoh: user.view, santri.edit..."
                                    maxlength="50"
                                    value="<?= htmlspecialchars($privilege->privilege ?? '') ?>"
                                    required>
                                <div class="form-text">Kata kunci privilege, biasanya berupa kata kerja (maks. 50 karakter).</div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-semibold" for="description">Deskripsi</label>
                                <textarea
                                    id="description"
                                    name="description"
                                    class="form-control"
                                    rows="3"
                                    placeholder="Penjelasan singkat tentang privilege ini..."><?= htmlspecialchars($privilege->description ?? '') ?></textarea>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary" :disabled="loading">
                                    <span class="spinner-border spinner-border-sm me-1" x-show="loading"></span>
                                    <i class="bi bi-check-lg" x-show="!loading"></i>
                                    <span x-text="loading ? 'Menyimpan...' : '<?= $btnText ?>'"></span>
                                </button>
                                <a href="<?= site_url('panel/privilege') ?>" class="btn btn-light">Batal</a>
                            </div>

                        </form>

                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
function privilegeForm() {
    return {
        loading: false,

        async submit(action) {
            this.loading = true;
            const alert  = document.getElementById('formAlert');
            alert.className = 'alert d-none';

            try {
                const formData = new FormData(document.getElementById('privilegeForm'));
                const res  = await fetch(action, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams(formData),
                });
                const data = await res.json();
                if (data.success) {
                    alert.className = 'alert alert-success';
                    alert.textContent = data.message;
                    setTimeout(() => window.location.href = data.redirect, 900);
                } else {
                    alert.className = 'alert alert-danger';
                    alert.textContent = data.message;
                    this.loading = false;
                }
            } catch (e) {
                alert.className = 'alert alert-danger';
                alert.textContent = 'Terjadi kesalahan. Silakan coba lagi.';
                this.loading = false;
            }
        },
    };
}
</script>
