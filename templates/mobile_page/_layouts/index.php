<!doctype html>
<html>

<head>
  <?php partialRoot(mobile_pages_path(), '_layouts/partials/head', ['page_title' => $page_title ?? 'SIBI Mobile']) ?>
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
    // tiap class PageController di root web mobile (app/pages/).
    <?= \Yllumi\Sayagi\PortPageRouter::getF7RoutesScript(\Yllumi\Sayagi\PortPageRouter::getPort('mobile', 8779)) ?>
  </script>

  <?php partialRoot(mobile_pages_path(), '_layouts/partials/foot') ?>
</body>

</html>