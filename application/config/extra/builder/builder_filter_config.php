<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$config['builder_filter_config_loaded'] = true;

$config['builder_filter_config'] = [
    'filters' => [],
    'help_block' => [],
    'buttons' => [],
    'actions' => [
        'submit' => true,
        'reset' => true,
    ],
];

$config['filter_logs_errors_config'] = [
    'filters' => [
        [
            'field' => 'log_threshold',
            'label' => 'lang:filter.log_threshold',
            'type' => 'select',
            'subtype' => 'selectpicker',
            'attributes' => [
                'placeholder' => 'filter.Select The Log Threshold',
                'multiple' => true,
            ],
            'option_attributes' => [
                'option_type' => 'field',
            ],
            'form_attributes' => [],
            'filter_attributes' => [
                'type' => 'where',
            ],
        ],
        [
            'field' => 'on_date',
            'label' => 'lang:filter.on_date',
            'type' => 'date',
            'subtype' => 'flatpickr',
            'default' => date('Y-m-d'),
            'attributes' => [
                'placeholder' => 'filter.Enter The Date',
            ],
            'filter_attributes' => [
                'type' => 'date',
            ],
        ],
        [
            'field' => 'search_word',
            'label' => 'lang:filter.search_word',
            'type' => 'text',
            'default' => '',
            'attributes' => [
                'placeholder' => 'filter.Enter The Search Word',
            ],
            'filter_attributes' => [
                'type' => 'like',
                'subtype' => 'value',
            ]
        ]
    ],
];

$config['filter_logs_user_access_config'] = [
    'filters' => [
        [
            'field' => 'user.user_id',
            'label' => 'lang:user.user_id',
            'type' => 'select',
            'subtype' => 'select2',
            'attributes' => [
                'placeholder' => 'filter.Select The User',
                'multiple' => true,
            ],
            'option_attributes' => [
                'option_type' => 'model',
                'option_data' => [
                    'model' => 'Model_User',
                    'method' => 'getList',
                ],
                'render' => [
                    'id' => 'user_id',
                    'text' => 'user.name',
                ],
            ],
            'form_attributes' => [],
            'filter_attributes' => [
                'type' => 'where',
            ],
        ],
        [
            'field' => 'type',
            'label' => 'lang:filter.access_type',
            'type' => 'select',
            'subtype' => 'selectpicker',
            'attributes' => [
                'placeholder' => 'filter.Select The Access Type',
                'multiple' => true,
            ],
            'option_attributes' => [
                'option_type' => 'access_type',
            ],
            'form_attributes' => [],
            'filter_attributes' => [
                'type' => 'where',
            ],
        ],
        [
            'field' => 'on_date',
            'label' => 'lang:filter.on_date',
            'type' => 'date',
            'subtype' => 'flatpickr',
            'default' => date('Y-m-d'),
            'attributes' => [
                'placeholder' => 'filter.Enter The Date',
            ],
            'filter_attributes' => [
                'type' => 'date',
            ],
        ],
        [
            'field' => 'start_date',
            'label' => 'lang:filter.start_date',
            'type' => 'date',
            'subtype' => 'flatpickr',
            'attributes' => [
                'placeholder' => 'filter.Enter The Start Date',
            ],
            'filter_attributes' => [
                'type' => 'date',
            ],
        ],
        [
            'field' => 'end_date',
            'label' => 'lang:filter.end_date',
            'type' => 'date',
            'subtype' => 'flatpickr',
            'attributes' => [
                'placeholder' => 'filter.Enter The End Date',
            ],
            'filter_attributes' => [
                'type' => 'date',
            ],
        ],
    ],
];

$config['filter_menu_auth_config'] = [
    'filters' => [
        [
            'field' => 'grade_cd',
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
                'change_after' => [
                    'callback' => 'onChangeTriggerAPIParams',
                    'params' => [
                        'target' => 'grade_cd'
                    ]
                ]
            ],
            'filter_attributes' => [
                'type' => 'where',
            ],
        ],
        [
            'field' => 'is_show',
            'label' => 'lang:menu.is_show',
            'type' => 'select',
            'subtype' => 'selectpicker',
            'attributes' => [
                'placeholder' => 'filter.Display Only Set Shown',
            ],
            'option_attributes' => [
                'option_type' => 'bool',
            ],
            'filter_attributes' => [
                'type' => 'where',
            ],
        ]
    ],
    'help_block' => [
        'tag' => 'span',
        'text' => '각 권한에 따라 체크박스에 체크를 해주세요.',
        'attr' => [
            'class' => 'small d-block mt-1 text-primary',
        ],
    ],
];
