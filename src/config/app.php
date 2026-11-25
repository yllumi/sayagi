<?php

use support\Request;

return [
    'enable' => true,
    'controller_suffix' => 'Controller',
    'controller_reuse' => false,
    
    'site_title' => 'Sayagi Admin',
    'enable_registration' => getenv('app.enable_registration') === 'true' ? true : false,

    // Nama tabel panel — ubah bila skema database memakai nama lain.
    'tables' => [
        'users'           => 'mein_users',
        'user_profile'    => 'mein_user_profile',
        'roles'           => 'mein_roles',
        'role_privileges' => 'mein_role_privileges',
    ],
];
