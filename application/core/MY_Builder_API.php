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

    /**
     * 모델 컬럼·설정 필드가 아닌데 요청 조건으로 받아야 하는 키 (hook에서 읽는 값 등)
     * 예: protected array $allowedRequestFields = ['company_id'];
     */
    protected array $allowedRequestFields = [];

    /**
     * 요청 조건으로 절대 받지 않는 컬럼 (값 추측·노출 방지). 테이블 접두어가 붙어도 막는다.
     */
    protected array $deniedRequestFields = ['password', 'user_api_key', 'api_key', 'token', 'secret', 'salt'];

    /**
     * 응답에서 항상 빼는 컬럼 (목록·상세 모두). 비밀번호 해시 등.
     */
    protected array $hiddenResponseFields = ['password'];

    /**
     * 목록 응답·내보내기에서 빼는 컬럼. 상세(수정 폼)에서는 보여 줘야 하는 값(API 키 등).
     */
    protected array $hiddenListFields = ['user_api_key', 'api_key', 'token', 'secret', 'salt'];

    public function __construct()
    {
        parent::__construct();

        $this->validateModel('Model_User_Logs');

        $this->validateMessages = [];
        $this->validateCallback = [];
        $this->exceptValidateKeys = ['_mode', '_submode', '_event', '_', 'select', 'format', 'draw', 'pageNo', 'limit', 'searchWord', 'searchCategory', 'filters'];
        $this->transTargetKeys = [];

        // /{API 폴더}/... 로 직접 들어오면 web 플랫폼, /{플랫폼}/{API 폴더}/... 면 그 플랫폼
        if(in_array($this->uri->segment(1), $this->router->routes['api_folders'] ?? ['api'], true)){
            $this->flag = 'web';
        }else{
            $this->flag = $this->uri->segment(1);
        }

        $this->loadConfigs(['builder_base_config', 'builder_route_config', 'builder_form_config', 'builder_list_config', 'builder_view_config']);

        // 설정 이름은 WEB과 같은 규칙(Resource_loader, builder_config_name())
        if($this->listConfigName === '') $this->listConfigName = $this->resourceLoader->entityConfigName('list', $this->router->class);
        if($this->formConfigName === '') $this->formConfigName = $this->resourceLoader->entityConfigName('form', $this->router->class);
        if($this->viewConfigName === '') $this->viewConfigName = $this->resourceLoader->entityConfigName('view', $this->router->class);

        if($this->router->method === 'index') {
            if($this->noIndexMethod) show_404();
        }
    }

    protected function beforeGet($key = 0, $strict = false): array
    {
        list($key, $data) = parent::beforeGet($key);

        return [
            $this->checkIdentifierExist($key, $strict),
            $this->sanitizeRequestConditions(reformat_get_data($data, $this->exceptValidateKeys))
        ];
    }

    /**
     * 요청 조건(where·filters)에 쓸 수 있는 필드 목록.
     * - 메인 Model 컬럼: 'col', '{table}.col'
     * - Model_Parent·Model_Child 컬럼: '{table}.col' 만 (조건은 메인 테이블에 붙으므로 'col'로 받으면 SQL 오류)
     * - form/list/view 설정 필드: 테이블 접두어가 있거나 메인 Model 컬럼일 때만
     * - filter 설정 필드(메서드별 이름 포함), $allowedRequestFields: 그대로
     * - 민감 필드($deniedRequestFields, form type password)는 항상 제외
     */
    protected function getAllowedRequestFields(): array
    {
        $fields = $this->allowedRequestFields;
        $mainColumns = [];

        foreach ($this->getModelPipeline() as $modelName) {
            if(!property_exists($this, $modelName) || !method_exists($this->{$modelName}, 'getRequestColumnList')) continue;
            $model = $this->{$modelName};
            $columns = $model->getRequestColumnList();
            if($modelName === 'Model') {
                $mainColumns = $columns;
                $fields = array_merge($fields, $columns);
            }
            foreach ($columns as $column) $fields[] = $model->table.'.'.$column;
        }

        foreach ([$this->formConfig, $this->listConfig, $this->viewConfig] as $config) {
            if(!is_array($config)) continue;
            foreach (array_column($config, 'field') as $field) {
                if(is_string($field) && (strpos($field, '.') !== false || in_array($field, $mainColumns, true))) $fields[] = $field;
            }
        }

        // filter 설정은 WEB(setConfigList)처럼 메서드별 이름도 본다 (예: logs/errors → filter_logs_errors_config)
        $class = builder_config_name($this->router->class);
        $method = builder_config_name($this->router->method);
        foreach (array_unique(["filter_{$class}_{$method}_config", "filter_{$class}_config", "filter_{$method}_config"]) as $name) {
            $config = $this->config->get($name, [], false);
            if(is_array($config)) $fields = array_merge($fields, array_column($config, 'field'));
        }

        $denied = array_merge($this->deniedRequestFields, array_column(array_filter($this->formConfig ?: [], function ($item) {
            return ($item['type'] ?? '') === 'password';
        }), 'field'));

        return array_values(array_unique(array_filter($fields, function ($field) use ($denied) {
            if(!is_string($field) || $field === '') return false;
            $column = strpos($field, '.') !== false ? substr($field, strrpos($field, '.') + 1) : $field;
            return !in_array($column, $denied, true);
        })));
    }

    /**
     * 요청에서 온 조건 중 허용 목록에 없는 필드를 조용히 뺀다 (오류 로그 화면에 쌓이지 않도록 기록하지 않음).
     * 모르는 키가 그대로 WHERE 컬럼이 되면 SQL 오류(500, 개발 환경에서는 쿼리 노출)가 나고,
     * 임의 컬럼(password 등)으로 조회·검색할 수 있게 되기 때문이다. hook에서 직접 넣는 조건은 영향 없음.
     */
    protected function sanitizeRequestConditions(array $data): array
    {
        $allowed = array_flip($this->getAllowedRequestFields());
        $isAllowed = function ($field) use ($allowed) {
            return is_string($field) && isset($allowed[$field]);
        };

        foreach (array_keys($data['where'] ?? []) as $field) {
            if(!$isAllowed($field)) {
                unset($data['where'][$field]);
            }
        }

        foreach (['where', 'whereIn', 'whereNot'] as $type) {
            foreach (array_keys($data['filter'][$type] ?? []) as $field) {
                if(!$isAllowed($field)) {
                    unset($data['filter'][$type][$field]);
                }
            }
        }

        if(isset($data['filter']['like']) && is_array($data['filter']['like'])) {
            foreach ($data['filter']['like'] as $key => $item) {
                // {field, value} 목록 형식: field 가 비면 문자열 컬럼 전체 검색(setFilterLike)이라 허용
                $field = is_array($item) ? ($item['field'] ?? '') : $key;
                if($field !== '' && !$isAllowed($field)) {
                    unset($data['filter']['like'][$key]);
                }
            }
            // {field, value} 목록이면 번호를 다시 매김
            if(count(array_filter(array_keys($data['filter']['like']), 'is_int')) === count($data['filter']['like'])) {
                $data['filter']['like'] = array_values($data['filter']['like']);
            }
        }

        if(isset($data['filter']['date']) && is_array($data['filter']['date'])) {
            $data['filter']['date'] = array_intersect_key($data['filter']['date'], array_flip(['start_date', 'end_date', 'on_date']));
        }

        return $data;
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
            $this->getListSelect($data['select'] ?? [], $model),
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
            if(is_object($list[$key])) {
                foreach ($this->hiddenListFields as $field) unset($list[$key]->{$field});
            }
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

        foreach ($this->hiddenResponseFields as $field) unset($data->{$field});

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

        list($key, $data) = parent::beforePatch($key);

        if(is_array($data) && count($data)) $this->validatePatchFormRules($data);

        return [$key, $data];
    }

    /**
     * PATCH는 일부 필드만 보내므로(목록 인라인 수정 등) 본문에 있는 필드의 form rules만 검증한다.
     */
    protected function validatePatchFormRules(array $data): void
    {
        if(is_empty($this->formConfig)) return;

        $formConfig = $this->formConfig;
        $this->formConfig = array_values(array_filter($formConfig, function ($item) use ($data) {
            $group = $item['group'] ?? '';
            return array_key_exists($item['field'] ?? '', $data) || ($group && $group !== 'base' && array_key_exists($group, $data));
        }));

        if(count($this->formConfig)) $this->validateFormRules('', $data);

        $this->formConfig = $formConfig;
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
                        $data[$field] = create_hash($value);
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
                    $seperator = $config['option_attributes']['seperator'] ?? ',';
                    // 체크박스가 하나면 문자열로 올 수 있어 join 전에 배열로 맞춤 (PHP 8 TypeError 방지)
                    $data[$field] = join($seperator, (array)$value);
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
            if(isset($data[$model->identifier]) && $data[$model->identifier] !== '') {
                $idData[$model->identifier] = $data[$model->identifier];
            }
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

    /**
     * 수정 조건(WHERE)이 식별자를 모두 갖췄는지 확인한다.
     * 비어 있거나 일부 키가 빠진 조건으로 UPDATE 하면 여러 행(최악은 테이블 전체)이 바뀌므로 400으로 멈춘다.
     */
    protected function checkModifyIdData(array $idData, $model): void
    {
        if($model->isAutoIncrement) {
            $requiredKeys = $model->identifier ? [$model->identifier] : [];
        }else{
            $requiredKeys = $model->primaryKeyList;
        }

        $missing = array_values(array_filter($requiredKeys, function ($field) use ($idData) {
            return !isset($idData[$field]) || $idData[$field] === '';
        }));

        if(!count($requiredKeys) || count($missing)) {
            $this->responseError([
                'location' => __METHOD__,
                'param' => [
                    'table' => $model->table,
                    'missing' => $missing,
                ],
                'msg' => "Identifier value is missing",
            ], [], RestController::HTTP_BAD_REQUEST);
        }
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
                    // 메인 Model은 요청 본문이 아니라 checkIdentifierExist()가 URL·쿼리로 확인한 식별자로 수정 대상을 정함
                    if($modelName === 'Model' && count($this->idData)) $idData = $this->idData;
                    $this->checkModifyIdData($idData, $this->{$modelName});
                    // 식별자 컬럼 자체는 SET에서 빼서 수정 대상 행의 키가 바뀌지 않게 함
                    $params = array_diff_key($params, $idData);
                    $this->modData($params, $idData, $bool, $this->{$modelName});
                }
            }
        }else{
            $dto = $this->setData($dto, $model);
            $this->checkModifyIdData($this->idData, $model);
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
            // CI3 set_rules()는 POST가 아니고 검증 데이터가 비어 있으면 규칙을 등록하지 않는다.
            // PUT·PATCH에서도 규칙이 등록되도록 요청 데이터를 먼저 넣고, 아래 분기에서 그룹별 데이터로 바꾼다.
            $this->form_validation->set_data(count($data) ? $data : ['_' => '']);
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

        // PUT·PATCH도 form 설정을 읽어야 수정 요청이 검증된다 (DELETE는 검증할 본문이 없음)
        if(!in_array($this->input->method(), ['get', 'post', 'put', 'patch'])) return;

        if(is_null($setConfig)) $setConfig = $this->setConfig;

        if($setConfig) {
            $this->listConfig = array_map(function ($item) {
                return array_merge($this->config->get('builder_list_base'), $item);
            }, $this->resourceLoader->entityConfig($this->listConfigName, [], false));
            $this->formConfig = array_map(function ($item) {
                return array_merge($this->config->get('builder_form_base'), $item);
            }, $this->resourceLoader->entityConfig($this->formConfigName, [], false));
            $this->viewConfig = array_map(function ($item) {
                return array_merge($this->config->get('builder_view_base'), $item);
            }, $this->resourceLoader->entityConfig($this->viewConfigName, [], false));

            if($this->input->method() === 'get') {
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

            // form 설정이 없는 엔티티(MenuAuth 등)도 POST를 받으므로 막지 않는다 (서버 form rules 검증만 생략됨)
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
        // 등록 여부: _mode=add 이거나, 수정 대상 식별자가 없을 때(afterPost/afterPut 이 add 로 처리)
        $modeAdd = $this->mode === 'add' || !count($this->idData);

        if(is_null($model)) $model = $this->Model;
        if(count($model->uniqueKeyList) > 0){
            foreach ($model->uniqueKeyList as $key) {
                if(array_search($key, array_column($this->formConfig, 'field')) === false) continue;
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
        $className = builder_config_name($this->router->class);
        if($this->resourceLoader->entityConfig($this->resourceLoader->entityConfigName('form', $className), [], false)) {
            $columns = $this->setFormColumns($className);
        }else{
            $config = $this->resourceLoader->entityConfig($this->resourceLoader->entityConfigName('list', $className), [], false);
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

        // 비밀번호·API 키 등은 내보내지 않는다
        $hidden = array_merge($this->hiddenResponseFields, $this->hiddenListFields);
        return array_values(array_filter($columns, function ($item) use ($hidden) {
            return !(
                $item['type'] === 'common' ||
                $item['type'] === 'file' ||
                $item['type'] === 'password' ||
                $item['subtype'] === 'identifier' ||
                in_array($item['field'] ?? '', $hidden, true)
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
            $this->getListSelect($params['select'] ?? [], $this->Model),
            $params,
        ));
    }

    /**
     * 목록 조회용 select 를 정리한다.
     * MY_Model::getListPDO() 는 select 컬럼이 1개면 행 객체 대신 값 배열을 돌려주는데,
     * 목록·내보내기는 transformList()/transformView() 가 행 객체를 받아야 하므로 식별자 컬럼을 함께 조회한다.
     */
    protected function getListSelect($select, $model): array
    {
        if(is_string($select)) $select = explode(',', $select);
        if(!is_array($select) || count($select) !== 1) return is_array($select) ? $select : [];

        $keys = array_values(array_filter(array_unique(array_merge(
            [$model->identifier ?? ''],
            $model->primaryKeyList ?? []
        ))));
        foreach ($keys as $key) {
            if(trim($select[0]) !== $key) return [$select[0], $key];
        }

        // 식별자 하나만 요청했고 함께 붙일 다른 키가 없으면 기본 select(모델 컬럼 전체)로 조회
        return [];
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
                        // $i 는 행 번호(by-reference)이므로 다른 변수명 사용
                        foreach ($exploded as $idx=>$v) {
                            if(!isset($options[$v])) continue;
                            $value .= $options[$v];
                            if($idx !== count($exploded) -1) $value .= $seperator.' ';
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

    /**
     * 내보내기 임시 파일 위치. 웹에서 직접 열 수 없는 application/cache/exports/ (downloadExports_get 으로만 내려받음)
     */
    protected function getExportsDir(): string
    {
        return APPPATH.'cache'.DIRECTORY_SEPARATOR.'exports'.DIRECTORY_SEPARATOR;
    }

    protected function prepareExportsPath($exportType): string
    {
        $dir = $this->getExportsDir();
        if (!is_dir($dir) && !@mkdir($dir, 0755, true)) throw new Exception('Unable to create exports directory', CREATE_FOLDER_FAIL);
        // 내려받지 않고 남은 파일은 하루 뒤 정리
        foreach (glob($dir.'*') ?: [] as $old) {
            if(is_file($old) && filemtime($old) < time() - 86400) @unlink($old);
        }
        $filename = APP_NAME.'_'.strtolower($this->router->class).'_'.date('YmdHis').'.'.$exportType;
        $encrypted = strtr($this->encryption->encrypt($filename), [
            '+' => '-',
            '/' => '_',
        ]);
        return $dir . $encrypted;
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

        $data = $this->sanitizeRequestConditions(reformat_get_data($data, $this->exceptValidateKeys));
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

        // UTF-8 BOM (Excel 에서 한글 깨짐 방지)
        fwrite($fp, "\xEF\xBB\xBF");

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

        // 암호화된 파일명만 허용 (경로 조작 방지)
        if(!$message && !preg_match('/^[A-Za-z0-9_=-]+$/', (string)$encrypted)) $message = $this->lang->status(WRONG_TOKEN);
        $fullPath = $this->getExportsDir() . $encrypted;
        if(!$message && !file_exists($fullPath)) $message = $this->lang->status(FILE_NOT_EXIST);

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
        // 경로 조작 방지: 클래스 이름 형식만 허용
        if(!is_string($class) || !preg_match('/^[A-Za-z][A-Za-z0-9_]*$/', $class)) $this->response(['code' => BAD_REQUEST]);

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

    // 새 행을 만드는 동작이라 POST로만 받음
    public function duplicate_post($key = 0)
    {
        // URL·쿼리로 받은 식별자로 원본 행을 찾음 (조건 없이 조회해 첫 행이 복제되던 문제 수정)
        $idData = $this->checkIdentifierExist($key, true);
        $data = $this->Model->getData([], [
            'where' => $idData,
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
