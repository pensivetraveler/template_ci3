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

// 게시글 관리: 게시판으로 거르기 + 제목 검색
$config['filter_articles_config'] = [
    'filters' => [
        [
            'field' => 'board_id',
            'label' => 'lang:board.board_id',
            'type' => 'select',
            'subtype' => 'select2',
            'attributes' => [
                'placeholder' => 'filter.Select The Board',
            ],
            'option_attributes' => [
                'option_type' => 'model',
                'option_data' => [
                    'model' => 'Model_Board',
                    'method' => 'getList',
                    'params' => [
                        'orderBy' => ['board_id' => 'ASC'],
                    ],
                ],
                'render' => [
                    'id' => 'board_id',
                    'text' => 'board_name',
                ],
            ],
            'filter_attributes' => [
                'type' => 'where',
            ],
        ],
        [
            'field' => 'subject',
            'label' => 'lang:article.subject',
            'type' => 'text',
            'attributes' => [
                'placeholder' => 'filter.Enter The Search Word',
            ],
            'filter_attributes' => [
                'type' => 'like',
            ],
        ],
    ],
];

$config['filter_partner_auth_config'] = [
    'filters' => [
        [
            'field' => 'partner_id',
            'label' => 'lang:partner.partner_id',
            'type' => 'select',
            'subtype' => 'select2',
            'default' => '',
            'attributes' => [
                'placeholder' => 'filter.Select The User Kind',
            ],
            'option_attributes' => [
                'option_type' => 'model',
                'option_data' => [
                    'model' => 'Model_Partner',
                    'method' => 'getList',
                    'params' => [
                        'where' => [
                            'user.del_yn' => 'N',
                            'user.withdraw_yn' => 'N',
                        ],
                        'orderBy' => [
                            'partner_id' => 'asc',
                        ]
                    ],
                ],
                'render' => [
                    'id' => 'partner_id',
                    'text' => 'user.name',
                ],
            ],
            'form_attributes' => [
                'set_default_first' => true,
                'change_after' => [
                    'callback' => 'onChangeTriggerAPIParams',
                    'params' => [
                        'target' => 'partner_id'
                    ]
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
    'actions' => [
//        'reset' => false,
    ]
];
