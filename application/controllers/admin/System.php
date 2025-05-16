<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once __DIR__ . '/Common.php';

class System extends Common
{
    public function __construct()
    {
        parent::__construct();

        $this->titleList[] = 'System Management';
    }

    public function sysCfg()
    {
        $this->titleList[] = 'SysCfg';

        $data = $this->prepareFormData();

        $this->load->model('Model_Sys_Cfg');
        $data['data'] = $this->Model_Sys_Cfg->getListByBig();

        $data['subPage'] = 'builder/system/system_config';

        $this->addJS['tail'][] = [
            base_url('public/assets/builder/js/app-page-system-config.js'),
        ];

        $this->addJsVars([
            'API_URI' => $this->apiUri.'system/sysCfg',
            'FORM_REGEXP' => $this->config->item('regexp'),
        ]);

        $this->viewApp($data);
    }

    public function sysCode()
    {
        $this->titleList[] = 'SysCode';

        $this->load->model('Model_Sys_Code');

        $data = $this->prepareFormData();

        $bigCdColumns = $this->setFormColumns('big_code');
        $bigCdFormData = $this->setFormData($bigCdColumns);

        $data['category'] = $this->Model_Sys_Code->getBigCodeList();
        $data['data'] = $this->Model_Sys_Code->getListByBig();
        $data['subPage'] = 'builder/system/system_codes';
        $data['bigCdFormData'] = restructure_form_data_by_type($bigCdFormData);

        $this->addCSS[] = [
            base_url('public/assets/builder/vendor/libs/datatables-bs5/datatables.bootstrap5.css'),
            base_url('public/assets/builder/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css'),
            base_url('public/assets/builder/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.css'),
            base_url('public/assets/builder/vendor/libs/datatables-checkboxes-jquery/datatables.checkboxes.css'),
        ];

        $this->addJS['tail'][] = [
            base_url('public/assets/builder/vendor/libs/datatables-bs5/datatables-bootstrap5.js'),
        ];

        if($this->isSystemAdmin) {
            $data['actions'] = reformat_bool_type_list($this->methodConfig['actions']);
            $data['buttons'] = $this->methodConfig['buttons']??[];
        }else{
            $this->addJsVars([
                'LIST_COLUMNS' => array_slice($this->jsVars['LIST_COLUMNS'], 0, count($this->jsVars['LIST_COLUMNS'])-1),
                'LIST_ACTIONS' => [],
                'LIST_BUTTONS' => [],
            ]);
        }

        $data['columns'] = $this->jsVars['LIST_COLUMNS']??[];

        $this->addJS['tail'][] = [
            base_url('public/assets/builder/js/app-page-list.js'),
            base_url('public/assets/builder/js/app-page-system-code.js'),
        ];

        $this->addJsVars([
            'API_PARAMS' => ['big_cd' => $data['category'][0]->big_cd],
            'API_URI' => $this->apiUri.'system/sysCode',
            'FORM_REGEXP' => $this->config->item('regexp'),
            'EXTRA_FORMDATA' => $bigCdFormData,
        ]);

        $this->viewApp($data);
    }
}