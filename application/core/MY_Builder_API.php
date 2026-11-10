<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'traits/BuilderInitTrait.php';
require_once APPPATH . 'traits/BuilderCommonTrait.php';
require_once APPPATH . 'traits/BuilderColumnsTrait.php';
require_once APPPATH . 'traits/BuilderApiCrudTrait.php';
require_once APPPATH . 'traits/BuilderApiValidationTrait.php';
require_once APPPATH . 'traits/BuilderApiUserLogTrait.php';
require_once APPPATH . 'traits/BuilderApiExportTrait.php';
require_once APPPATH . 'traits/BuilderApiExcelImportTrait.php';
require_once APPPATH . 'traits/BuilderApiFileTrait.php';
require_once APPPATH . 'traits/BuilderApiDuplicateTrait.php';
require_once APPPATH . 'traits/BuilderApiPermissionTrait.php';
require_once APPPATH . 'traits/BuilderApiDataScopeTrait.php';

class MY_Builder_API extends MY_Controller_API
{
    use BuilderInitTrait;
    use BuilderCommonTrait;
    use BuilderColumnsTrait;
    use BuilderApiCrudTrait;
    use BuilderApiValidationTrait;
    use BuilderApiUserLogTrait;
    use BuilderApiExportTrait;
    use BuilderApiExcelImportTrait;
    use BuilderApiFileTrait;
    use BuilderApiDuplicateTrait;
    use BuilderApiPermissionTrait;
    use BuilderApiDataScopeTrait;

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
    protected string $tabsConfigName = '';
    /** 이 사용자에게 숨긴 폼 탭의 필드 (get_hidden_tab_fields). 저장 요청에서 이 값은 받지 않는다 */
    protected array $hiddenTabFields = [];

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
    protected array $hiddenResponseFields = ['password', 'active_id', 'active_email'];

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
        $this->exceptValidateKeys = ['_mode', '_submode', '_event', '_', '_label', 'select', 'format', 'draw', 'pageNo', 'limit', 'searchWord', 'searchCategory', 'filters'];
        $this->transTargetKeys = [];

        // /{API 폴더}/... 로 직접 들어오면 web 플랫폼, /{플랫폼}/{API 폴더}/... 면 그 플랫폼
        if(in_array($this->uri->segment(1), $this->router->routes['api_folders'] ?? ['api'], true)){
            $this->flag = 'web';
        }else{
            $this->flag = $this->uri->segment(1);
        }

        // 메뉴 권한을 검사하지 않는 플랫폼(/api/* 등)은 permission.public 으로 연 API 만 받는다 (설정: api_method_config)
        if(!in_array($this->flag, $this->permissionFlags, true)) {
            $permission = $this->apiMethodFeature['permission'] ?? [];
            if(empty($permission['public']) || ($permission['web'] ?? true) === false) show_404();
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
        if($this->tabsConfigName === '') $this->tabsConfigName = $this->resourceLoader->entityConfigName('tabs', $resource);

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
        // 범위를 beforeList 앞에도 붙인다: beforeList 안에서 세는 건수(api/Common 의 datatable recordsTotal 등)가 범위 밖 행까지 세지 않게.
        // 뒤에 한 번 더 붙이는 것은 beforeList 가 같은 필드 조건을 넣어도 범위를 벗어나지 않게 하려는 것 (교집합이라 두 번 붙여도 같음)
        $data = $this->applyDataScope($this->beforeList($this->applyDataScope($data)));

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

    private array $cachedOptions = [];

    /**
     * 같은 요청 안에서는 필드별 옵션을 한 번만 만든다 (목록 행마다 getOptions — DB 조회 포함 — 하지 않게)
     */
    protected function getCachedOptions(string $field, array $optionAttributes): array
    {
        $key = $field.'|'.md5(json_encode($optionAttributes));
        if(!array_key_exists($key, $this->cachedOptions)) {
            $this->cachedOptions[$key] = $this->getOptions($field, $optionAttributes);
        }
        return $this->cachedOptions[$key];
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
                    $options = $this->getCachedOptions($key, $item['option_attributes']);
                    $data->{$key} = $options[$data->{$key}] ?? $data->{$key};
                }
            }

