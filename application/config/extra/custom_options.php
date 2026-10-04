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
    'access_type' => [
        'auth' => '로그인/로그아웃',
        'read' => '조회',
        'create' => '등록',
        'update' => '수정',
        'delete' => '삭제',
    ],
    'log_threshold' => [
        'ERROR' => 'Error',
        'DEBUG' => 'Debug',
        'INFO' => 'Information',
    ],
    'date_range' => [
        'none' => 'None',
        'today' => 'Today',
        'yesterday' => 'Yesterday',
        'last7days' => 'Last 7 Days',
        'last30days' => 'Last 30 Days',
        'currentMonth' => 'Current Month',
        'lastMonth' => 'Last Month',
    ],
    'date_range_log' => [
        'none' => 'None',
        'last7days' => 'Last 7 Days',
        'last30days' => 'Last 30 Days',
        'currentMonth' => 'Current Month',
        'lastMonth' => 'Last Month',
        'thisYear' => 'This Year',
        'lastYear' => 'Last Year',
    ],
];

$config['options']['search_category'] = [
    'administrators' => [
        'id' => '아이디',
        'name' => '이름',
        'email' => '이메일',
    ],
];

$config['options']['menu_auth'] = [
    'title' => '메뉴명',
    'class' => '클래스',
    'method' => '메소드',
];

$config['options']['font_family'] = [
    'YoonGothicPro' => '윤고딕',
    'Pretendard' => 'Pretendard',
    'NotoSansKr' => 'NotoSansKr',
];

$config['options']['search_category'] = [
    'users' => [
        'user.id' => '아이디',
        'user.name' => '이름',
        'user.email' => '이메일',
    ],
];
