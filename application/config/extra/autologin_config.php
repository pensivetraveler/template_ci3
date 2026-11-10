<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$config['autologin_config_loaded'] = true;

$config['autologin_cookie_name'] = 'autologin';
$config['autologin_table'] = 'user_autologin';
$config['autologin_columns'] = [
	'id' => 'aul_id',
	'key' => 'aul_key',
	'ip' => 'aul_ip',
	'date' => 'aul_datetime',
];
$config['autologin_cookie_lifetime'] = 86400*30;
// 사용자당 자동 로그인 기기 수. 넘으면 가장 오래된 기기의 자동 로그인 행을 지운다 (api/Auth::login_post)
$config['autologin_max_per_user'] = 5;
$config['user_table'] = 'user';
$config['user_limit_conditions'] = [
	'del_yn' => 'Y',
	'withdraw_yn' => 'Y',
];