            // _label=1: 선택형 컬럼(option_attributes 가 있는 list·view 설정)마다 {필드}_label 을 붙인다.
            // 값은 바꾸지 않는다 (화면 JS 는 코드 값으로 셀·체크박스·클릭 동작을 그림). 외부에서 API 를 바로 쓸 때용
            if($this->input->get('_label')) {
                // list·view 항목에 option_attributes 가 없으면 같은 필드의 form 설정 값을 쓴다 (화면 getListColumns 와 같음)
                $formOptions = [];
                foreach ($this->formConfig ?: [] as $formItem) {
                    if(isset($formItem['field']) && !empty($formItem['option_attributes'])) $formOptions[$formItem['field']] = $formItem['option_attributes'];
                }
                // list·view 설정이 없으면(예: view 설정 없는 엔티티의 단건 조회) form 설정 항목으로
                $labelItems = count($targetConfig) ? $targetConfig : ($this->formConfig ?: []);
                foreach ($labelItems as $item) {
                    $key = $item['field'] ?? '';
                    $optionAttributes = !empty($item['option_attributes']) ? $item['option_attributes'] : ($formOptions[$key] ?? []);
                    if($key === '' || empty($optionAttributes) || !property_exists($data, $key)) continue;
                    if(in_array($optionAttributes['option_type'] ?? '', ['api', 'script'], true)) continue;
                    $options = $this->getCachedOptions($optionAttributes['option_field'] ?? $key, $optionAttributes);
                    $value = $data->{$key};
                    if(is_array($value)) {
                        $data->{$key.'_label'} = array_map(function ($v) use ($options) { return $options[$v] ?? $v; }, $value);
                    }else{
                        $data->{$key.'_label'} = ($value === null || $value === '') ? '' : ($options[$value] ?? $value);
                    }
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
                    $file_dto = $this->Model_File->getListWhere($this->Model_File->notIncludeSelects(), ['file_id' => $file_id]);
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
        $data = $this->removeHiddenTabFields($data);

        $this->checkIdentifierExist($key);

        foreach ($this->defaultList as $field=>$default) {
            if(!isset($data[$field])) $data[$field] = $default;
        }

        // form 규칙은 한 번만 검증하고(전에는 파이프라인 모델 수만큼 같은 규칙을 다시 돌림), 모델별 필수값·고유값만 모델마다
        $this->validateFormRules('', $data);
        $this->validateOptionValues($data);
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
        $data = $this->removeHiddenTabFields($data);

        $this->checkIdentifierExist($key);

        foreach ($this->defaultList as $field=>$default) {
            if(!isset($data[$field])) $data[$field] = $default;
        }

        // form 규칙은 한 번만 검증하고(전에는 파이프라인 모델 수만큼 같은 규칙을 다시 돌림), 모델별 필수값·고유값만 모델마다
        $this->validateFormRules('', $data);
        $this->validateOptionValues($data, true);
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
        $data = $this->removeHiddenTabFields($data);

        if(is_array($data) && count($data)) {
            $this->validatePatchFormRules($data);
            $this->validateOptionValues($data, true);
        }

        $data = $this->sanitizeRichTextFields($data);

        return [$key, $data];
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
            $this->formConfig = $this->resolveFormConfig($this->resourceLoader->entityConfig($this->formConfigName, [], false));
            if($this->input->method() !== 'get') $this->setHiddenTabFields();
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

    /**
     * 폼 탭 정의(tabs_{엔티티}_config)에서 이 사용자에게 숨긴 탭의 필드를 찾아,
     * 저장 요청에서 받지 않도록 하고 form 설정에서도 뺀다 (검증·onoff 기본값 N 이 그 필드에 돌지 않도록)
     */
    protected function setHiddenTabFields(): void
    {
        $tabs = resolve_form_tabs($this->resourceLoader->entityConfig($this->tabsConfigName, [], false), $this->session->userdata('user_cd'));
        $this->hiddenTabFields = get_hidden_tab_fields($this->formConfig, $tabs);
        if(!count($this->hiddenTabFields)) return;

        $this->formConfig = array_values(array_filter($this->formConfig, function ($item) {
            return !isset($item['field']) || !in_array($item['field'], $this->hiddenTabFields, true);
        }));
    }

    /**
     * 숨긴 탭의 값을 요청에서 뺀다. 수정이면 DB 의 기존 값이 남는다
     */
    protected function removeHiddenTabFields($data)
    {
        if(!is_array($data) || !count($this->hiddenTabFields)) return $data;
        return array_diff_key($data, array_flip($this->hiddenTabFields));
    }

    /**
     * 화면과 같은 해석(setFormColumn: form·option·list attributes 기본값, option_field 등)을 거친 form 설정.
     * 서버 검증은 원래 label('lang:' 접두어)·rules·errors 로 한다 (setFormColumn 은 이 값들을 화면용으로 바꾼다)
     */
    protected function resolveFormConfig(array $config): array
    {
        return array_map(function ($item) {
            $merged = array_merge($this->config->get('builder_form_base'), $item);
            if(($merged['type'] ?? '') === 'common' || !isset($merged['field'])) return $merged;
            $column = $this->setFormColumn($item);
            foreach (['label', 'rules', 'errors', 'subtype'] as $key) $column[$key] = $merged[$key] ?? null;
            return $column;
        }, $config);
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

        // 본문의 식별자·부모 키가 수정 대상과 다르면 거절 (검증 전에)
        $this->checkBodyKeys($http, (string)$object_called, $arguments);

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
