<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once __DIR__.'/Common.php';

class UserCfg extends Common
{
    protected bool $noIndexMethod = true;

    function __construct()
    {
        parent::__construct();

        $this->routeTitle = '회원 설정';

        $this->load->model('Model_Sys_Cfg', 'Model');

        $this->setProperties($this->Model);

        if($this->flag !== 'admin') show_404();
    }

    public function save_post()
    {
        $emailYn = $this->input->post('signup_email_yn');
        $telYn = $this->input->post('signup_tel_yn');
        $mode = (string)$this->input->post('password_find_mode');

        if(!in_array($emailYn, ['Y', 'N'], true) || !in_array($telYn, ['Y', 'N'], true) || !in_array($mode, ['', 'show', 'reset'], true)) {
            $this->response(['code' => BAD_REQUEST, 'msg' => '설정값이 올바르지 않습니다.'], RestController::HTTP_BAD_REQUEST);
        }
        if($mode === 'show' && !PASSWORD_DECRYPTABLE) {
            $this->response(['code' => BAD_REQUEST, 'msg' => '복호화할 수 없는 저장 방식에서는 비밀번호 표시를 고를 수 없습니다.'], RestController::HTTP_BAD_REQUEST);
        }

        $this->saveCfg('SIGNUP_EMAIL_YN', '가입 필드: 이메일 사용', $emailYn);
        $this->saveCfg('SIGNUP_TEL_YN', '가입 필드: 전화번호 사용', $telYn);
        $this->saveCfg('PASSWORD_FIND_MODE', '비밀번호 찾기 방식 (show, reset)', $mode);

        $this->response(['code' => DATA_EDITED]);
    }

    private function saveCfg(string $sml, string $name, string $value): void
    {
        $key = 'USER_'.$sml;
        if($this->Model->getCntWhere(['cmb_cfg' => $key])) {
            $this->Model->modData(['cfg_val' => $value], ['cmb_cfg' => $key]);
        }else{
            $this->Model->addData([
                'cmb_cfg' => $key,
                'big_cfg' => 'USER',
                'sml_cfg' => $sml,
                'cfg_name' => $name,
                'cfg_val' => $value,
                'cfg_type' => 'text',
            ]);
        }
    }
}
