<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$config['admin_nav_config_loaded'] = true;

$config['admin_nav_top'] = [
];

$config['admin_nav_side'] = [
    [
        'icon' => 'ri-home-smile-line',
        'title' => 'Home',
        'class' => 'dashboard',
        'params' => [
            'layout' => 'side-menu',
        ],
    ],
    [
        'icon' => 'ri-team-line',
        'title' => 'Administrators Management',
        'class' => 'administrators',
        'params' => [
            'layout' => 'side-menu',
        ],
    ],
    [
        'icon' => 'ri-home-smile-line',
        'title' => 'Company Management',
        'class' => 'company',
        'params' => [
            'layout' => 'side-menu',
        ],
    ],
    [
        'icon' => 'ri-home-smile-line',
        'title' => 'Project Management',
        'class' => 'project',
        'params' => [
            'layout' => 'side-menu',
        ],
    ],
    [
        'icon' => 'ri-user-line',
        'title' => 'MyInfo',
        'class' => 'myInfo',
        'params' => [
            'layout' => 'side-menu',
        ],
    ],
    [
        'icon' => 'ri-settings-4-line',
        'title' => 'Settings',
    ],
    [
        'icon' => 'ri-folder-history-line',
        'title' => 'Logs',
        'route' => '',
        'params' => [
            'layout' => 'side-menu',
        ],
        'subMenu' => [

        ],
    ],
    [
        'icon' => 'ri-function-line',
        'title' => 'System',
        'route' => '',
        'params' => [
            'layout' => 'side-menu',
        ],
        'subMenu' => [
            [
                'icon' => '',
                'title' => 'SysCfg Management',
                'class' => 'system',
                'method' => 'sysCfg',
                'params' => [
                    'layout' => 'side-menu',
                ],
            ],
            [
                'icon' => '',
                'title' => 'SysCode Management',
                'class' => 'system',
                'method' => 'sysCode',
                'params' => [
                    'layout' => 'side-menu',
                ],
            ],
            [
                'icon' => '',
                'title' => 'MenuList Management',
                'class' => 'system',
                'method' => 'menuList',
                'params' => [
                    'layout' => 'side-menu',
                ],
            ],
            [
                'icon' => '',
                'title' => 'MenuAuth Management',
                'class' => 'system',
                'method' => 'menuAuth',
                'params' => [
                    'layout' => 'side-menu',
                ],
            ],
        ],
    ],
    [
        'icon' => 'ri-bar-chart-2-fill',
        'title' => 'Statistics',
        'route' => '',
        'params' => [
            'layout' => 'side-menu',
        ],
        'subMenu' => [
            [
                'icon' => '',
                'title' => 'Report',
                'attr' => [
                    'className' => ['text-primary', 'fw-bolder'],
                ],
            ],
            [
                'icon' => '',
                'title' => 'Visitor',
            ],
            [
                'icon' => '',
                'title' => 'Domain',
            ],
            [
                'icon' => '',
                'title' => 'Browser',
            ],
            [
                'icon' => '',
                'title' => 'OS',
            ],
        ]
    ],
    [
        'icon' => 'ri-questionnaire-fill',
        'title' => 'Help',
        'route' => '',
        'attr' => [
            'href' => 'https://naver.com',
            'className' => ['text-danger', 'fw-bolder'],
            'target' => '_popup'
        ],
    ],
];
