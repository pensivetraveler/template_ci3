<?php
$config['buider_form_config_loaded'] = true;

$config['form_side_prefix'] = 'form_side-';
$config['form_page_prefix'] = 'form_page-';

$config['builder_form_base'] = [
    'field' => '',
    'label' => '',
    'form' => true,
    'rules' => '',
    'errors' => [],
    'category' => 'base',
    'type' => 'text',
    'subtype' => 'base',
    'default' => '',
    'icon' => null,
    'form_text' => '',
    'attributes' => [],
    'form_attributes' => [],
    'option_attributes' => [],
    'group' => 'base',
    'group_attributes' => [],
    'list' => false,
    'list_attributes' => [],
];

$config['builder_form_base_form_attributes'] = [
    'form_sync' => true,
    'reset_value' => true,
    'detect_changed' => true,
    'with_btn' => false,
    'with_list' => false,
    'list_sorter' => false,
    'list_onclick' => 'download',
    'list_delete' => false,
];

$config['builder_form_base_option_attributes'] = [
    'option_type' => 'field',
    'option_data' => [],
    'render' => [],
    'option_stack' => 'vertical',
];

$config['builder_form_base_group_attributes'] = [
    'label' => '',
    'form_text' => '',
    'type' => 'base',
    'key' => '',
    'envelope_name' => false,
    'group_repeater' => false,
    'repeater_type' => 'base',
    'repeater_id' => '',
    'repeater_count' => 1,
];

$config['builder_form_filter_base'] = array_replace_recursive($config['builder_form_base'], [
    'icon' => null,
    'filter_attributes' => [
        'type' => 'where',
    ],
]);

/***
 * sample form configs
 */
$config['form_sample_config'] = [
    [
        'field' => 'field',
        'label' => 'lang:field',
        'form' => true,
        'rules' => 'trim|required',
        'errors' => [
            'required' => 'Enter the field'
        ],
        'category' => 'base',
        'type' => 'text',
        'subtype' => 'base',
        'default' => 'sample',
        'icon' => 'ri-user-line',
        'form_text' => '영문, 숫자를 포함한 4글자 이상으로 입력해주세요.',
        'attributes' => [
            'autocapitalize' => 'none',
            'autocomplete' => 'off',
            'placeholder' => 'Enter The User ID',
        ],
        'form_attributes' => [
            'editable' => true,
            'view_mod' => '',
            'with_btn' => true,
            'btn_type' => 'dup_check',
            'btn_params' => '{"key":"id", "title":"아이디"}',
            'text_type' => 'eng|num',
        ],
        'option_attributes' => [
            'option_type' => 'db',
            'option_data' => [
                'table' => 'program',
                'params' => [],
            ],
            'render' => [
                'id' => 'program_id',
                'text' => 'program_name',
            ],
        ],
        'group_key' => '',
        'group_attributes' => [
            'label' => 'lang:user.password',
            'form_text' => '',
            'type' => 'new_password',
        ],
        'list' => true,
        'list_attributes' => [
            'format' => 'img',
            'icon' => 'ri-file-fill',
            'render' => [
                'callback' => 'articleListRender',
                'params' => [
                    'article_cd' => 'ARC004',
                ]
            ]
        ]
    ],
];

