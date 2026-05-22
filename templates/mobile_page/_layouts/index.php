<!doctype html>
<html>

<head>
  <?php partial('_layouts/partials/head') ?>
</head>

<body>
  <div id="app" x-data></div>

  <div id="router" x-data="router()">
    <?= \Yllumi\Sayagi\FERouter::getRouter($ssr_route ?? '', $ssr_content ?? '', $ssr_data ?? null) ?>
  </div>

  <?php partial('_layouts/partials/foot') ?>
</body>

</html>