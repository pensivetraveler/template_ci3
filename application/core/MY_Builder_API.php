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

    public string $flag = '';
    public string $routeTitle = '';
    public string $mode = '';
    public string $submode = '';
    public string $apiRoute;
    public string $apiBaseUri;
    public string $apiUri;

    protected string $table = '';
    protected array $idData = [];
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
    protected bool $noIndexMethod = false;

    public function __construct()
    {
        parent::__construct();

        $this->validateModel('Model_User_Logs');

        if($this->listConfigName === '') $this->listConfigName = 'list_'.strtolower($this->router->class).'_config';
        if($this->formConfigName === '') $this->formConfigName = 'form_'.strtolower($this->router->class).'_config';
        if($this->viewConfigName === '') $this->viewConfigName = 'view_'.strtolower($this->router->class).'_config';
        $this->validateMessages = [];
        $this->validateCallback = [];
        $this->exceptValidateKeys = ['_mode', '_event', '_', 'select', 'format', 'draw', 'pageNo', 'limit', 'searchWord', 'searchCategory', 'filters'];
        $this->transTargetKeys = [];

        if($this->uri->segment(1) === 'api'){
            $this->flag = 'web';
        }else{
            $this->flag = $this->uri->segment(1);
        }

        $this->loadConfigs(['builder_base_config', 'builder_route_config', 'builder_form_config', 'builder_list_config', 'builder_view_config']);

        if($this->router->method === 'index') {
            if($this->noIndexMethod) show_404();
        }
    }

    protected function beforeGet($key = 0, $strict = false): array
    {
        list($key, $data) = parent::beforeGet($key);

        return [
            $this->checkIdentifierExist($key, $strict),
            reformat_get_data($data, $this->exceptValidateKeys)
        ];
    }

    protected function afterGet($key, $data = [])
    {
        if(!property_exists($this, 'Model')) {
            $this->responseError([
                'location' => __METHOD__,
                'param' => [
                    'class' => $this->router->class,
                ],
                'msg' => "Model is not defined",
            ]);
        }

        if(count(array_keys($this->idData))) {
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

    protected function executeList($data, $model = null): array
    {
        if($model === null) {
            if(!property_exists($this, 'Model')) {
                trigger_error(__METHOD__." : Model Property is not defined.", E_CORE_ERROR);
            }
            $model = $this->Model;
        }

        $list = $model->getList(
            $data['select'] ?? [],
            $data,
        );

        $data['list'] = $this->transformList($list);

        return $data;
    }

    protected function afterList($data, $isResponse = true): array
    {
        if($isResponse) {
            $data = $this->beforeResponse($data, true);

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
            $data = $this->beforeResponse($data, true);

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

        $this->checkIdentifierExist($key);

        foreach ($this->defaultList as $field=>$default) {
            if(!isset($data[$field])) $data[$field] = $default;
        }

        foreach ($this->getModelPipeline() as $modelName) {
            if (property_exists($this, $modelName)) {
                $exceptRequiredKeys = $this->getGeneratedForeignKeyFields($this->{$modelName}, $modelName);

                $this->validate($data, $this->{$modelName}, true, '', $exceptRequiredKeys);
                $this->checkUniqueExist($data, $this->{$modelName});
            }
        }

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

        $this->checkIdentifierExist($key);

        foreach ($this->defaultList as $field=>$default) {
            if(!isset($data[$field])) $data[$field] = $default;
        }

        foreach ($this->getModelPipeline() as $modelName) {
            if (property_exists($this, $modelName)) {
                $exceptRequiredKeys = $this->getGeneratedForeignKeyFields($this->{$modelName}, $modelName);

                $this->validate($data, $this->{$modelName}, true, '', $exceptRequiredKeys);
                $this->checkUniqueExist($data, $this->{$modelName});
            }
        }

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
        $this->checkIdentifierExist($key);

        return parent::beforePatch($key);
    }

    protected function afterPatch($key, $data = [])
    {
        $this->modify($key, $data, true);
    }

    protected function beforeDelete($key = 0): array
    {
        $this->checkIdentifierExist($key);

        return parent::beforeDelete($key);
    }

    protected function afterDelete($key, $data = [])
    {
        $this->remove($key, $data, true);
    }


    /* --------------------------------------------------------------- */
    protected function setData($data = [], $model = null)
    {
        if(is_null($model)) $model = $this->Model;

        $checkboxes = array_values(array_filter($this->formConfig, function ($item) {
            return $item['type'] === 'checkbox';
        }));

        $onoff = array_values(array_filter($checkboxes, function ($item) {
            return $item['subtype'] === 'onoff';
        }));

        $columnList = array_unique(array_merge($model->notNullList, $model->nullList));
        if($model->isDelYn) $columnList[] = DEL_YN_COLUMN_NAME;
        if($model->isUseYn) $columnList[] = USE_YN_COLUMN_NAME;

        foreach ($data as $field => $value) {
            if(!in_array($field, $columnList)){
                unset($data[$field]);
                continue;
            }

            if(!is_object($value) && !is_array($value)) $data[$field] = trim(preg_replace('/\s\s+/', ' ', $value));
            if(in_array($field, $model->strList) && empty($value)) $data[$field] = '';
            if(in_array($field, $model->intList)) {
                if(array_key_exists($field, $data)) {
                    $data[$field] = (int)$data[$field];
                }else{
                    $data[$field] = in_array($field, $model->notNullList) ? 0 : null;
                }
            }

            switch ($field) {
                case 'gender' :
                    $data[$field] = strtoupper($data[$field]);
                    break;
                case 'password' :
                    if($value) {
                        $data[$field] = $this->encryption->encrypt($value);
                    }else{
                        unset($data[$field]);
                    }
                    break;
            }

            if(in_array($field, array_column($checkboxes, 'field'))) {
                $index = array_search($field, array_column($checkboxes, 'field'));
                $config = $checkboxes[$index];
                if($config['subtype'] === 'onoff') {
                    $data[$field] = $value;
                }else{
                    $seperator = $checkboxes['option_attributes']['seperator'] ?? ',';
                    $data[$field] = join($seperator, $value);
                }
            }
        }

        foreach ($onoff as $config) {
            $field = $config['field'];
            if(!array_key_exists($field, $data)) $data[$field] = 'N';
        }

        return $data;
    }

    protected function setModData($data = [], $model = null)
    {
        if(is_null($model)) $model = $this->Model;

        $data = $this->setData($data, $model);

        $idData = [];
        if($model->isAutoIncrement) {
            $idData[$model->identifier] = $data[$model->identifier];
        }else if(count($model->primaryKeyList) > 0){
            $idData = array_reduce(array_merge($model->primaryKeyList, $model->uniqueKeyList), function ($carry, $item) use ($data) {
                if(isset($data[$item])) {
                    $carry[$item] = $data[$item];
                    unset($data[$item]);
                }
                return $carry;
            }, []);
        }

        return [$data, $idData];
    }

    /* --------------------------------------------------------------- */

    protected function add($dto, $bool)
    {
        $dto = $this->beforeAdd($dto);

        $dto = $this->executeAdd($dto, $bool);

        $this->afterAdd($dto);
    }

    protected function beforeAdd($dto, $model = null): array
    {
        if(is_null($model)) $model = $this->Model;

        if(!$model->isAutoIncrement && count($model->primaryKeyList) > 0) {
            $this->idData = array_reduce(
                array_merge($model->primaryKeyList, $model->uniqueKeyList)
                , function ($carry, $item) use ($dto) {
                    if(isset($dto[$item])) $carry[$item] = $dto[$item];
                    return $carry;
                }
                , []
            );
            if($this->checkDataExist(['where' => $this->idData], $model)) $this->response(['code' => DATA_ALREADY_EXIST]);
        }

        return $dto;
    }

    protected function executeAdd($dto, $bool, $model = null): array
    {
        if(is_null($model)) {
            foreach (['Model_Parent', 'Model', 'Model_Child'] as $modelName) {
                if (property_exists($this, $modelName)) {
                    $targetModel = $this->{$modelName};

                    $dto = $this->applyForeignKeyValues($dto, $targetModel);

                    $params = $this->setData($dto, $targetModel);
                    $params = $this->addData($params, $bool, $targetModel);

                    $dto = array_merge($dto, $params);
                }
            }
        }else{
            $dto = $this->applyForeignKeyValues($dto, $model);
            $dto = $this->setData($dto, $model);
            $dto = $this->addData($dto, $bool, $model);
        }

        return $dto;
    }

    protected function afterAdd($dto)
    {
        $data = $this->beforeResponse($dto);

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

    protected function executeModify($key, $dto, $bool, $model = null): array
    {
        if(is_null($model)) {
            foreach (['Model_Parent', 'Model', 'Model_Child'] as $modelName) {
                if(property_exists($this, $modelName)) {
                    list($params, $idData) = $this->setModData($dto, $this->{$modelName});
                    $this->modData($params, $idData, $bool, $this->{$modelName});
                }
            }
        }else{
            $dto = $this->setData($dto, $model);
            $this->modData($dto, $this->idData, $bool, $model);
        }

        return $dto;
    }

    protected function afterModify($key, $dto)
    {
        $data = $this->beforeResponse($dto);

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
        $baseRow = $this->getDeleteBaseRow();

        $pipeline = array_reverse($this->getModelPipeline());

        foreach ($pipeline as $modelName) {
            if (!property_exists($this, $modelName)) {
                continue;
            }

            $model = $this->{$modelName};

            $idData = $this->makeDeleteIdData($model, $baseRow);

            if (!count($idData)) {
                continue;
            }

            $model->delData($idData, $bool);
        }

        return $data;
    }

    protected function afterRemove($key, $data = [])
    {
        $data = $this->beforeResponse($data);

        $this->response([
            'code' => DATA_DELETED,
        ]);
    }

    protected function loggingUserlog()
    {
        $class = $this->router->class;
        $method = $this->router->method;
        $restMethod = $this->input->method();
        $idExist = !empty($this->idData);
        $userKind = '시스템';
        if($this->session->userdata('user_cd') !== 'USR000') {
            $userKind = $this->getCodeName([
                'cmb_cd' => $this->session->userdata('user_cd')
            ]);
        }

        if(!$this->devMode && $this->session->userdata('is_sys_admin')) return;

        $name = $this->session->userdata('name');
        $pp = $this->josa->__replace($name, '이');
        $subject = "{$name}($userKind){$pp}";
        $object = '';
        $verb = '';
        $message = '';

        switch ($class) {
            case 'auth' :
                $type = 'auth';
                if($method === 'login') {
                    $title = '로그인';
                    $verb = "로그인했습니다";
                }else if($method === 'logout') {
                    $title = '로그아웃';
                    $verb = "로그아웃했습니다";
                }
                break;
            default :
                switch ($restMethod) {
                    case 'get' :
                        $type = 'read';
                        $title = '조회';
                        $verb = "조회했습니다";
                        if($idExist) {
                            $object = "데이터를";
                        }else{
                            $object = "목록을";
                        }
                        break;
                    case 'post' :
                    case 'put' :
                    case 'patch' :
                        $object = "데이터를";
                        if($idExist) {
                            $title = '수정';
                            $type = 'update';
                            $verb = "수정했습니다";
                        }else{
                            $title = '등록';
                            $type = 'create';
                            $verb = "등록했습니다";
                        }
                        break;
                    case 'delete' :
                        $title = '삭제';
                        $type = 'delete';
                        $object = "데이터를";
                        $verb = "삭제했습니다";
                        break;
                }
                break;
        }

        if($object) {
            if($this->routeTitle) {
                $object = $this->routeTitle . ' ' . $object;
            }
        }

        foreach ([$subject, $object, $verb] as $i=>$item) {
            if($item !== '') $message .= $item;
            if($i === 2) {
                $message .= '.';
            }else{
                if($item !== '') $message .= ' ';
            }
        }

        $this->Model_User_Logs->addData([
            'user_id' => $this->session->userdata("user_id"),
            'class' => $this->router->class,
            'method' => $this->router->method,
            'title' => $title ?? '',
            'message' => $message ?? '',
            'log_id' => $this->_insert_id,
            'type' => $type ?? '',
        ]);
    }

    protected function beforeResponse($data, $isFetch = false)
    {
        if($this->mode === 'option') {
            $data['list'] = $this->getOptionsFromDBList($data['list'], $data['render']);
        }else{
            $this->loggingUserlog();
        }

        return $data;
    }

    /* --------------------------------------------------------------- */

    protected function addData($dto, $bool, $model = null)
    {
        if(is_null($model)) $model = $this->Model;

        $result = $model->addData($dto, $bool);

        if ($model->isAutoIncrement && $model->identifier) {
            $dto[$model->identifier] = $result;
            $this->idData[$model->identifier] = $result;
        }

        return $dto;
    }

    protected function modData($dto, $idData, $bool, $model = null)
    {
        if(is_null($model)) $model = $this->Model;

        $model->modData($dto, $idData, $bool);

        return $dto;
    }

    /* --------------------------------------------------------------- */

    protected function validate($data = [], $model = null, $validate = true, $configName = '', $exceptRequiredKeys = [])
    {
        if($validate) $this->validateFormRules($configName, $data);

        $this->validateManually(
            $data,
            $model ?? $this->Model,
            $this->validateMessages,
            $this->validateCallback,
            $exceptRequiredKeys,
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
                    if(!array_key_exists($group, $data)) continue;
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
            $this->responseError($errors, [
                'data' => $this->input->post(),
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

        $this->validateManually($parsed_data, $dto, $msgList, $callbacks);

        return $parsed_data;
    }

    protected function validateManually($data = [], $model = null, $msgList = [], $callbacks = [], $exceptRequiredKeys = [])
    {
        if($model === null || !isset($model->notNullList))
            $this->responseError([
                'location' => __METHOD__,
                'type' => 'required',
            ], [
                'code' => EMPTY_REQUIRED_DATA,
                'data' => $data,
            ]);

        if($this->input->method() === 'post') {
            foreach ($model->notNullList as $key) {
                if ($model->identifier && $key === $model->identifier) {
                    continue;
                }

                if (in_array($key, $model->primaryKeyList, true)) {
                    continue;
                }

                if (in_array($key, $exceptRequiredKeys, true)) {
                    continue;
                }

                if (array_key_exists($key, $callbacks)) {
                    $this->{$callbacks[$key]}();
                    continue;
                }

                $errorMsg = '';
                $value = null;
                $msg = '';

                if(array_key_exists($key, $msgList)) {
                    $msg = $msgList[$key];
                }else{
                    $lang = $model->table ? lang($model->table.'.'.$key) : $key;

                    if($this->request === 'post' && count($model->fileList) > 0 && in_array($key, $model->fileList)){
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

                        if($errorMsg)
                            $msg = $this->josa->__conv("$lang{은} 필수 입력값 입니다.");
                    }
                }

                if($errorMsg) {
                    $this->responseError([
                        'location' => __METHOD__,
                        'param' => $key,
                        'value' => $value,
                        'type' => 'required',
                        'msg' => $errorMsg,
                    ], [
                        'code' => EMPTY_REQUIRED_DATA,
                        'msg' => array_key_exists($key, $msgList) ? $msgList[$key] : $msg,
                        'data' => $data,
                    ]);
                }
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
            $this->responseError([
                'location' => __METHOD__,
                'param' => $key,
                'type' => 'upload error',
            ], [
                'code' => $e->getCode(),
                'msg' => strip_tags($e->getMessage()),
                'data' => $_FILES,
            ]);
        }

        return $dto;
    }

    protected function setProperties($model, $setConfig = null)
    {
        $this->identifier = $model->identifier;
        $this->fileList = $model->fileList;
        $this->primaryKeyList = $model->primaryKeyList;
        $this->uniqueKeyList = $model->uniqueKeyList;

        if(!in_array($this->input->method(), ['get', 'post'])) return;

        if(is_null($setConfig)) $setConfig = $this->setConfig;

        if($setConfig) {
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
                    $this->responseError([
                        'location' => __METHOD__,
                        'msg' => "Validation Rules Config For $this->formConfigName Is Empty",
                    ], [
                        'data' => $this->input->request(),
                    ]);
                }
            }
        }
    }

    protected function checkIdentifierExist($key = 0, $strict = false, $model = null): array
    {
        if (!$model && property_exists($this, 'Model')) {
            $model = $this->Model;
        }

        if (is_null($model)) {
            if ($strict) {
                $this->responseError([
                    'location' => __METHOD__,
                    'param' => [
                        'class' => $this->router->class,
                    ],
                    'msg' => "Model is not defined",
                ]);
            }else{
                return $this->idData;
            }
        }

        $this->idData = [];

        if ($model->isAutoIncrement) {
            if (!$model->identifier) {
                if ($strict) {
                    $this->responseError([
                        'location' => __METHOD__,
                        'param' => [
                            'class' => $this->router->class,
                        ],
                        'msg' => "Identifier is not defined",
                    ]);
                }
            }

            $value = $key ?: $this->input->get($model->identifier);

            if ($value !== null && $value !== '') {
                $this->idData[$model->identifier] = $value;
            }

            if ($strict && !count($this->idData)) {
                $this->responseError([
                    'location' => __METHOD__,
                    'param' => [
                        'class' => $this->router->class,
                    ],
                    'msg' => "Identifier value is missing",
                ]);
            }

            return $this->idData;
        }

        if (count($model->primaryKeyList) > 0) {
            $found = [];

            foreach ($model->primaryKeyList as $field) {
                $value = $this->input->get($field);

                if ($value !== null && $value !== '') {
                    $found[$field] = $value;
                }
            }

            if (count($found) === count($model->primaryKeyList)) {
                $this->idData = $found;
                return $this->idData;
            }

            if (count($found) > 0 && $strict) {
                $this->responseError([
                    'location' => __METHOD__,
                    'param' => [
                        'table' => $model->table,
                    ],
                    'msg' => "ID Field and Values is not equivalent counts",
                ]);
            }

            return [];
        }

        if ($strict) {
            $this->responseError([
                'location' => __METHOD__,
                'param' => [
                    'table' => $model->table,
                ],
                'msg' => "There\'s No Columns for Identifying",
            ]);
        }

        return [];
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

    protected function checkUniqueExist($dto, $model = null)
    {
        $modeAdd = $this->mode === 'add';

        if(is_null($model)) $model = $this->Model;
        if(count($model->uniqueKeyList) > 0){
            foreach ($model->uniqueKeyList as $key) {
                if(!array_search($key, array_column($this->formConfig, 'field'))) continue;
                if(!$modeAdd && !array_key_exists($key, $dto)) continue;

                $idx = array_search($key, array_column($this->formConfig, 'field'));
                $config = $this->formConfig[$idx];
                if(!$modeAdd && (!array_key_exists('editable', $config['form_attributes']) || !$config['form_attributes']['editable'])) continue;

                $isIncludeDeleted = false;
                if(array_key_exists('form_attributes', $this->formConfig[$idx]) && !is_empty($this->formConfig[$idx]['form_attributes'], 'check_delete')) {
                    $isIncludeDeleted = $config['form_attributes']['check_delete'];
                }

                if($this->checkDuplicate([$key => $dto[$key]], $model, !$modeAdd?$dto:[], $isIncludeDeleted)){
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

    public function validateExcel_post(): void
    {
        $this->beforeExcelUpload();

        $this->response([
            'code' => DATA_AVAILABLE,
        ]);
    }

    public function uploadExcel_post(): void
    {
        $data = $this->beforeExcelUpload();

        $this->afterExcelUpload($data);
    }

    protected function getExportsConfig(): array
    {
        $className = snakeize($this->router->class);
        if($this->config->get('form_'.$className.'_config', [], false)) {
            $columns = $this->setFormColumns(snakeize($this->router->class));
        }else{
            $config = $this->config->get('list_'.$className.'_config', [], false);
            $columns = array_reduce($config, function($carry, $item) {
                if(isset($item['field']) || $item['type'] === 'common') {
                    $carry[] = $this->setFormColumn($item);
                }
                return $carry;
            }, []);
        }

        if(empty($columns)) {
            return [];
        }

        return array_values(array_filter($columns, function ($item) {
            return !(
                $item['type'] === 'common' ||
                $item['type'] === 'file' ||
                $item['subtype'] === 'identifier'
            );
        }));
    }

    protected function getExportsHeader($config): array
    {
        $header['num'] = 'No.';
        foreach ($config as $item) $header[$item['field']] = lang($item['label']);
        return $header;
    }

    protected function getExportsDataTypes($config): array
    {
        // num formats
        $numFormats = array_values(array_map(function ($item) {
            return $item['field'];
        }, array_filter($config, function ($item) {
            return $item['type'] === 'int';
        })));

        // num formats
        $floatFormats = array_values(array_map(function ($item) {
            return $item['field'];
        }, array_filter($config, function ($item) {
            return $item['type'] === 'float';
        })));

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

        $checkboxFormats = array_column(array_filter($optionFiltered, function ($item) {
            return $item['type'] === 'checkbox';
        }), 'field');

        return [
            'int' => $numFormats,
            'float' => $floatFormats,
            'date' => $dateFormats,
            'option' => [
                'fields' => $optionFields,
                'config' => $optionConfigs,
            ],
            'checkbox' => $checkboxFormats,
        ];
    }

    protected function getExportsDataSource($params): array
    {
        return $this->transformList($this->Model->getList(
            $params['select'] ?? [],
            $params,
        ));
    }

    protected function getExportsDataset($data, $header, $formats): array
    {
        // data
        $source = $this->getExportsDataSource($data);

        // dataset
        $i = 0;
        return array_reduce($source, function ($carry, $item) use ($header, $formats, &$i) {
            $result['num'] = ++$i;

            foreach(array_keys($header) as $field) {
                if($field === 'num') continue;

                $value = $item->{$field};
                if(in_array($field, $formats['option']['fields'])) {
                    $optionConfig = $formats['option']['config'][$field];
                    $options = $this->getOptions($field, $optionConfig);
                    if(in_array($field, $formats['checkbox'])) {
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
    }

    protected function addExtraDataset($dataset): array
    {
        return $dataset;
    }

    protected function prepareExportsPath($exportType): string
    {
        $uploadPath = 'public/temps/';
        if (!make_directory($uploadPath)) throw new Exception($this->upload->display_errors(), CREATE_FOLDER_FAIL);
        $filename = APP_NAME.'_'.strtolower($this->router->class).'_'.date('YmdHis').'.'.$exportType;
        $encrypted = strtr($this->encryption->encrypt($filename), [
            '+' => '-',
            '/' => '_',
        ]);
        return FCPATH . $uploadPath . $encrypted;
    }

    public function prepareExports_get(): void
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
//        if($this->Model->getCnt($data) === 0) {
//            $this->response([
//                'code' => EMPTY_CONTENT,
//            ]);
//        }

        /**
         * 3.Prepare Data
         */
        $config = $this->getExportsConfig();

        // heads
        $header = $this->getExportsHeader($config);

        // types
        $formats = $this->getExportsDataTypes($config);

        // dataset
        $dataset = $this->getExportsDataset($data, $header, $formats);

        // extra
        $dataset = $this->addExtraDataset($dataset);

        /**
         * 4.Prepare Folder
         */
        try {
            // 저장할 폴더 경로 설정
            $filePath = $this->prepareExportsPath($exportType);

            if(in_array($exportType, ['xls', 'xlsx'])) {
                $this->prepareExcel($header, $dataset, $filePath, $formats);
            }else {
                $this->prepareCSV($header, $dataset, $filePath);
            }

            $this->response([
                'code' => FILE_CREATED,
                'data' => [
                    'filename' => basename($filePath),
                ]
            ]);
        } catch (Exception $e) {
            $this->response([
                'code' => INTERNAL_SERVER_ERROR,
            ]);
        }
    }

    protected function setExcelSheetStyle($sheet, $maxAlphabet, $rowMax): void
    {
        $range = get_alphabet_range('B', $maxAlphabet);
        foreach ($range as $columnID) {
            $sheet->getColumnDimension($columnID)->setWidth(20);
        }
        $sheet->getStyle('A1:'.$maxAlphabet.$rowMax)->applyFromArray(
            array(
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
    }

    protected function prepareExcel($header, $dataset, $filepath, $formats = []): void
    {
        $this->load->library('excel_lib');
        $this->load->helper('excel');
        $objPHPExcel = $this->excel_lib->load();

        // sheet
        $sheet = $objPHPExcel->getActiveSheet();
        $sheet->setTitle(date('Y-m-d'));
        $objPHPExcel->setActiveSheetIndex(0);

        // head
        $maxAlphabet = number_to_alphabet(count(array_keys($header))-1);
        foreach (array_keys($header) as $i=>$key) {
            $coord = number_to_alphabet($i);
            $value = $header[$key];
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
                $this->setExcelValue($sheet, $coord.($i+2), $field, $value, $formats);
                $j++;
            }
        }

        $rowMax = strval(count($dataset)+1);

        $this->setExcelSheetStyle($sheet, $maxAlphabet, $rowMax);

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

    protected function setExcelValue($sheet, $location, $field, $value, $formats)
    {
        if(in_array($field, $formats['date'])){
            if(!$value) {
                $sheet->setCellValue($location, '');
            }else{
                $diffInSeconds = strtotime($value) - strtotime('1899-12-30');
                $diffInDays = floor($diffInSeconds / (60 * 60 * 24))+1;
                $sheet->setCellValue($location, $diffInDays);
                $sheet->getStyle($location)
                    ->getNumberFormat()
                    ->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_DATE_YYYYMMDD2);
            }
        }elseif(
            in_array($field, $formats['int'])
            ||
            in_array($field, $formats['float'])
        ) {
            if(!$value) $value = 0;

            $sheet->setCellValueExplicit(
                $location,
                $value,
                PHPExcel_Cell_DataType::TYPE_NUMERIC
            );

            if(in_array($field, $formats['float'])) {
                $sheet->getStyle($location)
                    ->getNumberFormat()
                    ->setFormatCode('0.00');
            }
        }else {
            if(!$value) $value = '';

            $sheet->setCellValueExplicit($location, $value, PHPExcel_Cell_DataType::TYPE_STRING);
        }
    }

    protected function prepareCSV($header, $dataset, $filepath): void
    {
        // 예시 데이터 (보통은 DB에서 가져오겠지)
        array_unshift($dataset, $header);

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

    public function downloadExports_get(): void
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

    protected function beforeExcelUpload(): array
    {
        $json_data = $this->input->raw_input_stream;
        $data = json_decode($json_data, true);

        return $this->validateExcelData($data);
    }

    protected function afterExcelUpload($data): void
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
        if(!$this->session->userdata('is_admin') && $data->{CREATED_ID_COLUMN_NAME} !== $this->session->userdata('user_id')){
            $this->response(['code' => NO_PERMISSION]);
        }

        $this->response([
            'code' => DATA_PROCESSED,
        ]);
    }

    public function checkDuplicate_get()
    {
        $dto = $this->input->get();
        $unique = [$dto['field'] => $dto['value']];
        unset($dto['field'], $dto['value']);
        if($this->checkDuplicate($unique, null, $dto)){
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
        list($key, $data) = $this->beforeGet();
        $this->afterGet($key, $data);
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
        $file_id = $this->input->patch('file_id') ?? null;
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

    protected function getModelPipeline(): array
    {
        return ['Model_Parent', 'Model', 'Model_Child'];
    }

    protected function getGeneratedForeignKeyFields(object $model, string $currentModelName): array
    {
        if (!property_exists($model, 'foreignKeyList')) {
            return [];
        }

        $fields = [];

        foreach ($model->foreignKeyList as $field => $config) {
            if ($this->isForeignKeyGeneratedByPreviousModel($field, $model, $currentModelName)) {
                $fields[] = $field;
            }
        }

        return $fields;
    }

    protected function isForeignKeyGeneratedByPreviousModel(string $field, object $model, string $currentModelName): bool
    {
        if (
            !property_exists($model, 'foreignKeyList') ||
            !isset($model->foreignKeyList[$field])
        ) {
            return false;
        }

        $fk = $model->foreignKeyList[$field];

        $pipeline = $this->getModelPipeline();
        $currentIndex = array_search($currentModelName, $pipeline, true);

        if ($currentIndex === false || $currentIndex === 0) {
            return false;
        }

        $previousModelNames = array_slice($pipeline, 0, $currentIndex);

        foreach ($previousModelNames as $previousModelName) {
            if (!property_exists($this, $previousModelName)) {
                continue;
            }

            $previousModel = $this->{$previousModelName};

            $fkModelMatches = isset($fk['model']) && $fk['model'] === get_class($previousModel);
            $fkTableMatches = isset($fk['table']) && $fk['table'] === $previousModel->table;
            $fkColumnMatches = isset($fk['column']) && $fk['column'] === $previousModel->identifier;

            if (
                $previousModel->isAutoIncrement &&
                ($fkModelMatches || $fkTableMatches) &&
                $fkColumnMatches
            ) {
                return true;
            }
        }

        return false;
    }

    protected function applyForeignKeyValues(array $dto, object $model): array
    {
        if (!property_exists($model, 'foreignKeyList')) {
            return $dto;
        }

        foreach ($model->foreignKeyList as $field => $fk) {
            if (array_key_exists($field, $dto) && $dto[$field]) {
                continue;
            }

            $refColumn = $fk['column'] ?? null;

            if ($refColumn && array_key_exists($refColumn, $dto)) {
                $dto[$field] = $dto[$refColumn];
            }
        }

        return $dto;
    }

    protected function getDeleteBaseRow()
    {
        if (!count($this->idData)) {
            show_error('getDeleteBaseRow : idData is empty');
        }

        $row = $this->Model->getDataWhere([], $this->idData);

        if (!$row) {
            $this->response([
                'code' => DATA_NOT_EXIST,
            ]);
        }

        return $row;
    }

    protected function makeDeleteIdData(object $model, $baseRow): array
    {
        $idData = [];

        if ($model->isAutoIncrement) {
            if (!$model->identifier) {
                return [];
            }

            if (property_exists($baseRow, $model->identifier)) {
                $idData[$model->identifier] = $baseRow->{$model->identifier};
            }

            return $idData;
        }

        foreach ($model->primaryKeyList as $field) {
            if (property_exists($baseRow, $field)) {
                $idData[$field] = $baseRow->{$field};
            }
        }

        return $idData;
    }

    public function _remap($object_called, $arguments = [])
    {
        $this->mode = $this->input->get('_mode') ?? '';
        $this->submode = $this->input->get('_submode') ?? '';
        $this->apiBaseUri = base_url($this->flag . '/' . $this->apiRoute);
        $this->apiUri = $this->apiBaseUri . '/' . $this->router->class;

        parent::_remap($object_called, $arguments);
    }
}
