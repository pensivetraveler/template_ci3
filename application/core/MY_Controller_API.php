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

    public function __destruct()
    {
        parent::__destruct();
    }

    /* --------------------------------------------------------------- */

    public function index_get($key = 0)
    {
        $this->handleGet($key);
    }

    public function index_post($key = 0)
    {
        $this->handlePost($key);
    }

    public function index_put($key = 0)
    {
        $this->handlePut($key);
    }

    public function index_patch($key = 0)
    {
        $this->handlePatch($key);
    }

    public function index_delete($key = 0)
    {
        $this->handleDelete($key);
    }

    /* --------------------------------------------------------------- */

    protected function handleGet($key = 0)
    {
        list($key, $data) = $this->beforeGet($key);

        $this->afterGet($key, $data);
    }

    protected function handlePost($key = 0)
    {
        list($key, $data) = $this->beforePost($key);

        $this->afterPost($key, $data);
    }

    protected function handlePut($key = 0)
    {
        list($key, $data) = $this->beforePut($key);

        $this->afterPut($key, $data);
    }

    protected function handlePatch($key = 0)
    {
        list($key, $data) = $this->beforePatch($key);

        $this->afterPatch($key, $data);
    }

    protected function handleDelete($key = 0)
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
        return [$key, $this->input->put()??$this->input->json()];
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
        return [$key, $this->input->patch()??$this->input->json()];
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
        return [$key, $this->input->put()??$this->input->json()];
    }

    protected function afterDelete($key, $data = [])
    {
        $this->response([
            'code' => DATA_RETRIEVED,
            'key' => $key,
            'data' => $data,
        ]);
    }

    public function response($data = null, $http_code = null, $exit = true)
    {
        if($http_code === null) $http_code = floor((int)$data['code']/10);
        $response = $this->setResponseData($data, $http_code);
        RestController::response($response, $http_code);

        if ($exit) {
            // 최종 헤더+본문을 즉시 전송
            $this->output->_display();
            // FPM 환경이면 클라이언트 요청 마무리(선응답)
            if (function_exists('fastcgi_finish_request')) {
                fastcgi_finish_request();
            }
            exit; // 이후 코드 실행 방지
        }
    }

    public function responseError($error = [], $response = [], $http_code = RestController::HTTP_INTERNAL_SERVER_ERROR)
    {
        if(is_list_type($error)) {
            $errors = $error;
        }else{
            $errors = [array_merge([
                'location' => null,
                'param' => null,
                'value' => null,
                'type' => null,
                'msg' => null,
            ], $error)];
        }

        if(!is_empty($response, 'code')) $http_code = null;
        $this->response([
            'code' => $response['code'] ?? null,
            'msg' => $response['msg'] ?? null,
            'data' => $response['data'] ?? [],
            'errors' => $errors,
        ], $http_code);
    }

    protected function keyNotExist()
    {
        $this->response([
            'code' => EMPTY_REQUIRED_KEY,
            'errors' => [[
                'location' => __METHOD__,
                'param' => 'key',
                'value' => '',
                'type' => 'missing data',
                'msg' => 'required',
            ]]
        ], RestController::HTTP_BAD_REQUEST);
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
                    'errors' => [[
                        'location' => __METHOD__,
                        'param' => $name,
                        'type' => 'upload error',
                    ]]
                ], RestController::HTTP_INTERNAL_SERVER_ERROR);
            }
        }
    }

    public function validateModel($model, $name = '', $db_conn = FALSE): object
    {
        $obj = $this->load->model($model, $name, $db_conn);

        $targetModel = $this->{$name?:$model};

        // model check
        if(!$targetModel->validateModelDefinition()) {
            $this->response([
                'code' => MODEL_DATA_NOT_COINCIDENCE,
                'errors' => [[
                    'location' => __METHOD__,
                    'type' => 'model error',
                    'value' => [
                        'table' => $targetModel->table,
                        'identifier' => $targetModel->identifier,
                        'primaryKeyList' => $targetModel->primaryKeyList,
                        'isAutoIncrement' => $targetModel->isAutoIncrement,
                        'columnList' => $targetModel->getColumnList(),
                        'strList' => $targetModel->strList,
                        'intList' => $targetModel->intList,
                        'fileList' => $targetModel->fileList,
                        'diffList' => $targetModel->determineDiffColumns(),
                    ]
                ]]
            ], RestController::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $obj;
    }
}
