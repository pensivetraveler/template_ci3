<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| CLI
|--------------------------------------------------------------------------
*/
define('CLI_YN', php_sapi_name() === 'cli');

/*
|--------------------------------------------------------------------------
| NAME
|--------------------------------------------------------------------------
*/
//define("APP_NAME", getenv('APP_NAME'));
//define("APP_NAME_KR", getenv('APP_NAME_KR'));

/*
|--------------------------------------------------------------------------
| PATH
|--------------------------------------------------------------------------
*/
define('ABS_PATH', $_SERVER['DOCUMENT_ROOT']);
define('PUBLIC_PATH', FCPATH.'public/');
define('ASSET_PATH', PUBLIC_PATH.'assets/');
define('UPLOAD_PATH', PUBLIC_PATH.'uploads/');
define('PAGE_NUMBER', 10);
define('PAGE_SIZE', 10);

/*
|--------------------------------------------------------------------------
| LINK
|--------------------------------------------------------------------------
*/
const UPLOAD_LINK = '/public/uploads/';

/*
|--------------------------------------------------------------------------
| EXTRA DB CONFIG
|--------------------------------------------------------------------------
*/
const USER_TABLE_NAME = 'user';
const USER_ID_COLUMN_NAME = 'user_id';
const USER_CD_COLUMN_NAME = 'user_cd';
const CREATED_DT_COLUMN_NAME = 'created_dt';
const CREATED_ID_COLUMN_NAME = 'created_id';
const UPDATED_DT_COLUMN_NAME = 'updated_dt';
const UPDATED_ID_COLUMN_NAME = 'updated_id';
const DEL_YN_COLUMN_NAME = 'del_yn';
const USE_YN_COLUMN_NAME = 'use_yn';
const RECENT_DT_COLUMN_NAME = 'recent_dt';

/*
|--------------------------------------------------------------------------
| DOMAIN
|--------------------------------------------------------------------------
*/
// THIS_DOMAIN은 끝에 / 없도록.
if(ENVIRONMENT === 'development'){
	define("THIS_DOMAIN","");
}else{
	define("THIS_DOMAIN","");
}

/*
|--------------------------------------------------------------------------
| PROTOCOL
|--------------------------------------------------------------------------
*/
if(php_sapi_name() === 'cli') {
	define("_HTTP", "https://");
}else{
	if((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443) {
		define("_HTTP", "https://");
	}else{
		define("_HTTP", "http://");
	}
}

/*
|--------------------------------------------------------------------------
| MY EXCEPTION
|--------------------------------------------------------------------------
*/
const API_CALL_PATH = [
	'api', 'adm'
];

const PRESET_API_NOT_EXIST = [
	'code' => 'API_NOT_EXIST',
	'msg' => '존재하지 않는 경로입니다.',
];

const PRESET_ERR_OCCUR = [
	'code' => 'INTERNAL_SERVER_ERROR',
	'msg' => '오류가 발생했습니다.',
];

/*
|--------------------------------------------------------------------------
| 외부 전송 이메일 서버 설정
|--------------------------------------------------------------------------
*/
const SMTP_HOST = ""; //smtp 주소 ssl://smtp.naver.com
const SMTP_USER = ""; //smtp 계정 ***@naver.com
const SMTP_PASS = ""; //패스워드
const SMTP_PORT = ""; //smtp 포트 번호
const FROM_EMAIL = ""; //보내는 메일 주소
const FROM_NAME = ""; //보내는 메일 사용자 명

/*
|--------------------------------------------------------------------------
| FCM
|--------------------------------------------------------------------------
*/
// FCM API KEY
const FCM_KEY = "";

/*
|--------------------------------------------------------------------------
| AES
|--------------------------------------------------------------------------
*/
// AES KEY
const AES_KEY = '';

/*
|--------------------------------------------------------------------------
| APP Scheme
|--------------------------------------------------------------------------
*/
const APP_SCHEME = '';
const APP_HISTORY_BACK = "location.href='" . APP_SCHEME . "back" . "'";
const WEB_HISTORY_BACK = 'history.back();';

/*
|--------------------------------------------------------------------------
| PUBLIC OPEN API
|--------------------------------------------------------------------------
*/
const SPCDEINFO_SERVICE_KEY = '';
