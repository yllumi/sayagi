<?php

use support\Request;

return [
    'enable' => true,
    
    'debug' => true,
    'controller_suffix' => 'Controller',
    'controller_reuse' => false,
    'version' => '1.0.0',

    'site_title' => 'HeroicAdmin',
    'enable_registration' => getenv('app.enable_registration') === 'true' ? true : false,

    // Root halaman per port (dipakai \Yllumi\Sayagi\PortPageRouter).
    // Host bisa override di config/plugin/yllumi/sayagi/app.php (published)
    // atau di config/app.php -> 'pages' (legacy override).
    // 'pages' => [
    //     'main' => [
    //         'port'    => 8778,
    //         'path'    => app_path('pages'),
    //         'ns'      => 'app\\pages\\',
    //         'default' => 'home',
    //     ],
    //     'mobile' => [
    //         'port'    => 8779,
    //         'path'    => app_path('pages'),
    //         'ns'      => 'app\\pages\\',
    //         'default' => 'home',
    //     ],
    // ],
];
