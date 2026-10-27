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

        // builder_method_config: 메뉴 기본 권한 계산(getMenuRouteConfigs → fillMethodConfigProperties)이 method 기본값을 쓴다.
        // 플랫폼 설정보다 먼저 불러야 하므로 여기서 (WEB 과 같은 순서)
        // builder_nav_config: 메뉴 설정을 DB 와 맞출 때(MenuList::syncFromConfig_post) nav 항목 기본값을 쓴다
        $this->loadConfigs(['builder_base_config', 'builder_route_config', 'builder_method_config', 'builder_nav_config', 'builder_form_config', 'builder_list_config', 'builder_view_config']);

        // 설정 이름은 WEB과 같은 규칙(Resource_loader, builder_config_name()).
        // api_method_config 의 resource 로 다른 엔티티 설정을 쓸 수 있다 (예: users API ↔ 내 정보 화면의 myinfo 설정)
        $resource = is_string($this->apiMethodFeature['resource'] ?? null) && $this->apiMethodFeature['resource'] !== ''
            ? $this->apiMethodFeature['resource'] : $this->router->class;
        if($this->listConfigName === '') $this->listConfigName = $this->resourceLoader->entityConfigName('list', $resource);
        if($this->formConfigName === '') $this->formConfigName = $this->resourceLoader->entityConfigName('form', $resource);
        if($this->viewConfigName === '') $this->viewConfigName = $this->resourceLoader->entityConfigName('view', $resource);

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
        $data = $this->applyDataScope($this->beforeList($data));

        $data = $this->executeList($data);

        return $this->afterList($data, $isResponse);
    }

    protected function beforeList($data): array
    {
        $order = $this->getRequestedListOrder();
        if(count($order)) $data['orderBy'] = $order;

        return $data;
    }

    /**
     * 목록 정렬 요청(orderField·orderDir). 목록 설정에 있고 orderable 이 false 가 아니며
     * 요청 조건으로 받을 수 있는 컬럼(getAllowedRequestFields — 실제 DB 컬럼)만 받는다. 아니면 [] (모델 기본 정렬)
     */
    protected function getRequestedListOrder(): array
    {
        $field = $this->input->get('orderField');
        $dir = strtolower((string)$this->input->get('orderDir'));
        if(!is_string($field) || $field === '' || !in_array($dir, ['asc', 'desc'], true)) return [];

        $listFields = [];
        foreach ($this->listConfig ?: [] as $item) {
            if(!isset($item['field']) || ($item['orderable'] ?? true) === false) continue;
            if(in_array($item['type'] ?? '', ['row_num', 'actions', 'common'], true)) continue;
            $listFields[] = $item['field'];
        }
        if(!in_array($field, $listFields, true)) return [];
        if(!in_array($field, $this->getAllowedRequestFields(), true)) return [];

        return [$field => strtoupper($dir)];
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
                    $data->{$key} = $options[$data->{$key}] ?? $data->{$key};
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

        // form 규칙은 한 번만 검증하고(전에는 파이프라인 모델 수만큼 같은 규칙을 다시 돌림), 모델별 필수값·고유값만 모델마다
        $this->validateFormRules('', $data);
        foreach ($this->getModelPipeline() as $modelName) {
            if (property_exists($this, $modelName)) {
                $exceptRequiredKeys = $this->getGeneratedForeignKeyFields($this->{$modelName}, $modelName);

                $this->validate($data, $this->{$modelName}, false, '', $exceptRequiredKeys);
                $this->checkUniqueExist($data, $this->{$modelName});
            }
        }

        if(count($this->fileList) > 0) $data = $this->uploadFileInList($data);

        $data = $this->sanitizeRichTextFields($data);

        return [$key, $data];
    }

    /**
     * form 설정에서 subtype 이 quill(에디터)인 필드의 HTML 을 정화한다 (sanitize_rich_html, 허용 태그·속성만)
     */
    protected function sanitizeRichTextFields($data)
    {
        if(!is_array($data)) return $data;

        foreach ($this->formConfig ?: [] as $item) {
            if(($item['subtype'] ?? '') !== 'quill' || !isset($item['field'])) continue;
            $field = $item['field'];
            if(!isset($data[$field]) || !is_string($data[$field]) || $data[$field] === '') continue;
            try {
                $data[$field] = sanitize_rich_html($data[$field]);
            } catch (RuntimeException $e) {
                $this->responseError([
                    'location' => __METHOD__,
                    'param' => $field,
                    'msg' => $e->getMessage(),
                ], ['code' => INTERNAL_SERVER_ERROR], RestController::HTTP_INTERNAL_SERVER_ERROR);
            }
        }

        return $data;
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

        // form 규칙은 한 번만 검증하고(전에는 파이프라인 모델 수만큼 같은 규칙을 다시 돌림), 모델별 필수값·고유값만 모델마다
        $this->validateFormRules('', $data);
        foreach ($this->getModelPipeline() as $modelName) {
            if (property_exists($this, $modelName)) {
                $exceptRequiredKeys = $this->getGeneratedForeignKeyFields($this->{$modelName}, $modelName);

                $this->validate($data, $this->{$modelName}, false, '', $exceptRequiredKeys);
                $this->checkUniqueExist($data, $this->{$modelName});
            }
        }

        $data = $this->sanitizeRichTextFields($data);

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

        $data = $this->sanitizeRichTextFields($data);

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

        $this->checkDataScopeValues($dto, true);

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
            if(count($this->idData)) {
                // soft delete(del_yn='Y')·미사용(use_yn='N') 행도 같은 PK를 차지하므로 기본 조건 없이 확인 (DB duplicate key 오류 대신 409)
                $useDelYnAsBase = $model->useDelYnAsBase;
                $useUseYnAsBase = $model->useUseYnAsBase;
                $model->useDelYnAsBase = false;
                $model->useUseYnAsBase = false;
                $exists = $this->checkDataExist(['where' => $this->idData], $model);
                $model->useDelYnAsBase = $useDelYnAsBase;
                $model->useUseYnAsBase = $useUseYnAsBase;

                if($exists) $this->response(['code' => DATA_ALREADY_EXIST]);
            }
        }

        return $dto;
    }

    protected function executeAdd($dto, $bool, $model = null): array
    {
        if(is_null($model)) {
            foreach ($this->getModelPipeline() as $modelName) {
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

        $this->checkDataScopeValues($dto, false);

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
            foreach ($this->getModelPipeline() as $modelName) {
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
            // 옵션 변환은 목록에만 적용 (상세·등록·수정·삭제 응답에는 list 가 없음)
            if(is_array($data) && array_key_exists('list', $data)) {
                $data['list'] = $this->getOptionsFromDBList($data['list'], $data['render'] ?? []);
            }
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

        if ($result === false) $this->responseDbWriteError(__METHOD__, $model);

        if ($model->isAutoIncrement && $model->identifier) {
            $dto[$model->identifier] = $result;
            $this->idData[$model->identifier] = $result;
        }

        return $dto;
    }

    protected function modData($dto, $idData, $bool, $model = null)
    {
        if(is_null($model)) $model = $this->Model;

        if ($model->modData($dto, $idData, $bool) === false) $this->responseDbWriteError(__METHOD__, $model);

        return $dto;
    }

    /**
     * MY_Model::addDataPDO/modDataPDO 가 실패(rollback 후 false)했을 때 500으로 응답한다.
     */
    protected function responseDbWriteError(string $location, $model): void
    {
        $this->responseError([
            'location' => $location,
            'param' => [
                'table' => $model->table,
            ],
            'type' => 'db error',
            'msg' => 'Database write failed',
        ], [
            'code' => INTERNAL_SERVER_ERROR,
        ]);
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
        // MY_Builder_WEB::setFormData 와 같이 그룹 항목의 group_attributes 를 순서대로 누적 병합
        $groups = [];
        foreach ($config as $item) {
            $group = $item['group'];
            if(!array_key_exists($group, $groups)) $groups[$group] = $group === 'base' ? [] : $this->config->get('builder_form_base_group_attributes');
            if($group !== 'base') $groups[$group] = array_merge($groups[$group], $item['group_attributes'] ?? []);
        }
        foreach ($groups as $group => $attr) {
            $groupConfig = array_filter($config, function($item) use ($group) {
                return $item['group'] === $group;
            });
            // CI3 set_rules()는 POST가 아니고 검증 데이터가 비어 있으면 규칙을 등록하지 않는다.
            // PUT·PATCH에서도 규칙이 등록되도록 요청 데이터를 먼저 넣고, 아래 분기에서 그룹별 데이터로 바꾼다.
            // 그룹마다 새로 검증한다. 앞 그룹 규칙이 남아 있으면 다음 그룹 run() 에서 다시 검사돼 같은 오류가 두 번 들어간다
            $this->form_validation->reset_validation();
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

                        for($i = 0; $i < $cnt; $i++) {
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

                    // 파일 컬럼은 업로드 여부로 검사 (수정은 기존 파일이 유지되므로, 값이 이미 있으면 통과)
                    if(count($model->fileList) > 0 && in_array($key, $model->fileList)){
                        if(!is_file_posted($key) && is_empty($data, $key) && !count($this->idData)) {
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
            // 화면과 같은 해석(setFormColumn: form·option·list attributes 기본값, option_field 옵션).
            // 서버 검증은 원래 label('lang:' 접두어)·rules·errors 로 한다 (setFormColumn 은 화면용으로 바꿈)
            $this->formConfig = array_map(function ($item) {
                $merged = array_merge($this->config->get('builder_form_base'), $item);
                if(($merged['type'] ?? '') === 'common' || !isset($merged['field'])) return $merged;
                $column = $this->setFormColumn($item);
                foreach (['label', 'rules', 'errors', 'subtype'] as $key) $column[$key] = $merged[$key] ?? null;
                return $column;
            }, $this->resourceLoader->entityConfig($this->formConfigName, [], false));
            $this->viewConfig = array_map(function ($item) {
                return array_merge($this->config->get('builder_view_base'), $item);
            }, $this->resourceLoader->entityConfig($this->viewConfigName, [], false));

            if($this->input->method() === 'get') {
                if(is_empty($this->listConfig)) {
                    // form 설정의 list => true 항목으로 (WEB 과 같은 규칙, getListConfigFromForm)
                    $this->listConfig = array_map(
                        function($item) {
                            $label = (string)$item['label'];
                            if(sscanf($label, 'lang:%s', $line) === 1) $label = $line;
                            if($this->lang->line_exists($label.'_list')) $label = $label.'_list';
                            return array_merge($this->config->get('builder_list_base', []), $item, ['label' => $label]);
                        },
                        $this->getListConfigFromForm($this->formConfig)
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

            // 기본키가 하나뿐이면 URL 키(/sysCode/ABC001)를 그 값으로 쓴다. 없으면 쿼리 파라미터
            // (전에는 URL 키를 무시해 SysCode::beforeRemove 처럼 idData 와 $key 가 다른 출처에서 올 수 있었음)
            if (count($model->primaryKeyList) === 1 && $key !== null && $key !== '' && $key !== 0 && $key !== '0') {
                $this->idData = [$model->primaryKeyList[0] => $key];
                return $this->idData;
            }

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

            // 수정 중인 자기 행은 제외. 메인 Model은 URL·쿼리로 확인한 idData, 그 외에는 dto의 식별자 값
            $whereNot = [];
            if(!is_empty($dto) && $model->identifier) {
                $isMainModel = property_exists($this, 'Model') && $model === $this->Model;
                $selfId = ($isMainModel ? ($this->idData[$model->identifier] ?? null) : null) ?? ($dto[$model->identifier] ?? null);
                if($selfId !== null && $selfId !== '') $whereNot = [$model->identifier => $selfId];
            }
            return $model->checkDuplicate($unique, $whereNot, $isIncludeDeleted);
        }
    }

    protected function checkCnt($dto, $model = null)
    {
        if(is_null($model)) $model = $this->Model;
        return $model->getCnt($dto) > 0;
    }

    /**
     * 엑셀 가져오기 1단계: 행을 검증만 한다. 오류가 있으면 422 + 행·필드별 오류 목록.
     * 요청 바디(JSON): [{"_row": 엑셀 행 번호, "{필드}": 값, ...}, ...]
     */
    public function validateExcel_post(): void
    {
        $this->beforeExcelUpload();

        $this->response([
            'code' => DATA_AVAILABLE,
        ]);
    }

    /**
     * 엑셀 가져오기 2단계: 다시 검증한 뒤 한 번에(트랜잭션) INSERT 한다.
     */
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
        // 조회 결과에 있는 컬럼(Model·부모·자식 모델)만 내보낸다. form 의 다른 테이블 필드(스타일 등)는 행에 값이 없다
        $modelColumns = [];
        foreach ($this->getModelPipeline() as $modelName) {
            if(property_exists($this, $modelName) && method_exists($this->{$modelName}, 'getRequestColumnList')) {
                $modelColumns = array_merge($modelColumns, $this->{$modelName}->getRequestColumnList());
            }
        }
        $listColumns = array_merge(array_column($this->listConfig ?: [], 'field'), [RECENT_DT_COLUMN_NAME]);
        return array_values(array_filter($columns, function ($item) use ($hidden, $modelColumns, $listColumns) {
            // 목록 설정에 있는 열(recent_dt 처럼 select 에서 계산하는 값 포함)은 내보낸다
            if(count($modelColumns) && !in_array($item['field'] ?? '', $modelColumns, true) && !in_array($item['field'] ?? '', $listColumns, true)) return false;
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

                // form 에는 있지만 조회 결과에 없는 필드(다른 테이블에 저장하는 값 등)는 빈 값
                $value = property_exists($item, $field) ? $item->{$field} : '';
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
        if(!property_exists($this, 'Model') || is_null($this->Model)) {
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

        $data = $this->applyDataScope($this->sanitizeRequestConditions(reformat_get_data($data, $this->exceptValidateKeys)));
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
        if(!$message && !preg_match('/^[A-Za-z0-9_=-]+$/', (string)$encrypted)) $message = $this->lang->status(FILE_NOT_EXIST);
        $fullPath = $this->getExportsDir() . $encrypted;
        if(!$message && !file_exists($fullPath)) $message = $this->lang->status(FILE_NOT_EXIST);

        $encrypted = strtr($encrypted, [
            '-' => '+',
            '_' => '/',
        ]);
        $filename = $message ? false : $this->encryption->decrypt($encrypted);
        if(!$message && $filename === false) $message = $this->lang->status(WRONG_TOKEN);

        if($message) show_alert('error', $message, true);

        $fileContents = file_get_contents($fullPath);
        @unlink($fullPath);

        force_download($filename, $fileContents, true);
    }

    /** 한 번에 받는 최대 행 수 (화면은 50행씩 나눠 보냄) */
    protected int $excelChunkLimit = 500;

    /**
     * 엑셀로 받을 필드. excel_{class}_config 가 있으면 그 field, 없으면 form 설정에서
     * 화면(MY_Builder_WEB::getExcelHeaders)과 같은 규칙으로 고른다: form 노출, hidden·common·file·password·공통 컬럼·확인 필드(matches) 제외
     */
    protected function getExcelImportFields(): array
    {
        $excel = $this->resourceLoader->entityConfig($this->resourceLoader->entityConfigName('excel', $this->router->class), [], false);
        if($excel) return array_values(array_filter(array_column($excel, 'field')));

        $except = [CREATED_ID_COLUMN_NAME, CREATED_DT_COLUMN_NAME, UPDATED_ID_COLUMN_NAME, UPDATED_DT_COLUMN_NAME, DEL_YN_COLUMN_NAME, USE_YN_COLUMN_NAME, RECENT_DT_COLUMN_NAME];
        $fields = [];
        foreach ($this->formConfig as $item) {
            if(empty($item['field']) || (array_key_exists('form', $item) && !$item['form'])) continue;
            if(in_array($item['type'] ?? '', ['hidden', 'common', 'file', 'password'], true)) continue;
            if(in_array($item['field'], $except, true)) continue;
            if(preg_match('/matches\[/', $item['rules'] ?? '')) continue;
            $fields[] = $item['field'];
        }
        return $fields;
    }

    /**
     * 엑셀 행을 검증해 저장할 값만 남긴다. 오류가 하나라도 있으면 422 로 응답하고 멈춘다.
     *  - 받는 필드 외 키는 버림, defaultList 로 빈 값 채움
     *  - form rules (등록 기준: required_mod[add] 는 required 로), 모델 notNullList 필수
     *  - 고유키: DB 중복과 이번 요청 안의 중복
     * 오류 항목: param = 필드, value = 엑셀 행 번호(_row), type = 규칙, msg = 메시지
     */
    protected function validateExcelData($data): array
    {
        if(!is_array($data) || !count($data) || array_keys($data) !== range(0, count($data) - 1)) {
            $this->response(['code' => EMPTY_REQUIRED_DATA], RestController::HTTP_BAD_REQUEST);
        }
        if(count($data) > $this->excelChunkLimit) {
            $this->response(['code' => BAD_REQUEST, 'msg' => "한 번에 {$this->excelChunkLimit}행까지 보낼 수 있습니다."], RestController::HTTP_BAD_REQUEST);
        }

        // 부모·자식 모델이 있는 엔티티(예: 관리자 = 관리자 + 사용자)는 한 행이 여러 테이블에 나뉘어 저장돼 일괄 등록을 지원하지 않는다
        if(property_exists($this, 'Model_Parent') || property_exists($this, 'Model_Child')) {
            $this->response(['code' => BAD_REQUEST, 'msg' => '이 화면은 엑셀 일괄 등록을 지원하지 않습니다.'], RestController::HTTP_BAD_REQUEST);
        }

        $model = $this->Model;
        $fields = $this->getExcelImportFields();
        $fieldConfig = array_values(array_filter($this->formConfig, function ($item) use ($fields) {
            return in_array($item['field'] ?? '', $fields, true);
        }));
        $rules = array_map(function ($item) {
            $item['rules'] = preg_replace('/required_mod\[[^\]]*add[^\]]*\]/', 'required', $item['rules'] ?: 'do_nothing');
            return $item;
        }, $fieldConfig);
        $labels = array_column($fieldConfig, 'label', 'field');

        // 선택형 필드는 선택지 값만 받는다 (form option_attributes)
        $optionValues = [];
        foreach ($fieldConfig as $item) {
            if(is_empty($item, 'option_attributes')) continue;
            $options = $this->getOptions($item['field'], $item['option_attributes']);
            if(is_array($options) && count($options)) $optionValues[$item['field']] = [array_map('strval', array_keys($options)), $item['option_attributes']['seperator'] ?? ','];
        }

        $errors = [];
        $rows = [];
        $seenUnique = [];
        // 같은 행·필드 오류는 한 번만 (form rule 과 notNull·고유키 검사가 겹칠 수 있음)
        $addError = function ($rowNo, $field, $type, $msg) use (&$errors) {
            foreach ($errors as $error) if($error['value'] === $rowNo && $error['param'] === $field) return;
            $errors[] = ['location' => __METHOD__, 'param' => $field, 'value' => $rowNo, 'type' => $type, 'msg' => $msg];
        };
        foreach ($data as $idx => $row) {
            $rowNo = is_array($row) && isset($row['_row']) && is_numeric($row['_row']) ? (int)$row['_row'] : $idx + 1;
            if(!is_array($row)) {
                $addError($rowNo, '', 'row', '행 형식이 올바르지 않습니다.');
                continue;
            }

            $clean = [];
            foreach ($fields as $field) {
                if(array_key_exists($field, $row) && $row[$field] !== null && !is_array($row[$field])) $clean[$field] = trim((string)$row[$field]);
            }
            foreach ($this->defaultList as $field => $default) {
                if(!isset($clean[$field]) || $clean[$field] === '') $clean[$field] = $default;
            }

            // form rules
            if(count($rules)) {
                $this->form_validation->reset_validation();
                $this->form_validation->set_data(count($clean) ? $clean : ['_' => '']);
                $this->form_validation->set_rules($rules);
                if($this->form_validation->run() === false) {
                    foreach (validation_errors_array() as $error) {
                        $addError($rowNo, $error['field'], $error['rule'] ?? 'rule', $error['message']);
                    }
                }
            }

            // 선택지 값 검사
            foreach ($optionValues as $field => [$values, $seperator]) {
                if(!isset($clean[$field]) || $clean[$field] === '') continue;
                foreach (explode($seperator, $clean[$field]) as $value) {
                    if(!in_array(trim($value), $values, true)) {
                        $label = lang(str_replace('lang:', '', $labels[$field] ?? $field));
                        $addError($rowNo, $field, 'in_list', $this->josa->__conv("{$label}{은} 선택할 수 있는 값이 아닙니다: ").trim($value));
                        break;
                    }
                }
            }

            // 모델 필수 컬럼
            foreach ($model->notNullList as $key) {
                if(($model->isAutoIncrement && $key === $model->identifier) || in_array($key, $model->fileList, true)) continue;
                if(!in_array($key, $fields, true) && !array_key_exists($key, $this->defaultList)) continue;
                if(!isset($clean[$key]) || $clean[$key] === '') {
                    $label = lang(str_replace('lang:', '', $labels[$key] ?? $key));
                    $addError($rowNo, $key, 'required', $this->josa->__conv("{$label}{은} 필수 입력값 입니다."));
                }
            }

            // 고유키 (DB, 이번 요청 안)
            foreach ($model->uniqueKeyList as $key) {
                if(!isset($clean[$key]) || $clean[$key] === '') continue;
                $label = lang(str_replace('lang:', '', $labels[$key] ?? $key));
                if(isset($seenUnique[$key][$clean[$key]])) {
                    $addError($rowNo, $key, 'duplicate', $this->josa->__conv("{$label}{이} {$seenUnique[$key][$clean[$key]]}행과 같습니다."));
                }elseif($this->checkDuplicate([$key => $clean[$key]], $model)) {
                    $addError($rowNo, $key, 'duplicate', $this->josa->__conv("동일 {$label}{이} 이미 존재합니다."));
                }
                $seenUnique[$key][$clean[$key]] = $rowNo;
            }

            $rows[] = $clean;
        }

        if(count($errors)) {
            $this->responseError($errors, [], RestController::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $rows;
    }

    /**
     * 엑셀 가져오기 전 권한 확인.
     * route 설정에 excel 메서드가 있는 클래스만 허용한다 (화면을 켜지 않은 엔티티에 API 로 대량 INSERT 하지 못하게).
     * 메뉴 권한(import) 검사는 API 권한 검사(TODO.md 2장)와 함께 넣는다. 필요하면 오버라이드.
     */
    protected function checkExcelImportPermission(): void
    {
        $route = $this->resourceLoader->routeEntry($this->router->class);
        if(!isset($route['methods']['excel'])) {
            $this->response(['code' => FORBIDDEN_ACCESS, 'msg' => '엑셀 일괄 등록이 허용되지 않은 화면입니다.'], RestController::HTTP_FORBIDDEN);
        }
    }

    protected function beforeExcelUpload(): array
    {
        $this->checkExcelImportPermission();

        if(!property_exists($this, 'Model')) {
            $this->response(['code' => MODEL_IS_NOT_DEFINED], RestController::HTTP_INTERNAL_SERVER_ERROR);
        }

        $data = json_decode($this->input->raw_input_stream, true);

        return $this->validateExcelData($data);
    }

    protected function afterExcelUpload($data): void
    {
        // 저장 전 값 정리(형 변환·비밀번호 해시 등)는 일반 등록과 같은 setData()
        $rows = array_map(function ($row) {
            return $this->setData($row);
        }, $data);

        // 데이터 범위 밖 값이 있으면 저장하지 않음
        foreach ($rows as $row) $this->checkDataScopeValues($row, true);

        if($this->Model->addList($rows) === false) {
            $this->response([
                'code' => WRITE_FILEDB_FAIL,
            ], RestController::HTTP_INTERNAL_SERVER_ERROR);
        }

        $this->response([
            'code' => DATA_CREATED,
            'data' => ['count' => count($rows)],
        ], RestController::HTTP_CREATED);
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

    /**
     * 첨부 파일 삭제: PATCH /{엔티티}/deleteFile/{행 id}?type={파일 필드}, 본문 file_id
     * 그 행의 파일 필드에 있는 파일만 지우고, 행의 컬럼에서도 file_id 를 뺀다
     */
    public function deleteFile_patch($key = 0)
    {
        $type = $this->input->get('type') ?? null;
        $file_id = $this->input->patch('file_id') ?? null;
        if(!$type || !$file_id) $this->response(['code' => EMPTY_REQUIRED_DATA]);

        $model = null;
        foreach ($this->getModelPipeline() as $modelName) {
            if(property_exists($this, $modelName) && $this->{$modelName} && in_array($type, $this->{$modelName}->fileList, true)) {
                $model = $this->{$modelName};
                break;
            }
        }
        if(!$model) {
            // 파일 필드가 아닌 첨부(별도 테이블 등)는 엔티티의 deleteOtherFile() 이 처리
            if(!$this->deleteOtherFile($key, $type, (string)$file_id)) {
                $this->response(['code' => BAD_REQUEST], RestController::HTTP_BAD_REQUEST);
            }
            $this->response(['code' => DATA_DELETED]);
        }

        $idData = $this->checkIdentifierExist($key, false, $model);
        if(!count($idData)) $this->response(['code' => EMPTY_REQUIRED_DATA]);

        // 미사용(use_yn='N') 행도 관리 화면에서 수정하므로 use_yn 기본 조건 없이 조회·수정 (삭제된 행은 그대로 제외)
        $useUseYnAsBase = $model->useUseYnAsBase;
        $model->useUseYnAsBase = false;

        $row = $model->getDataWhere([$type], $idData);
        $current = is_object($row) ? ($row->{$type} ?? null) : $row;
        $fileIds = $this->parseFileColumnValue($current);
        if(!in_array((string)$file_id, $fileIds, true)) {
            $model->useUseYnAsBase = $useUseYnAsBase;
            $this->response(['code' => DATA_NOT_EXIST], RestController::HTTP_NOT_FOUND);
        }

        $remain = array_values(array_diff($fileIds, [(string)$file_id]));
        $this->modData([$type => count($remain) ? implode(',', $remain) : 0], $idData, false, $model);
        $model->useUseYnAsBase = $useUseYnAsBase;

        $this->delFileData(['file_id' => $file_id]);

        $this->response([
            'code' => DATA_DELETED,
        ]);
    }

    /**
     * 모델 파일 필드가 아닌 첨부 삭제 (예: 게시글 첨부 테이블). 처리했으면 true, 모르는 type 이면 false(400).
     * 그 행 소속 파일인지 확인한 뒤 delFileData() 로 지운다. 소속이 아니면 404 로 응답
     */
    protected function deleteOtherFile($key, string $type, string $fileId): bool
    {
        return false;
    }

    /**
     * 파일 컬럼 값(file_id 하나, 'id,id', JSON 배열)을 file_id 문자열 목록으로
     */
    protected function parseFileColumnValue($value): array
    {
        if(is_array($value)) {
            $list = $value;
        }else{
            $value = trim((string)$value);
            if($value === '' || $value === '0') return [];
            $decoded = json_decode($value, true);
            $list = is_array($decoded) ? $decoded : explode(',', $value);
        }
        return array_values(array_filter(array_map(function ($v) {
            return trim((string)$v);
        }, $list), function ($v) {
            return $v !== '' && $v !== '0';
        }));
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
        }

        $dto = (array)$data;
        // 사용 여부(use_yn)는 원본 값을 그대로 둔다 (빼면 DB 기본값 NULL 로 들어감 — bizsoft #빌더-34)
        foreach ([$this->identifier, CREATED_ID_COLUMN_NAME, CREATED_DT_COLUMN_NAME, UPDATED_ID_COLUMN_NAME, UPDATED_DT_COLUMN_NAME, DEL_YN_COLUMN_NAME] as $field) {
            unset($dto[$field]);
        }
        if($this->Model->isDelYn) $dto[DEL_YN_COLUMN_NAME] = 'N';

        // 일반 등록과 같은 경로 (엔티티 beforeAdd·데이터 범위 값 검사), 메인 Model 만 저장
        $dto = $this->beforeDuplicate($dto, $data);
        $dto = $this->beforeAdd($dto);
        $this->checkDataScopeValues($dto, true);
        $this->executeAdd($dto, false, $this->Model);

        $this->afterDuplicate($this->idData, $data);

        $this->response([
            'code' => DATA_PROCESSED,
            'msg' => lang('Data replication has been completed'),
            'data' => $this->idData,
        ]);
    }

    /**
     * 복제할 값 손보기. 기본:
     *  - 민감 컬럼($deniedRequestFields·$hiddenResponseFields: 비밀번호·API 키 등)은 복사하지 않음
     *  - 파일 컬럼은 비움(0) — 같은 file_id 를 나눠 가지면 한쪽 파일 삭제가 다른 쪽 파일도 지움
     *  - 고유 컬럼(uniqueKeyList): 문자열이면 '_copy', '_copy2' … 중 비어 있는 값, 아니면 비움
     * 엔티티에서 오버라이드해 규칙을 바꾼다 (예: 별칭 규칙)
     */
    protected function beforeDuplicate(array $dto, $source): array
    {
        $model = $this->Model;

        foreach (array_unique(array_merge($this->deniedRequestFields, $this->hiddenResponseFields)) as $field) {
            unset($dto[$field]);
        }

        foreach ($model->fileList as $field) {
            if(array_key_exists($field, $dto)) $dto[$field] = 0;
        }

        foreach ($model->uniqueKeyList as $field) {
            if(!array_key_exists($field, $dto) || $dto[$field] === null || $dto[$field] === '') continue;
            if(is_string($dto[$field]) && !is_numeric($dto[$field])) {
                $dto[$field] = $this->getDuplicateUniqueValue($field, $dto[$field], $model);
            }else{
                unset($dto[$field]);
            }
        }

        return $dto;
    }

    /**
     * 복제 뒤 처리 (딸린 표 복사 등). $newIdData: 새 행 식별자, $source: 원본 행. 기본은 없음
     */
    protected function afterDuplicate(array $newIdData, $source): void
    {
    }

    /**
     * 고유 컬럼 값 뒤에 '_copy', '_copy2' … 를 붙여 아직 없는 값 (삭제·미사용 행도 같은 값을 차지하므로 기본 조건 없이 확인)
     */
    protected function getDuplicateUniqueValue(string $field, string $value, $model): string
    {
        $useDelYnAsBase = $model->useDelYnAsBase;
        $useUseYnAsBase = $model->useUseYnAsBase;
        $model->useDelYnAsBase = false;
        $model->useUseYnAsBase = false;

        $candidate = $value.'_copy';
        for ($i = 2; $i <= 100 && $model->getCntWhere([$field => $candidate]) > 0; $i++) {
            $candidate = $value.'_copy'.$i;
        }

        $model->useDelYnAsBase = $useDelYnAsBase;
        $model->useUseYnAsBase = $useUseYnAsBase;

        return $candidate;
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
            // 식별자 없이 DELETE 하면 HTML 500(show_error) 대신 JSON 400
            $this->keyNotExist();
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

    /* --------------------------------------------------------------- */
    /* API 권한 검사 (메뉴 권한)                                          */
    /* --------------------------------------------------------------- */

    /**
     * 메뉴 권한을 검사할 플랫폼(flag). 기본은 관리자(/admin/api/*)만. 공개 /api/* 는 로그인·api_method_config 의 auth 만
     */
    protected array $permissionFlags = ['admin'];

    /**
     * 이름 있는 엔드포인트의 권한 자리. 없으면 HTTP 메서드로 판단 (GET 메뉴 표시만, POST·PUT·PATCH 수정, DELETE 삭제)
     */
    protected array $permissionEndpointModes = [
        'prepareExports' => 'export', 'downloadExports' => 'export',
        'validateExcel' => 'import', 'uploadExcel' => 'import', 'deleteExcelFile' => 'import',
        'duplicate' => 'create',
    ];

    /**
     * 요청이 메뉴 권한에 맞는지 검사한다. 맞지 않으면 403.
     *  1. 시스템 관리자, 로그인 전 요청(auth none 엔드포인트), permission.check === false → 통과
     *  2. permission.super 또는 연결된 메뉴가 isSuper → 시스템 관리자만
     *  3. 메뉴와 연결되지 않은 API → 로그인만 (통과)
     *  4. CACHING_MENU=false(권한 데이터 없음) 또는 메뉴 isAuth=false → 통과 (화면과 같음)
     *  5. 메뉴 권한 행이 없거나 숨김 → 403, 요청 권한 자리(create·read·update·delete·export·import)가 꺼져 있으면 403
     *     권한 자리가 'read|update' 처럼 여럿이면 하나만 켜져 있어도 통과, '' 이면 메뉴 표시(is_show)만 확인
     * 메뉴 연결: api_method_config 의 permission.menu ('class' 또는 'class/method'), 없으면 API 클래스와 같은 이름의 메뉴
     */
    protected function checkApiPermission(string $http, string $object, array $arguments): void
    {
        if(!in_array($this->flag, $this->permissionFlags, true)) return;

        $permission = $this->apiMethodFeature['permission'] ?? [];
        if(array_key_exists('check', $permission) && $permission['check'] === false) return;
        if(!$this->session->userdata('user_id')) return;
        if($this->session->userdata('is_sys_admin')) return;

        if(!empty($permission['super'])) $this->denyApiPermission('super');

        $menu = $this->findApiMenu($permission['menu'] ?? null, $object);
        if(!count($menu)) return;
        if(!empty($menu['isSuper'])) $this->denyApiPermission('super');
        if(!env('CACHING_MENU')) return;
        if(array_key_exists('isAuth', $menu) && !$menu['isAuth']) return;

        $auth = $this->getApiMenuAuth($menu);
        if(!$auth || (int)($auth->is_show ?? 0) !== 1) $this->denyApiPermission('menu');

        $mode = $permission['mode'] ?? $this->getApiPermissionMode($http, $object, $arguments);
        if($mode === '' || $mode === null) return;
        $modes = is_array($mode) ? $mode : explode('|', (string)$mode);
        foreach ($modes as $item) {
            if(is_auth_avaliable($item, (string)$auth->menu_auth)) return;
        }
        $this->denyApiPermission(implode('|', $modes));
    }

    /**
     * 요청이 쓰는 권한 자리. '' 는 메뉴 표시만 확인
     *  - 목록 GET·이름 있는 GET(선택 목록·중복 확인 등): '' (화면 목록이 권한 자리를 검사하지 않는 것과 같음)
     *  - 단건 GET: 'read|update' (상세 화면은 read, 수정 화면도 단건 조회를 씀)
     */
    protected function getApiPermissionMode(string $http, string $object, array $arguments): string
    {
        if($object !== 'index' && method_exists($this, "{$object}_{$http}")) {
            if(array_key_exists($object, $this->permissionEndpointModes)) return $this->permissionEndpointModes[$object];
            return $http === 'get' ? '' : ($http === 'delete' ? 'delete' : 'update');
        }

        switch ($http) {
            case 'get' :
                $key = $object !== 'index' ? $object : ($arguments[0] ?? 0);
                return count($this->checkIdentifierExist($key)) ? 'read|update' : '';
            case 'delete' : return 'delete';
            case 'put' :
            case 'patch' : return 'update';
            case 'post' :
                // 식별자(URL 키 또는 쿼리)가 있으면 수정, 없으면 등록 (afterPost 와 같은 기준)
                $key = $object !== 'index' ? $object : ($arguments[0] ?? 0);
                return count($this->checkIdentifierExist($key)) ? 'update' : 'create';
        }
        return '';
    }

    /**
     * API 에 연결된 메뉴 항목(nav 설정의 항목 배열). 없으면 [].
     * 같은 class 가 여러 메뉴면 method 가 같은 항목 → method 없는(index) 항목 순
     */
    protected function findApiMenu(?string $spec, string $object): array
    {
        if($spec) {
            [$class, $method] = array_pad(explode('/', $spec, 2), 2, '');
        }else{
            $class = $this->router->class;
            $method = $object !== 'index' && method_exists($this, "{$object}_".strtolower($this->input->method())) ? $object : '';
        }

        $items = [];
        $walk = function ($menu) use (&$walk, &$items, $class) {
            foreach ($menu as $item) {
                if(($item['class'] ?? '') === $class) $items[] = $item;
                if(!empty($item['subMenu'])) $walk($item['subMenu']);
            }
        };
        $walk($this->config->item("{$this->flag}_nav_menu") ?: []);
        if(!count($items)) return [];

        foreach ($items as $item) if($method !== '' && ($item['method'] ?? '') === $method) return $item;
        foreach ($items as $item) if(in_array($item['method'] ?? '', ['', 'index'], true)) return $item;
        return $items[0];
    }

    /**
     * 메뉴 권한 행 {menu_auth, is_show}. 기본은 회원 등급(user_cd)별 menu_auth.
     * 파트너별 권한처럼 다른 기준을 쓰는 프로젝트는 오버라이드한다.
     */
    protected function getApiMenuAuth(array $menu): ?object
    {
        $this->load->model('Model_Menu');
        $this->load->model('Model_Menu_Auth');

        $method = $menu['method'] ?? '';
        $row = $this->Model_Menu->getData(['menu_id'], [
            'where' => [
                'class' => $menu['class'],
                'method' => $method !== '' ? $method : 'index',
            ],
        ]);
        if(!$row) return null;

        $auth = $this->Model_Menu_Auth->getData(['menu_auth', 'is_show'], [
            'where' => [
                'grade_cd' => $this->session->userdata('user_cd'),
                'menu_id' => is_object($row) ? $row->menu_id : $row,
            ],
        ]);
        return is_object($auth) ? $auth : null;
    }

    protected function denyApiPermission(string $reason): void
    {
        $this->responseError([
            'location' => __METHOD__,
            'param' => ['class' => $this->router->class, 'reason' => $reason],
            'msg' => 'Permission Denied',
        ], [
            'code' => FORBIDDEN_ACCESS,
        ], RestController::HTTP_FORBIDDEN);
    }

    /* --------------------------------------------------------------- */
    /* 데이터 범위 (회사·담당자 등 요청자가 다룰 수 있는 행)                    */
    /* --------------------------------------------------------------- */

    /**
     * 이 엔티티의 범위 필드. 프로젝트의 getDataScope() 가 이 목록을 보고 값을 채운다. 예: ['company_id']
     */
    protected array $scopeFields = [];

    /**
     * 첫 인자가 이 엔티티의 식별자인 이름 있는 엔드포인트 (범위 검사 대상). 프로젝트 엔드포인트는 엔티티에서 추가
     */
    protected array $scopeKeyEndpoints = ['duplicate', 'deleteFile', 'deleteRepeater', 'isMyData'];

    private ?array $dataScope = null;

    /**
     * 쓰기 요청(POST·PUT·PATCH·DELETE) 하나를 트랜잭션 하나로 묶을지. 저장 중간에 실패하면 앞 단계 저장도 되돌린다.
     * 파일 저장소(업로드·삭제·복사)는 되돌리지 못한다. truncate·DDL 은 MySQL 이 그 자리에서 커밋하므로 그런 엔드포인트는 뺀다
     */
    protected bool $useRequestTransaction = true;

    /**
     * 요청 트랜잭션을 쓰지 않는 이름 있는 엔드포인트 (표를 비우는 초기화 등)
     */
    protected array $noTransactionEndpoints = ['reset'];

    private bool $wantRequestTransaction = false;

    protected function beforeControllerMethod(string $controller_method): void
    {
        if($this->wantRequestTransaction) $this->beginRequestTransaction();
    }

    /**
     * 요청자가 다룰 수 있는 범위: [필드 => 허용 값 목록]. 빈 배열이면 제한 없음 (기본).
     * 프로젝트의 api/Common 에서 세션(등급·담당 회사 등)과 $scopeFields 로 구현한다.
     * 적용: 목록·내보내기 조건, 식별자로 지정한 행(단건·수정·삭제·$scopeKeyEndpoints), 등록·수정 값, 엑셀 가져오기 행
     */
    protected function getDataScope(): array
    {
        return [];
    }

    protected function getDataScopeOnce(): array
    {
        if($this->dataScope === null) {
            $this->dataScope = [];
            foreach ($this->getDataScope() as $field => $values) {
                $values = is_array($values) ? $values : explode(',', (string)$values);
                $this->dataScope[$field] = array_values(array_unique(array_map('strval', array_filter($values, function ($v) {
                    return $v !== null && $v !== '';
                }))));
            }
        }
        return $this->dataScope;
    }

    /**
     * 목록·내보내기 조건에 범위를 붙인다. 이미 같은 필드 조건이 있으면 교집합
     */
    protected function applyDataScope(array $data): array
    {
        foreach ($this->getDataScopeOnce() as $field => $values) {
            if(isset($data['whereIn'][$field])) {
                $current = $data['whereIn'][$field];
                $current = is_array($current) ? $current : explode(',', (string)$current);
                $values = array_values(array_intersect($values, array_map('strval', $current)));
            }
            // 허용 값이 없으면 아무 행도 나오지 않게 (IN (NULL))
            $data['whereIn'][$field] = count($values) ? $values : [null];
        }
        return $data;
    }

    /**
     * 식별자로 지정한 행이 범위 안에 있는지
     */
    protected function isInDataScope(array $idData): bool
    {
        $scope = $this->getDataScopeOnce();
        if(!count($scope) || !count($idData)) return true;
        return $this->checkDataExist($this->applyDataScope(['where' => $idData]));
    }

    protected function checkDataScopeTarget(string $http, string $object, array $arguments): void
    {
        if(!count($this->getDataScopeOnce()) || !property_exists($this, 'Model') || !$this->Model) return;

        if($object !== 'index' && method_exists($this, "{$object}_{$http}")) {
            if(!in_array($object, $this->scopeKeyEndpoints, true)) return;
            $key = $arguments[0] ?? 0;
        }else{
            $key = $object !== 'index' ? $object : ($arguments[0] ?? 0);
        }

        if(!$this->isInDataScope($this->checkIdentifierExist($key))) $this->denyDataScope();
    }

    /**
     * 저장할 값이 범위 안인지. 등록은 범위 필드가 빠져도 거부
     */
    protected function checkDataScopeValues(array $dto, bool $isAdd): void
    {
        foreach ($this->getDataScopeOnce() as $field => $values) {
            if(!array_key_exists($field, $dto)) {
                if($isAdd) $this->denyDataScope();
                continue;
            }
            if(!in_array((string)$dto[$field], $values, true)) $this->denyDataScope();
        }
    }

    /**
     * 범위 밖은 있는지조차 알리지 않도록 404
     */
    protected function denyDataScope(): void
    {
        $this->response([
            'code' => DATA_NOT_EXIST,
        ], RestController::HTTP_NOT_FOUND);
    }

    public function _remap($object_called, $arguments = [])
    {
        // /reset, /duplicate 처럼 다른 HTTP 메서드로만 있는 엔드포인트를 잘못된 메서드로 부르면,
        // index_{http} 의 식별자로 해석되지 않도록 405 로 막는다
        $http = strtolower($this->input->method());
        if(is_string($object_called) && !method_exists($this, "{$object_called}_{$http}")) {
            foreach (['get', 'post', 'put', 'patch', 'delete'] as $other) {
                if($other !== $http && method_exists($this, "{$object_called}_{$other}")) {
                    $this->responseError([
                        'location' => __METHOD__,
                        'param' => ['method' => $object_called],
                        'msg' => 'Method Not Allowed',
                    ], [], RestController::HTTP_METHOD_NOT_ALLOWED);
                }
            }
        }

        // 메뉴 권한 검사 (/admin/api/* 등 permissionFlags 플랫폼)
        $this->checkApiPermission($http, (string)$object_called, $arguments);

        // 데이터 범위 검사 (식별자로 지정한 행이 getDataScope() 범위 밖이면 404)
        $this->checkDataScopeTarget($http, (string)$object_called, $arguments);

        // 쓰기 요청은 한 트랜잭션으로 (RestController 가 인증·제한·요청 로그를 마친 뒤 beforeControllerMethod 에서 열고,
        // 응답할 때 커밋·롤백 — MY_Controller_API::finishRequestTransaction)
        $this->wantRequestTransaction = $this->useRequestTransaction
            && in_array($http, ['post', 'put', 'patch', 'delete'], true)
            && !in_array((string)$object_called, $this->noTransactionEndpoints, true);

        $this->mode = $this->input->get('_mode') ?? '';
        $this->submode = $this->input->get('_submode') ?? '';
        $this->apiBaseUri = base_url($this->flag . '/' . $this->apiRoute);
        $this->apiUri = $this->apiBaseUri . '/' . $this->router->class;

        parent::_remap($object_called, $arguments);
    }
}
