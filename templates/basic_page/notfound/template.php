<div id="page-notfound">

  <div class="nf-wrap">

    <div class="nf-code">404</div>

    <div class="nf-graphic">
      <div class="nf-circle nf-circle-1"></div>
      <div class="nf-circle nf-circle-2"></div>
      <i class="bi bi-compass nf-icon"></i>
    </div>

    <h1 class="nf-title">Halaman Tidak Ditemukan</h1>
    <p class="nf-desc">Halaman yang kamu cari tidak ada atau telah dipindahkan.</p>

    <a href="/" class="nf-btn" x-data="{loading:false}" x-on:click.prevent="loading=true; $router.navigate('/')">
      <span x-show="loading" class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>
      <i class="bi bi-house-fill me-1" x-show="!loading"></i>
      Kembali ke Beranda
    </a>

  </div>

</div>

<style>
#page-notfound {
  min-height: 100vh;
  background: linear-gradient(160deg, #fff7ed 0%, #fff 50%);
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: 'Poppins', sans-serif;
  padding: 40px 24px;
}

.nf-wrap {
  text-align: center;
  max-width: 340px;
}

.nf-code {
  font-size: 96px;
  font-weight: 900;
  letter-spacing: -4px;
  line-height: 1;
  background: linear-gradient(135deg, #f97316, #fb923c);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  margin-bottom: 8px;
}

.nf-graphic {
  position: relative;
  width: 120px;
  height: 120px;
  margin: 0 auto 28px;
}

.nf-circle {
  position: absolute;
  border-radius: 50%;
  border: 2px solid #fed7aa;
}
.nf-circle-1 {
  inset: 0;
  animation: nf-pulse 3s ease-in-out infinite;
}
.nf-circle-2 {
  inset: 14px;
  border-color: #fdba74;
  animation: nf-pulse 3s ease-in-out infinite 0.5s;
}

.nf-icon {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 42px;
  color: #f97316;
}

@keyframes nf-pulse {
  0%, 100% { transform: scale(1);   opacity: 1; }
  50%       { transform: scale(1.06); opacity: 0.5; }
}

.nf-title {
  font-size: 22px;
  font-weight: 700;
  color: #1c1917;
  margin: 0 0 10px;
  letter-spacing: -0.3px;
}

.nf-desc {
  font-size: 14px;
  color: #78716c;
  margin: 0 0 28px;
  line-height: 1.6;
}

.nf-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 12px 28px;
  border-radius: 12px;
  background: linear-gradient(135deg, #f97316, #ea580c);
  color: #fff;
  font-size: 14px;
  font-weight: 600;
  text-decoration: none;
  box-shadow: 0 4px 16px #f9731640;
  transition: opacity .2s, transform .1s;
}
.nf-btn:hover  { opacity: .9; color: #fff; }
.nf-btn:active { transform: scale(.97); }
</style>
