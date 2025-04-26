<?php
defined('BASEPATH') or exit('No direct script access allowed');

class MY_Builder_API extends MY_Controller_API
{
    public string $flag;
    protected string $table = '';
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

        $this->identifier = '';
        $this->listConfigName = $this->listConfigName??'list_'.strtolower($this->router->class).'_config';
        $this->formConfigName = $this->formConfigName??'form_'.strtolower($this->router->class).'_config';
        $this->viewConfigName = $this->viewConfigName??'view_'.strtolower($this->router->class).'_config';
        $this->validateMessages = [];
        $this->validateCallback = [];
        $this->exceptValidateKeys = ['_mode', '_event', '_', 'select', 'format', 'draw', 'pageNo', 'limit', 'searchWord', 'searchCategory', 'filters'];
        $this->transTargetKeys = [];
        $this->indexAPI = true;
        $this->loadConfigs();
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

    protected function loadConfigs()
    {
        if($this->uri->segment(1) === 'api'){
            $this->flag = 'web';
        }else{
            $this->flag = $this->flag??$this->uri->segment(1);
        }

        foreach (['builder_base_config', 'builder_form_config'] as $config) {
            $this->config->load('extra/builder/'.$config, false);
        }

        require_once APPPATH . 'config/extra/builder/builder_base_constants.php';
        $this->load->helper(["builder/builder_web","builder/builder_base","builder/builder_form",]);
        $this->lang->load("builder/base", $this->config->item('language'));

        if(!$this->flag) show_error("Platform flag is not set.");

        foreach (glob(APPPATH . "config/extra/{$this->flag}/*_config.php") as $file) {
            $this->config->load("extra/{$this->flag}/" . substr(basename($file),0,strpos(basename($file),'.')));
        }
        foreach (glob(APPPATH . "config/extra/{$this->flag}/*_constants.php") as $file) {
            require_once $file;
        }
        foreach (glob(APPPATH.'language'.DIRECTORY_SEPARATOR.$this->config->item('language').DIRECTORY_SEPARATOR.$this->flag.DIRECTORY_SEPARATOR.'*_lang.php') as $file) {
            $this->lang->load($this->flag.DIRECTORY_SEPARATOR.str_replace('_lang.php', '', basename($file)), $this->config->item('language'));
        }
    }

    protected function beforeGet()
    {
        $data = [];
        foreach ($this->input->get() as $key=>$val) {
            if(in_array($key, $this->exceptValidateKeys)) continue;
            if(!$val) continue;
            $data['where'][$key] = $this->input->get($key);
        }

        if($this->input->get('filters')) {
            $filters = $this->input->get('filters');
            foreach ($filters as $type => $filter) {
                switch ($type) {
                    case 'where' :
                        foreach ($filter as $key=>$val) {
                            if(!$val) continue;
                            $data['filter']['where'][$key] = $val;
                        }
                        break;
                    case 'like' :
                        if($filter['value']) {
                            $data['filter']['like'] = [
                                'field' => $filter['field']??'',
                                'value' => $filter['value'],
                            ];
                        }
                        break;
                    case 'date' :
                        foreach ($filter as $key=>$val) {
                            if(!$val) continue;
                            $data['filter']['date'][$key] = $val;
                        }
                        break;
                }
            }
        }else{
            $data['filter'] = [];
        }

        if($this->input->get('format') === 'datatable') {
            if($this->input->get('searchWord') && $this->input->get('searchCategory')) {
                $data['filter']['like'][$this->input->get('searchCategory')] = $this->input->get('searchWord');
            }
        }

        $data['select'] = $this->input->get('select');
        return $data;
    }

    protected function afterGet($key, $data)
    {
        empty($key) ? $this->list($data) : $this->view($key);
    }

    protected function list($data = [])
    {
        $data = $this->listBefore($data);

        $list = $this->Model->getList(
            $data['select'] ?? [],
            $data['where'] ?? [],
            $data['like'] ?? [],
            $data['limit'] ?? [],
            $data['order_by'] ?? [],
            $data['filter'] ?? [],
        );

        $this->response([
            'code' => DATA_RETRIEVED,
            'data' => $this->listAfter($list),
            'extra' => $data['extraFields'] ?? [],
        ]);
    }

    protected function listBefore($data)
    {
        return $data;
    }

    protected function listAfter($list)
    {
        foreach ($list as $key=>$item) {
            $list[$key] = $this->viewAfter($item);
        }
        return $list;
    }

