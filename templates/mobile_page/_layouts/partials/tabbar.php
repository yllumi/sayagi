<?php
/**
 * Bottom tabbar (Framework7 9.x) untuk halaman beranda & katalog buku.
 *
 * Usage:
 *   partialRoot(mobile_pages_path(), '_layouts/partials/tabbar', ['active_tab' => 'home'|'books'])
 *
 * Diletakkan di dalam `.page`, SEBELUM `.page-content`, supaya selector F7
 * `.toolbar-bottom~*` menerapkan --f7-page-toolbar-bottom-offset sehingga
 * konten tidak tertutup tabbar.
 */
?>
<div class="toolbar tabbar tabbar-icons toolbar-bottom sibi-tabbar">
  <div class="toolbar-inner">
    <a href="/" class="link prevent-router<?= ($active_tab ?? '') === 'home' ? ' tab-link-active' : '' ?>">
      <i class="icon f7-icons">house</i>
      <span class="tabbar-label">Beranda</span>
    </a>
    <a href="/books/" class="link prevent-router<?= ($active_tab ?? '') === 'books' ? ' tab-link-active' : '' ?>">
      <i class="icon f7-icons">books_2</i>
      <span class="tabbar-label">Buku</span>
    </a>
  </div>
</div>
