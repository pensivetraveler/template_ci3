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
            show_error(__METHOD__.': Cache File is not generated. Please Check The Permission of Document Root');
        }

        return true;
    }

    protected function setupBuilderDB()
    {
        $this->formColumns = $this->setFormColumns([
            [
                'field' => 'sql',
                'label' => 'sql',
                'rules' => 'required',
            ]
        ]);
        $this->addJsVars([
            'API_URI' => base_url('api/setup/').'addTables/',
            'FORM_DATA' => $this->setFormData(),
            'FORM_REGEXP' => $this->config->item('regexp'),
        ]);

        $data['platformName'] = BUILDER_FLAGNAME;
        $data['subPage'] = 'builder/setup/set_db';
        $data['backLink'] = WEB_HISTORY_BACK;
        $data['formData'] = restructure_form_data_by_type($this->jsVars['FORM_DATA'], 'base');
        $data['hideLogoAtLogin'] = $this->hideLogoAtLogin;
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

    public function addSystemUser()
    {
        $userColumns = [];
        $columns = $this->getSystemUserColumn();

        foreach ($columns as $field) {
            if(in_array($field, [USER_ID_COLUMN_NAME, USER_CD_COLUMN_NAME, CREATED_ID_COLUMN_NAME, CREATED_DT_COLUMN_NAME, UPDATED_ID_COLUMN_NAME, UPDATED_DT_COLUMN_NAME, DEL_YN_COLUMN_NAME, USE_YN_COLUMN_NAME])) continue;
            $userColumns[] = [
                'field' => $field,
                'label' => $field,
                'rules' => 'trim|required',
            ];
        }

        $this->formColumns = $this->setFormColumns($userColumns);
        $this->addJsVars([
            'API_URI' => base_url('api/setup/').'addSystemUser/',
            'FORM_DATA' => $this->setFormData(),
            'FORM_REGEXP' => $this->config->item('regexp'),
        ]);

        $data['platformName'] = BUILDER_FLAGNAME;
        $data['subPage'] = 'builder/setup/add_system_user';
        $data['backLink'] = WEB_HISTORY_BACK;
        $data['formData'] = restructure_form_data_by_type($this->jsVars['FORM_DATA'], 'base');
        $data['hideLogoAtLogin'] = $this->hideLogoAtLogin;
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

    public function getSystemUserColumn()
    {
        $columns = $this->Model_Common->getNotNullColumns(USER_TABLE_NAME);
        if(empty($columns)) show_error(lang('Check The User Table'));
        return $columns;
    }
}
