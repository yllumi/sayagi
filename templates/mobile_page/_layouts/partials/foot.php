<!-- Framework7 Bundle -->
<script src="<?= asset_url('/page_theme/framework7/js/framework7-bundle.min.js') ?>"></script>
<!-- Alpine.js -->
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<!-- Heroic helpers ($sayagi) -->
<script src="<?= asset_url('/page_theme/js/sayagi.js') ?>"></script>
<!-- Framework7 app config -->
<script src="<?= asset_url('/page_theme/js/f7-app.js') ?>"></script>

<script>
    let base_url = `<?= getenv('app.url') ?>`
    let api_url = `<?= getenv('api_url') ?? '' ?>`
</script>