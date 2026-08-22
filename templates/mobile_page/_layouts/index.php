<!doctype html>
<html>

<head>
  <?php partial('_layouts/partials/head', ['page_title' => $page_title ?? 'Sayagi App']) ?>
</head>

<body>
  <div id="app">
    <div class="view view-main view-init" data-url="<?= $ssr_route ?? '/' ?>">
      <?= $ssr_content ?? '' ?>
    </div>
  </div>

  <?php
    // SSR data untuk hydrasi Alpine pada halaman awal (initial page F7).
    // Ikuti konvensi heroic.js/FERouter: root "/" → "home/data" (bukan "/data"),
    // supaya __HEROIC_SSR_URL__ cocok dengan cache key F7/heroic (home/data)
    // dan SSR dipakai langsung tanpa fetch ulang saat load pertama.
    $ssrRoute = $ssr_route ?? '/';
    $ssrUrl = ($ssrRoute === '/' || $ssrRoute === '') ? 'home/data' : trim($ssrRoute, '/') . '/data';
  ?>
  <script>
    <?= \Yllumi\Sayagi\FERouter::ssrDataScript($ssr_data ?? null, $ssrUrl) ?>
  </script>

  <script>
    // Daftar routes F7 di-generate server-side dari atribut #[FrontendRoute]
    // tiap class PageController di root app/pages/.
    <?= \Yllumi\Sayagi\FERouter::getF7RoutesScript() ?>
  </script>

  <?php partial('_layouts/partials/foot') ?>
</body>

</html>