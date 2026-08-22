<div class="page" data-name="mobile-books" x-data="$sayagi.page()">
  <div class="navbar">
    <div class="navbar-inner">
      <div class="title">Katalog Buku</div>
    </div>
  </div>

  <?php partial('_layouts/partials/tabbar', ['active_tab' => 'books']) ?>

  <div class="page-content">
    <!-- Loading -->
    <div class="block" x-show="ui.loading" style="text-align:center; padding-top: 40px;">
      <div class="preloader"></div>
    </div>

    <!-- Error -->
    <div class="block" x-show="ui.error">
      <p class="sayagi-error" x-text="ui.errorMessage || 'Terjadi kesalahan saat memuat data.'"></p>
    </div>

    <!-- List -->
    <div class="list media-list list-outline" x-show="data.books">
      <ul>
        <template x-for="book in data.books || []" :key="book.id">
          <li>
            <a :href="'/books/' + book.id + '/'" class="item-link item-content">
              <div class="item-media"><img :src="book.cover" alt="" class="sayagi-cover"></div>
              <div class="item-inner">
                <div class="item-title-row">
                  <div class="item-title" x-text="book.title"></div>
                </div>
                <div class="item-subtitle" x-text="book.author"></div>
                <div class="item-text">
                  <span class="sayagi-chip" x-text="book.category"></span>
                  <span style="color:#64748b; margin-left: 6px;" x-text="book.year"></span>
                </div>
              </div>
            </a>
          </li>
        </template>
      </ul>
    </div>
  </div>
</div>
