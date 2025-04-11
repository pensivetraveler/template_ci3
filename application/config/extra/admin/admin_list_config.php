<?php
defined('BASEPATH') or exit('No direct script access allowed');

$config['admin_list_config_loaded'] = true;

$config['list_company_config'] = [
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