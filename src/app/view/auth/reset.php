<script>
function resetForm() {
    return {
        loading: false,
        alertMsg: '',
        alertType: '',
        alertShow: false,
        showPassword: false,
        showConfirm: false,
        async submit(event) {
            this.alertShow = false;
            this.loading   = true;
            try {
                const res  = await fetch(`<?= site_url('/panel/auth/reset') ?>`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams(new FormData(event.target)),
                });
                const data = await res.json();
                if (data.success) {
                    this.alertMsg  = data.message;
                    this.alertType = 'success';
                    this.alertShow = true;
                    setTimeout(() => window.location.href = data.redirect, 1800);
                } else {
                    this.alertMsg  = data.message;
                    this.alertType = 'error';
                    this.alertShow = true;
                    this.loading   = false;
                }
            } catch (e) {
                this.alertMsg  = 'Terjadi kesalahan. Silakan coba lagi.';
                this.alertType = 'error';
                this.alertShow = true;
                this.loading   = false;
            }
        }
    };
}
</script>

<div x-data="resetForm()">

    <!-- Alert -->
    <div class="auth-alert" :class="alertType" x-show="alertShow" x-cloak>
        <i class="bi" :class="alertType === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-circle-fill'"></i>
        <span x-text="alertMsg"></span>
    </div>

    <!-- Header -->
    <div class="auth-header">
        <div class="auth-logo">
            <img src="https://image.web.id/images/clipboard-image-1753328088.png" style="width: 250px; height: auto;">
        </div>
        <h1>Reset Password</h1>
        <p>Masukkan kode OTP yang telah kami kirim ke email dan password baru Anda</p>
    </div>

    <!-- Form -->
    <form autocomplete="off" @submit.prevent="submit($event)">

        <input type="hidden" name="token" value="<?= $token ?? '' ?>">

        <div class="form-group">
            <label class="form-label" for="otp">Kode OTP</label>
            <div class="input-wrapper">
                <i class="bi bi-shield-lock input-icon"></i>
                <input
                    type="text"
                    id="otp"
                    name="otp"
                    class="form-control"
                    placeholder="______"
                    maxlength="6"
                    inputmode="numeric"
                    pattern="[0-9]{6}"
                    required
                    autofocus
                    style="letter-spacing: 6px; font-size: 1.2rem; font-weight: 700; text-align: center;">
            </div>
        </div>

        <div class="form-group">
            <label class="form-label" for="password">Password Baru</label>
            <div class="input-wrapper">
                <i class="bi bi-lock input-icon"></i>
                <input
                    :type="showPassword ? 'text' : 'password'"
                    id="password"
                    name="password"
                    class="form-control"
                    placeholder="Minimal 8 karakter"
                    required>
                <button type="button" class="toggle-password" tabindex="-1" @click="showPassword = !showPassword" aria-label="Tampilkan password">
                    <i :class="showPassword ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
                </button>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label" for="password_confirmation">Konfirmasi Password</label>
            <div class="input-wrapper">
                <i class="bi bi-lock-fill input-icon"></i>
                <input
                    :type="showConfirm ? 'text' : 'password'"
                    id="password_confirmation"
                    name="password_confirmation"
                    class="form-control"
                    placeholder="Ulangi password baru"
                    required>
                <button type="button" class="toggle-password" tabindex="-1" @click="showConfirm = !showConfirm" aria-label="Tampilkan password">
                    <i :class="showConfirm ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
                </button>
            </div>
        </div>

        <button type="submit" class="btn-login" :disabled="loading">
            <span class="spinner" x-show="loading" style="display:none"></span>
            <span x-text="loading ? 'Menyimpan...' : 'Simpan Password Baru'"></span>
            <i class="bi bi-check-lg" x-show="!loading"></i>
        </button>

    </form>

    <div class="auth-divider">atau</div>

    <div class="auth-footer">
        <a href="<?= site_url('panel/auth/forgot') ?>"><i class="bi bi-arrow-left" style="font-size:.8rem"></i> Minta kode baru</a>
        <span style="margin:0 .5rem;opacity:.4">|</span>
        <a href="<?= site_url('panel/auth/login') ?>">Kembali ke login</a>
        <br><br>Powered by WebmanPanel
    </div>

</div>
