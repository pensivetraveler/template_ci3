<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$config['custom_upload_config_loaded'] = true;

/*
|--------------------------------------------------------------------------
| FILE CONFIG
|--------------------------------------------------------------------------
*/
// 'private' => true: 웹으로 바로 열리지 않는 application/uploads 에 두고 downloader 로만 받는다.
// S3 를 쓰면 CDN 에서 application/uploads 경로를 막아야 한다 (docs/builder/api-hooks.md)
$config['base_upload_config'] = [
    'max_size' => '5120',
    'overwrite' => false,
    'encrypt_name' => true,
];

$config['thumbnail_upload_config'] = array_merge($config['base_upload_config'], [
	'allowed_types' => 'gif|jpg|jpeg|png',
]);

$config['uploads_upload_config'] = array_merge($config['base_upload_config'], [
	'allowed_types' => 'pdf|gif|jpg|jpeg|png',
]);

$config['category_icon_upload_config'] = array_merge($config['base_upload_config'], [
    'allowed_types' => 'gif|jpg|jpeg|png',
]);