$config['form_first_registration_config'] = [
    [
        'field' => 'user_id',
        'label' => 'lang:user.user_id',
        'form' => true,
        'type' => 'hidden',
        'subtype' => 'identifier',
    ],
    [
        'field' => 'id',
        'label' => 'lang:user.id',
        'form' => true,
        'rules' => 'trim|required|min_length[4]',
        'category' => 'base',
        'type' => 'text',
        'icon' => 'ri-user-line',
        'form_text' => 'Please enter at least 4 characters',
        'attributes' => [
            'autocapitalize' => 'none',
            'autocomplete' => 'off',
            'placeholder' => 'Enter The User ID',
        ],
    ],
    [
        'field' => 'password',
        'label' => 'lang:user.password',
        'rules' => 'trim|required|min_length[4]|max_length[15]',
        'form' => true,
        'errors' => [],
        'category' => 'base',
        'type' => 'text',
        'icon' => 'svg:ri-lock-password-line',
        'form_text' => 'Please enter 4 to 15 characters, including letters and numbers',
        'attributes' => [
            'autocomplete' => 'new-password',
            'placeholder' => 'Password Dots',
        ],
        'form_attributes' => [
            'detect_changed' => true,
        ],
    ],
    [
        'field' => 'name',
        'label' => 'lang:user.name',
        'rules' => 'trim|required',
        'form' => true,
    ],
    [
        'field' => 'email',
        'label' => 'lang:user.email',
        'rules' => 'trim|required',
        'form' => true,
        'category' => 'base',
        'type' => 'text',
        'icon' => 'ri-font-family',
        'attributes' => [
            'placeholder' => 'Enter The User Email',
        ],
        'list' => true,
    ],
    [
        'field' => 'tel',
        'label' => 'lang:user.tel',
        'form' => true,
        'rules' => 'trim|required',
        'errors' => [],
        'category' => 'base',
        'type' => 'tel',
        'subtype' => 'cleave-hp',
        'icon' => null,
        'form_text' => '',
        'attributes' => [],
        'default' => '',
        'list' => true,
        'list_attributes' => [],
    ],
];

$config['form_syscfg_config'] = [
    [
        'field' => 'cmb_cfg',
        'label' => 'lang:system.cmb_cfg',
        'rules' => 'trim|required_mod[edit]|concat[big_cfg,sml_cfg|&#95;]',
        'subtype' => 'identifier',
        'attributes' => [
            'readonly' => 'readonly',
        ],
    ],
    [
        'field' => 'big_cfg',
        'label' => 'lang:system.big_cfg',
        'rules' => 'trim|required',
        'form_attributes' => [
            'editable' => false,
        ],
    ],
    [
        'field' => 'sml_cfg',
        'label' => 'lang:system.sml_cfg',
        'rules' => 'trim|required',
        'form_attributes' => [
            'editable' => false,
        ],
    ],
    [
        'field' => 'cfg_name',
        'label' => 'lang:system.cfg_name',
        'rules' => 'trim|required',
    ],
    [
        'field' => 'cfg_val',
        'label' => 'lang:system.cfg_val',
        'rules' => 'trim',
    ],
    [
        'field' => 'cfg_desc',
        'label' => 'lang:system.cfg_desc',
        'rules' => 'trim',
    ],
    [
        'field' => 'cfg_type',
        'label' => 'lang:system.cfg_type',
        'rules' => 'trim|required',
        'type' => 'select',
        'subtype' => 'selectpicker',
        'option_attributes' => [
            'option_type' => 'field',
        ],
    ],
];

$config['form_big_code_config'] = [
    [
        'field' => 'cmb_cd',
        'label' => 'lang:system.cmb_cd',
        'rules' => 'trim|required_mod[edit]|concat[big_cd,sml_cd]',
        'type' => 'text',
        'subtype' => 'identifier',
        'attributes' => [
            'readonly' => 'readonly',
        ],
    ],
    [
        'field' => 'sml_cd',
        'label' => 'lang:system.sml_cd',
        'rules' => 'trim|required',
        'type' => 'hidden',
        'default' => '000',
    ],
    [
        'field' => 'cd_val',
        'label' => 'lang:system.cd_val',
        'rules' => 'trim|required',
        'type' => 'hidden',
        'default' => '',
    ],
    [
        'field' => 'cd_desc',
        'label' => 'lang:system.cd_desc',
        'rules' => 'trim|required',
        'type' => 'hidden',
        'default' => '',
    ],
    [
        'field' => 'cd_nick',
        'label' => 'lang:system.cd_nick',
        'rules' => 'trim|required',
        'type' => 'hidden',
        'default' => '',
    ],
    [
        'field' => 'cd_srt',
        'label' => 'lang:system.cd_srt',
        'rules' => 'trim|required',
        'type' => 'hidden',
        'default' => '0',
    ],
    [
        'field' => 'use_yn',
        'label' => 'lang:system.use_yn',
        'rules' => 'trim|required',
        'type' => 'hidden',
        'default' => 'N',
    ],
    [
        'field' => 'big_cd',
        'label' => 'lang:system.big_cd',
        'rules' => 'trim|required|min_length[3]|max_length[3]|alpha_uppercase',
        'subtype' => 'identifier',
        'form_attributes' => [
            'editable' => false,
        ],
    ],
    [
        'field' => 'cd_name',
        'label' => 'lang:system.cd_name',
        'rules' => 'trim|required',
    ],
];

