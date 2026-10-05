<?php defined('BASEPATH') OR exit('No direct script access allowed');

$config['admin_route_config_loaded'] = true;

$config['route_config'] = [
    'auth' => [
        'category' => 'page',
        'type' => 'page',
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
                'category' => 'form',
                'type' => 'auth',
                'subtype' => 'login',
            ],
            'findId' => [
                'category' => 'form',
                'type' => 'auth',
                'subtype' => 'find_id',
            ],
            'findPassword' => [
                'category' => 'form',
                'type' => 'auth',
                'subtype' => 'find_password',
            ],
        ],
    ],
	'dashboard' => [
		'category' => 'page',
        'type' => 'page',
		'subtype' => 'base',
		'properties' => [
			'baseMethod' => 'view',
			'allows' => ['view'],
            'noIdentifier' => true,
		],
        'methods' => [
            'view' => [
                'category' => 'view',
                'type' => 'dashboard',
                'subtype' => 'base',
            ],
        ]
	],
    'administrators' => [
        'category' => 'page',
        'type' => 'page',
        'subtype' => 'base',
        'properties' => [
            'baseMethod' => 'list',
        ],
        'methods' => [
            'list' => [
                'category' => 'list',
                'type' => 'table',
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
    'partners' => [
        'category' => 'page',
        'type' => 'page',
        'subtype' => 'base',
        'properties' => [
            'baseMethod' => 'list',
        ],
        'methods' => [
            'list' => [
                'category' => 'list',
                'type' => 'table',
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
    'partnerAuth' => [
        'category' => 'page',
        'type' => 'page',
        'subtype' => 'base',
        'properties' => [
            'baseMethod' => 'list',
        ],
        'methods' => [
            'list' => [
                'category' => 'list',
                'type' => 'table',
                'subtype' => 'datatable',
                'buttons' => [
                    'add' => false,
                    'excel' => false,
                    'resetDB' => [
                        'text' => 'DB 초기화',
                        'classes' => ['btn','btn-warning','waves-effect','waves-light','my-5'],
                        'action' => 'resetMenuAuthDB',
                    ],
                ],
                'actions' => [
                    'view' => false,
                    'edit' => false,
                    'delete' => false,
                ],
                'properties' => [
                    'formExist' => false,
                ],
            ],
        ],
    ],
    'categories' => [
        'category' => 'page',
        'type' => 'page',
        'subtype' => 'base',
        'properties' => [
            'baseMethod' => 'list',
        ],
        'methods' => [
            'list' => [
                'category' => 'list',
                'type' => 'table',
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
    'companies' => [
        'category' => 'page',
        'type' => 'page',
        'subtype' => 'base',
        'properties' => [
            'baseMethod' => 'list',
        ],
        'methods' => [
            'list' => [
                'category' => 'list',
                'type' => 'table',
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
                ]
            ]
        ]
    ],
    'projects' => [
        'category' => 'page',
        'type' => 'page',
        'subtype' => 'base',
        'properties' => [
            'baseMethod' => 'list',
            'allows' => ['list','add','edit'],
        ],
        'methods' => [
            'list' => [
                'category' => 'list',
                'type' => 'table',
                'subtype' => 'datatable',
                'buttons' => [
                    'excel' => false,
                ],
                'actions' => [
                    'view' => false,
                    'excel' => false,
                ],
                'properties' => [
                    'formExist' => false,
                ],
            ],
            'add' => [
                'category' => 'form',
                'type' => 'base',
                'subtype' => 'grid',
                'mode' => 'create',
            ],
            'edit' => [
                'category' => 'form',
                'type' => 'base',
                'subtype' => 'grid',
                'mode' => 'update',
            ],
        ],
    ],
    'myinfo' => [
        'category' => 'page',
        'type' => 'page',
        'subtype' => 'base',
        'properties' => [
            'baseMethod' => 'edit',
        ],
        'methods' => [
            'edit' => [
                'category' => 'form',
                'type' => 'base',
                'subtype' => 'base',
                'resourceConfig' => 'myinfo',
                'mode' => 'update',
            ],
        ],
    ],
    'visitors' => [
        'category' => 'page',
        'type' => 'page',
        'subtype' => 'base',
        'properties' => [
            'noIndex' => true,
            'noIdentifier' => true,
        ],
        'methods' => [
            'list' => [
                'category' => 'list',
                'type' => 'table',
                'subtype' => 'datatable',
                'buttons' => [
                    'excel' => false,
                    'add' => false,
                ],
                'actions' => [
                    'edit' => false,
                    'view' => false,
                    'excel' => false,
                    'delete' => false,
                ],
                'properties' => [
                    'formExist' => true,
                ],
            ],
        ]
    ],
    'logs' => [
        'category' => 'page',
        'type' => 'page',
        'subtype' => 'base',
        'properties' => [
            'noIndex' => true,
            'noIdentifier' => true,
        ],
        'methods' => [
            'userAccess' => [
                'category' => 'list',
                'type' => 'log',
                'subtype' => 'access',
                'buttons' => [
                    'excel' => false,
                    'add' => false,
                ],
                'actions' => [
                    'edit' => false,
                    'view' => false,
                    'excel' => false,
                    'delete' => false,
                ],
            ],
            'errors' => [
                'category' => 'list',
                'type' => 'log',
                'subtype' => 'error',
            ]
        ]
    ],
    'system' => [
        'category' => 'page',
        'type' => 'page',
        'subtype' => 'base',
        'properties' => [
            'noIndex' => true,
            'noIdentifier' => true,
            'baseMethod' => 'sysCfg',
        ],
        'methods' => [
            'sysCfg' => [
                'category' => 'form',
                'type' => 'base',
                'subtype' => 'side',
                'resourceConfig' => 'syscfg',
                'mode' => 'update',
            ],
            'sysCode' => [
                'category' => 'list',
                'type' => 'table',
                'subtype' => 'datatable',
                'resourceConfig' => 'syscode',
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
                'category' => 'form',
                'type' => 'menu',
                'subtype' => 'menu_list',
                'resourceConfig' => 'menu_list',
            ],
            'menuAuth' => [
                'category' => 'list',
                'type' => 'table',
                'subtype' => 'datatable',
                'actions' => [
                    'view' => false,
                    'edit' => false,
                    'delete' => false,
                ],
                'buttons' => [
                    'add' => false,
                    'excel' => false,
                    'resetDB' => [
                        'text' => 'DB 초기화',
                        'classes' => ['btn','btn-warning','waves-effect','waves-light'],
                        'action' => 'resetMenuAuthDB',
                    ],
                ],
                'properties' => [
                    'filterConfig' => 'menu_auth',
                    'isRowNumber' => false,
                ],
                'resourceConfig' => 'menu_auth',
            ],
        ],
    ],
];
