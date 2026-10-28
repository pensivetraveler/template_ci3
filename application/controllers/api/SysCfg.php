<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once __DIR__.'/Common.php';

class SysCfg extends Common
{
    function __construct()
    {
        parent::__construct();

        $this->routeTitle = '환경값 설정';

        $this->load->model('Model_Sys_Cfg', 'Model');

        $this->setProperties($this->Model);

        if($this->flag !== 'admin') show_404();
    }

    protected function beforePost($key = 0, $model = null): array
    {
        list($key, $data) = parent::beforePost($key, $model);

        // 인증 토큰 유효 시간(common_helper token_lifetime)은 0 이상 정수(초)만 받는다. 비우면 .env TOKEN_LIFETIME
        $cmbCfg = $data['cmb_cfg'] ?? '';
        if($cmbCfg === '') $cmbCfg = $key ?: ($data['big_cfg'] ?? '').'_'.($data['sml_cfg'] ?? '');
        $value = trim((string)($data['cfg_val'] ?? ''));
        if($cmbCfg === 'SYS_AUTH_TOKEN_LIFETIME' && $value !== '' && !ctype_digit($value)) {
            $this->responseError([
                'location' => __METHOD__,
                'param' => 'cfg_val',
                'value' => $value,
                'msg' => '토큰 유효 시간은 0 이상의 정수(초)로 입력하세요. 0 은 만료 없음입니다.',
            ], [], RestController::HTTP_BAD_REQUEST);
        }

        return [$key, $data];
    }
}
