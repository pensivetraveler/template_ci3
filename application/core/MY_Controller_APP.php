<?php
defined('BASEPATH') or exit('No direct script access allowed');

class MY_Controller_APP extends MY_Controller_WEB
{
    public function __construct()
    {
        parent::__construct();

        $this->baseViewPath = 'app/layout/index';

        $this->load->library('jwt_token', ['config' => 'extra/jwt_config']);
    }

    protected function setTitleList($data = [])
    {
        $this->titleList = $data?:[$this->categoryTitle, $this->headerTitle];
    }


    protected function validateToken($path = 'web'): array
    {
        if($path === 'web') {
            $token = $this->input->post('token')?:$this->session->userdata('token');
            if(empty($token)){
                alert('토큰 값이 없습니다.', base_url($this->noLoginRedirect));
            }else{
                $decodedToken = $this->jwt_token->validateToken($token);
                if($decodedToken['status'] === FALSE){
                    $this->session->unset_userdata('token');
                    switch ($decodedToken['message']) {
                        case 'Token Time Expire.':
                            alert(lang('Token Expired'), base_url($this->noLoginRedirect));
                        default:
                            alert(lang('Invalid Token'), base_url($this->noLoginRedirect));
                    }
                }else{
                    $this->session->set_userdata('token', $token);
                    return $decodedToken['data'];
                }
            }
        }

        if($path === 'api') {
            $headers = array_change_key_case($this->input->request_headers(), CASE_LOWER);

            if (isset($headers['authorization'])) {
                $decodedToken = $this->jwt_token->validateTokenFromHeader();
                if($decodedToken['status'] === FALSE){
                    switch ($decodedToken['message']) {
                        case 'Token Time Expire.':
                            $this->response([
                                'code' => TOKEN_EXPIRED,
                                'data' => ['token' => $headers['authorization']],
                            ], RestController::HTTP_UNAUTHORIZED);
                        default:
                            $this->response([
                                'code' => WRONG_TOKEN,
                                'data' => ['token' => $headers['authorization']],
                            ], RestController::HTTP_UNAUTHORIZED);
                    }
                }else{
                    return $decodedToken['data'];
                }
            }else{
                $this->response([
                    'code' => EMPTY_TOKEN,
                ], RestController::HTTP_UNAUTHORIZED);
            }
        }

        return false;
    }
}
