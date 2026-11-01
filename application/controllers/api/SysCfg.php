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

    /**
     * 소구분 000 은 카테고리다. 새 big_cfg 를 직접 입력하므로 카테고리 폼 규칙으로 검증한다
     */
    private function useBigCfgForm(): void
    {
        if($this->input->post_put('sml_cfg') === Model_Sys_Cfg::CATEGORY_SML) {
            $this->formConfig = $this->resolveFormConfig($this->resourceLoader->entityConfig('form_big_cfg_config', [], false));
        }
    }

    protected function beforeRemove($key = 0, $data = []): array
    {
        $cmbCfg = (string)($this->idData['cmb_cfg'] ?? $key);
        $row = $this->Model->getData([], ['where' => ['cmb_cfg' => $cmbCfg]]);

        if($row && $row->sml_cfg === Model_Sys_Cfg::CATEGORY_SML && $this->Model->getCntWhere(['big_cfg' => $row->big_cfg]) > 1) {
            $this->response(['code' => BAD_REQUEST, 'msg' => 'You Should Delete Configs In The Category']);
        }

        return parent::beforeRemove($key, $data);
    }

    protected function beforePost($key = 0, $model = null): array
    {
        $this->useBigCfgForm();
        list($key, $data) = parent::beforePost($key, $model);
        $this->checkRowKeys($key, $data);
        $this->checkCfgValue($key, $data);

        return [$key, $data];
    }

    protected function beforePut($key = 0, $model = null): array
    {
        $this->useBigCfgForm();
        list($key, $data) = parent::beforePut($key, $model);
        $this->checkRowKeys($key, $data);
        $this->checkCfgValue($key, $data);

        return [$key, $data];
    }

    protected function beforePatch($key = 0, $model = null): array
    {
        list($key, $data) = parent::beforePatch($key, $model);
        $this->checkRowKeys($key, $data);
        $this->checkCfgValue($key, $data);

        return [$key, $data];
    }

    /**
     * 등록한 행의 키·대구분·소구분은 바꿀 수 없다. 값 행을 소구분 000 으로 보내 카테고리로 바꾸는 것도 여기서 막는다
     */
    private function checkRowKeys($key, array $data): void
    {
        $row = $this->Model->getData([], ['where' => ['cmb_cfg' => (string)($this->idData['cmb_cfg'] ?? $key)]]);
        if(!$row) return;

        foreach (['cmb_cfg', 'big_cfg', 'sml_cfg'] as $field) {
            if(isset($data[$field]) && (string)$data[$field] !== $row->{$field}) {
                $this->cfgError($field, $data[$field], '키, 대구분, 소구분은 바꿀 수 없습니다.');
            }
        }
    }

    /**
     * 코드가 읽는 환경값은 쓸 수 있는 값만 받는다. 비우면 기본 동작이다
     */
    private function checkCfgValue($key, array $data): void
    {
        if(!array_key_exists('cfg_val', $data)) return;

        $value = trim((string)$data['cfg_val']);
        if($value === '') return;

        $cmbCfg = $this->idData['cmb_cfg'] ?? ($data['cmb_cfg'] ?? '');
        if($cmbCfg === '') $cmbCfg = $key ?: ($data['big_cfg'] ?? '').'_'.($data['sml_cfg'] ?? '');

        switch ($cmbCfg) {
            case 'SYS_AUTH_TOKEN_LIFETIME' :
                // 인증 토큰 유효 시간(common_helper token_lifetime). 비우면 .env TOKEN_LIFETIME
                if(!ctype_digit($value)) $this->cfgError('cfg_val', $value, '토큰 유효 시간은 0 이상의 정수(초)로 입력하세요. 0 은 만료 없음입니다.');
                break;
            case 'USER_SIGNUP_EMAIL_YN' :
            case 'USER_SIGNUP_TEL_YN' :
                if(!in_array($value, ['Y', 'N'], true)) $this->cfgError('cfg_val', $value, 'Y 또는 N 으로 입력하세요.');
                break;
            case 'USER_PASSWORD_FIND_MODE' :
                if(!in_array($value, ['show', 'reset'], true)) $this->cfgError('cfg_val', $value, 'show 또는 reset 으로 입력하세요.');
                if($value === 'show' && !PASSWORD_DECRYPTABLE) $this->cfgError('cfg_val', $value, '복호화할 수 없는 저장 방식에서는 비밀번호 표시를 고를 수 없습니다.');
                break;
            case 'USER_LOG_KEEP_DAYS' :
                if(!ctype_digit($value)) $this->cfgError('cfg_val', $value, '로그 보관 기간은 0 이상의 정수(일)로 입력하세요.');
                break;
        }
    }

    private function cfgError(string $param, $value, string $msg): void
    {
        $this->responseError([
            'location' => __METHOD__,
            'param' => $param,
            'value' => $value,
            'msg' => $msg,
        ], [], RestController::HTTP_BAD_REQUEST);
    }
}
