<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . "libraries/RestController.php"; // ⭐ 추가

class MY_Controller_API extends RestController
{
	public function __construct()
    {
        parent::__construct('extra/rest_config');

        if($this->router->class === 'common') redirect('/welcome');

        $this->load->library('authorization_token', ['config' => 'extra/jwt_config']);
        $this->lang->load('status_code', $this->siteLang);
    }

    public function index_get($key = 0)
    {
        $data = $this->beforeGet();

        $this->afterGet($key, $data);
    }

    public function index_post($key = 0)
    {
        $dto = $this->beforePost($key);

        $this->afterPost($key, $dto);
    }

    public function index_put($key = 0)
    {
		$data = $this->beforePut($key);

		$this->afterPut($key, $data);
    }

    public function index_patch($key = 0)
    {
        if($key === 0) {
            $this->keyNotExist();
        }else{
            $data = $this->beforePatch($key);

            $this->afterPatch($key, $data);
        }
    }

    public function index_delete($key = 0)
    {
		$this->beforeDelete($key);

		$this->afterDelete($key);
    }

    /* --------------------------------------------------------------- */

    protected function beforeGet()
    {
		return $this->input->get();
    }

    protected function afterGet($key, $data)
    {
		$this->response([
			'code' => DATA_RETRIEVED,
			'key' => $key,
			'data' => $data,
		]);
    }

    protected function beforePost($key, $model = null)
    {
		return $this->input->post();
    }

    protected function afterPost($key, $dto)
    {
		$this->response([
			'code' => DATA_RETRIEVED,
			'key' => $key,
			'data' => $dto,
		]);
    }

    protected function beforePut($key, $model = null)
    {
		return $this->put();
    }

    protected function afterPut($key, $dto)
    {
		$this->response([
			'code' => DATA_RETRIEVED,
			'key' => $key,
			'data' => $dto,
		]);
    }

    protected function beforePatch($key, $model = null)
    {
		return $this->patch();
    }

    protected function afterPatch($key, $dto)
    {
		$this->response([
			'code' => DATA_RETRIEVED,
			'key' => $key,
			'data' => $dto,
		]);
    }

    protected function beforeDelete($key)
    {
        return $key;
    }

    protected function afterDelete($key)
    {
		$this->response([
			'code' => DATA_RETRIEVED,
			'key' => $key,
		]);
    }

	public function response($data = null, $http_code = null)
    {
		header('Content-Type: application/json');

        if(is_empty($data, 'code') && $http_code === null)
            show_error('Insufficient response data provided');

        if($http_code === null) $http_code = floor((int)$data['code']/10);
        $http_big_code = floor($http_code/100);

        $response = new stdClass();
        $response->code = is_empty($data, 'code')?(int)$http_code*10:$data['code'];
        $response->msg = is_empty($data, 'msg')?$this->lang->status($response->code):$data['msg'];
        $response->data = [];
        if(!is_empty($data, 'data')) {
            if(is_array($data['data'])) {
                $response->data = $data['data'];
            }else{
                $response->data[] = $data['data'];
            }
        }
        $response->errors = [];
        if(in_array($http_big_code, [4,5])) {
            if(is_empty($data, 'errors')) {
                $response->errors = [[
                    'location' => 'body',
                    'param' => null,
                    'value' => null,
                    'type' => 'server error',
                    'msg' => 'error occurred',
                ]];
            }else{
                $response->errors = $data['errors'];
            }
        }

        if(!is_empty($data, 'extra')) foreach ($data['extra'] as $k=>$v) $response->{$k} = $v;

        RestController::response($response, $http_code);
        $this->output->_display();
        exit;
    }

    protected function keyNotExist()
    {
        $this->response([
            'code' => EMPTY_REQUIRED_KEY,
            'errors' => [
                'location' => 'keyNotExist',
                'param' => 'key',
                'value' => '',
                'type' => 'missing data',
                'msg' => 'required',
            ]
        ], RestController::HTTP_BAD_REQUEST);
    }

    protected function auth()
    {
        $this->validateToken();
    }

    protected function validateToken()
    {
		$headers = array_change_key_case($this->input->request_headers(), CASE_LOWER);

        if (isset($headers['authorization'])) {
            $decodedToken = $this->authorization_token->validateToken();
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

    protected function uploader($name, $fileDto = null)
    {
        $response = parent::uploader($name, $fileDto);

        if($response['result']) {
            return $response['data'];
        }else{
            if($response['code'] === UPLOAD_DATA_NOT_EXIST) {
                return [];
            }else{
                $this->response([
                    'code' => $response['code'],
                    'msg' => strip_tags($response['message']),
                    'data' => $_FILES,
                    'errors' => [
                        'location' => 'uploader',
                        'param' => $name,
                        'type' => 'upload error',
                    ]
                ], RestController::HTTP_INTERNAL_SERVER_ERROR);
            }
        }
    }
}
