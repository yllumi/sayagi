<?php

use support\Request;

return [
    'enable' => true,
    'controller_suffix' => 'Controller',
    'controller_reuse' => false,
    
    'site_title' => 'Sayagi Admin',
    'enable_registration' => getenv('app.enable_registration') === 'true' ? true : false,
];
