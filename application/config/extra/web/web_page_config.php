<?php
$config['page_config_loaded'] = true;

$config['page_config'] = [
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
		'subType' => 'base',
		'properties' => [
			'baseMethod' => 'edit',
			'allows' => ['edit'],
			'formExist' => true,
			'listExist' => false,
		],
		'formProperties' => [
			'formConfig' => 'myinfo',
			'formType' => 'page',
		],
	],
];
