<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'traits/BuilderInitTrait.php';
require_once APPPATH . 'traits/BuilderCommonTrait.php';
require_once APPPATH . 'traits/BuilderColumnsTrait.php';

class MY_Builder_API extends MY_Controller_API
{
    use BuilderInitTrait;
    use BuilderCommonTrait;
    use BuilderColumnsTrait;

    public string $flag;
    protected string $table = '';
    protected array $idData = [];
    protected bool $isAutoId = false;
    protected string $identifier = '';
    protected array $primaryKeyList = [];
    protected array $uniqueKeyList = [];
    protected array $notNullList = [];
    protected array $nullList = [];
    protected array $strList = [];
    protected array $intList = [];
    protected array $fileList = [];
    protected array $defaultList = [];

    protected bool $setConfig = true;
    protected array $listConfig = [];
    protected array $formConfig = [];
    protected array $viewConfig = [];
    protected string $listConfigName = '';
    protected string $formConfigName = '';
    protected string $viewConfigName = '';

    protected array $validateMessages = [];
    protected array $validateCallback = [];
    protected array $exceptValidateKeys = [];
    protected array $transTargetKeys = [];
    protected bool $indexAPI = false;

    public function __construct()
    {
        parent::__construct();

        if($this->listConfigName === '') $this->listConfigName = 'list_'.strtolower($this->router->class).'_config';
        if($this->formConfigName === '') $this->formConfigName = 'form_'.strtolower($this->router->class).'_config';
        if($this->viewConfigName === '') $this->viewConfigName = 'view_'.strtolower($this->router->class).'_config';
        $this->validateMessages = [];
        $this->validateCallback = [];
        $this->exceptValidateKeys = ['_mode', '_event', '_', 'select', 'format', 'draw', 'pageNo', 'limit', 'searchWord', 'searchCategory', 'filters'];
        $this->transTargetKeys = [];
        $this->indexAPI = true;

        if($this->uri->segment(1) === 'api'){
            $this->flag = 'web';
        }else{
            $this->flag = $this->flag??$this->uri->segment(1);
        }

        $this->loadConfigs(['builder_base_config', 'builder_form_config', 'builder_list_config', 'builder_view_config']);
    }

    public function index_get($key = 0)
    {
        if(!$this->indexAPI) show_404();
        parent::index_get($key);
    }

    public function index_post($key = 0)
    {
        if(!$this->indexAPI) show_404();
        parent::index_post($key);
    }

    public function index_put($key = 0)
    {
        if(!$this->indexAPI) show_404();
        parent::index_put($key);
    }

    public function index_patch($key = 0)
    {
        if(!$this->indexAPI) show_404();
        parent::index_patch($key);
    }

    public function index_delete($key = 0)
    {
        if(!$this->indexAPI) show_404();
        parent::index_delete($key);
    }

    protected function beforeGet($key = 0): array
    {
        list($key, $data) = parent::beforeGet($key);

        return [
            $this->checkIdentifierExist($key),
            reformat_get_data($data, $this->exceptValidateKeys)
        ];
    }

    protected function afterGet($key, $data = [])
    {
        if(count(array_keys($this->idData)) > 0 && count(array_keys($this->idData)) === count($this->primaryKeyList)) {
            $this->view($key, $data);
        }else{
            $this->list($data);
        }
    }

    protected function list($data = [], $isResponse = true)
    {
        $data = $this->beforeList($data);

        $data = $this->executeList($data);

        return $this->afterList($data, $isResponse);
    }

    protected function beforeList($data): array
    {
        return $data;
    }

    protected function executeList($data): array
    {
        $list = $this->Model->getList(
            $data['select'] ?? [],
            $data,
        );

        $data['list'] = $this->transformList($list);

        return $data;
    }

    protected function afterList($data, $isResponse = true)
    {
        if($isResponse) {
            $this->beforeResponse($data, true);

            $this->response([
                'code' => DATA_RETRIEVED,
                'data' => $data['list'],
                'extra' => $data['extraFields'] ?? [],
            ]);
        }else{
            return $data;
        }
    }

    protected function transformList($list): array
    {
        foreach ($list as $key=>$item) {
            $list[$key] = $this->transformView($item);
        }
        return $list;
    }

    protected function view($key, $data = [], $isResponse = true)
    {
        list($key, $data) = $this->beforeView($key, $data);

        list($key, $data) = $this->executeView($key, $data);

        return $this->afterView($key, $data, $isResponse);
    }

    protected function beforeView($key, $data): array
    {
        if(!$this->checkDataExist(['where' => $this->idData])) $this->response(['code' => DATA_NOT_EXIST]);

        return [$key, $data];
    }

    protected function executeView($key, $data): array
    {
        $view = $this->Model->getDataWhere([], $this->idData);

        $data['view'] = $this->transformView($view);

        return [$key, $data];
    }

    protected function afterView($key, $data, $isResponse = true)
    {
        if($isResponse) {
            $this->beforeResponse($data, true);

            $this->response([
                'code' => $data['view']?DATA_RETRIEVED:DATA_NOT_EXIST,
                'data' => $data['view']??[],
            ]);
        }else{
            return [$key, $data];
        }
    }

