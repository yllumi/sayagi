<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'Heroic' ?></title>

    <link rel="shortcut icon" href="data:image/svg+xml,%3csvg%20xmlns='http://www.w3.org/2000/svg'%20viewBox='0%200%2033%2034'%20fill-rule='evenodd'%20stroke-linejoin='round'%20stroke-miterlimit='2'%20xmlns:v='https://vecta.io/nano'%3e%3cpath%20d='M3%2027.472c0%204.409%206.18%205.552%2013.5%205.552%207.281%200%2013.5-1.103%2013.5-5.513s-6.179-5.552-13.5-5.552c-7.281%200-13.5%201.103-13.5%205.513z'%20fill='%23435ebe'%20fill-rule='nonzero'/%3e%3ccircle%20cx='16.5'%20cy='8.8'%20r='8.8'%20fill='%2341bbdd'/%3e%3c/svg%3e" type="image/x-icon">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" crossorigin href="<?= site_url('/panel_theme/app.css') ?>">
    <link rel="stylesheet" crossorigin href="<?= site_url('/panel_theme/app-ext.css') ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/simplemde/latest/simplemde.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.9/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-tagsinput/1.3.6/jquery.tagsinput.min.css">
    <!-- Datepicker -->
    <link rel="stylesheet" id="theme-style" href="https://cdnjs.cloudflare.com/ajax/libs/datepicker/1.0.10/datepicker.min.css">
    
    <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.4/dist/jquery.min.js"></script>
    <style>[x-cloak]{display:none!important}</style>
    <script src="<?= site_url('/panel_theme/static/js/initTheme.js') ?>"></script>
</head>

<body>
    <div id="app">

        <?= cell('_partials/sidebar', ['module' => $module ?? '', 'submodule' => $submodule ?? ''], 'yllumi/sayagi') ?>

        <!-- Content Section -->
        <div id="main" class="layout-navbar navbar-fixed position-relative">
            <?= cell('_partials/header', [], 'yllumi/sayagi') ?>

            <div id="main-content">
                <?= cell('_partials/alerts', [], 'yllumi/sayagi') ?>

                <?= $content ?>
            </div>

            <?= cell('_partials/footer', [], 'yllumi/sayagi') ?>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    <script src="<?= site_url('/panel_theme/tinymce/js/tinymce/tinymce.min.js') ?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.9/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/moment@2.30.1/moment.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    
    <script src="<?= site_url('/panel_theme/app.js') ?>"></script>
    
    <!-- https://fengyuanchen.github.io/datepicker -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/datepicker/1.0.10/datepicker.min.js" type="text/javascript"></script>

    <!-- Ace Code Editor -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.4.13/ace.min.js" integrity="sha512-jB1NOQkR0yLnWmEZQTUW4REqirbskxoYNltZE+8KzXqs9gHG5mrxLR5w3TwUn6AylXkhZZWTPP894xcX/X8Kbg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.4.13/mode-html.min.js" integrity="sha512-vSQkVhmiIt31RHmh8b65o0ap3yoL08VJ6MeuiCGo+92JDdSSWAEWoWELEf3WBk4e2tz/0CvnTe87Y2rFrNjcbg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.10.1/mode-yaml.min.js" integrity="sha512-WcvQVyf7ECu3mkQRpaJJ2l05xJAIlFM1bscCbwduQBztxzoGUWqkAawsMdLr6tkD9ke4V6soIh6aufeAuW1ruw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.23.1/mode-json.min.js" integrity="sha512-dux75XSGmyoN14vXQ2uJ7dvx/uOjmTZfVPG/MBk27VT/k2dug8X1TSgye8RhHv3fhhZLTnWMwi8doXXbM4cvUw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.23.1/mode-css.min.js" integrity="sha512-y7tJeEggFZ4vA7ILQ9woUlZKyrTDdJfzCX6xUztUU6gGMy6k1DJfE/94YxZbLu5do99cTUDW6l+xqkekx0FFlg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

    <!-- ColorPicker -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tinyColorPicker/1.1.1/jqColorPicker.min.js" integrity="sha512-jQ+T1MmwqyWSgkn1MtW6OxXc6wySH9YnmC8rPlEAn0CLgWH4gY1Di/6r42BOqO9zSbLQxZ/47Xs/6qc2rIZmXw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

    <!-- Prompts.js -->
    <script src="https://cdn.jsdelivr.net/npm/prompts-js"></script>
</body>

</html>