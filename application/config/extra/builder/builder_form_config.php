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
	'group' => '',
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

$config['builder_form_base_list_attributes'] = [
	'list' => true,
    'type' => 'text',
    'subtype' => 'base',
	'field' => '',
	'label' => '',
	'format' => 'text',
	'icon' => '',
	'text' => '',
	'classes' => [],
	'onclick' => [],
	'render' => [],
	'option_attributes' => [],
    'options' => [],
];

$config['builder_form_filter_base'] = array_replace_recursive($config['builder_form_base'], [
	'icon' => null,
	'filter_attributes' => [
		'type' => 'where',
	],
]);

/***
 * sample configs
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

$config['form_system_config_config'] = [
	[
		'field' => 'cmb_cfg',
		'label' => 'lang:system.cmb_cfg',
        'rules' => 'trim|required_mod[edit]',
        'subtype' => 'identifier',
        'attributes' => [
            'readonly' => 'readonly',
        ],
	],
	[
		'field' => 'big_cfg',
		'label' => 'lang:system.big_cfg',
		'rules' => 'trim|required',
	],
	[
		'field' => 'sml_cfg',
		'label' => 'lang:system.sml_cfg',
		'rules' => 'trim|required',
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
		'rules' => 'trim',
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
        'rules' => 'trim|required_mod[edit]',
        'type' => 'hidden',
        'subtype' => 'identifier',
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
		'rules' => 'trim|required',
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

$config['form_system_code_config'] = [
	[
		'field' => 'cmb_cd',
		'label' => 'lang:system.cmb_cd',
        'rules' => 'trim|required_mod[edit]',
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
                'text' => 'big_cd',
            ],
        ],
        'form_attributes' => [
            'editable' => false,
        ],
	],
	[
		'field' => 'sml_cd',
		'label' => 'lang:system.sml_cd',
		'rules' => 'trim|required',
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
