<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$config['admin_base_config_loaded'] = true;

$config['platform_config'] = [
	'isLoginRedirect' => 'dashboard',
	'noLoginRedirect' => 'auth',
];

$config['options'] = array_merge(
    $config['options'],
    [

    ]
);

$config['options']['search_category'] = array_merge(
    $config['options']['search_category'],
    [

    ]
);