    protected function transformView($data): object
    {
        if($this->input->get('_mode') && $this->input->get('_mode') !== 'form') {
            $transTargetKeys = [];
            $targetConfig = [];

            switch ($this->input->get('_mode')) {
                case 'list' :
                    $transTargetKeys = $this->transTargetKeys['list']??[];
                    $targetConfig = $this->listConfig;
                    break;
                case 'view' :
                    $transTargetKeys = $this->transTargetKeys['view']??[];
                    $targetConfig = $this->viewConfig;
                    break;
            }

            foreach ($transTargetKeys as $key) {
                if(!property_exists($data, $key) || !$data->{$key}) continue;
                if(array_search($key, array_column($targetConfig, 'field')) !== false) {
                    $idx = array_search($key, array_column($targetConfig, 'field'));
                    $item = $targetConfig[$idx];
                    if(!isset($item['option_attributes'])) continue;
                    $options = $this->getOptions($key, $item['option_attributes']);
                    $data->{$key} = $options[$data->{$key}];
                }
            }
        }

        if(count($this->fileList) > 0) {
            foreach ($this->fileList as $key) {
                if($data->{$key} === '0') {
                    $data->{$key} = null;
                    continue;
                }
                if($data->{$key}) {
                    $file_id = $data->{$key};
                    $file_dto = $this->Model_File->getListWhere([], ['file_id' => $file_id]);
                    if($file_dto) {
                        $data->{$key} = $file_dto;
                    }else{
                        $data->{$key} = null;
                    }
                }
            }
        }

        return $data;
    }

    protected function beforePost($key = 0, $model = null): array
    {
        list($key, $data) = parent::beforePost($key);

        $key = $this->checkIdentifierExist($key);

        $data = $this->validate($data, $model);

        $this->checkUniqueExist($data, $model, is_empty($key));

        if(count($this->fileList) > 0) $data = $this->uploadFileInList($data);

        return [$key, $data];
    }

    protected function afterPost($key, $data = [])
    {
        if(count(array_keys($this->idData))) {
            $this->modify($key, $data, true);
        }else{
            $this->add($data, false);
        }
    }

    protected function beforePut($key = 0, $model = null): array
    {
        list($key, $data) = parent::beforePut($key);

        $key = $this->checkIdentifierExist($key);

        $data = $this->validate($data, $model);

        if($key) $this->checkUniqueExist($data, $model, false);

        return [$key, $data];
    }

    protected function afterPut($key, $data = [])
    {
        if(count(array_keys($this->idData))) {
            $this->modify($key, $data, true);
        }else{
            $this->add($data, false);
        }
    }


    protected function beforePatch($key = 0, $model = null): array
    {
        $key = $this->checkIdentifierExist($key);

        return parent::beforePatch($key);
    }

    protected function afterPatch($key, $data = [])
    {
        $this->modify($key, $data, true);
    }

    protected function beforeDelete($key = 0): array
    {
        $key = $this->checkIdentifierExist($key);

        return parent::beforeDelete($key);
    }

    protected function afterDelete($key, $data = [])
    {
        $this->remove($key, $data, true);
    }


    /* --------------------------------------------------------------- */
    protected function add($dto, $bool)
    {
        $dto = $this->beforeAdd($dto);

        $dto = $this->executeAdd($dto, $bool);

        $this->afterAdd($dto);
    }

    protected function beforeAdd($dto): array
    {
        if(!$this->isAutoId && count($this->primaryKeyList) > 0) {
            $this->idData = array_reduce(array_merge($this->primaryKeyList, $this->uniqueKeyList), function ($carry, $item) {
                if($this->input->post($item)) $carry[$item] = $this->input->post($item);
                return $carry;
            }, []);
            if($this->checkDataExist(['where' => $this->idData])) $this->response(['code' => DATA_ALREADY_EXIST]);
        }

        return $dto;
    }

    protected function executeAdd($dto, $bool): array
    {
        $result = $this->Model->addData($dto, $bool);
        if($this->isAutoId) {
            $dto[$this->identifier] = $result;
            $this->idData = [$this->identifier => $dto[$this->identifier]];
        }

        return $dto;
    }

    protected function afterAdd($dto)
    {
        $this->beforeResponse($dto);

        $this->response([
            'code' => DATA_CREATED,
            'data' => $this->idData,
        ], RestController::HTTP_CREATED);
    }

    protected function modify($key, $dto, $bool)
    {
        $dto = $this->beforeModify($key, $dto);

        $dto = $this->executeModify($key, $dto, $bool);

        $this->afterModify($key, $dto);
    }

    protected function beforeModify($key, $dto): array
    {
        return $dto;
    }

    protected function executeModify($key, $dto, $bool): array
    {
        $this->Model->modData($dto, $this->idData, $bool);

        return $dto;
    }

    protected function afterModify($key, $dto)
    {
        $this->beforeResponse($dto);

        $this->response([
            'code' => DATA_EDITED,
            'data' => $this->idData,
        ]);
    }

    protected function remove($key, $data, $bool)
    {
        $data = $this->beforeRemove($key, $data);

        $data = $this->executeRemove($key, $data, $bool);

        $this->afterRemove($key, $data);
    }

    protected function beforeRemove($key, $data = []): array
    {
        return $data;
    }

    protected function executeRemove($key, $data, $bool): array
    {
        $this->Model->delData($this->idData, $bool);

        return $data;
    }

    protected function afterRemove($key, $data = [])
    {
        $this->beforeResponse($data);

        $this->response([
            'code' => DATA_DELETED,
        ]);
    }

    protected function beforeResponse($data, $isFetch = false)
    {

    }

