<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$config['builder_filter_config_loaded'] = true;

$config['filter_menu_auth_config'] = [
    'filters' => [
        [
            'field' => 'user_cd',
            'label' => 'lang:menu.user_cd',
            'type' => 'select',
            'subtype' => 'select2',
            'default' => 'USR001',
            'attributes' => [
                'placeholder' => 'filter.Select The User Kind',
            ],
            'option_attributes' => [
                'option_type' => 'model',
                'option_data' => [
                    'model' => 'Model_Sys_Code',
                    'method' => 'getList',
                    'params' => [
                        'where' => [
                            'big_cd' => 'USR'
                        ],
                        'whereNot' => [
                            'sml_cd' => '000'
                        ],
                    ],
                ],
                'render' => [
                    'id' => 'cmb_cd',
                    'text' => 'cd_name',
                ],
            ],
            'form_attributes' => [
                'changeAfter' => [
                    'callback' => 'afterChangeUserKind'
                ]
            ],
            'filter_attributes' => [
                'type' => 'where',
            ],
        ],
    ],
    'help_block' => [
        'tag' => 'span',
        'text' => '각 권한에 따라 체크박스에 체크를 해주세요.',
        'attr' => [
            'class' => 'small d-block mt-1 text-primary',
        ],
    ],
];