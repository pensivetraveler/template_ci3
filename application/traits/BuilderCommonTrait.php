<?php
defined('BASEPATH') OR exit('No direct script access allowed');

trait BuilderCommonTrait
{
    protected function destroyUserData(): void
    {
        delete_cookie('autologin');

        if(count($this->session->userdata())) {
            foreach ($this->session->userdata() as $key=>$val) {
                $this->session->unset_userdata($key);
            }
            $this->session->sess_destroy();
        }

        // 세션 쿠키 삭제
        if (isset($_COOKIE[$this->config->item('sess_cookie_name')])) {
            setcookie($this->config->item('sess_cookie_name'), '', time() - 3600, '/');
        }
    }

    protected function getIdentifierData($key, $identifiers)
    {
        $data = [];
        if(count($identifiers) > 0){
            if(count($identifiers) === 1) {
                $data[$identifiers[0]] = $key;
            }else{
                foreach ($identifiers as $field) {
                    $data[$field] = $this->input->get($field);
                }
            }
        }
        return $data;
    }
}