    /* --------------------------------------------------------------- */

    protected function validate($data = [], $model = null, $validate = true, $configName = '')
    {
        if($validate) $data = $this->validateFormRules($configName, $data);

        if(is_null($model)) $model = $this->Model;

        return $this->validateManually(
            $data,
            $model,
            $this->validateMessages,
            $this->validateCallback,
        );
    }

    protected function validateFormRules($configName = '', $data = []): array
    {
        $method = __METHOD__;
        $errors = [];
        $config = $this->config->get($configName, $this->formConfig, false);
        if(empty($data)) $data = $this->input->post_put();

        // base rule validation
        $config = array_map(function ($item) {
            if(!array_key_exists('rules', $item) || !$item['rules']) $item['rules'] = 'do_nothing';
            if(is_empty($item, 'group')) $item['group'] = 'base';
            return $item;
        }, array_filter($config, function ($item) {
            return $item['type'] !== 'common';
        }));
        $groups = array_flip(array_unique(array_column($config, 'group')));

        foreach ($groups as $group=>$idx) {
            if($group !== 'base') $groups[$group] = array_merge($this->config->get('builder_form_base_group_attributes'), $config[$idx]['group_attributes']);
        }
        foreach ($groups as $group => $attr) {
            $groupConfig = array_filter($config, function($item) use ($group) {
                return $item['group'] === $group;
            });
            $this->form_validation->set_rules($groupConfig);

            if($group === 'base') {
                $targetData = [];
                foreach ($data as $field => $value) {
                    if(in_array($field, array_column($groupConfig, 'field'))){
                        $targetData[$field] = $value;
                    }
                }
                $this->form_validation->set_data($targetData);
                if($this->form_validation->run() === false) {
                    $errors = array_merge(
                        $errors,
                        $this->setValidateFormErrors(validation_errors_array(), $method),
                    );
                }
            }else{
                $enveloped = $attr['envelope_name'];
                $targetData = [];
                if($enveloped) {
                    $targetData = $data[$group];
                }else{
                    foreach ($data as $field => $value) {
                        if(in_array($field, array_column($groupConfig, 'field'))){
                            $targetData[$field] = $value;
                        }
                    }
                }

                if($attr['group_repeater']) {
                    if($enveloped) {
                        foreach ($targetData as $i => $item) {
                            foreach ($item as $field => $value) {
                                if(empty($value)) {
                                    unset($targetData[$i]);
                                    break;
                                }
                            }
                        }
                        $targetData = array_values($targetData);
                        for($i = 0; $i < count($targetData); $i++) {
                            $this->form_validation->set_data($targetData[$i]);
                            if($this->form_validation->run() === false) {
                                $errors = array_merge(
                                    $errors,
                                    $this->setValidateFormErrors(validation_errors_array(), $method, $group, $attr, $i),
                                );
                            }
                        }
                    }else{
                        $cnt = 0;
                        foreach ($targetData as $field => $value) {
//							$targetData[$field] = array_values($value);
                            if($cnt === 0) $cnt = count($value);
                            $cnt = min($cnt, count($value));
                        }

                        for($i = 0; $i <= $cnt; $i++) {
                            $item = [];
                            foreach ($targetData as $k => $v) $item[$k] = $v[$i];
                            $this->form_validation->set_data($item);
                            if($this->form_validation->run() === false) {
                                $errors = array_merge(
                                    $errors,
                                    $this->setValidateFormErrors(validation_errors_array(), $method, $group, $attr, $i),
                                );
                            }
                        }
                    }
                }else{
                    $this->form_validation->set_data($targetData);
                    if($this->form_validation->run() === false) {
                        $errors = array_merge(
                            $errors,
                            $this->setValidateFormErrors(validation_errors_array(), $method, $group, $attr),
                        );
                    }
                }
            }
        }

        // file rule validation
        foreach ($config as $item) {
            // Check if 'rules' exists in the array item
            if (isset($item['rules'])) {
                // Use regex to check
                foreach ($this->config->item('file_rules') as $rule=>$ruleData) {
                    if (preg_match("/{$ruleData['exp']}/{$ruleData['flags']}", $item['rules'], $matches)) {
                        $param = $matches[2]??null;
                        if($this->form_validation->{$rule}($item['field'], $matches[2]) === false){
                            $errors[] = [
                                'location' => $method,
                                'param' => $item['field'],
                                'value' => $param,
                                'type' => $rule,
                                'msg' => $this->form_validation->get_error_msg($rule, $item['label'], $param),
                            ];
                        }
                    }
                }
            }
        }

        if(count($errors)) {
            $this->response([
                'data' => $this->input->post(),
                'errors' => $errors,
            ], RestController::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $data;
    }

    protected function setValidateFormErrors($errors, $method, $group = 'base', $attr = [], $i = 0)
    {
        return array_reduce($errors, function ($carry, $item) use ($method, $group, $attr, $i) {
            $param = $item['field'];
            if(!empty($attr)) {
                if($attr['envelope_name']) {
                    $param = $group.($attr['group_repeater']?"[$i]":'')."[$param]";
                }else{
                    $param = $param.($attr['group_repeater']?"[$i]":'');
                }
            }

            $carry[] = [
                'location' => $method,
                'param' => $param,
                'value' => $item['value'],
                'type' => $item['rule'],
                'msg' => $item['message'],
            ];
            return $carry;
        }, []);
    }

    protected function validateJson($required = [], $optional = [], $strList = [], $intList = [], $msgList = [], $callbacks = [])
    {
        $json_data = $this->input->raw_input_stream;
        $parsed_data = (array)json_decode($json_data);
        if(empty($required)) $required = array_keys($parsed_data);

        $dto = new class {};
        $dto->table = '';
        $dto->identifier = '';
        $dto->primaryKeyList = [];
        $dto->notNullList = empty($required)?array_keys($parsed_data):$required;
        $dto->nullList = $optional;
        $dto->strList = $strList;
        $dto->intList = $intList;
        $dto->fileList = [];

        return $this->validateManually($parsed_data, $dto, $msgList, $callbacks);
    }

    protected function validateManually($data = [], $dto = null, $msgList = [], $callbacks = [])
    {
        if($dto === null || !isset($dto->notNullList))
            $this->response([
                'code' => EMPTY_REQUIRED_DATA,
                'msg' => lang('status_code.'.EMPTY_REQUIRED_DATA),
                'data' => $data,
                'errors' => [[
                    'location' => 'validateManually',
                    'param' => null,
                    'value' => null,
                    'type' => 'required',
                    'msg' => lang('status_code.'.EMPTY_REQUIRED_DATA),
                ]]
            ], RestController::HTTP_BAD_REQUEST);

        if($this->input->method() === 'post') {
            foreach ($this->defaultList as $field=>$default) {
                if(!isset($data[$field])) $data[$field] = $default;
            }

            foreach ($dto->notNullList as $key) {
                if( $dto->identifier && $key === $dto->identifier ) continue;
                if( in_array($key, $dto->primaryKeyList) ) continue;

                if(array_key_exists($key, $callbacks)){
                    $this->{$callbacks[$key]}();
                }else{
                    $errorMsg = '';
                    $value = null;

                    if(array_key_exists($key, $msgList)) {
                        $msg = $msgList[$key];
                    }else{
                        $lang = $dto->table?lang($dto->table.'.'.$key):$key;
                        if($this->request === 'post' && count($dto->fileList) > 0 && in_array($key, $dto->fileList)){
                            if(!is_file_posted($key)) {
                                $errorMsg = "File Data {$key} Is Missing.";
                                $data = $_FILES;
                                $msg = $this->josa->__conv("$lang{을} 업로드하세요.");
                            }
                        }else{
                            if(!array_key_exists($key, $data)) {
                                $errorMsg = 'Required';
                            }else if(is_empty($data, $key)) {
                                $value = $data[$key];
                                $errorMsg = 'empty';
                            }
                            if($errorMsg) $msg = $this->josa->__conv("$lang{은} 필수 입력값 입니다.");
                        }
                    }

                    if($errorMsg) {
                        $this->response([
                            'code' => EMPTY_REQUIRED_DATA,
                            'msg' => array_key_exists($key, $msgList)?$msgList[$key]:$msg,
                            'data' => $data,
                            'errors' => [[
                                'location' => 'validateManually',
                                'param' => $key,
                                'value' => $value,
                                'type' => 'required',
                                'msg' => $errorMsg,
                            ]]
                        ], RestController::HTTP_BAD_REQUEST);
                    }
                }
            }
        }

        $checkboxes = array_values(array_filter($this->formConfig, function ($item) {
            return $item['type'] === 'checkbox';
        }));

        $columnList = array_unique(array_merge($dto->notNullList, $dto->nullList));
        foreach ($data as $key => $val) {
            if(!in_array($key, $columnList)){
                unset($data[$key]);
                continue;
            }

            if(!is_object($val) && !is_array($val)) $data[$key] = trim(preg_replace('/\s\s+/', ' ', $val));
            if(in_array($key, $dto->strList) && empty($val)) $data[$key] = '';
            if(in_array($key, $dto->intList)) {
                if(array_key_exists($key, $data)) {
                    $data[$key] = (int)$data[$key];
                }else{
                    $data[$key] = in_array($key, $dto->notNullList) ? 0 : null;
                }
            }

            switch ($key) {
                case 'gender' :
                    $data[$key] = strtoupper($data[$key]);
                    break;
                case 'password' :
                    if($val) {
                        $data[$key] = $this->encryption->encrypt($val);
                    }else{
                        unset($data[$key]);
                    }
                    break;
            }

            if(in_array($key, array_column($checkboxes, 'field'))) {
                $seperator = $checkboxes['option_attributes']['seperator'] ?? ',';
                $data[$key] = join($seperator, $data[$key]);
            }
        }

        return $data;
    }

    protected function uploadFileInList($dto, $model = null)
    {
        if(is_null($model)) $model = $this->Model;
        $key = null;
        try {
            $uploadPath = 'public/uploads/'.$this->router->class.'/'.date('Y').'/';
            if(!make_directory($uploadPath)) throw new Exception($this->upload->display_errors(), CREATE_FOLDER_FAIL);

            $files = $_FILES;
            foreach ($model->fileList as $key) {
                if(is_file_posted($key)) {
                    $config = $this->config->item($this->router->class . '_' . $key . '_upload_config')
                        ?: $this->config->item($key . '_upload_config')
                            ?: $this->config->item('base_upload_config');

                    if(!array_key_exists('allowed_types', $config))
                        throw new Exception('Upload config is not defined : '.$key, UPLOAD_FILE_FAIL);

                    $this->upload->initialize(
                        array_merge(
                            $config,
                            [
                                'upload_path' => $uploadPath,
                            ]
                        )
                    );

                    if(gettype($files[$key]['name']) === 'string') {
                        if(!$this->upload->do_upload($key)) throw new Exception($this->upload->display_errors(), UPLOAD_FILE_FAIL);
                        $dto[$key] = $this->Model_File->addData($this->upload->data(), false);
                        if(!$dto[$key]) throw new Exception('FILE DB Error', WRITE_FILEDB_FAIL);
                    }else{
                        foreach ($files[$key]['name'] as $idx => $val) {
                            $_FILES[$key]['name'] = $files[$key]['name'][$idx];
                            $_FILES[$key]['type'] = $files[$key]['type'][$idx];
                            $_FILES[$key]['tmp_name'] = $files[$key]['tmp_name'][$idx];
                            $_FILES[$key]['error'] = $files[$key]['error'][$idx];
                            $_FILES[$key]['size'] = $files[$key]['size'][$idx];

                            if(!$this->upload->do_upload($key)) throw new Exception($this->upload->display_errors(), UPLOAD_FILE_FAIL);
                            $dto[$key][$idx] = $this->Model_File->addData($this->upload->data(), false);
                            if(!$dto[$key][$idx]) throw new Exception('FILE DB Error', WRITE_FILEDB_FAIL);
                        }
                    }
                }
            }
        } catch (Exception $e) {
            $this->response([
                'code' => $e->getCode(),
                'msg' => strip_tags($e->getMessage()),
                'data' => $_FILES,
                'errors' => [
                    'location' => 'uploadFileInList',
                    'param' => $key,
                    'type' => 'upload error',
                ]
            ], RestController::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $dto;
    }

    protected function setProperties($model, $model_parent = null)
    {
        $this->identifier = $model->identifier;
        $this->fileList = $model->fileList;
        $this->isAutoId = $model->isAutoIncrement;
        $this->primaryKeyList = $model->primaryKeyList;
        $this->uniqueKeyList = $model->uniqueKeyList;

        // model check
        if(!$model->validateTableColumns()) {
            $this->response([
                'code' => MODEL_DATA_NOT_COINCIDENCE,
                'errors' => [
                    'location' => 'model',
                    'type' => 'model error',
                    'value' => [
                        'columnList' => $model->getColumnList(),
                        'strList' => $model->strList,
                        'intList' => $model->intList,
                        'fileList' => $model->fileList,
                        'diffList' => $model->determineDiffColumns(),
                    ]
                ]
            ], RestController::HTTP_INTERNAL_SERVER_ERROR);
        }

        if(!in_array($this->input->method(), ['get', 'post'])) return;

        if($this->setConfig) {
            $this->listConfig = array_map(function ($item) {
                return array_merge($this->config->get('builder_list_base'), $item);
            }, $this->config->get($this->listConfigName, [], false));
            $this->formConfig = array_map(function ($item) {
                return array_merge($this->config->get('builder_form_base'), $item);
            }, $this->config->get($this->formConfigName, [], false));
            $this->viewConfig = array_map(function ($item) {
                return array_merge($this->config->get('builder_view_base'), $item);
            }, $this->config->get($this->viewConfigName, [], false));

            if($this->input->method === 'get') {
                if(is_empty($this->listConfig)) {
                    $this->listConfig = array_map(
                        function($item) {
                            $attributes = $item['list_attributes'] ?? [];
                            $label = is_empty($attributes, 'label')?$item['label']:$attributes['label'];
                            if(sscanf($label, 'lang:%s', $line) === 1) $label = $line;
                            if($this->lang->line_exists($label.'_list')) $label = $label.'_list';
                            return array_merge(
                                $this->config->get('builder_list_base', []),
                                $attributes,
                                [
                                    'field' => $item['field'],
                                    'label' => $label,
                                    'option_attributes' => $item['option_attributes'] ?? []
                                ]
                            );
                        },
                        array_filter($this->formConfig, function ($item) {
                            return array_key_exists('list', $item) && $item['list'];
                        })
                    );
                }
            }

            if($this->input->method === 'post') {
                if(is_empty($this->formConfig)) {
                    $this->response([
                        'data' => $this->input->request(),
                        'errors' => [
                            [
                                'location' => __METHOD__,
                                'param' => '',
                                'value' => '',
                                'type' => '',
                                'msg' => "Validation Rules Config For $this->formConfigName Is Empty",
                            ]
                        ],
                    ], RestController::HTTP_BAD_REQUEST);
                }
            }
        }
    }

    protected function checkIdentifierExist($key = 0, $model = null): string
    {
        if(!$model && property_exists($this, 'Model')) $model = $this->Model;

        if(is_null($model)) return $key;

        if($key || $this->input->get($model->identifier)) {
            if(!$key) $key = $this->input->get($model->identifier);
            $this->idData = [$model->identifier => $key];
        }else{
            if(!count(array_merge($model->primaryKeyList, $model->uniqueKeyList))) {
                show_error('There\'s No data for Identifying');
            }

            $this->idData = array_reduce(array_merge($model->primaryKeyList, $model->uniqueKeyList), function ($carry, $item) {
                if($this->input->get($item)) $carry[$item] = $this->input->get($item);
                return $carry;
            }, []);

//            if(count(array_keys($this->idData)) > 0 && count(array_keys($this->idData)) !== count($model->primaryKeyList)) {
//                show_error('ID Field and Values is not equivalent counts');
//            }
        }
        if(!count(array_keys($this->idData))) return $key;

        return $key;
    }

    protected function checkDataExist($data, $model = null, $exit = false): bool
    {
        if(is_null($model)) $model = $this->Model;
        $result = $this->checkCnt($data, $model);

        if($exit && !$result) {
            $this->response([
                'code' => DATA_NOT_EXIST,
            ], RestController::HTTP_NOT_FOUND);
        }

        return $result;
    }

    protected function checkUniqueExist($dto, $model = null, $add = true)
    {
        if(is_null($model)) $model = $this->Model;
        if(count($model->uniqueKeyList) > 0){
            foreach ($model->uniqueKeyList as $key) {
                if(!array_search($key, array_column($this->formConfig, 'field'))) continue;
                if(!$add && !array_key_exists($key, $dto)) continue;

                $idx = array_search($key, array_column($this->formConfig, 'field'));
                $config = $this->formConfig[$idx];
                if(!$add && (!array_key_exists('editable', $config['form_attributes']) || !$config['form_attributes']['editable'])) continue;

                $isIncludeDeleted = false;
                if(array_key_exists('form_attributes', $this->formConfig[$idx]) && !is_empty($this->formConfig[$idx]['form_attributes'], 'check_delete')) {
                    $isIncludeDeleted = $config['form_attributes']['check_delete'];
                }

                if($this->checkDuplicate([$key => $dto[$key]], $model, !$add?$dto:[], $isIncludeDeleted)){
                    $lang = $model?lang($model->table.'.'.$key):$key;
                    $this->response([
                        'code' => DATA_ALREADY_EXIST,
                        'msg' => $this->josa->__conv("동일 $lang{이} 이미 존재합니다."),
                    ], RestController::HTTP_CONFLICT);
                    break;
                }
            }
        }
    }

    protected function checkDuplicate($unique, $model = null, $dto = [], $isIncludeDeleted = false)
    {
        foreach ($unique as $key=>$val) {
            if(is_null($model)) {
                if(property_exists($this, 'Model_Parent') && in_array($key, $this->Model_Parent->uniqueKeyList)) {
                    $model = $this->Model_Parent;
                }else if(property_exists($this, 'Model_Child') && in_array($key, $this->Model_Child->uniqueKeyList)) {
                    $model = $this->Model_Child;
                }else if(property_exists($this, 'Model')){
                    $model = $this->Model;
                }
            }

            $whereNot = is_empty($dto)?[]:[$model->identifier => $dto[$model->identifier]];
            return $model->checkDuplicate($unique, $whereNot, $isIncludeDeleted);
        }
    }

    protected function checkCnt($dto, $model = null)
    {
        if(is_null($model)) $model = $this->Model;
        return $model->getCnt($dto) > 0;
    }

    public function validateExcel_post()
    {
        $this->beforeExcelUpload();

        $this->response([
            'code' => DATA_AVAILABLE,
        ]);
    }

    public function uploadExcel_post()
    {
        $data = $this->beforeExcelUpload();

        $this->afterExcelUpload($data);
    }

    public function prepareExports_get()
    {
        /**
         * 1.Model check
         */
        if(is_null($this->Model)) {
            $this->response([
                'code' => MODEL_IS_NOT_DEFINED,
            ]);
        }

        /**
         * 2.Count check
         */
        $data = $this->input->get();
        $exportType = $data['exportType'];
        if(!in_array($exportType, ['csv', 'xlsx'])) {
            $this->response([
                'code' => BAD_REQUEST,
            ]);
        }
        unset($data['exportType']);

        $data = reformat_get_data($data, $this->exceptValidateKeys);
        if($this->Model->getCnt($data) === 0) {
            $this->response([
                'code' => EMPTY_CONTENT,
            ]);
        }

        /**
         * 3.Prepare Data
         */
        $config = array_values(array_filter($this->setFormColumns(snakeize($this->router->class)), function ($item) {
            return !(
                $item['type'] === 'common' ||
                $item['type'] === 'file' ||
                $item['subtype'] === 'identifier'
            );
        }));

        // heads
        $heads['num'] = 'No.';
        foreach ($config as $item) $heads[$item['field']] = lang($item['label']);

        // dateFormats
        $dateFormats = array_values(array_map(function ($item) {
            return $item['field'];
        }, array_filter($config, function ($item) {
            return $item['type'] === 'date';
        })));

        // optionFormats
        $optionFiltered = array_filter($config, function ($item) {
            return !empty($item['option_attributes']);
        });
        $optionConfigs = array_combine(
            array_column($optionFiltered, 'field'),
            array_column($optionFiltered, 'option_attributes'),
        );
        $optionFields = array_keys($optionConfigs);

        $chkboxFormats = array_column(array_filter($optionFiltered, function ($item) {
            return $item['type'] === 'checkbox';
        }), 'field');

        // data
        $list = $this->transformList($this->Model->getList(
            $data['select'] ?? [],
            $data,
        ));

        // dataset
        $i = 0;
        $dataset = array_reduce($list, function ($carry, $item) use ($heads, $optionFields, $optionConfigs, $chkboxFormats, &$i) {
            $result['num'] = ++$i;

            foreach(array_keys($heads) as $field) {
                if($field === 'num') continue;

                $value = $item->{$field};
                if(in_array($field, $optionFields)) {
                    $optionConfig = $optionConfigs[$field];
                    $options = $this->getOptions($field, $optionConfig);
                    if(in_array($field, $chkboxFormats)) {
                        $seperator = $optionConfig['seperator'] ?? ',';
                        $exploded = explode($seperator, $value);
                        $value = '';
                        foreach ($exploded as $i=>$v) {
                            if(!isset($options[$v])) continue;
                            $value .= $options[$v];
                            if($i !== count($exploded) -1) $value .= $seperator.' ';
                        }
                    }else{
                        $value = $options[$value] ?? '';
                    }
                }

                $result[$field] = $value;
            }

            $carry[] = $result;
            return $carry;
        }, array());

        /**
         * 4.Prepare Folder
         */
        // 저장할 폴더 경로 설정
        $uploadPath = 'public/temps/';
        if (!make_directory($uploadPath)) throw new Exception($this->upload->display_errors(), CREATE_FOLDER_FAIL);
        $filename = APP_NAME.'_'.strtolower($this->router->class).'_'.date('YmdHis').'.'.$exportType;
        $encrypted = strtr($this->encryption->encrypt($filename), [
            '+' => '-',
            '/' => '_',
        ]);
        $fullPath = FCPATH . $uploadPath . $encrypted;

        if(in_array($exportType, ['xls', 'xlsx'])) {
            $this->prepareExcel($heads, $dataset, $fullPath, $dateFormats);
        }else {
            $this->prepareCSV($heads, $dataset, $fullPath);
        }

        $this->response([
            'code' => FILE_CREATED,
            'data' => [
                'filename' => $encrypted,
            ]
        ]);
    }

    protected function prepareExcel($heads, $dataset, $filepath, $dateFormats = []): void
    {
        $this->load->library('excel_lib');
        $this->load->helper('excel');
        $objPHPExcel = $this->excel_lib->load();

        // sheet
        $sheet = $objPHPExcel->getActiveSheet();
        $sheet->setTitle(date('Y-m-d'));
        $objPHPExcel->setActiveSheetIndex(0);

        // head
        $maxAlphabet = number_to_alphabet(count(array_keys($heads))-1);
        foreach (array_keys($heads) as $i=>$key) {
            $coord = number_to_alphabet($i);
            $value = $heads[$key];
            $sheet
                ->setCellValue($coord.'1',$value);
        }

        $sheet->getStyle("A1:{$maxAlphabet}1")
            ->getFont()->setBold(true);
        $sheet->getStyle("A1:{$maxAlphabet}1")
            ->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
        $sheet->getStyle("A1:{$maxAlphabet}1")
            ->getFill()->getStartColor()->setRGB('EEEEEE');
        $sheet->getStyle("A1:{$maxAlphabet}1")
            ->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        // body
        foreach ($dataset as $i=>$item) {
            $j = 0;
            foreach ($item as $field=>$value) {
                $coord = number_to_alphabet($j);
                if(in_array($field, $dateFormats)){
                    $diffInSeconds = strtotime($value) - strtotime('1899-12-30');
                    $diffInDays = floor($diffInSeconds / (60 * 60 * 24))+1;
                    $sheet->setCellValue($coord.($i+2), $diffInDays);
                    $sheet->getStyle($coord.($i+2))
                        ->getNumberFormat()
                        ->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_DATE_YYYYMMDD2);
                }else{
                    $sheet->setCellValueExplicit($coord.($i+2), $value, PHPExcel_Cell_DataType::TYPE_STRING);
                }
                $j++;
            }
        }

        $rowMax = strval(count($dataset)+1);

        $range = get_alphabet_range('B', $maxAlphabet);
        foreach ($range as $columnID) {
            $sheet->getColumnDimension($columnID)->setWidth(20);
        }
        $sheet->getStyle('A1:'.$maxAlphabet.$rowMax)->applyFromArray(
            array(
                'width' => 10,
                'alignment' => array(
                    'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
                ),
                'borders' => array(
                    'allborders' => array(
                        'style' => PHPExcel_Style_Border::BORDER_THIN,
                    ),
                ),
            )
        );

        // 엑셀 Writer 생성 (XLSX 형식)
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');

        // 파일 저장
        error_clear_last(); // 이전 에러 초기화

        try {
            $objWriter->save($filepath);
        } catch (Exception $e) {
            $this->response([
                'code' => INTERNAL_SERVER_ERROR,
                'msg' => $e->getMessage()
            ]);
        }

        $last_error = error_get_last();
        if ($last_error) {
            $this->response([
                'code' => INTERNAL_SERVER_ERROR,
                'msg' => $last_error['message']
            ]);
        }
    }

    protected function prepareCSV($heads, $dataset, $filepath): void
    {
        // 예시 데이터 (보통은 DB에서 가져오겠지)
        array_unshift($dataset, $heads);

        // 파일 열기
        $fp = fopen($filepath, 'w');
        if ($fp === false) {
            $this->response([
                'code' => PERMISSION_OR_DISK_ERROR,
            ]);
        }

        // fputcsv 로 한 줄씩 쓰기
        foreach ($dataset as $row) {
            $row = array_values($row);
            fputcsv($fp, $row);  // 기본 구분자: 콤마 (,)
        }

        fclose($fp);
    }

    public function downloadExports_get()
    {
        $message = '';

        $encrypted = $this->input->get('filename');
        if(empty($encrypted)) $message = $this->lang->status(EMPTY_REQUIRED_DATA);

        $fullPath = FCPATH . 'public/temps/' . $encrypted;
        if(!file_exists($fullPath)) $message = $this->lang->status(FILE_NOT_EXIST);

        $encrypted = strtr($encrypted, [
            '-' => '+',
            '_' => '/',
        ]);
        $filename = $this->encryption->decrypt($encrypted);
        if($filename === false) $message = $this->lang->status(WRONG_TOKEN);

        if($message) show_alert('error', $message, true);

        $fileContents = file_get_contents($fullPath);
        @unlink($fullPath);

        force_download($filename, $fileContents, true);
    }

    protected function validateExcelData($data): array
    {
        return $data;
    }

    protected function beforeExcelUpload()
    {
        $json_data = $this->input->raw_input_stream;
        $data = json_decode($json_data, true);

        return $this->validateExcelData($data);
    }

    protected function afterExcelUpload($data)
    {
        if(!property_exists($this, 'Model')) {
            $this->response([
                'code' => MODEL_IS_NOT_DEFINED,
                'data' => $data,
            ], RestController::HTTP_INTERNAL_SERVER_ERROR);
        }

        try {
            $this->Model->addList($data);

            $this->response([
                'code' => DATA_CREATED,
                'data' => [],
            ], RestController::HTTP_CREATED);
        } catch (Exception $e) {
            $this->response([
                'code' => WRITE_FILEDB_FAIL,
                'data' => $data,
            ], RestController::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Common API
     */
    function isMyData_get($key = 0, $model = null)
    {
        $tokenData = $this->validateToken();

        if(!property_exists($this, 'Model')) {
            if(is_null($model)) {
                $this->response([
                    'code' => MODEL_IS_NOT_DEFINED,
                ]);
            }
        }else{
            $model = $this->Model;
        }

        $data = $model->getDataWhere([], $this->getIdentifierData($key, $model->primaryKeyList));

        if(!$data) $this->response(['code' => DATA_NOT_EXIST]);
        if(!$tokenData->is_admin && $data->{CREATED_ID_COLUMN_NAME} !== $tokenData->user_id){
            $this->response(['code' => NO_PERMISSION]);
        }

        $this->response([
            'code' => DATA_PROCESSED,
        ]);
    }

    public function checkDuplicate_get()
    {
        $dto = $this->input->get();
        if($this->checkDuplicate([$dto['field'] => $dto['value']], $this->Model??null, $dto)){
            $this->response([
                'code' => DATA_ALREADY_EXIST,
                'dto' => $dto,
            ]);
        }else{
            $this->response([
                'code' => DATA_AVAILABLE,
                'dto' => $dto,
            ]);
        }
    }

    public function options_get()
    {
        show_404();
    }

    public function reorder_patch()
    {
        $new_index = $this->input->get('new_index') ?? null;
        $file_id = $this->input->get('file_id') ?? null;

        if(!$new_index || !$file_id) {
            $this->response([
                'code' => EMPTY_REQUIRED_DATA,
            ]);
        }
    }

    public function deleteRepeater_patch($key = 0)
    {
        $this->response([
            'code' => DATA_DELETED,
        ]);
    }

    public function deleteFile_patch($key = 0)
    {
        $type = $this->input->get('type') ?? null;
        $file_id = $this->patch('file_id') ?? null;
        if(!$type || !$file_id) $this->response(['code' => EMPTY_REQUIRED_DATA]);

        $this->delFileData(['file_id' => $file_id]);

        $this->response([
            'code' => DATA_DELETED,
        ]);
    }

    public function deleteExcelFile_patch()
    {
        $class = $this->input->patch('class') ?? null;
        if(!$class) $this->response(['code' => EMPTY_REQUIRED_DATA]);

        $filename = $class.'_upload_sample.xlsx';
        $filepath = 'public'.DIRECTORY_SEPARATOR.'sample'.DIRECTORY_SEPARATOR;
        if(!unlink(FCPATH.$filepath.$filename)) $this->response(['code' => INTERNAL_SERVER_ERROR]);

        $this->response([
            'code' => DATA_DELETED,
        ]);
    }

    public function getMethodList_get()
    {
        if($this->flag !== 'admin') show_404();

        if(empty($this->input->get('class'))) $this->response(['code' => EMPTY_REQUIRED_DATA]);

        $list = $this->getMethodList($this->input->get('class'));
        $result = [];
        foreach ($list as $key=>$val) {
            $result[] = [
                'id' => $key,
                'text' => $val,
            ];
        }

        $this->response(['code' => DATA_RETRIEVED, 'data' => $result]);
    }

    public function duplicate_get($key = 0)
    {
        $data = $this->Model->getData([], [
            $this->identifier => $key,
        ]);

        if(!$data) {
            $this->response([
                'code' => DATA_NOT_EXIST,
            ]);
        }else{
            foreach ([$this->identifier, CREATED_ID_COLUMN_NAME, CREATED_DT_COLUMN_NAME, UPDATED_ID_COLUMN_NAME, UPDATED_DT_COLUMN_NAME, DEL_YN_COLUMN_NAME, USE_YN_COLUMN_NAME] as $field) {
                if(property_exists($data, $field)) unset($data->{$field});
            }
            $this->Model->addData((array)$data);

            $this->response([
                'code' => DATA_PROCESSED,
                'msg' => lang('Data replication has been completed')
            ]);
        }
    }
}
