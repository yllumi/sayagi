<div class="page" data-name="mobile-books-detail" x-data="$sayagi.page()">
  <div class="navbar">
    <div class="navbar-inner">
      <div class="left">
        <a href="/books/" class="link back-btn icon-only prevent-router" data-back-to="/books/"><i class="icon f7-icons">arrow_left</i></a>
      </div>
      <div class="title">Detail Buku</div>
    </div>
  </div>

  <div class="page-content">
    <!-- Loading -->
    <div class="block" x-show="ui.loading" style="text-align:center; padding-top: 40px;">
      <div class="preloader"></div>
    </div>

    <!-- Error -->
    <div class="block" x-show="ui.error">
      <p class="sayagi-error" x-text="ui.errorMessage || 'Terjadi kesalahan saat memuat data.'"></p>
    </div>

    <!-- Detail -->
    <template x-if="data.book">
      <div class="block block-strong sayagidetail" style="margin-top: 16px;">
        <img :src="data.book.cover" alt="" class="sayagi-detail-cover">
        <h2 style="font-size: 20px; margin: 0 0 4px;" x-text="data.book.title"></h2>
        <p class="sayagi-detail-meta" x-text="data.book.author + ' · ' + data.book.year"></p>
        <span class="sayagi-chip" x-text="data.book.category"></span>
        <p class="sayagi-detail-desc" style="margin-top: 14px;" x-text="data.book.description"></p>
      </div>
    </template>

    <!-- Not found -->
    <template x-if="data.book === null">
      <div class="block" style="text-align:center;">
        <p>Buku tidak ditemukan.</p>
        <a href="/books/" class="button button-outline">Kembali ke Katalog</a>
      </div>
    </template>
  </div>
</div>
