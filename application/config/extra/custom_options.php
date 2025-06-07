<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| CUSTOM OPTIONS
|--------------------------------------------------------------------------
*/
$config['options'] = [
    'default' => [
        1 => 'Option 1',
        2 => 'Option 2',
    ],
    'bool' => [
        1 => 'Y',
        0 => 'N',
    ],
    'yn' => [
        'Y' => 'Y',
        'N' => 'N',
    ],
    'gender' => [
        'M' => '남',
        'F' => '여',
    ],
    'cfg_type' => [
        'text' => '텍스트',
        'password' => '비밀번호',
    ],
    'target' => [
        '_self' => 'self',
        '_blank' => 'blank',
        '_popup' => 'popup',
    ],
    'menu_auth' => [
        'title' => '메뉴명',
        'class' => '클래스',
        'method' => '메소드',
    ],
];

$config['options']['search_category'] = [
    'administrators' => [
        'id' => '아이디',
        'name' => '이름',
        'email' => '이메일',
    ],
];