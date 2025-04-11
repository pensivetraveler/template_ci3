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
$config['user_table'] = 'user';
$config['user_limit_conditions'] = [
	'del_yn' => 'Y',
	'withdraw_yn' => 'Y',
];
