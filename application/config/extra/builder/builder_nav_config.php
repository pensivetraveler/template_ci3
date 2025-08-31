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
    [
        'icon' => 'ri-home-smile-line',
        'title' => 'Home',
        'class' => 'dashboard',
        'params' => [
            'layout' => 'side-menu',
        ],
    ],
    [
        'icon' => 'ri-user-line',
        'title' => 'Welcome',
        'class' => 'dashboard',
        'params' => [
            'layout' => 'side-menu',
        ],
        'isSubMenu' => true,
        'subMenu' => [
            [
                'icon' => '',
                'title' => 'Welcome Sub 1',
                'class' => 'dashboard',
                'params' => [
                    'layout' => 'side-menu',
                ],
            ],
            [
                'icon' => '',
                'title' => 'Welcome Sub 2',
                'class' => 'dashboard',
                'params' => [
                    'layout' => 'side-menu',
                ],
            ],
        ],
    ],
    [
        'icon' => 'ri-user-line',
        'title' => 'User',
        'class' => 'users',
        'params' => [
            'layout' => 'side-menu',
        ],
    ],
];