    protected function view($key)
    {
        $this->viewBefore($key);

        $data = $this->Model->getData([], [$this->identifier => $key]);

        if(!$data) {
            $this->response([
                'code' => DATA_NOT_EXIST,
                'data' => [],
            ], RestController::HTTP_NOT_FOUND);
        }else{
            $this->response([
                'code' => DATA_RETRIEVED,
                'data' => $this->viewAfter($data),
            ]);
        }
    }

    protected function viewBefore($key)
    {
        $this->checkIdentifierExist($key);
    }

    protected function viewAfter($data)
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
                    $file_dto = $this->Model_File->getList([], ['file_id' => $file_id]);
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

    protected function beforePost($key, $model = null)
    {
        if($key) $this->checkIdentifierExist($key);

        $dto = $this->validate($this->input->post(), $model);

        $this->checkUniqueExist($dto, $model, is_empty($key));

        if(count($this->fileList) > 0) $dto = $this->uploadFileInList($dto);

        return $dto;
    }

    protected function afterPost($key, $dto)
    {
        if($key) {
            $this->modData($key, $dto, true);
        }else{
            $this->addData($dto, false);
        }
    }

    protected function beforePut($key, $model = null)
    {
        if($key) $this->checkIdentifierExist($key);

        $dto = $this->validate($this->put(), $model);

        if($key) $this->checkUniqueExist($dto, $model, false);

        return $dto;
    }

    protected function afterPut($key, $dto)
    {
        if($key) {
            $this->modData($key, $dto, true);
        }else{
            if($this->Model->primaryKeyList) {
                $where = [];
                foreach ($this->Model->primaryKeyList as $key) {
                    if(array_key_exists($key, $dto)) {
                        $where[$key] = $dto[$key];
                    }
                }
                if($this->Model->getCnt($where)){
                    $this->Model->modData($dto, $where, true);
                    $this->response([
                        'code' => DATA_EDITED,
                    ]);
                }else{
                    $this->Model->addData($dto, true);
                    $this->response([
                        'code' => DATA_CREATED,
                    ], RestController::HTTP_CREATED);
                }
            }else{
                $this->Model->addData($dto, true);
                $this->response([
                    'code' => DATA_CREATED,
                ], RestController::HTTP_CREATED);
            }
        }
    }


    protected function beforePatch($key, $model = null)
    {
        $this->checkIdentifierExist($key);

        return $this->validate($this->patch());
    }

    protected function afterPatch($key, $dto)
    {
        $this->modData($key, $dto, true);
    }

    protected function beforeDelete($key)
    {
        $this->checkIdentifierExist($key);
    }

    protected function afterDelete($key)
    {
        $this->delData($key, true);
    }


    /* --------------------------------------------------------------- */
    protected function addData($dto, $bool)
    {
        $dto = $this->beforeAddData($dto);

        $dto[$this->identifier] = $this->Model->addData($dto, $bool);

        $dto = $this->afterAddData($dto);

        $this->response([
            'code' => DATA_CREATED,
            'data' => [$this->identifier => $dto[$this->identifier]],
        ], RestController::HTTP_CREATED);
    }

    protected function beforeAddData($dto)
    {
        return $dto;
    }

    protected function afterAddData($dto)
    {
        return $dto;
    }

    protected function modData($key, $dto, $bool)
    {
        $dto = $this->beforeModData($key, $dto);

        $this->Model->modData($dto, [$this->identifier => $key], $bool);

        $dto = $this->afterModData($key, $dto);

        $this->response([
            'code' => DATA_EDITED,
            'data' => [$this->identifier => $key],
        ]);
    }

    protected function beforeModData($key, $dto)
    {
        return $dto;
    }

    protected function afterModData($key, $dto)
    {
        return $dto;
    }

    protected function delData($key, $bool)
    {
        $this->beforeDelData($key);

        $this->Model->delData([$this->identifier => $key], $bool);

        $this->afterDelData($key);

        $this->response([
            'code' => DATA_DELETED,
        ]);
    }

    protected function beforeDelData($key)
    {

    }

    protected function afterDelData($key)
    {

    }

    /* --------------------------------------------------------------- */

    protected function validate($data = [], $model = null, $validate = true, $configName = '')
    {
        if($validate) $this->validateFormRules($configName);

        if($this->input->method() === 'post') {
            if(!$model) $model = $this->Model;

            foreach ($this->defaultList as $field=>$default) {
                if(!isset($data[$field])) $data[$field] = $default;
            }

            return $this->validateManually(
                $data,
                $model,
                $this->validateMessages,
                $this->validateCallback,
            );
        }else{
            return $data;
        }
    }

