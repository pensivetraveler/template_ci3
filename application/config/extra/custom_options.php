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
];

$config['options']['search_category'] = [
    'administrators' => [
        'id' => '아이디',
        'name' => '이름',
        'email' => '이메일',
    ],
];