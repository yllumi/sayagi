<script>
function loginForm() {
    return {
        loading: false,
        alertMsg: '',
        alertType: '',
        alertShow: false,
        showPassword: false,
        async submit(event) {
            this.alertShow = false;
            this.loading  = true;
            try {
                const res  = await fetch(`<?= site_url('/panel/auth/login') ?>`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams(new FormData(event.target)),
                });
                const data = await res.json();
                if (data.success) {
                    window.location.href = data.redirect;
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

<!------------- TEMPLATE -------------->
<div x-data="loginForm()">

    <!-- Alert -->
    <div class="auth-alert" :class="alertType" x-show="alertShow" x-cloak>
        <i class="bi bi-exclamation-circle-fill"></i>
        <span x-text="alertMsg"></span>
    </div>

    <!-- Header -->
    <div class="auth-header">
        <div class="auth-logo">
            <img src="https://image.web.id/images/clipboard-image-1753328088.png" style="width: 250px; height: auto;">
        </div>
        <h1>Masuk ke Akun</h1>
        <p>Masukkan kredensial Anda untuk melanjutkan</p>
    </div>

    <!-- Form -->
    <form autocomplete="off" @submit.prevent="submit($event)">

        <div class="form-group">
            <label class="form-label" for="username">Username / Email</label>
            <div class="input-wrapper">
                <i class="bi bi-person input-icon"></i>
                <input
                    type="text"
                    id="username"
                    name="username"
                    class="form-control"
                    placeholder="username atau email"
                    required
                    autofocus>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label" for="password">Kata Sandi</label>
            <div class="input-wrapper">
                <i class="bi bi-lock input-icon"></i>
                <input
                    :type="showPassword ? 'text' : 'password'"
                    id="password"
                    name="password"
                    class="form-control"
                    placeholder="••••••••"
                    required>
                <button type="button" class="toggle-password" tabindex="-1" @click="showPassword = !showPassword" aria-label="Tampilkan kata sandi">
                    <i :class="showPassword ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
                </button>
            </div>
        </div>

        <div class="form-extras">
            <label class="form-check">
                <input type="checkbox" name="remember" value="1">
                <span class="form-check-label">Ingat saya</span>
            </label>
            <a href="<?= site_url('/panel/auth/forgot') ?>" class="forgot-link">Lupa password?</a>
        </div>

        <button type="submit" class="btn-login" :disabled="loading">
            <span class="spinner" x-show="loading" style="display:none"></span>
            <span x-text="loading ? 'Memproses...' : 'Masuk Sekarang'"></span>
            <i class="bi bi-arrow-right" x-show="!loading"></i>
        </button>

    </form>

    <?php if (getenv('app.enable_registration') === 'true'): ?>
        <div class="auth-divider">atau</div>
        <div class="auth-footer">
            Belum punya akun? <a href="<?= site_url('/panel/auth/register') ?>">Daftar di sini</a>
        </div>
    <?php endif; ?>

    <div class="auth-footer">
        Powered by WebmanPanel
    </div>

</div>