$config['form_syscode_config'] = [
    [
        'field' => 'cmb_cd',
        'label' => 'lang:system.cmb_cd',
        'rules' => 'trim|required_mod[edit]|concat[big_cd,sml_cd]',
        'subtype' => 'identifier',
        'attributes' => [
            'readonly' => 'readonly',
        ],
    ],
    [
        'field' => 'big_cd',
        'label' => 'lang:system.big_cd',
        'rules' => 'trim|required',
        'type' => 'select',
        'subtype' => 'selectpicker',
        'option_attributes' => [
            'option_type' => 'model',
            'option_data' => [
                'model' => 'Model_Sys_Code',
                'method' => 'getBigCodeList',
                'params' => [],
            ],
            'render' => [
                'id' => 'big_cd',
                'text' => 'cd_name',
                'add' => 'big_cd'
            ],
        ],
        'form_attributes' => [
            'editable' => false,
        ],
    ],
    [
        'field' => 'sml_cd',
        'label' => 'lang:system.sml_cd',
        'rules' => 'trim|required|min_length[3]|max_length[3]|numeric',
        'form_attributes' => [
            'editable' => false,
        ],
    ],
    [
        'field' => 'cd_name',
        'label' => 'lang:system.cd_name',
        'rules' => 'trim|required',
    ],
    [
        'field' => 'cd_val',
        'label' => 'lang:system.cd_val',
        'rules' => 'trim',
        'form_attributes' => [
            'detect_changed' => false,
        ],
    ],
    [
        'field' => 'cd_desc',
        'label' => 'lang:system.cd_desc',
        'rules' => 'trim',
        'form_attributes' => [
            'detect_changed' => false,
        ],
    ],
    [
        'field' => 'cd_nick',
        'label' => 'lang:system.cd_nick',
        'rules' => 'trim',
        'form_attributes' => [
            'detect_changed' => false,
        ],
    ],
    [
        'field' => 'cd_srt',
        'label' => 'lang:system.cd_srt',
        'rules' => 'trim|required|min[1]',
        'type' => 'number',
        'form_attributes' => [
            'detect_changed' => false,
        ],
    ],
    [
        'field' => 'use_yn',
        'label' => 'lang:common.use_yn',
        'rules' => 'trim|required',
        'type' => 'select',
        'subtype' => 'selectpicker',
        'option_attributes' => [
            'option_type' => 'yn',
        ],
        'form_attributes' => [
            'detect_changed' => false,
        ],
    ],
];

