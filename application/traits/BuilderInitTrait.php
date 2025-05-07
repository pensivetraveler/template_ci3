<?php
defined('BASEPATH') OR exit('No direct script access allowed');

trait BuilderInitTrait
{
    protected function isBuilderAvailable(): bool
    {
        // 1. 캐시가 존재하면 바로 true 반환
        if ($this->cache->file->get('init_done') === true) {
            return true;
        }

        // 2. DB 테이블 존재 여부 확인
        if($this->Model_Common->getTableCount() === 0) {
            $this->setupBuilderDB();
            return false;
        }

        // 3. 시스템 사용자 존재 여부 확인
        if(!$this->Model_Common->checkSystemUserExist()) {
            $this->addSystemUser();
            return false;
        }

        // 4. 모든 조건 통과 → 캐시 저장 (1일 유효) 86400초 = 1일
        if(!$this->cache->file->save('init_done', true, 86400)) {
            show_error('Cache File is not generated. Please Check The Permission of Document Root');
        }

        return true;
    }

    protected function setupBuilderDB()
    {
        if($this->input->post('sql')){
            $this->load->library('sql_parser');
            $sql = $this->sql_parser->parsing($this->input->post('sql'));
            foreach (explode(';', $sql) as $qry) {
                try {
                    $this->db->query($qry);
                }catch (Exception $e) {
                    $this->input->raw_input_stream = null; // 원본 요청 데이터 초기화
//					$this->Model_Common->deleteAllTables();
                    show_error($e->getMessage(), 500);
                    break;
                }
            }

            $_POST[] = '';
            redirect($this->baseUri);
        }else{
            $this->formColumns = $this->setFormColumns([
                [
                    'field' => 'sql',
                    'label' => 'sql',
                    'rules' => 'required',
                ]
            ]);
            $this->addJsVars([
                'FORM_DATA' => $this->setFormData(),
                'FORM_REGEXP' => $this->config->item('regexp'),
            ]);

            $data['platformName'] = BUILDER_FLAGNAME;
            $data['subPage'] = 'builder/setup/set_db';
            $data['backLink'] = WEB_HISTORY_BACK;
            $data['formData'] = restructure_form_data_by_type($this->jsVars['FORM_DATA'], 'base');
            $data['includes'] = [
                'head' => true,
                'header' => false,
                'modalPrepend' => true,
                'modalAppend' => true,
                'footer' => false,
                'tail' => true,
            ];

            parent::viewApp($data);
        }
    }

    public function addSystemUser()
    {
        $userColumns = [];
        $columns = $this->Model_Common->getNotNullColumns(USER_TABLE_NAME);
        if(empty($columns)) show_error(lang('Check The User Table'));

        foreach ($columns as $field) {
            if(in_array($field, [USER_ID_COLUMN_NAME, USER_CD_COLUMN_NAME, CREATED_ID_COLUMN_NAME, CREATED_DT_COLUMN_NAME, UPDATED_ID_COLUMN_NAME, UPDATED_DT_COLUMN_NAME, DEL_YN_COLUMN_NAME, USE_YN_COLUMN_NAME])) continue;
            $userColumns[] = [
                'field' => $field,
                'label' => $field,
            ];
        }

        if($this->input->post()) {
            $set = array_merge([
                USER_CD_COLUMN_NAME => 'USR000',
            ], array_intersect_key($this->input->post(), array_flip($columns)));
            if(array_key_exists('password', $set))
                $set['password'] = $this->encryption->encrypt($this->input->post('password'));

            $this->db->set($set)->insert(USER_TABLE_NAME);

            redirect($this->baseUri);
        }else{
            $this->formColumns = $this->setFormColumns($userColumns);
            $this->addJsVars([
                'FORM_DATA' => $this->setFormData(),
                'FORM_REGEXP' => $this->config->item('regexp'),
            ]);

            $data['platformName'] = BUILDER_FLAGNAME;
            $data['subPage'] = 'builder/setup/add_system_user';
            $data['backLink'] = WEB_HISTORY_BACK;
            $data['formData'] = restructure_form_data_by_type($this->jsVars['FORM_DATA'], 'base');
            $data['includes'] = [
                'head' => true,
                'header' => false,
                'modalPrepend' => true,
                'modalAppend' => true,
                'footer' => false,
                'tail' => true,
            ];

            parent::viewApp($data);
        }
    }
}