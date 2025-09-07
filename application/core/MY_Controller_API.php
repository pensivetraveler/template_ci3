<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . "libraries/RestController.php"; // ⭐ 추가

class MY_Controller_API extends RestController
{
    public function __construct()
    {
        parent::__construct('extra/rest_config');

        if($this->router->class === 'common') redirect('/welcome');

        $this->lang->load('status_code', $this->config->item('language'));
        $this->config->set_item('compress_output', FALSE);
    }

    public function index_get($key = 0)
    {
        list($key, $data) = $this->beforeGet($key);

        $this->afterGet($key, $data);
    }

    public function index_post($key = 0)
    {
        list($key, $data) = $this->beforePost($key);

        $this->afterPost($key, $data);
    }

    public function index_put($key = 0)
    {
        list($key, $data) = $this->beforePut($key);

        $this->afterPut($key, $data);
    }

    public function index_patch($key = 0)
    {
        list($key, $data) = $this->beforePatch($key);

        $this->afterPatch($key, $data);
    }

    public function index_delete($key = 0)
    {
        list($key, $data) = $this->beforeDelete($key);

        $this->afterDelete($key, $data);
    }

    /* --------------------------------------------------------------- */

    protected function beforeGet($key = 0)
    {
        return [$key, $this->input->get()];
    }

    protected function afterGet($key, $data = [])
    {
        $this->response([
            'code' => DATA_RETRIEVED,
            'key' => $key,
            'data' => $data,
        ]);
    }

    protected function beforePost($key = 0, $model = null)
    {
        return [$key, $this->input->post()??$this->input->json()];
    }

    protected function afterPost($key, $data = [])
    {
        $this->response([
            'code' => DATA_RETRIEVED,
            'key' => $key,
            'data' => $data,
        ]);
    }

    protected function beforePut($key = 0, $model = null)
    {
        return [$key, $this->put()??$this->input->json()];
    }

    protected function afterPut($key, $data = [])
    {
        $this->response([
            'code' => DATA_RETRIEVED,
            'key' => $key,
            'data' => $data,
        ]);
    }

    protected function beforePatch($key = 0, $model = null)
    {
        return [$key, $this->patch()??$this->input->json()];
    }

    protected function afterPatch($key, $data = [])
    {
        $this->response([
            'code' => DATA_RETRIEVED,
            'key' => $key,
            'data' => $data,
        ]);
    }

    protected function beforeDelete($key = 0)
    {
        return [$key, $this->put()??$this->input->json()];
    }

    protected function afterDelete($key, $data = [])
    {
        $this->response([
            'code' => DATA_RETRIEVED,
            'key' => $key,
            'data' => $data,
        ]);
    }

    public function response($data = null, $http_code = null)
    {
        if($http_code === null) $http_code = floor((int)$data['code']/10);
        $response = $this->setResponseData($data, $http_code);

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
