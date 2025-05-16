<?php
defined('BASEPATH') or exit('No direct script access allowed');

$config['builder_list_config_loaded'] = true;

$config['builder_list_base_attributes'] = [
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

/***
 * sample configs
 */
$config['list_system_code_config'] = [
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