<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * TODO costom_constant 이후에 admin_constant가 선언될 수 있도록 조정이 필요. get_path method 이용해서 admin 관련 common class 에서 관련 helper, config, const를 별도 로드할 수 있도록 하는 것도 좋을 것으로 보임.
 * const 파일의 경우 아래와 같이 로드 가능
 * class Welcome extends CI_Controller {
 *     public function index() {
 *         $this->load->config('constant');  // constant.php 파일 로드
 *         echo SITE_NAME;  // 상수 출력
 *     }
 * }
 */
define('PLATFORM_NAME', basename(__DIR__));
const PLATFORM_ASSET_URI = 'public/assets/'.PLATFORM_NAME.DIRECTORY_SEPARATOR;
const PLATFORM_ASSET_PATH = FCPATH . PLATFORM_ASSET_URI;

const PLATFORM_ASSET_AUDIO_URI = PLATFORM_ASSET_URI . 'audio/';
const PLATFORM_ASSET_AUDIO_PATH = FCPATH . PLATFORM_ASSET_AUDIO_URI;

const PLATFORM_ASSET_CSS_URI = PLATFORM_ASSET_URI . 'css/';
const PLATFORM_ASSET_CSS_PATH = FCPATH . PLATFORM_ASSET_CSS_URI;

const PLATFORM_ASSET_IMG_URI = PLATFORM_ASSET_URI . 'img/';
const PLATFORM_ASSET_IMG_PATH = FCPATH . PLATFORM_ASSET_IMG_URI;

const PLATFORM_ASSET_JS_URI = PLATFORM_ASSET_URI . 'js/';
const PLATFORM_ASSET_JS_PATH = FCPATH . PLATFORM_ASSET_JS_URI;

const PLATFORM_ASSET_JSON_URI = PLATFORM_ASSET_URI . 'json/';
const PLATFORM_ASSET_JSON_PATH = FCPATH . PLATFORM_ASSET_JSON_URI;

const PLATFORM_ASSET_SVG_URI = PLATFORM_ASSET_URI . 'svg/';
const PLATFORM_ASSET_SVG_PATH = FCPATH . PLATFORM_ASSET_SVG_URI;

const PLATFORM_ASSET_VENDOR_URI = PLATFORM_ASSET_URI . 'vendor/';
const PLATFORM_ASSET_VENDOR_PATH = FCPATH . PLATFORM_ASSET_VENDOR_URI;

const APP_AOS_URL = '';
const APP_IOS_URL = '';
