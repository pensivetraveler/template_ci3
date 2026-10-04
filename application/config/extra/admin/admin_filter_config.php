<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$config['admin_filter_config_loaded'] = true;

$config['filter_administrators_config'] = [
    'filters' => [
        [
            'field' => 'search_category',
            'label' => 'lang:filter.search_category',
            'type' => 'select',
            'subtype' => 'selectpicker',
            'attributes' => [
                'placeholder' => 'filter.Select The Search Category',
            ],
            'option_attributes' => [
                'option_type' => 'field',
                'option_field' => 'search_category.users',
            ],
            'filter_attributes' => [
                'type' => 'like',
                'subtype' => 'field',
            ],
            'form_attributes' => [
                'set_default_first' => true,
            ]
        ],
        [
            'field' => 'search_word',
            'label' => 'lang:filter.search_word',
            'type' => 'text',
            'attributes' => [
                'placeholder' => 'filter.Enter The Search Word',
            ],
            'filter_attributes' => [
                'type' => 'like',
                'subtype' => 'value',
            ],
        ],
    ],
];
