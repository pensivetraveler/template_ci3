<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once __DIR__.'/Common.php';

class Setup extends Common
{
    function __construct()
    {
        parent::__construct();

        $this->noIndexMethod = true;

        if ($this->cache->file->get('init_done') === true) {
            show_error('Not Avaliable ', 500, 'Error');
        }
    }

    public function addSystemUser_post()
    {
        $this->load->model('Model_User');

        if($this->cache->file->get('init_done')) {
            $this->response([
                'code' => BAD_REQUEST,
            ]);
        }else{
            $columns = $this->getSystemUserColumn();
            $set = array_merge([
                USER_CD_COLUMN_NAME => 'USR000',
            ], array_intersect_key($this->input->post(), array_flip($columns)));
            if(array_key_exists('password', $set))
                $set['password'] = $this->encryption->encrypt($this->input->post('password'));

            $redirectTo = base_url();
            if($this->input->post('redirect_to')) $redirectTo = base_url($this->input->post('redirect_to'));

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
