<?php defined('BASEPATH') OR exit('No direct script access allowed');

$config['admin_page_config_loaded'] = true;

$config['page_config'] = [
    'auth' => [
        'category' => 'page',
        'type' => 'base',
        'subtype' => 'base',
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
        'methods' => [
            'login' => [
                'type' => 'form',
                'subtype' => 'auth',
            ],
            'findId' => [
                'type' => 'form',
                'subtype' => 'auth',
            ],
            'findPassword' => [
                'type' => 'form',
                'subtype' => 'auth',
            ],
            'logout' => [
                'type' => 'action',
                'properties' => [
                    'perform'     => 'logoutUser',      // 컨트롤러 메소드 이름
                    'redirectUri' => '/admin/auth',    // 실행 후 이동할 URI
                ],
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
    'administrators' => [
        'category' => 'page',
        'type' => 'base',
        'subtype' => 'base',
        'properties' => [
            'baseMethod' => 'list',
        ],
        'methods' => [
            'list' => [
                'type' => 'list',
                'subtype' => 'datatable',
                'buttons' => [
                    'excel' => false,
                ],
                'actions' => [
                    'view' => false,
                    'excel' => false,
                ],
                'properties' => [
                    'formExist' => true,
                ],
            ],
        ],
    ],
    'emailLogs' => [
        'category' => 'page',
        'type' => 'page',
        'subType' => 'base',
        'properties' => [
            'baseMethod' => 'list',
        ],
        'methods' => [
            'list' => [
                'type' => 'list',
                'subtype' => 'datatable',
                'actions' => [
                    'edit' => false,
                    'view' => false,
                    'delete' => false,
                ],
                'buttons' => [
                    'add' => false,
                    'excel' => false,
                ],
            ],
        ],
    ],
    'myinfo' => [
        'category' => 'page',
        'type' => 'page',
        'subType' => 'base',
        'properties' => [
            'baseMethod' => 'edit',
        ],
        'methods' => [
            'edit' => [
                'type' => 'form',
                'subtype' => 'base',
                'config' => 'myinfo',
            ],
        ],
    ],
    'system' => [
        'category' => 'page',
        'type' => 'page',
        'subType' => 'base',
        'properties' => [
            'noIndex' => true,
            'noIdentifier' => true,
            'baseMethod' => 'sysCfg',
        ],
        'methods' => [
            'sysCfg' => [
                'type' => 'form',
                'subType' => 'side',
                'config' => 'syscfg',
            ],
            'sysCode' => [
                'type' => 'list',
                'subtype' => 'datatable',
                'config' => 'syscode',
                'actions' => [
                    'edit' => true,
                ],
                'buttons' => [
                    'add' => true,
                    'excel' => false,
                    'editBigCd' => [
                        'text' => 'Edit Big Cd',
                        'classes' => 'edit-big-cd btn btn-outline-primary waves-effect waves-light me-4',
                    ],
                    'deleteBigCd' => [
                        'text' => 'Delete Big Cd',
                        'classes' => 'delete-big-cd btn btn-outline-danger waves-effect waves-light me-4',
                    ],
                ],
                'properties' => [
                    'formExist' => true,
                    'formConfig' => 'syscode'
                ],
            ],
            'menuList' => [
                'type' => 'form',
                'subtype' => 'menuList',
                'config' => 'menu_list',
            ],
            'menuAuth' => [
                'type' => 'list',
                'subtype' => 'datatable',
                'config' => 'menu_auth',
                'actions' => [
                    'view' => false,
                    'edit' => false,
                    'delete' => false,
                ],
                'buttons' => [
                    'add' => false,
                    'excel' => false,
                ],
                'properties' => [
                    'filterConfig' => 'menu_auth'
                ],
            ],
        ],
    ],
];
