<?php
$config['buider_nav_config_loaded'] = true;

$config['builder_nav_top_base'] = [

];

$config['builder_nav_menu_base'] = [
    'code' => '',
    'icon' => '',
    'title' => '',
    'class' => '',
    'method' => '',
    'attr' => [
        'className' => [],
        'target' => '_self',
        'href' => '',
    ],
    'params' => [
        'layout' => 'side-menu',
    ],
    'authParams' => [],
    'subMenu' => [],
    'isSubMenu' => false,
    'isLogin' => true,
    'isAuth' => false,
    'isSuper' => false,
    'isUse' => true,
];

$config['builder_nav_top_sample'] = [

];

/***
 * sample nav configs
 */
$config['builder_nav_menu_sample'] = [
    'dashboard' => [
        'icon' => 'ri-home-smile-line',
        'title' => 'Home',
        'route' => '/admin/dashboard',
        'method' => 'dashboard',
        'params' => [
            'layout' => 'side-menu',
        ],
    ],
    'welcome' => [
        'icon' => 'ri-user-line',
        'title' => 'Welcome',
        'route' => '',
        'method' => 'dashboard',
        'params' => [
            'layout' => 'side-menu',
        ],
        'subMenu' => [
            'Welcome Sub 1' => [
                'icon' => '',
                'title' => 'Welcome Sub 1',
                'route' => '/admin/',
                'params' => [
                    'welcome' => 1,
                ],
                'className' => [],
            ],
            'Welcome Sub 2' => [
                'icon' => '',
                'title' => 'Welcome Sub 2',
                'route' => '/admin/',
                'params' => [
                    'welcome' => 2,
                ],
                'className' => [],
            ],

        ],
    ],
    'user' => [
        'icon' => 'ri-user-line',
        'title' => 'User',
        'route' => '/admin/users',
        'method' => 'dashboard',
        'params' => [
            'layout' => 'side-menu',
        ],
    ],

];