    protected function validateFormRules($configName = '')
    {
        $method = __METHOD__;
        $errors = [];
        $config = $configName?$this->config->get($configName):$this->formConfig;

        // base rule validation
        $config = array_map(function ($item) {
            if(!array_key_exists('rules', $item) || !$item['rules']) $item['rules'] = 'do_nothing';
            if(is_empty($item, 'group')) $item['group'] = 'base';
            return $item;
        }, $config);
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
                foreach ($this->input->post_put() as $field => $value) {
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
                    $targetData = $this->input->post_put($group);
                }else{
                    foreach ($this->input->post_put() as $field => $value) {
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
                    $exp = $ruleData['exp'];
                    $flags = $ruleData['flags'];
                    if (preg_match("/$exp/$flags", $item['rules'], $matches)) {
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
                            $errorMsg = 'notEmpty';
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

        foreach ($data as $key => $val) {
            $columnList = array_unique(array_merge($dto->notNullList, $dto->nullList));

            if(!in_array($key, $columnList)){
                unset($data[$key]);
                continue;
            }

            if(!is_object($val) && !is_array($val)) $data[$key] = trim(preg_replace('/\s\s+/', ' ', $val));
            if(in_array($key, $dto->strList) && empty($val)) $data[$key] = '';
            if(in_array($key, $dto->intList) && empty($val)) $data[$key] = 0;
            if(in_array($key, $dto->intList) && $data[$key]) $data[$key] = (int)$data[$key];

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
        }

        return $data;
    }

    protected function uploadFileInList($dto, $model = null)
    {
        if(!$model) $model = $this->Model;
        $key = null;
        try {
            $uploadPath = set_realpath('public/uploads/'.$this->router->class.'/'.date('Y').'/');
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
                    ]
                ]
            ], RestController::HTTP_INTERNAL_SERVER_ERROR);
        }

        if(!in_array($this->input->method(), ['get', 'post'])) return;

        if($this->setConfig) {
            $this->listConfig = $this->config->get($this->listConfigName, [], false);
            $this->formConfig = $this->config->get($this->formConfigName, [], false);
            $this->viewConfig = $this->config->get($this->viewConfigName, [], false);

            if($this->input->method === 'get') {
                if(is_empty($this->listConfig)) {
                    $this->listConfig = array_map(
                        function($item) {
                            $attributes = $item['list_attributes'] ?? [];
                            $label = is_empty($attributes, 'label')?$item['label']:$attributes['label'];
                            if(sscanf($label, 'lang:%s', $line) === 1) $label = $line;
                            if($this->lang->line_exists($label.'_list')) $label = $label.'_list';
                            return array_merge(
                                $this->config->get('builder_form_base_list_attributes', []),
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

    protected function checkIdentifierExist($key, $model = null)
    {
        if(!$key) return;
        if(!$model) $model = $this->Model;
        $this->checkCnt([$model->identifier => $key], $model);
    }

    protected function checkUniqueExist($dto, $model = null, $add = true)
    {
        if(!$model) $model = $this->Model;
        if(count($model->uniqueKeyList) > 0){
            foreach ($model->uniqueKeyList as $key) {
                if(!array_search($key, array_column($this->formConfig, 'field'))) continue;
                if(!$add && !array_key_exists($key, $dto)) continue;

                $idx = array_search($key, array_column($this->formConfig, 'field'));
                $config = $this->formConfig[$idx];
                if(!$add && !$config['form_attributes']['editable']) continue;

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
        if(!$model) $model = $this->Model;
        if($model->getCnt($dto) === 0){
            $this->response([
                'code' => DATA_NOT_EXIST,
            ], RestController::HTTP_NOT_FOUND);
        }
    }

    public function excelValidate_post()
    {
        $this->beforeExcelUpload();

        $this->response([
            'code' => DATA_AVAILABLE,
        ]);
    }

    public function excelUpload_post()
    {
        $data = $this->beforeExcelUpload();

        $this->afterExcelUpload($data);
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
    function isMyData_get($key, $model = null)
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

        $data = $model->getData([], [
            $model->identifier => $key,
        ]);

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
        if($this->checkDuplicate([$dto['field'] => $dto['value']])){
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

        $filename = $class.'_upload_sample.xlsx';;
        $filepath = 'public'.DIRECTORY_SEPARATOR.'sample'.DIRECTORY_SEPARATOR;
        if(!unlink(FCPATH.$filepath.$filename)) $this->response(['code' => INTERNAL_SERVER_ERROR]);

        $this->response([
            'code' => DATA_DELETED,
        ]);
    }
}