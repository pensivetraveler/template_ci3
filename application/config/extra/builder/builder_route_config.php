<?php
$config['builder_route_config_loaded'] = true;

$config['base_includes_config'] = [
    'head' => true,
    'header' => true,
    'modalPrepend' => true,
    'modalAppend' => true,
    'footer' => true,
    'tail' => true,
];

$config['route_base_config'] = [
    'category' => 'page',
    'subtype' => 'base',
    'properties' => [
        'baseMethod' => '',
        'allows' => [],
        'noIndex' => false,
        'formExist' => false,
        'includes' => $config['base_includes_config'],
        'allowNoLogin' => false,
        'identifier' => [],
        'noIdentifier' => false,
    ],
    'methods' => [],
];

$config['route_page_base_config'] = [
    'category' => 'page',
    'type' => 'page',
];

$config['route_page_auth_config'] = [
    'category' => 'page',
    'type' => 'page',
    'subtype' => 'auth',
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
];

$config['route_modal_base_config'] = [
    'category' => 'page',
    'type' => 'modal',
    'subtype' => 'base',
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
];
