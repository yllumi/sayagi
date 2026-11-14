<?php
/**
 * @var object $user
 * @var array  $endpoints
 * @var string|null $avatar_url
 */
$config = [
    'endpoints' => $endpoints,
    'avatar'    => $avatar_url,
];
$json = htmlspecialchars(
    json_encode($config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES),
    ENT_QUOTES,
    'UTF-8'
);
$esc = static fn ($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<div class="content profile-page" x-data="profilePage(<?= $json ?>)">

    <div class="page-heading">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h3 class="mb-1"><?= $esc($page_title ?? 'Profil Saya') ?></h3>
                <p class="text-muted mb-0 small">Perbarui identitas, avatar, dan password akun Anda sendiri.</p>
            </div>
            <span class="badge bg-light text-dark border">
                <i class="bi bi-shield-check me-1"></i><?= $esc(($user->status ?? '') === 'active' ? 'Akun aktif' : ucfirst((string) ($user->status ?? ''))) ?>
            </span>
        </div>
    </div>

    <div class="page-content">
        <div class="row g-4">
            <div class="col-lg-7">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="mb-0">Informasi profil</h5>
                    </div>
                    <div class="card-body p-4">

                        <div class="alert d-none" id="profileAlert" role="alert"></div>

                        <form id="profileForm" @submit.prevent="saveProfile()">
                            <div class="mb-4">
                                <?= cell('_partials/avatar-uploader', [
                                    'config' => [
                                        'url'         => $endpoints['avatar'],
                                        'remove_url'  => $endpoints['avatar_delete'],
                                        'field'       => 'avatar',
                                        'name'        => (string) $user->name,
                                        'url_current' => $avatar_url,
                                    ],
                                    'name' => (string) $user->name,
                                    'url'  => $avatar_url,
                                ], 'yllumi/sayagi') ?>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold" for="profileName">Nama Lengkap <span class="text-danger">*</span></label>
                                <input type="text" id="profileName" name="name" class="form-control"
                                       value="<?= $esc($user->name) ?>" required>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold" for="profileUsername">Username <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-at"></i></span>
                                        <input type="text" id="profileUsername" name="username" class="form-control"
                                               value="<?= $esc($user->username) ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold" for="profilePhone">No. HP</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-phone"></i></span>
                                        <input type="text" id="profilePhone" name="phone" class="form-control"
                                               value="<?= $esc($user->phone) ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold" for="profileEmail">Email <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                    <input type="email" id="profileEmail" name="email" class="form-control"
                                           value="<?= $esc($user->email) ?>" required>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-semibold" for="profileBio">Bio singkat</label>
                                <textarea id="profileBio" name="short_description" class="form-control" rows="3"
                                          maxlength="255" placeholder="Ditampilkan sebagai keterangan singkat profil."><?= $esc($user->short_description) ?></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary" :disabled="saving">
                                <span class="spinner-border spinner-border-sm me-1" x-show="saving" x-cloak></span>
                                <span x-text="saving ? 'Menyimpan…' : 'Simpan Profil'">Simpan Profil</span>
                            </button>
                        </form>

                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Ringkasan akun</h5>
                    </div>
                    <div class="card-body">
                        <dl class="row mb-0 small">
                            <dt class="col-5 text-muted">Role</dt>
                            <dd class="col-7"><?= $esc($user->role_name ?? '—') ?></dd>
                            <dt class="col-5 text-muted">Terdaftar</dt>
                            <dd class="col-7"><?= $esc($user->created_at ?? '—') ?></dd>
                            <dt class="col-5 text-muted">Login terakhir</dt>
                            <dd class="col-7 mb-0"><?= $esc($user->last_login ?? '—') ?></dd>
                        </dl>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Ganti password</h5>
                    </div>
                    <div class="card-body p-4">

                        <div class="alert d-none" id="passwordAlert" role="alert"></div>

                        <form id="passwordForm" @submit.prevent="savePassword()">
                            <div class="mb-3">
                                <label class="form-label fw-semibold" for="currentPassword">Password lama <span class="text-danger">*</span></label>
                                <input type="password" id="currentPassword" name="current_password" class="form-control"
                                       autocomplete="current-password" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold" for="newPassword">Password baru <span class="text-danger">*</span></label>
                                <input type="password" id="newPassword" name="password" class="form-control"
                                       autocomplete="new-password" minlength="8" required>
                                <div class="form-text">Minimal 8 karakter.</div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-semibold" for="newPasswordConfirm">Ulangi password baru <span class="text-danger">*</span></label>
                                <input type="password" id="newPasswordConfirm" name="password_confirmation" class="form-control"
                                       autocomplete="new-password" minlength="8" required>
                            </div>

                            <button type="submit" class="btn btn-dark" :disabled="savingPassword">
                                <span class="spinner-border spinner-border-sm me-1" x-show="savingPassword" x-cloak></span>
                                <span x-text="savingPassword ? 'Menyimpan…' : 'Ganti Password'">Ganti Password</span>
                            </button>
                        </form>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function profilePage(config) {
        return {
            saving: false,
            savingPassword: false,

            showAlert(target, type, message) {
                const el = document.getElementById(target);
                if (!el) return;
                el.className = 'alert alert-' + type;
                el.textContent = message;
            },

            async post(action, formId) {
                const form = document.getElementById(formId);
                const response = await fetch(action, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams(new FormData(form)),
                });

                return response.json();
            },

            async saveProfile() {
                this.saving = true;
                try {
                    const data = await this.post(config.endpoints.update, 'profileForm');
                    this.showAlert('profileAlert', data.success ? 'success' : 'danger', data.message);
                } catch (e) {
                    this.showAlert('profileAlert', 'danger', 'Terjadi kesalahan. Silakan coba lagi.');
                } finally {
                    this.saving = false;
                }
            },

            async savePassword() {
                this.savingPassword = true;
                try {
                    const data = await this.post(config.endpoints.password, 'passwordForm');
                    this.showAlert('passwordAlert', data.success ? 'success' : 'danger', data.message);
                    if (data.success) {
                        document.getElementById('passwordForm').reset();
                    }
                } catch (e) {
                    this.showAlert('passwordAlert', 'danger', 'Terjadi kesalahan. Silakan coba lagi.');
                } finally {
                    this.savingPassword = false;
                }
            },
        };
    }
</script>
