<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$config['regexp'] = [
    'numeric' => [
        'exp' => "^[\-+]?[0-9]*\.?[0-9]+$",
        'flags' => ''
    ],
    'alpha_numeric' => [
        'exp' => "^[a-zA-Z0-9]+$",
        'flags' => ''
    ],
    'alpha_numeric_spaces' => [
        'exp' => "^[a-zA-Z0-9 ]+$",
        'flags' => 'i'
    ],
    'alpha_dash' => [
        'exp' => "^[a-zA-Z0-9_-]+$",
        'flags' => 'i'
    ],
    'decimal' => [
        'exp' => "^[\-+]?[0-9]+\.[0-9]+$",
        'flags' => ''
    ],
    'integer' => [
        'exp' => "^[\-+]?[0-9]+$",
        'flags' => ''
    ],
    'is_natural' => [
        'exp' => "^\d+$",
        'flags' => ''
    ],
    'is_natural_no_zero' => [
        'exp' => "^[1-9]\d*$",
        'flags' => ''
    ],
    'valid_date' => [
        'exp' => "^\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])$",
        'flags' => ''
    ],
    'version_callable' => [
        'exp' => "[0-9]{1,}\.[0-9]{1,}\.[0-9]{1,}",
        'flags' => ''
    ],
];

$config['file_rules'] = [
    'required_if_empty_file' => [
        'exp' => '(required_if_empty_file)\[([^\]]+)\]',
        'flags' => '',
    ],
    'max_files' => [
        'exp' => '(max_files)\[([^\]]+)\]',
        'flags' => '',
    ]
];