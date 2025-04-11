<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$config['admin_nav_config_loaded'] = true;

$config['admin_nav_top'] = [
];

$config['admin_nav_side'] = [
    'Dashboard' => [
        'icon' => 'ri-home-smile-line',
        'title' => 'Home',
        'route' => '/admin/dashboard',
        'method' => 'dashboard',
        'router' => 'index',
        'params' => [
            'layout' => 'side-menu',
        ],
        'className' => [],
        'subMenu' => [],
    ],
    'Company' => [
        'icon' => 'ri-home-smile-line',
        'title' => 'Company',
        'route' => '/admin/company',
        'router' => 'company',
        'params' => [
            'layout' => 'side-menu',
        ],
        'className' => [],
        'subMenu' => [],
    ],
    'Project' => [
        'icon' => 'ri-home-smile-line',
        'title' => 'Project',
        'route' => '/admin/project',
        'router' => 'project',
        'params' => [
            'layout' => 'side-menu',
        ],
        'className' => [],
        'subMenu' => [],
    ],
    'MyInfo' => [
        'icon' => 'ri-user-line',
        'title' => 'MyInfo',
        'route' => '/admin/myInfo',
        'params' => [
            'layout' => 'side-menu',
        ],
        'className' => [],
        'subMenu' => [],
    ],
];
