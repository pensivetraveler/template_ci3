<?php
$config['route_config_loaded'] = true;

$config['route_config'] = [
	'auth' => [
		'category' => 'auth',
		'type' => 'page',
		'properties' => [
			'baseMethod' => 'login',
			'allowNoLogin' => true,
		],
	],
	'myinfo' => [
		'category' => 'page',
		'type' => 'page',
		'subtype' => 'base',
		'properties' => [
			'baseMethod' => 'edit',
			'allows' => ['edit'],
			'formExist' => true,
		],
		'formProperties' => [
			'formConfig' => 'myinfo',
			'formType' => 'page',
		],
	],
];
