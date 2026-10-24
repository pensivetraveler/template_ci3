<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . "libraries/RestController.php"; // ⭐ 추가

class MY_Controller_API extends RestController
{
    /**
     * 현재 요청에 적용된 api_method_config 값 (auth, key, log, limit, time, level, permission)
     * permission 은 API 권한 검사(TODO.md 2장)를 넣을 때 여기서 읽는다.
     */
    protected array $apiMethodFeature = [];

    public function __construct()
    {
        // RestController 생성자 안에서 인증을 검사하므로, 엔드포인트별 auth 설정은 부모 생성자 전에 넣는다
        $this->applyApiMethodAuth();

        parent::__construct('extra/rest_config');

        $this->applyApiMethodFeatures();

        if($this->router->class === 'common') redirect('/welcome');

        $this->lang->load('status_code', $this->config->item('language'));

        $this->config->set_item('compress_output', FALSE);
    }

    public function __destruct()
    {
        parent::__destruct();
    }

    /* --------------------------------------------------------------- */
    /* api_method_config: 엔드포인트별 REST 기능 설정                    */
    /* --------------------------------------------------------------- */

    /**
     * 현재 요청(클래스·메서드·HTTP)에 맞는 api_method_config 항목을 합친다.
     * [*][*] → [*][http] → [메서드][*] → [메서드][http] 순으로 덮어쓴다.
     */
    protected function resolveApiMethodFeature(array $classConfig, string $method, string $http): array
    {
        $feature = [];
        foreach ([['*', '*'], ['*', $http], [$method, '*'], [$method, $http]] as [$methodKey, $httpKey]) {
            if(isset($classConfig[$methodKey][$httpKey]) && is_array($classConfig[$methodKey][$httpKey])) {
                $feature = array_replace_recursive($feature, $classConfig[$methodKey][$httpKey]);
            }
        }
        return $feature;
    }

    /**
     * 부모 생성자 전에 실행: api_method_config 를 읽어 현재 요청의 기능 설정을 정하고,
     * auth 값은 rest_config 의 auth_override_class_method_http 에 넣는다.
     * rest_config 를 먼저 읽어 두므로 RestController 가 다시 읽어 덮어쓰지 않는다 (CI_Config::load 는 같은 파일을 한 번만 읽음).
     */
    protected function applyApiMethodAuth(): void
    {
        $config =& load_class('Config', 'core');
        $router =& load_class('Router', 'core');
        $input =& load_class('Input', 'core');

        $config->load('extra/rest_config');
        $config->load('extra/api_method_config', false, true);

        $class = $router->class;
        $method = $router->method;
        $http = strtolower($input->method());

        // 없는 메서드는 RestController 가 index_{http} 로 처리하므로 설정도 index 기준으로 찾는다
        $featureMethod = method_exists($this, "{$method}_{$http}") ? $method : 'index';

        $classConfig = ($config->item('api_method_config') ?: [])[$class] ?? [];
        $this->apiMethodFeature = $this->resolveApiMethodFeature($classConfig, $featureMethod, $http);
        $this->apiMethodFeature['_method'] = $featureMethod;

        if(isset($this->apiMethodFeature['auth'])) {
            $overrides = $config->item('auth_override_class_method_http') ?: [];
            // RestController::_auth_override_check() 는 router 의 class·method 원본 값으로 찾는다
            $overrides[$class][$method][$http] = $this->apiMethodFeature['auth'];
            $config->set_item('auth_override_class_method_http', $overrides);
        }
    }

    /**
     * 부모 생성자 후에 실행: key·log·limit·time·level 을 $this->methods['{메서드}_{HTTP}'] 로 넣는다.
     * 컨트롤러가 $this->methods 를 직접 지정한 값이 있으면 그 값이 우선한다.
     */
    protected function applyApiMethodFeatures(): void
    {
        $features = array_intersect_key($this->apiMethodFeature, array_flip(['key', 'log', 'limit', 'time', 'level']));
        if(!count($features)) return;

        $controllerMethod = $this->apiMethodFeature['_method'].'_'.strtolower($this->input->method());
        $this->methods[$controllerMethod] = array_merge($features, $this->methods[$controllerMethod] ?? []);
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
