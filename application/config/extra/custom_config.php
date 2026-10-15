<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$config['custom_config_loaded'] = true;

/*
|--------------------------------------------------------------------------
| LIFETIME CONFIG
|--------------------------------------------------------------------------
*/
$config['life_cycle'] = 'post_controller_constructor';
$config['loaded_views'] = [];
$config['error_occurs'] = false;

/*
|--------------------------------------------------------------------------
| PHPTOJS CONFIG
|--------------------------------------------------------------------------
*/
$config['phptojs']['namespace'] = "common";

/*
|--------------------------------------------------------------------------
| SMTP CONFIG
|--------------------------------------------------------------------------
*/
$config['smtp'] = array();
$config['smtp']['protocol'] = "smtp";
$config['smtp']['smtp_host'] = "smtp.naver.com";
$config['smtp']['smtp_port'] = "587";
$config['smtp']['smtp_user'] = "";
$config['smtp']['smtp_pass'] = "";
$config['smtp']['smtp_encryption'] = "tls";

/*
|--------------------------------------------------------------------------
| KAKAO LOGIN
|--------------------------------------------------------------------------
*/
$config['kakao_login'] = array();
$config['kakao_login']['callback_url'] = 'kakao/callback';
$config['kakao_login']['cliend_id'] = '';
$config['kakao_login']['secret'] = '';

/*
|--------------------------------------------------------------------------
| FILE EDITOR (module/file)
|--------------------------------------------------------------------------
| 편집기로 열람·수정할 수 있는 최상위 경로 (FCPATH 기준, 끝에 /)
*/
$config['file_editor_roots'] = ['public/'];

require_once __DIR__.'/custom_options.php';
