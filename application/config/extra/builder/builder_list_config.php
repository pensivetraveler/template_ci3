<?php
defined('BASEPATH') or exit('No direct script access allowed');

$config['builder_list_config_loaded'] = true;

$config['builder_list_base'] = [
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

$config['list_syscode_config'] = [
    [
        'field' => 'cmb_cd',
        'label' => 'lang:system.cmb_cd',
        'subtype' => 'identifier',
    ],
    [
        'field' => 'cd_name',
        'label' => 'lang:system.cd_name',
    ],
    [
        'field' => 'cd_val',
        'label' => 'lang:system.cd_val',
    ],
    [
        'field' => 'cd_desc',
        'label' => 'lang:system.cd_desc',
    ],
    [
        'field' => 'cd_nick',
        'label' => 'lang:system.cd_nick',
    ],
    [
        'field' => 'cd_srt',
        'label' => 'lang:system.cd_srt',
    ],
    [
        'field' => 'use_yn',
        'label' => 'lang:common.use_yn',
    ],
];

$config['list_menu_auth_config'] = [
    [
        'field' => 'menu_id',
        'label' => 'lang:system.menu_id',
        'type' => 'hidden',
        'subtype' => 'identifier',
    ],
    [
        'field' => 'user_cd',
        'label' => 'lang:system.user_cd',
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
        'field' => 'create',
        'label' => 'lang:menu.create',
        'type' => 'checkbox',
        'subtype' => 'boolean',
    ],
    [
        'field' => 'read',
        'label' => 'lang:menu.read',
        'type' => 'checkbox',
        'subtype' => 'boolean',
    ],
    [
        'field' => 'update',
        'label' => 'lang:menu.update',
        'type' => 'checkbox',
        'subtype' => 'boolean',
    ],
    [
        'field' => 'delete',
        'label' => 'lang:menu.delete',
        'type' => 'checkbox',
        'subtype' => 'boolean',
    ],
];