<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$config['admin_base_config_loaded'] = true;

$config['platform_config'] = [
    'loggedInRedirect' => 'dashboard',
    'noLoginRedirect' => 'auth',
    'navDefaultParams' => [
        'layout' => 'side-menu',
    ],
    'notAllowAccess' => [
        'common',
    ]
];
