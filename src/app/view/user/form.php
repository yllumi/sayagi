<?php
$isEdit  = !empty($user);
$action  = $isEdit ? site_url('/panel/user/update') : site_url('/panel/user/store');
$btnText = $isEdit ? 'Simpan Perubahan' : 'Tambah User';
?>

<div class="content" x-data="userForm()" x-init="init()">

    <div class="page-heading">
        <div class="d-flex justify-content-between align-items-center">
            <h3><?= $page_title ?></h3>
        </div>
    </div>

    <div class="page-content">
        <div class="row">
            <div class="col-md-8 col-lg-6">
                <div class="card">
                    <div class="card-body p-4">

                        <!-- Alert -->
                        <div class="alert d-none" id="formAlert" role="alert"></div>

                        <form id="userForm" @submit.prevent="submit('<?= $action ?>')">

                            <?php if ($isEdit): ?>
                                <input type="hidden" name="id" value="<?= $user->id ?>">
                            <?php endif; ?>

                            <div class="mb-3">
                                <label class="form-label fw-semibold" for="name">Nama Lengkap <span class="text-danger">*</span></label>
                                <input
                                    type="text"
                                    id="name"
                                    name="name"
                                    class="form-control"
                                    placeholder="Nama lengkap"
                                    value="<?= htmlspecialchars($user->name ?? '') ?>"
                                    required>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold" for="username">Username <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-at"></i></span>
                                        <input
                                            type="text"
                                            id="username"
                                            name="username"
                                            class="form-control"
                                            placeholder="username"
                                            value="<?= htmlspecialchars($user->username ?? '') ?>"
                                            required>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold" for="phone">No. HP</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-phone"></i></span>
                                        <input
                                            type="text"
                                            id="phone"
                                            name="phone"
                                            class="form-control"
                                            placeholder="08xxxxxxxxxx"
                                            value="<?= htmlspecialchars($user->phone ?? '') ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold" for="email">Email <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                    <input
                                        type="email"
                                        id="email"
                                        name="email"
                                        class="form-control"
                                        placeholder="nama@email.com"
                                        value="<?= htmlspecialchars($user->email ?? '') ?>"
                                        required>
                                </div>
                            </div>

                            <?php if(isAllow('user.set_role')): ?>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold" for="role_id">Role</label>
                                    <select id="role_id" name="role_id" class="form-select">
                                        <option value="">-- Tanpa Role --</option>
                                        <?php foreach ($roles as $role): ?>
                                            <option value="<?= $role->id ?>" <?= ($user->role_id ?? null) == $role->id ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($role->role_name) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            <?php endif; ?>

                            <div class="mb-3">
                                <label class="form-label fw-semibold" for="status">Status</label>
                                <select id="status" name="status" class="form-select">
                                    <option value="active" <?= ($user->status ?? 'active') === 'active'   ? 'selected' : '' ?>>Aktif</option>
                                    <option value="inactive" <?= ($user->status ?? '') === 'inactive' ? 'selected' : '' ?>>Nonaktif</option>
                                    <option value="deleted" <?= ($user->status ?? '') === 'deleted' ? 'selected' : '' ?>>Dihapus</option>
                                </select>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-semibold" for="password">
                                    Password <?= $isEdit ? '<small class="text-muted fw-normal">(kosongkan jika tidak diganti)</small>' : '<span class="text-danger">*</span>' ?>
                                </label>
                                <div class="input-group">
                                    <input
                                        :type="showPass ? 'text' : 'password'"
                                        id="password"
                                        name="password"
                                        class="form-control"
                                        placeholder="<?= $isEdit ? 'Biarkan kosong jika tidak diubah' : 'Minimal 8 karakter' ?>"
                                        <?= $isEdit ? '' : 'required' ?>>
                                    <button type="button" class="btn btn-outline-secondary" @click="showPass = !showPass" tabindex="-1">
                                        <i :class="showPass ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary" :disabled="loading">
                                    <span class="spinner-border spinner-border-sm d-none" :class="{'d-inline-block': loading, 'd-none': !loading}"></span>
                                    <i class="bi bi-check-lg" x-show="!loading"></i>
                                    <span x-text="loading ? 'Menyimpan...' : '<?= $btnText ?>'"></span>
                                </button>
                                <a href="<?= site_url('panel/user') ?>" class="btn btn-light">Batal</a>
                            </div>

                        </form>

                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
    function userForm() {
        return {
            loading: false,
            showPass: false,
            init() {},
            async submit(action) {
                this.loading = true;
                const alert = document.getElementById('formAlert');
                alert.className = 'alert d-none';

                try {
                    const res = await fetch(action, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: new URLSearchParams(new FormData(document.getElementById('userForm'))),
                    });
                    const data = await res.json();
                    if (data.success) {
                        alert.className = 'alert alert-success';
                        alert.textContent = data.message;
                        setTimeout(() => window.location.href = data.redirect, 1000);
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
            }
        };
    }
</script>