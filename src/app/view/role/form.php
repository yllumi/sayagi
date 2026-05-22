<?php
$isEdit  = !empty($role);
$action  = $isEdit ? site_url('/panel/role/update') : site_url('/panel/role/store');
$btnText = $isEdit ? 'Simpan Perubahan' : 'Tambah Role';
// PHP array to JSON for Alpine bootstrap
$rolePrivsJson = json_encode($role_privs ?? []);
?>

<div class="content" x-data="roleForm()" x-init="init()">

    <div class="page-heading">
        <div class="d-flex justify-content-between align-items-center">
            <h3><?= $page_title ?></h3>
            <a href="<?= site_url('panel/role') ?>" class="btn btn-light">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    <div class="page-content">
        <div class="row g-4">

            <!-- Left: role info -->
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header fw-semibold">Informasi Role</div>
                    <div class="card-body">

                        <div class="alert d-none" id="formAlert" role="alert"></div>

                        <form id="roleForm" @submit.prevent="submit('<?= $action ?>')">

                            <?php if ($isEdit): ?>
                                <input type="hidden" name="id" value="<?= $role->id ?>">
                            <?php endif; ?>

                            <!-- Hidden privilege checkboxes (driven by Alpine) -->
                            <template x-for="priv in checkedPrivs" :key="priv">
                                <input type="hidden" name="privileges[]" :value="priv">
                            </template>

                            <div class="mb-3">
                                <label class="form-label fw-semibold" for="name">
                                    Nama Role <span class="text-danger">*</span>
                                </label>
                                <input
                                    type="text"
                                    id="name"
                                    name="name"
                                    class="form-control"
                                    placeholder="Contoh: Admin, Operator, Bendahara..."
                                    value="<?= htmlspecialchars($role->role_name ?? '') ?>"
                                    required>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-semibold" for="role_slug">Slug</label>
                                <input
                                    type="text"
                                    id="role_slug"
                                    name="role_slug"
                                    class="form-control"
                                    placeholder="Contoh: admin, operator... (otomatis jika kosong)"
                                    value="<?= htmlspecialchars($role->role_slug ?? '') ?>">
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary w-100" :disabled="loading">
                                    <span class="spinner-border spinner-border-sm me-1" x-show="loading"></span>
                                    <i class="bi bi-check-lg" x-show="!loading"></i>
                                    <span x-text="loading ? 'Menyimpan...' : '<?= $btnText ?>'"></span>
                                </button>
                            </div>

                        </form>

                    </div>
                </div>
            </div>

            <!-- Right: privileges -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center fw-semibold">
                        <span>Privilege Akses</span>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-outline-primary" @click="selectAll()">
                                <i class="bi bi-check2-all"></i> Pilih Semua
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" @click="clearAll()">
                                <i class="bi bi-x-lg"></i> Hapus Semua
                            </button>
                        </div>
                    </div>
                    <div class="card-body">

                        <p class="text-muted small mb-3">
                            Dipilih: <strong x-text="checkedPrivs.length"></strong> privilege
                        </p>

                        <?php foreach ($all_privileges as $feature => $privs): ?>
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="mb-0 fw-semibold text-uppercase text-muted small"
                                    style="letter-spacing:.05em"><?= htmlspecialchars($feature) ?></h6>
                                <div class="d-flex gap-1">
                                    <button type="button" class="btn btn-xs btn-link text-primary p-0 small"
                                        @click='selectModule(<?= json_encode(array_keys($privs)) ?>)'>Pilih semua</button>
                                    <span class="text-muted small">·</span>
                                    <button type="button" class="btn btn-xs btn-link text-secondary p-0 small"
                                        @click='clearModule(<?= json_encode(array_keys($privs)) ?>)'>Hapus</button>
                                </div>
                            </div>
                            <div class="row g-2">
                                <?php foreach ($privs as $priv => $description):
                                    $parts = explode('.', $priv);
                                    $label = $parts[1] ?? $priv;
                                ?>
                                <div class="col-6 col-sm-4 col-md-3">
                                    <label
                                        class="d-flex align-items-center gap-2 border rounded px-2 py-2 cursor-pointer"
                                        :class="checkedPrivs.includes('<?= $priv ?>') ? 'border-primary bg-primary bg-opacity-10' : 'border-light bg-light bg-opacity-50'"
                                        style="cursor:pointer"
                                        <?= $description ? 'title="' . htmlspecialchars($description) . '"' : '' ?>>
                                        <input
                                            type="checkbox"
                                            class="form-check-input m-0 flex-shrink-0"
                                            value="<?= $priv ?>"
                                            :checked="checkedPrivs.includes('<?= $priv ?>')"
                                            @change="togglePriv('<?= $priv ?>', $event.target.checked)">
                                        <span class="small"><?= $label ?></span>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>

                    </div>
                </div>
            </div>

        </div>
    </div>

</div>

<script>
let initialPrivs = <?= $rolePrivsJson ?>;
function roleForm() {
    return {
        loading:      false,
        checkedPrivs: initialPrivs || [],

        init() {},

        togglePriv(priv, checked) {
            if (checked) {
                if (!this.checkedPrivs.includes(priv)) this.checkedPrivs.push(priv);
            } else {
                this.checkedPrivs = this.checkedPrivs.filter(p => p !== priv);
            }
        },

        selectAll() {
            // Search within the Alpine component root, not inside #roleForm
            // because the visible checkboxes live outside the <form> tag
            const allInputs = this.$root.querySelectorAll('input[type=checkbox]');
            this.checkedPrivs = [...allInputs].map(el => el.value);
        },

        clearAll() {
            this.checkedPrivs = [];
        },

        selectModule(privs) {
            privs.forEach(p => {
                if (!this.checkedPrivs.includes(p)) this.checkedPrivs.push(p);
            });
        },

        clearModule(privs) {
            this.checkedPrivs = this.checkedPrivs.filter(p => !privs.includes(p));
        },

        async submit(action) {
            this.loading = true;
            const alert  = document.getElementById('formAlert');
            alert.className = 'alert d-none';

            try {
                const formData = new FormData(document.getElementById('roleForm'));
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