$config['form_menu_list_config'] = [
    [
        'field' => 'menu_id',
        'label' => 'lang:menu.menu_id',
        'rules' => 'trim|required_mod[edit]',
        'type' => 'hidden',
        'subtype' => 'identifier',
    ],
    [
        'field' => 'depth',
        'label' => 'lang:menu.depth',
        'rules' => 'trim',
        'type' => 'hidden',
        'default' => '1',
    ],
    [
        'field' => 'srt',
        'label' => 'lang:menu.srt',
        'rules' => 'trim',
        'type' => 'hidden',
    ],
    [
        'field' => 'is_sub_menu',
        'label' => 'lang:menu.is_sub_menu',
        'rules' => 'trim',
        'type' => 'hidden',
        'default' => '0',
    ],
    [
        'field' => 'code',
        'label' => 'lang:menu.code',
        'rules' => 'trim|required',
        'type' => 'custom',
        'subtype' => 'unique',
        'form_attributes' => [
            'detect_changed' => true,
            'with_btn' => true,
            'btn_type' => 'dup_check',
            'btn_params' => '{"key":"code", "title":"메뉴코드"}',
            'text_type' => 'eng|num',
        ],
    ],
    [
        'field' => 'parent_id',
        'label' => 'lang:menu.parent_id',
        'rules' => 'trim',
        'type'  => 'select',
        'subtype'  => 'select2',
        'option_attributes' => [
            'option_type' => 'model',
            'option_data' => [
                'model' => 'Model_Menu',
                'method' => 'getParentList',
                'params' => [],
            ],
            'render' => [
                'id' => 'menu_id',
                'text' => 'title',
            ],
        ],
        'form_attributes' => [
            'detect_changed' => false,
        ],
    ],
    [
        'field' => 'title',
        'label' => 'lang:menu.title',
        'rules' => 'trim|required',
    ],
    [
        'field' => 'icon',
        'label' => 'lang:menu.icon',
        'rules' => 'trim',
    ],
    [
        'field' => 'class',
        'label' => 'lang:menu.class',
        'rules' => 'trim',
        'type'  => 'select',
        'subtype'  => 'select2',
        'option_attributes' => [
            'option_type' => 'method',
            'option_data' => [
                'method' => 'getClassList',
            ],
        ],
        'form_attributes' => [
            'detect_changed' => false,
            'change_after' => [
                'params' => [
                    'target' => '[name="method"]',
                    'add_uri' => 'options',
                ],
                'callback' => 'setDynamicSelect2Options',
            ]
        ],
    ],
    [
        'field' => 'method',
        'label' => 'lang:menu.method',
        'rules' => 'trim',
        'type'  => 'select',
        'subtype'  => 'select2',
        'form_attributes' => [
            'detect_changed' => false,
        ],
    ],
    [
        'field' => 'href',
        'label' => 'lang:menu.href',
        'rules' => 'trim',
        'category' => 'base',
        'form_attributes' => [
            'detect_changed' => false,
        ],
        'group' => 'attr',
        'group_attributes' => [
            'envelope_name' => true,
            'label' => 'lang:menu.attr',
            'form_text' => '',
            'type' => 'base',
            'key' => 'href',
        ],
    ],
    [
        'field' => 'target',
        'label' => 'lang:menu.target',
        'rules' => 'trim',
        'type' => 'select',
        'subtype' => 'selectpicker',
        'form_attributes' => [
            'detect_changed' => false,
        ],
        'option_attributes' => [
            'option_type' => 'target',
        ],
        'group' => 'attr',
        'group_attributes' => [
            'key' => 'target',
        ],
    ],
    [
        'field' => 'className',
        'label' => 'lang:menu.className',
        'rules' => 'trim',
        'type' => 'custom',
        'subtype' => 'tag-base',
        'form_attributes' => [
            'detect_changed' => false,
        ],
        'group' => 'attr',
        'group_attributes' => [
            'key' => 'className',
        ],
    ],
    [
        'field' => 'is_login',
        'label' => 'lang:menu.is_login',
        'rules' => 'trim|required',
        'type' => 'select',
        'subtype' => 'selectpicker',
        'option_attributes' => [
            'option_type' => 'bool',
        ],
    ],
    [
        'field' => 'is_auth',
        'label' => 'lang:menu.is_auth',
        'rules' => 'trim|required',
        'type' => 'select',
        'subtype' => 'selectpicker',
        'option_attributes' => [
            'option_type' => 'bool',
        ],
    ],
    [
        'field' => 'is_super',
        'label' => 'lang:menu.is_super',
        'rules' => 'trim|required',
        'type' => 'select',
        'subtype' => 'selectpicker',
        'option_attributes' => [
            'option_type' => 'bool',
        ],
    ],
    [
        'field' => 'is_use',
        'label' => 'lang:menu.is_use',
        'rules' => 'trim|required',
        'type' => 'select',
        'subtype' => 'selectpicker',
        'option_attributes' => [
            'option_type' => 'bool',
        ],
    ],
];
