<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Common extends MY_Builder_WEB
{
    public string $token;
    public object $userData;
    public bool $formConfigExist;

    public function __construct()
    {
        $this->flag = 'admin';
        $this->apiFlag = 'api';

        parent::__construct();

        $this->load->model('Model_User');

        $this->navAuth = [];
        $this->defaultController = $this->config->get('platform_config.isLoginRedirect', 'dashboard');

        $this->addCSS[] = base_url('public/assets/admin/css/style.css');
        $this->switchLang('korean');
    }

    protected function checkLogin(): bool
    {
        if(parent::checkLogin()){
            $user = $this->Model_User->getData([], ['user_id' => $this->session->userdata('user_id')]);
            if(!$user || !in_array($user->user_cd, ['USR000','USR001'])) return false;

            $this->isAdmin = true;
            $this->userData = $user;
            $this->headerData = [
                'id' => $user->id,
                'user_id' => $user->user_id,
                'name' => $user->name,
                'user_cd' => $user->user_cd,
            ];

            return true;
        }else{
            if($this->uri->uri_string !== $this->noLoginRedirect)
                redirect(base_url($this->noLoginRedirect));
            return false;
        }
    }
}
