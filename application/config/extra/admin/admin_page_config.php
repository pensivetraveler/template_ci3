<?php defined('BASEPATH') OR exit('No direct script access allowed');

$config['admin_page_config_loaded'] = true;

$config['page_config'] = [
	'auth' => [
		'category' => 'auth',
		'type' => 'page',
		'properties' => [
			'baseMethod' => 'login',
			'allowNoLogin' => true,
			'includes' => [
				'head' => true,
				'header' => false,
				'modalPrepend' => true,
				'modalAppend' => false,
				'footer' => false,
				'tail' => true,
			],
		],
	],
	'dashboard' => [
		'category' => 'page',
		'type' => 'page',
		'subType' => 'dashboard',
		'properties' => [
			'baseMethod' => 'view',
			'allows' => ['view'],
            'includes' => [
                'head' => true,
                'header' => true,
                'modalPrepend' => true,
                'modalAppend' => true,
                'footer' => true,
                'tail' => true,
            ],
		],
		'formProperties' => [
			'formConfig' => '',
			'formType' => '',
		],
	],
    'company' => [
        'category' => 'page',
        'type' => 'page',
        'subType' => 'base',
        'properties' => [
            'baseMethod' => 'list',
            'allows' => ['list'],
            'formExist' => true,
            'listExist' => true,
        ],
        'formProperties' => [
            'formConfig' => 'company',
            'formType' => 'side',
        ],
        'listProperties' => [
            'listConfig' => 'company',
            'plugin' => 'datatable',
            'actions' => [
                'view' => false,
            ],
            'buttons' => [
                'excel' => true,
            ],
        ],
    ],
    'project' => [
        'category' => 'page',
        'type' => 'page',
        'subType' => 'base',
        'properties' => [
            'baseMethod' => 'list',
            'allows' => ['list'],
            'formExist' => true,
            'listExist' => true,
        ],
        'formProperties' => [
            'formConfig' => 'project',
            'formType' => 'side',
        ],
        'listProperties' => [
            'listConfig' => 'project',
            'plugin' => 'datatable',
            'actions' => [
                'view' => false,
            ],
            'buttons' => [
                'excel' => true,
            ],
        ],
    ],
    'administrators' => [
        'category' => 'page',
        'type' => 'page',
        'subType' => 'base',
        'properties' => [
            'baseMethod' => 'list',
            'allows' => ['list','add','edit'],
            'formExist' => true,
            'listExist' => true,
        ],
        'formProperties' => [
            'formConfig' => 'administrators',
            'formType' => 'side',
        ],
        'listProperties' => [
            'listConfig' => 'administrators',
            'plugin' => 'datatable',
            'buttons' => [
                'excel' => false,
            ],
            'actions' => [
                'view' => false,
            ],
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
		'listProperties' => [
			'listConfig' => '',
			'plugin' => '',
		],
	],
];
