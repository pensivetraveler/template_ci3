<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$config['admin_nav_config_loaded'] = true;

$config['admin_nav_top'] = [

];

$config['admin_nav_menu'] = [
    [
        'icon' => 'ri-team-line',
        'title' => 'Administrators Management',
        'class' => 'administrators',
    ],
    [
        'icon' => 'ri-shapes-line',
        'title' => 'Categories Management',
        'class' => 'categories',
    ],
    [
        'icon' => 'ri-building-line',
        'title' => 'Companies Management',
        'class' => 'companies',
    ],
    [
        'icon' => 'ri-code-box-line',
        'title' => 'Projects Management',
        'class' => 'projects',
    ],
    [
        'icon' => 'ri-folder-history-line',
        'title' => 'Logs',
        'subMenu' => [
            [
                'title' => 'Visitors',
                'class' => 'visitors',
                'isSuper' => true,
            ],
            [
                'title' => 'UserAccess',
                'class' => 'logs',
                'method' => 'userAccess',
            ],
            [
                'title' => 'ErrorLogs',
                'class' => 'logs',
                'method' => 'errors',
                'isSuper' => true,
            ]
        ],
    ],
    [
        'icon' => 'ri-function-line',
        'title' => 'System',
        'class' => '',
        'subMenu' => [
            [
                'title' => 'SysCfg Management',
                'class' => 'system',
                'method' => 'sysCfg',
                'isSuper' => true,
            ],
            [
                'title' => 'SysCode Management',
                'class' => 'system',
                'method' => 'sysCode',
                'isSuper' => true,
            ],
            [
                'title' => 'MenuList Management',
                'class' => 'system',
                'method' => 'menuList',
                'isSuper' => true,
            ],
            [
                'title' => 'MenuAuth Management',
                'class' => 'system',
                'method' => 'menuAuth',
            ],
        ],
    ],
    [
        'icon' => 'ri-user-5-line',
        'title' => 'MyInfo',
        'class' => 'myInfo',
        'isAuth' => false,
    ],
];
