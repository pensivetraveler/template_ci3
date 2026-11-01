<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once __DIR__ . '/Common.php';

class Auth extends Common
{
    public function __construct()
    {
        parent::__construct();

        $this->addCSS[] = [
            base_url('public/assets/builder/vendor/css/pages/page-auth.css'),
            base_url('public/assets/builder/vendor/libs/@form-validation/form-validation.css'),
            base_url('public/assets/builder/vendor/libs/bootstrap-maxlength/bootstrap-maxlength.css'),
        ];

        $this->addJS['tail'][] = [
            base_url('public/assets/builder/vendor/libs/@form-validation/popular.js'),
            base_url('public/assets/builder/vendor/libs/@form-validation/bootstrap5.js'),
            base_url('public/assets/builder/vendor/libs/@form-validation/auto-focus.js'),
            base_url('public/assets/builder/vendor/libs/bootstrap-maxlength/bootstrap-maxlength.js'),
        ];

        $this->addJS['tail'][] = [
            base_url('public/assets/builder/js/app-page-auth.js'),
        ];

        $this->addJsVars([
            'API_URI' => $this->apiUri.'auth/',
            'FORM_REGEXP' => $this->config->item('regexp'),
        ]);
    }

    public function login()
    {
        if($this->isLoggedIn) redirect($this->loggedInRedirect);

        $this->addJsVars([
            'API_URI_ADD' => 'login',
            'REDIRECT_URI' => base_url($this->loggedInRedirect)
        ]);

        $data['subPage'] = 'admin/auth/login';
        $data['backLink'] = WEB_HISTORY_BACK;
        $data['formData'] = restructure_admin_form_data($this->jsVars['FORM_DATA'], 'page');

        $this->viewApp($data);
    }

    public function findId()
    {
        if($this->isLoggedIn) redirect($this->loggedInRedirect);
        if(!find_id_enabled()) show_404();

        $this->addJsVars([
            'API_URI_ADD' => 'findId',
            'REDIRECT_URI' => base_url($this->noLoginRedirect),
            'FORM_DATA' => filter_auth_form($this->jsVars['FORM_DATA'], 'find_id'),
        ]);

        $data['subPage'] = 'admin/auth/find_id';
        $data['backLink'] = WEB_HISTORY_BACK;
        $data['formData'] = restructure_admin_form_data($this->jsVars['FORM_DATA'], 'page');

        $this->viewApp($data);
    }

    public function resetPassword()
    {
        if($this->isLoggedIn) redirect($this->loggedInRedirect);
        if(!find_password_enabled() || user_cfg('PASSWORD_FIND_MODE') !== 'reset' || !$this->db->table_exists('password_reset')) show_404();

        $this->load->model('Model_Password_Reset');
        $token = (string)$this->input->get('token');
        if(!$this->Model_Password_Reset->findValid($token)) {
            alert('링크가 만료되었거나 이미 사용되었습니다. 비밀번호 찾기를 다시 해 주세요.', base_url($this->noLoginRedirect));
        }

        $this->addJsVars([
            'API_URI_ADD' => 'resetPassword',
            'REDIRECT_URI' => base_url($this->noLoginRedirect),
        ]);

        $data['subPage'] = 'admin/auth/reset_password';
        $data['backLink'] = WEB_HISTORY_BACK;
        $data['token'] = $token;
        $data['formData'] = restructure_admin_form_data($this->jsVars['FORM_DATA'], 'page');

        $this->viewApp($data);
    }

    public function findPassword()
    {
        if($this->isLoggedIn) redirect($this->loggedInRedirect);
        if(!find_password_enabled()) show_404();

        $this->addJsVars([
            'API_URI_ADD' => 'findPassword',
            'REDIRECT_URI' => base_url($this->noLoginRedirect),
            'FORM_DATA' => filter_auth_form($this->jsVars['FORM_DATA'], 'find_password'),
        ]);

        $data['subPage'] = 'admin/auth/find_password';
        $data['backLink'] = WEB_HISTORY_BACK;
        $data['formData'] = restructure_admin_form_data($this->jsVars['FORM_DATA'], 'page');

        $this->viewApp($data);
    }
}
