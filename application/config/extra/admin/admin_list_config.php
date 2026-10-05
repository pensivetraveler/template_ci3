<?php
defined('BASEPATH') or exit('No direct script access allowed');

$config['admin_list_config_loaded'] = true;

$config['list_administrators_config'] = [
    [
        'field' => 'administrator_id',
        'type' => 'hidden',
        'subtype' => 'identifier',
    ],
    [
        'field' => 'id',
        'label' => 'lang:user.id',
    ],
    [
        'field' => 'name',
        'label' => 'lang:user.name',
    ],
    [
        'field' => 'email',
        'label' => 'lang:user.email',
    ],
    [
        'field' => 'tel',
        'label' => 'lang:user.tel',
    ],
];

$config['list_partners_config'] = [
    [
        'field' => 'partner_id',
        'type' => 'hidden',
        'subtype' => 'identifier',
    ],
    [
        'field' => 'id',
        'label' => 'lang:user.id',
    ],
    [
        'field' => 'name',
        'label' => 'lang:user.name',
    ],
    [
        'field' => 'email',
        'label' => 'lang:user.email',
    ],
    [
        'field' => 'tel',
        'label' => 'lang:user.tel',
    ],
    [
        'field' => 'company_ids',
        'label' => 'lang:partner.company_ids',
    ],
];

$config['list_partner_auth_config'] = [
    [
        'field' => 'menu_id',
        'label' => 'lang:system.menu_id',
        'type' => 'hidden',
        'subtype' => 'identifier',
    ],
    [
        'field' => 'partner_id',
        'label' => 'lang:partner.partner_id',
        'type' => 'hidden',
        'subtype' => 'identifier',
    ],
    [
        'field' => 'title',
        'label' => 'lang:menu.title',
    ],
    [
        'field' => 'code',
        'label' => 'lang:menu.code',
    ],
    [
        'field' => 'is_show',
        'label' => 'lang:menu.is_show',
        'type' => 'checkbox',
        'subtype' => 'boolean',
    ],
    [
        'field' => 'create',
        'label' => 'lang:menu.create',
        'type' => 'checkbox',
        'subtype' => 'boolean',
        'render' => [
            'callback' => 'renderMenuAuthCheckbox',
        ]
    ],
    [
        'field' => 'read',
        'label' => 'lang:menu.read',
        'type' => 'checkbox',
        'subtype' => 'boolean',
        'render' => [
            'callback' => 'renderMenuAuthCheckbox',
        ]
    ],
    [
        'field' => 'update',
        'label' => 'lang:menu.update',
        'type' => 'checkbox',
        'subtype' => 'boolean',
        'render' => [
            'callback' => 'renderMenuAuthCheckbox',
        ]
    ],
    [
        'field' => 'delete',
        'label' => 'lang:menu.delete',
        'type' => 'checkbox',
        'subtype' => 'boolean',
        'render' => [
            'callback' => 'renderMenuAuthCheckbox',
        ]
    ],
    [
        'field' => 'export',
        'label' => 'lang:menu.export',
        'type' => 'checkbox',
        'subtype' => 'boolean',
        'render' => [
            'callback' => 'renderMenuAuthCheckbox',
        ]
    ],
    [
        'field' => 'import',
        'label' => 'lang:menu.import',
        'type' => 'checkbox',
        'subtype' => 'boolean',
        'render' => [
            'callback' => 'renderMenuAuthCheckbox',
        ]
    ],
];

$config['list_company_config'] = [
    [
        'field' => 'company_id',
        'label' => 'lang:company.company_id',
        'type'  => 'hidden',
        'subtype'  => 'identifier',
    ],
    [
        'field' => 'comp_code',
        'label' => 'lang:company.comp_code',
    ],
    [
        'field' => 'comp_name',
        'label' => 'lang:company.comp_name',
    ],
    [
        'field' => 'comp_ceo',
        'label' => 'lang:company.comp_ceo',
    ],
    [
        'field' => 'comp_tel',
        'label' => 'lang:company.comp_tel',
    ],
    [
        'field' => 'comp_addr',
        'label' => 'lang:company.comp_addr',
    ],
];

$config['list_categories_config'] = [
    [
        'field' => 'category_id',
        'label' => 'lang:category.category_id',
        'type'  => 'hidden',
        'subtype'  => 'identifier',
    ],
    [
        'field' => 'category_name',
        'label' => 'lang:category.category_name',
    ],
    [
        'field' => 'category_nick',
        'label' => 'lang:category.category_nick',
    ],
    [
        'field' => 'category_icon',
        'label' => 'lang:category.category_icon',
        'type' => 'img',
    ],
    [
        'field' => 'category_srt',
        'label' => 'lang:category.category_srt',
    ],
    [
        'field' => 'use_yn',
        'label' => 'lang:common.use_yn',
    ],
];

$config['list_project_config'] = [
    [
        'field' => 'project_name',
        'label' => 'lang:project.project_name',
    ],
    [
        'field' => 'comp_name',
        'label' => 'lang:project.comp_name',
    ],
    [
        'field' => 'start_dt',

        'label' => 'lang:project.start_dt',
    ],
    [
        'field' => 'end_dt',
        'label' => 'lang:project.end_dt',
    ],
];

$config['list_visit_log_config'] = [
    [
        'field' => 'domain',
        'label' => 'lang:frame.domain_name',
    ],
    [
        'field' => 'frame_name',
        'label' => 'lang:frame.frame_name',
    ],
    [
        'field' => 'company_name',
        'label' => 'lang:company.company_name',
    ],
    [
        'field' => 'vi_ip',
        'label' => 'lang:visitor.vi_ip',
    ],
    [
        'field' => 'vi_datetime',
        'label' => 'lang:visitor.vi_datetime',
    ],
    [
        'field' => 'vi_referrer',
        'label' => 'lang:visitor.vi_referrer',
    ],
    [
        'field' => 'vi_browser',
        'label' => 'lang:visitor.vi_browser',
    ],
    [
        'field' => 'vi_device',
        'label' => 'lang:visitor.vi_device',
    ],
];
