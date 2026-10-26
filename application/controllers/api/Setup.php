<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once __DIR__.'/Common.php';

class Setup extends Common
{
    function __construct()
    {
        parent::__construct();

        $this->noIndexMethod = true;

        // 설치 완료 여부는 DB 상태로 판단 (init_done 캐시는 하루 뒤 만료·새 컨테이너에는 없어서, 그동안 열려 있었음)
        if ($this->isInstalled()) {
            show_error('Not Available', 403, 'Error');
        }
    }

    /**
     * 테이블이 있고 시스템 관리자(USR000)가 있으면 설치 완료
     */
    protected function isInstalled(): bool
    {
        if ((int)$this->Model_Common->getTableCount() === 0) return false;
        return $this->Model_Common->checkSystemUserExist();
    }

    public function addSystemUser_post()
    {
        $this->load->model('Model_User');

        // 테이블이 먼저 있어야 하고, 시스템 관리자는 한 번만 만든다
        if((int)$this->Model_Common->getTableCount() === 0 || $this->Model_Common->checkSystemUserExist()) {
            $this->response([
                'code' => BAD_REQUEST,
            ]);
        }else{
            $columns = $this->getSystemUserColumn();

            $set = array_merge([
                USER_CD_COLUMN_NAME => 'USR000',
            ], array_intersect_key($this->input->post(), array_flip($columns)));

            if(array_key_exists('password', $set))
                $set['password'] = PASSWORD_DECRYPTABLE
                    ? $this->encryption->encrypt($this->input->post('password'))
                    : create_hash($this->input->post('password'));

            $redirectTo = base_url();

            if($this->input->post('redirect_to'))
                $redirectTo = base_url($this->input->post('redirect_to'));

            $this->Model_User->addData($set);

            $this->response([
                'code' => DATA_CREATED,
                'data' => [
                    'redirect_to' => $redirectTo,
                ]
            ]);
        }
    }

    public function addTables_post()
    {
        // 받은 SQL 을 그대로 실행하므로 운영에서는 막는다 (운영 DB 는 마이그레이션·직접 작업으로 준비)
        if (ENVIRONMENT === 'production') {
            show_error('Not Available', 403, 'Error');
        }

        if ($this->Model_Common->getTableCount() > 0) {
            $this->response([
                'code' => BAD_REQUEST,
            ]);
        } else {
            $this->load->library('sql_parser');
            $sql = $this->sql_parser->parsing($this->input->post('sql'));
            foreach (explode(';', $sql) as $qry) {
                try {
                    $this->db->query($qry);
                } catch (Exception $e) {
                    $this->input->raw_input_stream = null; // 원본 요청 데이터 초기화
//					$this->Model_Common->deleteAllTables();
                    show_error($e->getMessage(), 500);
                    break;
                }
            }

            $redirectTo = base_url();
            if($this->input->post('redirect_to')) $redirectTo = base_url($this->input->post('redirect_to'));

            $this->response([
                'code' => DATA_CREATED,
                'data' => [
                    'redirect_to' => $redirectTo,
                ]
            ]);
        }
    }
}
