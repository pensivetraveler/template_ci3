<?php
$config['builder_method_config_loaded'] = true;

$config['method_base_config'] = [
    'category' => 'base',
    'type' => 'base',
    'subtype' => 'base',
    'mode' => 'read',
    'resourceConfig' => '',
    'layout' => [
        'default' => 'base',
        'allowed' => ['base', 'page', 'modal', 'offcanvas', 'fragment'],
    ],
    'actions' => [],
    'buttons' => [],
    'properties' => [
        'noIdentifier' => false,
        'identifier' => [],
    ],
    'widgets' => [],
];

$config['method_list_base_config'] = [
    'category' => 'list',
    'type' => 'table',
    'subtype' => 'base',
    'mode' => 'read',
    'resourceConfig' => '',
    'buttons' => [
        'add' => true,
        // buttons.excel = csv/excel import screen button
        'excel' => true,
    ],
    'actions' => [
        'duplicate' => false,
        'edit' => true,
        'view' => false,
        'delete' => true,
    ],
    'exports' => [
        'print' => false,
        'csv' => false,
        'excel' => false,
        'pdf' => false,
        'copy' => false,
    ],
    'properties' => [
        // false 면 페이징 없이 전체를 한 번에 조회 (짧은 목록)
        'paging' => true,
        'noIdentifier' => false,
        'identifier' => [],
        'plugin' => '',
        'isCheckbox' => false,
        'formExist' => false,
        'formConfig' => '',
        'formType' => 'side',
        'formSubType' => 'base',
        'formStyle' => 'base',
        'filterConfig' => '',
        'isRowNumber' => true,
    ]
];

$config['method_list_table_datatable_config'] = [
    'subtype' => 'datatable',
    'properties' => [
        'plugin' => 'datatable',
    ]
];

$config['method_list_log_config'] = [
    'category' => 'list',
    'type' => 'log',
    'subtype' => 'base',
    'mode' => 'read',
    // fill_config_properties()는 빈 배열을 기본값으로 다시 채우므로 끌 항목은 false로 명시
    'actions' => [
        'duplicate' => false,
        'edit' => false,
        'view' => false,
        'delete' => false,
    ],
    'buttons' => [
        'add' => false,
        'excel' => false,
    ],
    'properties' => [
        'plugin' => '',
        'isCheckbox' => false,
        'isRowNumber' => false,
        'formExist' => false,
    ],
];

$config['method_form_base_config'] = array_replace_recursive($config['method_base_config'], [
    'category' => 'form',
    'type' => 'base',
    'subtype' => 'base',
    'mode' => '',
    'resourceConfig' => '',
    'actions' => [
        'list' => true,
        'delete' => true,
    ],
    'buttons' => [],
]);

$config['method_form_base_grid_config'] = array_replace_recursive($config['method_form_base_config'], [
    'category' => 'form',
    'type' => 'base',
    'subtype' => 'grid',
]);

$config['method_form_article_config'] = [
    'category' => 'form',
    'type' => 'article',
    'subtype' => 'base',
    'resourceConfig' => '',
    'actions' => [
        'list' => true,
        'delete' => true,
    ],
    'buttons' => [],
    'properties' => [
        'editor' => [
            'type' => 'quill',
            'imageUpload' => true,
        ],
        'thumbnail' => true,
        'attachments' => true,
        'seo' => true,
        'preview' => true,
    ],
];

$config['method_form_auth_config'] = array_replace_recursive($config['method_form_base_config'], [
    'category' => 'form',
    'type' => 'auth',
    'mode' => 'authenticate',
    // array_replace_recursive·fill_config_properties 모두 빈 배열로는 지울 수 없으므로 false로 명시
    'actions' => [
        'list' => false,
        'delete' => false,
    ],
]);

$config['method_form_auth_login_config'] = array_replace_recursive($config['method_form_auth_config'], [
    'subtype' => 'login',
    'resourceConfig' => 'login',
]);

$config['method_form_auth_find_id_config'] = array_replace_recursive($config['method_form_auth_config'], [
    'subtype' => 'find_id',
    'resourceConfig' => 'find_id',
]);

$config['method_form_auth_find_password_config'] = array_replace_recursive($config['method_form_auth_config'], [
    'subtype' => 'find_password',
    'resourceConfig' => 'find_password',
]);

$config['method_view_base_config'] = [
    'category' => 'view',
    'type' => 'base',
    'subtype' => 'base',
    'mode' => 'read',
    'resourceConfig' => '',
    'actions' => [
        'list' => true,
        'edit' => true,
        'delete' => true,
    ],
    'properties' => [
        'formConfig' => '',
        'formType' => '',
        'isComments' => false,
    ],
];

$config['method_excel_base_config'] = [
    'category' => 'excel',
    'type' => 'base',
    'subtype' => 'base',
    'mode' => 'import',
    'resourceConfig' => '',
    'actions' => [
        'list' => true,
    ],
    'properties' => [
        'templateDownload' => true,
        'preview' => true,
        'validateBeforeImport' => true,
    ],
];
