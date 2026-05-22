<script>
function registerForm() {
    return {
        loading: false,
        alertMsg: '',
        alertType: '',
        alertShow: false,
        showPassword: false,
        showConfirm: false,
        async submit(event) {
            this.alertShow = false;
            this.loading  = true;
            try {
                const res  = await fetch(`<?= site_url('/panel/auth/register') ?>`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams(new FormData(event.target)),
                });
                const data = await res.json();
                if (data.success) {
                    this.alertMsg  = data.message;
                    this.alertType = 'success';
                    this.alertShow = true;
                    setTimeout(() => window.location.href = data.redirect, 1500);
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
<div x-data="registerForm()">

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
        <h1>Buat Akun Baru</h1>
        <p>Daftarkan diri Anda untuk mengakses panel</p>
    </div>

    <!-- Form -->
    <form autocomplete="off" @submit.prevent="submit($event)">

        <div class="form-group">
            <label class="form-label" for="name">Nama Lengkap</label>
            <div class="input-wrapper">
                <i class="bi bi-person-vcard input-icon"></i>
                <input
                    type="text"
                    id="name"
                    name="name"
                    class="form-control"
                    placeholder="Nama lengkap Anda"
                    required
                    autofocus>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label" for="email">Email</label>
            <div class="input-wrapper">
                <i class="bi bi-envelope input-icon"></i>
                <input
                    type="email"
                    id="email"
                    name="email"
                    class="form-control"
                    placeholder="nama@email.com"
                    required>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label" for="username">Username</label>
            <div class="input-wrapper">
                <i class="bi bi-at input-icon"></i>
                <input
                    type="text"
                    id="username"
                    name="username"
                    class="form-control"
                    placeholder="username unik Anda"
                    required>
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
                    placeholder="Minimal 8 karakter"
                    required>
                <button type="button" class="toggle-password" tabindex="-1" @click="showPassword = !showPassword" aria-label="Tampilkan kata sandi">
                    <i :class="showPassword ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
                </button>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label" for="password_confirmation">Konfirmasi Kata Sandi</label>
            <div class="input-wrapper">
                <i class="bi bi-lock-fill input-icon"></i>
                <input
                    :type="showConfirm ? 'text' : 'password'"
                    id="password_confirmation"
                    name="password_confirmation"
                    class="form-control"
                    placeholder="Ulangi kata sandi"
                    required>
                <button type="button" class="toggle-password" tabindex="-1" @click="showConfirm = !showConfirm" aria-label="Tampilkan kata sandi">
                    <i :class="showConfirm ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
                </button>
            </div>
        </div>

        <button type="submit" class="btn-login" :disabled="loading">
            <span class="spinner" x-show="loading" style="display:none"></span>
            <span x-text="loading ? 'Memproses...' : 'Daftar Sekarang'"></span>
            <i class="bi bi-arrow-right" x-show="!loading"></i>
        </button>

    </form>

    <div class="auth-divider">atau</div>

    <div class="auth-footer">
        Sudah punya akun? <a href="<?= site_url('panel/auth/login') ?>">Masuk di sini</a>
        <br><br>Powered by WebmanPanel
    </div>

</div>
