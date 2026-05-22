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
];
