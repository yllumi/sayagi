<div class="page" data-name="mobile-home" x-data="$sayagi.page()">

  <?php partial('_layouts/partials/tabbar', ['active_tab' => 'home']) ?>

  <div class="page-content">
    <!-- Hero -->
    <div class="sibi-hero">
      <div class="sibi-hero-badge">
        <img src="/page_theme/logo-text.png" alt="Sayagi"><br>
      </div>
      <p class="sibi-hero-tagline">PHP · Alpine.js · Framework7</p>
      <span class="sibi-chip">
        v<span x-text="data.version || '1.0.0'"></span> &nbsp;·&nbsp; PHP <span x-text="data.php_version"></span>
      </span>
    </div>

    <!-- Stack -->
    <div class="block-title">Built on</div>
    <div class="list list-outline media-list sibi-stack-list">
      <ul>
        <template x-for="item in data.stack || []" :key="item.name">
          <li class="media-item">
            <div class="item-content">
              <div class="item-media"><i class="icon f7-icons">app_fill</i></div>
              <div class="item-inner">
                <div class="item-title-row">
                  <div class="item-title" x-text="item.name"></div>
                </div>
                <div class="item-text" x-text="item.desc"></div>
              </div>
            </div>
          </li>
        </template>
      </ul>
    </div>

    <!-- Features -->
    <div class="block-title">What's included</div>
    <div class="row no-gap">
      <template x-for="f in data.features || []" :key="f.title">
        <div class="col-50" style="padding: 4px;">
          <div class="card sibi-feature-card">
            <div class="card-content card-content-padding">
              <i class="icon f7-icons sibi-feature-icon">star</i>
              <div class="sibi-feature-title" x-text="f.title"></div>
              <div class="sibi-feature-desc" x-text="f.desc"></div>
            </div>
          </div>
        </div>
      </template>
    </div>

  </div>
</div>
