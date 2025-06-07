<?php
$config['builder_page_config_loaded'] = true;

$config['base_includes_config'] = [
    'head' => true,
    'header' => true,
    'modalPrepend' => true,
    'modalAppend' => true,
    'footer' => true,
    'tail' => true,
];

$config['page_base_config'] = [
    'category' => 'page',
    'type' => 'page',
    'subtype' => 'base',
    'properties' => [
        'baseMethod' => '',
        'allows' => [],
        'noIndex' => false,
        'formExist' => false,
        'listExist' => false,
        'includes' => $config['base_includes_config'],
        'allowNoLogin' => false,
        'identifier' => [],
        'noIdentifier' => false,
    ],
    'methods' => [],
];

$config['modal_base_config'] = array_replace_recursive($config['page_base_config'], [
    'category' => 'modal',
    'properties' => [
        'includes' => [
            'head' => false,
            'header' => false,
            'modalPrepend' => false,
            'modalAppend' => false,
            'footer' => false,
            'tail' => false,
        ],
    ],
]);

$config['page_method_base_config'] = [
    'type' => 'widgetGroup',
    'subtype' => 'base',
    'actions' => [],
    'buttons' => [],
    'properties' => [
        'noIdentifier' => false,
        'identifier' => [],
    ],
    'widgets' => [],
];

$config['page_list_base_config'] = array_replace_recursive($config['page_method_base_config'], [
    'type' => 'list',
    'subtype' => 'base',
    'config' => '',
    'buttons' => [
        'add' => true,
        'excel' => true,
    ],
    'actions' => [
        'edit' => true,
        'view' => false,
        'delete' => true,
    ],
    'properties' => [
        'plugin' => '',
        'isCheckbox' => false,
        'exports' => [
            'print' => false,
            'csv' => false,
            'excel' => false,
            'pdf' => false,
            'copy' => false,
        ],
        'formExist' => false,
        'formConfig' => '',
        'formType' => 'side',
        'filterConfig' => '',
    ]
]);

$config['page_list_datatable_config'] = array_replace_recursive($config['page_list_base_config'], [
    'properties' => [
        'plugin' => 'datatable',
    ]
]);

$config['page_view_base_config'] = array_replace_recursive($config['page_method_base_config'], [
    'type' => 'view',
    'subtype' => 'base',
    'config' => '',
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
]);

$config['page_form_base_config'] = array_replace_recursive($config['page_method_base_config'], [
    'type' => 'form',
    'subtype' => 'base',
    'config' => '',
    'actions' => [
        'list' => true,
        'delete' => true,
    ],
]);

$config['page_form_auth_config'] = array_replace_recursive($config['page_method_base_config'], [
    'type' => 'form',
    'subtype' => 'auth',
    'config' => '',
]);

$config['page_excel_base_config'] = array_replace_recursive($config['page_method_base_config'], [
    'type' => 'excel',
    'subtype' => 'base',
    'config' => '',
]);