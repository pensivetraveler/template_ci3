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
    'actions' => [],
    'buttons' => [],
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
    'actions' => []
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
