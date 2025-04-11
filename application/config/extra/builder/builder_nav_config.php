<?php
$config['buider_nav_config_loaded'] = true;

$config['builder_nav_top_base'] = [

];

$config['builder_nav_side_base'] = [
	'code' => '',
	'icon' => '',
	'title' => 'Sample',
	'route' => '/admin/',
	'method' => 'dashboard',
	'router' => 'index',
	'params' => [
		'layout' => 'side-menu',
	],
	'className' => [],
	'authCheck' => false,
	'authParams' => [],
	'subMenu' => [],
];

$config['builder_nav_top_sample'] = [

];

$config['builder_nav_side_sample'] = [
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

