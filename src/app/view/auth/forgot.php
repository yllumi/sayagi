<script>
function forgotForm() {
    return {
        loading: false,
        alertMsg: '',
        alertType: '',
        alertShow: false,
        async submit(event) {
            this.alertShow = false;
            this.loading   = true;

            // Execute reCAPTCHA v3
            const siteKey = document.getElementById('recaptchaSiteKey').value;
            let recaptchaToken = '';
            if (siteKey) {
                try {
                    recaptchaToken = await grecaptcha.execute(siteKey, { action: 'forgot_password' });
                } catch (e) { /* recaptcha unavailable, proceed */ }
            }

            const formData = new FormData(event.target);
            formData.set('recaptcha_token', recaptchaToken);

            try {
                const res  = await fetch(`<?= site_url('/panel/auth/forgot') ?>`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams(formData),
                });
                const data = await res.json();
                if (data.success && data.token) {
                    window.location.href = '/panel/auth/reset?token=' + encodeURIComponent(data.token);
                } else if (data.success) {
                    this.alertMsg  = data.message;
                    this.alertType = 'success';
                    this.alertShow = true;
                    this.loading   = false;
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

<?php if (!empty($recaptcha_site_key)): ?>
<script src="https://www.google.com/recaptcha/api.js?render=<?= htmlspecialchars($recaptcha_site_key) ?>" async defer></script>
<?php endif; ?>

<input type="hidden" id="recaptchaSiteKey" value="<?= htmlspecialchars($recaptcha_site_key ?? '') ?>">

<div x-data="forgotForm()">

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
        <h1>Lupa Password</h1>
        <p>Masukkan email Anda untuk kami kirimkan kode OTP</p>
    </div>

    <!-- Form -->
    <form autocomplete="off" @submit.prevent="submit($event)">

        <div class="form-group">
            <label class="form-label" for="email">Alamat Email</label>
            <div class="input-wrapper">
                <i class="bi bi-envelope input-icon"></i>
                <input
                    type="email"
                    id="email"
                    name="email"
                    class="form-control"
                    placeholder="nama@email.com"
                    required
                    autofocus>
            </div>
        </div>

        <button type="submit" class="btn-login" :disabled="loading">
            <span class="spinner" x-show="loading" style="display:none"></span>
            <span x-text="loading ? 'Mengirim...' : 'Kirim Kode OTP'"></span>
            <i class="bi bi-send" x-show="!loading"></i>
        </button>

    </form>

    <div class="auth-divider">atau</div>

    <div class="auth-footer">
        Ingat password? <a href="<?= site_url('panel/auth/login') ?>">Masuk di sini</a>
        <br><br>Powered by WebmanPanel
    </div>

</div>
