<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'traits/BuilderInitTrait.php';
require_once APPPATH . 'traits/BuilderCommonTrait.php';
require_once APPPATH . 'traits/BuilderColumnsTrait.php';

class MY_Builder_WEB extends MY_Controller_WEB
{
    use BuilderInitTrait;
    use BuilderCommonTrait;
    use BuilderColumnsTrait;

    public string $flag = '';
    public string $apiFlag = '';
    public string $baseUri = '';
    public string $apiUri = '';
    public array $routeConfig = [];
    public array $methodConfig = [];
    public array $pageConfig = [];
    public array $allowedMethods = [];
    public string $pageType = 'form';
    public bool $formExist = false;
    public bool $listForm = false;
    public array $userData = [];
    public array $headerData = [];
    public array $sessionData = [];
    public string $href = '';
    public array $listColumns = [];
    public array $filterConfig = [];
    public array $formColumns = [];
    public array $viewColumns = [];
    public string $viewPath = '';
    public array $menuList = [];
    public array $currentMenu = [];
    public array $formAssets = [];
    public bool $isLoggedIn = false;
    public bool $isAdmin = false;
    public string $pageAuth = INIT_AUTH_CHAR;
    public string $scope = '';
    public bool $isSystemAdmin = false;
    public array $errors = [];
    public bool $methodNotInRoute = false;
    protected bool $tokenExpired = false;


    public function __construct()
    {
        parent::__construct();

        $this->config->load('extra/autologin_config', false);

        // 토큰이 만료(token_lifetime())됐으면 로그인 값만 지우고, 자동 로그인 쿠키가 있으면 아래에서 새 토큰을 받는다
        if($this->session->userdata('token') && $this->getSessionTokenState() === 'expired') {
            $this->clearLoginSession();
            $this->tokenExpired = true;
        }

        // 세션이 없으면 자동 로그인 쿠키로 먼저 로그인. 생성자에서 해야 post_controller_constructor hook(setPHPVars)이
        // 새 토큰을 화면 JS 에 넣고, _remap 의 checkLoggedIn 이 로그인으로 본다
        $this->loginWithAutologinCookie();

        if(!$this->flag) show_error("Platform flag is not set.");

        /**
         * TODO
         * base config 병합 필요
         * 이외 config는 반드시 로드할 필요가 있는지 점검 필요
         */
        $this->loadConfigs(['builder_base_config', 'builder_form_config', 'builder_nav_config', 'builder_route_config', 'builder_method_config', 'builder_list_config', 'builder_filter_config', 'builder_view_config']);

        $this->baseViewPath = BUILDER_FLAGNAME."/layout/index";
        $this->baseUri = $this->flag === $this->router->routes['default_platform'] ? '' : $this->flag;
        $this->apiUri = base_url($this->flag . DIRECTORY_SEPARATOR . $this->apiFlag . DIRECTORY_SEPARATOR);
        $this->loggedInRedirect = "$this->baseUri/{$this->config->item('platform_config.loggedInRedirect')}";
        $this->noLoginRedirect = "$this->baseUri/{$this->config->item('platform_config.noLoginRedirect')}";

        $this->titleList = [ucfirst($this->flag)];
        $this->href = base_url("$this->baseUri/{$this->router->class}");
        $this->viewPath = "$this->flag/{$this->router->class}";
        $this->jsVars = [
            'TITLE' => $this->router->class,
            'MODULE_URI' => base_url('module' . DIRECTORY_SEPARATOR),
            'API_BASE_URI' => $this->apiUri,
            'API_URI' => '',
            'API_PARAMS' => [],
        ];

        $this->setRouteConfig();
        $this->setMethodConfig();

        if($this->devMode)
            $this->output->enable_profiler(TRUE);
    }

    public function index()
    {
        parent::index();

        if($this->routeConfig['properties']['noIndex']) {
            if($this->routeConfig['properties']['baseMethod']) {
                redirect($this->href . DIRECTORY_SEPARATOR . $this->routeConfig['properties']['baseMethod']);
            }
            trigger_error(__METHOD__." : We couldn't find the page you are looking for");
        }

        if(empty($this->routeConfig['methods'])) {
            $data['subPage'] = '';
            $data['backLink'] = WEB_HISTORY_BACK;
            $this->viewApp($data);
        }else{
            if(!$this->routeConfig['properties']['allowNoLogin'] && !$this->isLoggedIn){
                redirect($this->noLoginRedirect);
            }

            if($this->router->class === 'common') {
                redirect("$this->baseUri/$this->defaultController");
            }

            $this->{"{$this->routeConfig['properties']['baseMethod']}"}();
        }
    }

    public function blank()
    {
        $this->viewApp();
    }

    public function list()
    {
        $this->titleList[] = 'List';

        $data['mode'] = 'list';
        $data['backLink'] = WEB_HISTORY_BACK;
        $data = $this->prepareListData($data);

//        $this->addJS['tail'][] = [
//            base_url('public/assets/builder/js/app-page-list.js'),
//        ];

        $this->viewApp($data);
    }

    public function view($key = 0)
    {
        $this->checkIdentifierExist($key);

        $this->titleList[] = 'View';

        $data['mode'] = 'view';
        if($this->isDetailView()) {
            $data['backLink'] = WEB_HISTORY_BACK;
            $data = $this->prepareViewData($data);

            $this->addJS['tail'][] = [
                base_url('public/assets/builder/js/app-page-view.js'),
            ];
        }

        $this->viewApp($data);
    }

    public function add()
    {
        if($this->listForm) show_404();

        $this->titleList[] = 'Add';

        $data['mode'] = 'add';
        $data['backLink'] = WEB_HISTORY_BACK;
        $data = $this->prepareFormData($data);

        $this->addJS['tail'][] = [
            base_url('public/assets/builder/js/app-page-add.js'),
        ];

        $this->viewApp($data);
    }

    public function edit($key = 0)
    {
        if($this->listForm) show_404();

        $this->checkIdentifierExist($key);

        $this->titleList[] = 'Edit';

        $data['mode'] = 'edit';
        $data['backLink'] = WEB_HISTORY_BACK;
        $data = $this->prepareFormData($data);

        $this->addJS['tail'][] = [
            base_url('public/assets/builder/js/app-page-edit.js'),
        ];

        $this->viewApp($data);
    }

    protected function checkIdentifierExist($key = 0)
    {
        if( !($this->routeConfig['properties']['noIdentifier'] || $this->methodConfig['properties']['noIdentifier']) ) {
            $idData = $this->getIdentifierData($key, $this->jsVars['IDENTIFIER']);

            if(empty($idData)) alert(lang('Incorrect Access'));

            $this->addJsVars(['KEY' => count($idData)===1?array_values($idData)[0]:$idData]);
        }
    }

    public function excel()
    {
        $this->addJS['head'][] = [
            base_url('public/assets/builder/vendor/libs/jquery-tabledit/jquery.tabledit.js'),
            base_url('public/assets/builder/js/app-page-excel.js'),
            "https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js",
        ];

        $this->titleList[] = 'Excel';

        $data['backLink'] = WEB_HISTORY_BACK;
        $data['excelHeaders'] = $this->getExcelHeaders();
        $data['sampleFile'] = $this->getExcelSample($data['excelHeaders']);

        if(!count($data['excelHeaders'])) show_error('Please Check The Excel Header List', 500);

        $this->viewApp($data);
    }

    protected function prepareListData($data): array
    {
        $data['backLink'] = WEB_HISTORY_BACK;
        $data['filters'] = reformat_filter_data($this->jsVars['FILTER_COLUMNS']??[], $this->filterConfig);
        $data['filterHelpBlock'] = $this->filterConfig['help_block'] ?? [];
        $data['columns'] = $this->jsVars['LIST_COLUMNS']??[];
        $data['isCheckbox'] = $this->methodConfig['properties']['isCheckbox'];
        $data['actions'] = reformat_bool_type_list($this->methodConfig['actions']);
        $data['buttons'] = $this->methodConfig['buttons']??[];

        $data['formExist'] = false;
        if($this->methodConfig['properties']['formExist']) {
            $data['formExist'] = true;
            $data['formData'] = count($this->formColumns)>0?restructure_form_data_by_type($this->jsVars['FORM_DATA']):[];
            $data['formType'] = count($this->formColumns)>0?$this->methodConfig['properties']['formType']:'';

            if(count($this->formColumns)>0) {
                $data['formSubType'] = $this->methodConfig['properties']['formSubType']??'base';
            }else{
                $data['formSubType'] = '';
            }

            $data['formStyle'] = count($this->formColumns)>0?$this->methodConfig['properties']['formStyle']:'';
            $this->addFormScripts();
        }else{
            foreach ($data['actions'] as $i=>$action) {
                if($action === 'delete') continue;
                if(!in_array($action, $this->routeConfig['properties']['allows'])) unset($data['actions'][$i]);
            }
            $data['actions'] = array_values($data['actions']);
        }

        // 모달 상세 보기 (setJSVariables 가 VIEW_COLUMNS 를 만들었을 때)
        $data['viewExist'] = false;
        if(!empty($this->methodConfig['properties']['viewConfig'])) {
            $data['viewExist'] = true;
            $data['viewType'] = 'modal';
            $data['viewData'] = reformat_form_data_by_type($this->jsVars['VIEW_COLUMNS'], 'page', 'modal');

            // 입력 요소가 있으면 폼 자산이 필요 (옆 폼이 있으면 이미 붙음)
            if(!$data['formExist'] && count(array_filter($this->viewColumns, function($item) {
                return !in_array($item['type'], ['view', 'hidden', 'common'], true);
            }))) {
                $this->addFormScripts();
            }

            $this->addJS['tail'][] = [
                base_url('public/assets/builder/js/app-page-view.js'),
            ];
        }

        if(!array_key_exists('subPage', $data))
            $data['subPage'] = 'builder/layout/list';

        $this->addListScripts($this->methodConfig['subtype']);

        return $data;
    }

    /**
     * 설정만으로 그리는 상세 보기 화면인지: category view + type base.
     * type 이 dashboard·statistics 처럼 다르면 컨트롤러·전용 뷰가 그리는 화면이라 이 경로를 타지 않는다.
     * subtype 은 화면 틀(view_{subtype}.php: base·grid·modal·article·comments)
     */
    protected function isDetailView(): bool
    {
        return ($this->methodConfig['category'] ?? '') === 'view' && ($this->methodConfig['type'] ?? '') === 'base';
    }

    protected function prepareViewData($data): array
    {
        $data['viewType'] = $this->methodConfig['subtype'];

        // 2번째 인자는 폼 형태(page), 3번째가 subtype(grid 면 12칸 행으로 나눔)
        $data['viewData'] = reformat_form_data_by_type($this->jsVars['VIEW_COLUMNS'], 'page', $data['viewType']);

        $data['identifier'] = array_filter($this->viewColumns, function ($item) {
            return array_key_exists('field', $item)&&in_array($item['field'], $this->jsVars['IDENTIFIER']);
        });

        // 목록은 항상, 수정은 수정 화면(edit 메서드)과 수정 권한이 있을 때, 삭제는 삭제 권한이 있을 때
        $data['actions'] = array_values(array_filter(reformat_bool_type_list($this->methodConfig['actions']), function ($action) {
            switch ($action) {
                case 'list' : return true;
                case 'edit' : return in_array('edit', $this->routeConfig['properties']['allows']) && is_auth_avaliable('update', $this->pageAuth);
                case 'delete' : return is_auth_avaliable('delete', $this->pageAuth);
                default : return in_array($action, $this->routeConfig['properties']['allows']);
            }
        }));
        $data['buttons'] = $this->methodConfig['buttons']??[];

        $data['isComments'] = $this->methodConfig['properties']['isComments'];

        // 입력 요소가 있을 때만 폼 자산을 붙임 (hidden·common 은 입력 요소가 아님). 댓글 입력 폼(app-page-comment.js)도 FormValidation 을 쓴다
        if($data['isComments'] || count(array_filter($this->viewColumns, function($item) {
            return !in_array($item['type'], ['view', 'hidden', 'common'], true);
        }))) {
            $this->addFormScripts();
        }

        if($data['isComments']) {
            $this->addJS['tail'][] = [
                base_url('public/assets/builder/js/app-page-comment.js')
            ];
        }

        // subPage 를 정하지 않으면 viewApp() 이 {flag}/{class}/view → … → builder/layout/view 순으로 찾는다 (전용 뷰 우선)

        return $data;
    }

    protected function prepareFormData($data): array
    {
        $data['formType'] = $this->methodConfig['type'];
        $data['formSubType'] = $this->methodConfig['subtype'];
        $data['formData'] = restructure_form_data_by_type($this->jsVars['FORM_DATA'], $data['formType'], $data['formSubType']);

        $data['actions'] = array_values(array_filter(reformat_bool_type_list($this->methodConfig['actions']), function ($action) {
            return $action === 'delete' || in_array($action, $this->routeConfig['properties']['allows']);
        }));
        $data['buttons'] = $this->methodConfig['buttons']??[];

        if(!array_key_exists('subPage', $data))
            $data['subPage'] = 'builder/layout/form';

        $this->addFormScripts();

        return $data;
    }

    protected function beforeViewApp($data = []): array
    {
        if($this->formExist) {
            // dropzone·dropzone-full·dropzone-single 모두 같은 에셋을 쓴다 (전에는 정확히 'dropzone' 일 때만 불러와 나머지는 동작 안 함)
            $usesDropzone = count(array_filter(array_column($this->formColumns, 'subtype'), function ($subtype) {
                return is_string($subtype) && strpos($subtype, 'dropzone') === 0;
            })) > 0;
            if($usesDropzone) {
                $this->addCSS[] = [
                    base_url('public/assets/builder/vendor/libs/dropzone/dropzone.css'),
                    base_url(BUILDER_ASSET_LIBS_URI.'/dropzone/style.css'),
                ];

                $this->addJS['tail'][] = [
                    base_url('public/assets/builder/vendor/libs/dropzone/dropzone.js'),
                    base_url(BUILDER_ASSET_LIBS_URI.'/dropzone/common.js'),
                ];
            }
        }

        // common
        $data['userData'] = $this->userData;
        $data['headerData'] = $this->headerData;
        $data['includes'] = $this->routeConfig['properties']['includes'];
        $data['pageConfig'] = $this->methodConfig;
        $data['platformName'] = PLATFORM_NAME??'builder';
        $data['hideBack'] = element('hideBack', $data);

        // builder attributes
        $data['htmlAttrs'] = get_builder_html_attributes($this->flag);
        $data['bodyAttrs'] = get_builder_body_attributes($this->devMode);

        // menu
        $data['menus'] = $this->menuList;

        return $data;
    }

    protected function afterViewApp($data = []): array
    {
        // 플랫폼·화면별 빈 asset 파일은 개발 환경에서만 자동으로 만든다.
        // 그 밖에서는 웹 요청이 공개 디렉터리에 파일을 만들지 않고, 있는 파일만 붙인다.
        $useAsset = function (string $path): bool {
            if(file_exists($path)) return true;
            if(ENVIRONMENT !== 'development') return false;
            $file = fopen($path, "w");
            if(!$file) show_error("viewApp : Unable to open file!", E_USER_ERROR);
            fclose($file);
            return true;
        };

        if($useAsset(PLATFORM_ASSET_CSS_PATH.'style.css')) {
            $this->addCSS[] = [
                base_url(PLATFORM_ASSET_CSS_URI.'style.css'),
            ];
        }

        if($useAsset(PLATFORM_ASSET_JS_PATH.'common.js')) {
            $this->addJS['tail'][] = [
                base_url(PLATFORM_ASSET_JS_URI.'common.js'),
            ];
        }

        foreach (['_preset', '_onload'] as $subfix) {
            $filename = snakeize($this->router->class);
            if(!in_array(snakeize($this->router->method), ['index','list','add','edit','excel'])) {
                $filename .= '_'.snakeize($this->router->method);
            }
            $filename .= $subfix.'.js';

            if(!$useAsset(PLATFORM_ASSET_JS_PATH.$filename)) continue;

            if($subfix === '_preset') {
                $this->addJS['head'][] = [
                    base_url(PLATFORM_ASSET_JS_URI.$filename),
                ];
            }else{
                $this->addJS['tail'][] = [
                    base_url(PLATFORM_ASSET_JS_URI.$filename),
                ];
            }
        }

        // error
        $data['errors'] = $this->errors;

        return $data;
    }

    protected function viewApp($data = [])
    {
        $data = $this->beforeViewApp($data);

        if(!array_key_exists('subPage', $data)) {
            $view = null;
            $method = $this->router->method === 'index'?$this->routeConfig['properties']['baseMethod']:$this->router->method;
            $method = snakeize($method);

            foreach ([get_path(), BUILDER_FLAGNAME] as $firstPath) {
                if($view) continue;
                if(!file_exists(VIEWPATH.$firstPath)) continue;
                foreach ([$this->router->class, 'layout'] as $secondPath) {
                    $path = $firstPath.DIRECTORY_SEPARATOR.$secondPath.DIRECTORY_SEPARATOR;
                    // route 기본 메서드 화면이면 클래스 폴더의 index.php 도 전용 뷰로 본다 (예: builder/dashboard/index.php)
                    $names = [$method];
                    if($secondPath === $this->router->class && $method === snakeize($this->routeConfig['properties']['baseMethod'] ?? '')) $names[] = 'index';
                    foreach ($names as $name) {
                        if(!$view && file_exists(VIEWPATH.$path.$name.'.php')) $view = $path.$name;
                    }
                    if($view) break;
                }
            }

            if(is_null($view) || !file_exists(VIEWPATH.$view.'.php')){
                $this->addErrors([
                    'message' => "viewApp : View file for {$this->router->class}:{$method} does not exist.",
                    'code' => E_USER_ERROR,
                ]);
//                trigger_error("viewApp : View file for {$this->router->class}:{$method} does not exist.", E_USER_ERROR);
                $data['subPage'] = BUILDER_FLAGNAME.DIRECTORY_SEPARATOR.'layout'.DIRECTORY_SEPARATOR.'blank';
            }else{
                $data['subPage'] = $view;
            }
        }

        if($this->baseViewPath===$data['subPage']) show_error('view file is not set.', E_USER_ERROR);

        $data = $this->afterViewApp($data);

        parent::viewApp($data);
    }

    protected function setRouteConfig(): void
    {
        $config = $this->config->item("route_config");

        if($this->router->class === 'common') return;

        $className = $this->resourceLoader->routeKey($this->router->class);

        if($className === '') {
            // routeConfig를 비운 채 두면 checkLoggedIn()이 'Incorrect Access'로 처리
            // trigger_error 는 개발 환경에서 화면에 먼저 출력돼 이후 redirect 가 막히므로 로그로만 남김
            log_message('error', __METHOD__." : Class '{$this->router->class}' does not exist in route config.");
            return;
        }

        $this->routeConfig = $this->fillRouteConfigProperties($className, $config[$className]);
    }

    protected function setMethodConfig($data = []): void
    {
        if(empty($this->routeConfig)) return;

        if($this->router->method === 'index' && $this->routeConfig['properties']['baseMethod']) {
            $method = $this->routeConfig['properties']['baseMethod'];
        }else{
            $method = $this->router->method;
        }

        $config = $this->routeConfig['methods'] ?? $this->config->get("method_config", [], false);
        if(empty($config)) return;

        if(!array_key_exists($method, $config) && empty($data)) {
            if($method === 'index') {
                show_404();
            }else{
                log_message('error', __METHOD__." : Method '$method' does not exist in route config.");
            }
            // 생성자 안에서는 로그인 여부를 아직 모름 → _remap()이 로그인 검사 후 index로 보냄
            $this->methodNotInRoute = true;
            $method = $this->routeConfig['properties']['baseMethod'];
        }

        $methodConfig = [];
        if(
            !is_empty($config, $method)
            ||
            !is_empty($config, strtolower($method))
        ){
            $methodConfig = $config[$method]??$config[strtolower($method)];
        }
        if(empty($methodConfig)) $methodConfig = $data;

        if(!isset($methodConfig['category'])) {
            // 예전에는 적은 내용을 통째로 버리고 method_base_config 로 바꿨다 → category 'base' 로 보고 기본값만 채움
            if(!empty($methodConfig)) log_message('error', __METHOD__." : method '{$method}' of '{$this->router->class}' has no category, treated as 'base'.");
            $methodConfig['category'] = 'base';
        }

        if($methodConfig['category'] === 'action') {
            // 화면 없는 메서드도 setupPlatform·_remap이 읽는 mode·actions·buttons·properties 기본값은 채움 (category는 action 유지)
            $this->methodConfig = fill_config_properties($methodConfig, $this->config->get("method_base_config", []));
            return;
        }

        $methodConfig = $this->fillMethodConfigProperties($methodConfig);

        $this->methodConfig = $methodConfig;
    }

    protected function setJSVariables($data = [])
    {
        if(empty($this->methodConfig)) return;

        $methodConfig = $this->methodConfig;

        // method 별.
        switch ($methodConfig['category']) {
            case 'list' :
                if($methodConfig['type'] === 'table') {
                    $listActions = $this->setListActions($methodConfig['actions']);
                    $this->addJsVars([
                        'LIST_BUTTONS' => $this->setListButtons($methodConfig['buttons']),
                        'LIST_ACTIONS' => $listActions,
                        'LIST_EXPORTS' => $this->setListExports($methodConfig['exports']),
                        'LIST_COLUMNS' => $this->setListColumns($methodConfig['properties']['isRowNumber'], count($listActions) > 0),
                        'LIST_PLUGIN' => $methodConfig['properties']['plugin'],
                        'LIST_OPTIONS' => $methodConfig['properties'],
                        'LIST_CHEKBOX' => $methodConfig['properties']['isCheckbox'],
                        'LIST_PAGING' => ($methodConfig['properties']['paging'] ?? true) !== false,
                    ]);
                }

                $this->addJsVars([
                    'FILTER_COLUMNS' => $this->setFilterColumns(),
                ]);

                if($methodConfig['properties']['formExist']) {
                    $this->formExist = true;
                    $this->listForm = true;
                    $this->formColumns = $this->setFormColumns($methodConfig['properties']['formConfig']);
                    $this->addJsVars([
                        'FORM_EXIST' => true,
                        'FORM_DATA' => $this->setFormData(),
                        'FORM_REGEXP' => $this->config->item('regexp'),
                        'FORM_TYPE' => $methodConfig['properties']['formType'],
                        'FORM_STYLE' => $methodConfig['properties']['formStyle'],
                    ]);
                }

                // 목록에서 모달로 상세 보기: properties.viewConfig 가 true 면 기본 view 설정, 문자열이면 view_{값}_config 를 먼저 찾는다
                // 옆 폼과 같은 페이지에 그리므로 요소 id 에 view- 를 붙인다
                if(!empty($methodConfig['properties']['viewConfig'])) {
                    $viewConfig = $methodConfig['properties']['viewConfig'];
                    $this->addJsVars([
                        'VIEW_COLUMNS' => $this->setViewColumns(null, is_string($viewConfig) ? $viewConfig : null, 'view-'),
                        'VIEW_TYPE' => 'modal',
                        'VIEW_CHECK_OWNER' => (bool)($methodConfig['properties']['checkOwner'] ?? false),
                    ]);
                }
                break;
            case 'view' :
                if($this->isDetailView()) {
                    $this->addJsVars([
                        'VIEW_COLUMNS' => $this->setViewColumns(),
                        'VIEW_TYPE' => $methodConfig['subtype'],
                        // true 면 내가 만든 데이터일 때만 수정·삭제 버튼 표시 (게시판 등)
                        'VIEW_CHECK_OWNER' => (bool)($methodConfig['properties']['checkOwner'] ?? false),
                    ]);
                }
                break;
            case 'form' :
                $this->formExist = true;
                $this->formColumns = $this->setFormColumns($methodConfig['resourceConfig']);
                $this->addJsVars([
                    'FORM_DATA' => $this->setFormData(),
                    'FORM_REGEXP' => $this->config->item('regexp'),
                    'FORM_TYPE' => $methodConfig['subtype']??'base',
                ]);
                break;
        }

        $this->addJsVars([
            'IDENTIFIER' => $this->setIdentifier(),
        ]);

        $uris = [];
        $methodButtons = array_merge($methodConfig['actions'], $methodConfig['buttons']);
        foreach (['list','add','edit','view','excel',] as $action) {
            $addUri = true;
            if(array_key_exists($action, $methodButtons)) {
                if($methodButtons[$action]){
                    switch ($action) {
                        case 'add' :
                        case 'edit' :
                            if($methodConfig['category'] === 'list' && !empty($methodConfig['properties']['formConfig'])) {
                                $addUri = false;
                            }
                            break;
                        case 'view' :
                            if($methodConfig['category'] === 'list' && !empty($methodConfig['properties']['viewConfig'])) {
                                $addUri = false;
                            }
                            break;
                    }
                }else{
                    $addUri = false;
                }
            }

            if($addUri && in_array($action, $this->routeConfig['properties']['allows'])) {
                $uris['PAGE_'.strtoupper($action).'_URI'] = $this->href.DIRECTORY_SEPARATOR.$action;
            }else{
                $uris['PAGE_'.strtoupper($action).'_URI'] = '';
            }
        }

        $this->addJsVars(array_merge($uris, $data));
    }

    protected function setIdentifier(): array
    {
        $identifiers = [];
        if (count($this->routeConfig['properties']['identifier'])) {
            $identifiers = $this->routeConfig['properties']['identifier'];
        } elseif (count($this->methodConfig['properties']['identifier'])) {
            $identifiers = $this->methodConfig['properties']['identifier'];
        } else {
            if(property_exists($this, $this->methodConfig['category'].'Columns')){
                return array_values(array_map(function ($item) {
                    return $item['field'];
                }, array_filter($this->{$this->methodConfig['category'].'Columns'}, function ($item) {
                    return $item['subtype'] === 'identifier';
                })));
            }
        }
        return $identifiers;
    }

    protected function setFormData($formColumns = []): array
    {
        if(empty($formColumns)) $formColumns = $this->formColumns;

        $result = [];
        $groups = [];
        $attr = [];
        foreach ($formColumns as $i=>$item) {
            if(isset($item['type']) && $item['type'] === 'common') {
                $result[] = $item;
                continue;
            }

            if (!$item['form']) continue;

            if ($item['subtype'] === 'identifier' && !in_array($this->router->method, ['index', 'list'])){
                // page type form 에 identifier default 값 부여
                if(end($this->uri->segments) !== $this->router->method)
                    $item['default'] = end($this->uri->segments);
            }

            /**
             * 예외 처리 : textarea 가 wysiwyg quill 인 경우
             */
            if($this->listForm && $item['type'] === 'textarea' && $item['subtype'] !== 'autosize'){
                $item['subtype'] = 'autosize';
            }

            // 페이지 폼의 textarea + quill 은 custom quill 뷰(form_{type}_custom_quill.php)로 그린다
            // (전에는 일반 textarea 로 나왔음). 그 폼 type 의 quill 뷰가 없으면 textarea 그대로
            if(!$this->listForm && $item['type'] === 'textarea' && $item['subtype'] === 'quill') {
                $pageFormType = $this->methodConfig['type'] ?? 'base';
                if(is_file(APPPATH."views/builder/layout/form_{$pageFormType}_custom_quill.php")) $item['type'] = 'custom';
            }

            if ($item['group'] !== 'base') {
                if(!in_array($item['group'], $groups)) {
                    $groups[] = $item['group'];
                    $attr = array_merge($this->config->get("builder_form_base_group_attributes", []), $item['group_attributes']);
                }else{
                    $attr = array_merge(
                        $attr,
                        $item['group_attributes'],
                    );
                }

                // repeater base
                if($attr['type'] === 'base' && $attr['group_repeater']) {
                    $attr['type'] = 'repeater_'.$attr['repeater_type'];
                }

                $item['group_attributes'] = $attr;

                $item['id'] = get_group_field_id($item['group_attributes'], $item['group'], $item['field']);
                $item['name'] = get_group_field_name($item['group_attributes'], $item['group'], $item['field']);

                $item['form_attributes'] = array_merge(
                    $item['form_attributes'],
                    [
                        'group_name' => $item['group'],
                        'group_field' => $item['field'],
                        'group_key' => $item['group_attributes']['key'],
                        'group_view' => $attr['type'],
                    ]
                );
            }else{
                // group category 예외처리
                $item['group_attributes'] = [];
                $item['id'] = get_form_item_id($item['field'], $this->listForm?$this->config->item('form_side_prefix'):$this->config->item('form_page_prefix'));
                $item['name'] = $item['field'];
            }

            // view type
            $item['view'] = $item['subtype'];

            $result[] = $item;
        }

        return $result;
    }

    protected function getListColumns($name = null): array
    {
        if(isset($name)) {
            $config = $this->config->get($name, []);
        }else{
            $config = $this->resourceLoader->entityConfig($this->setConfigList(
                'list', $this->methodConfig['resourceConfig'] ?? null
            ), [], false);
        }
        $formConfig = $this->resourceLoader->entityConfig($this->setConfigList('form', $this->methodConfig['properties']['formConfig'] ?? null), [], false) ?: [];
        // list 설정이 없으면 form 설정의 list => true 항목으로 (API 와 같은 규칙, getListConfigFromForm)
        if(empty($config) && !isset($name)) $config = $this->getListConfigFromForm($formConfig);
        if(empty($config)) show_error(__METHOD__." : List Config is Empty For ".$this->router->location);

        // 목록 항목에 option_attributes 가 없으면 같은 필드의 form 설정 값을 쓴다 (셀에 코드 대신 라벨 표시)
        $formOptions = [];
        foreach ($formConfig as $formItem) {
            if(isset($formItem['field']) && !is_empty($formItem, 'option_attributes')) $formOptions[$formItem['field']] = $formItem['option_attributes'];
        }

        $this->listColumns = array_reduce($config, function($carry, $item) use ($formOptions) {
            $item = array_merge($this->config->get("builder_list_base", []), $item);

            if(is_empty($item, 'option_attributes') && isset($item['field'], $formOptions[$item['field']]))
                $item['option_attributes'] = $formOptions[$item['field']];

            // 폼·필터와 같이 option_field 가 있으면 그 필드로 옵션을 찾는다
            if(!is_empty($item, 'option_attributes'))
                $item['options'] = $this->getOptions($item['option_attributes']['option_field'] ?? $item['field'], $item['option_attributes']);

            if($item['type'] === 'hidden') $item['list'] = false;

            $carry[] = $item;
            return $carry;
        }, []);

        return array_column(array_filter($this->listColumns, function ($item) {
            return $item['list'];
        }), 'field');
    }

    protected function setListColumns($isRowNumber = true, $listActionExist = true): array
    {
        $columns = $this->getListColumns();

        $list = array_reduce(array_keys($columns), function($carry, $key) use($columns) {
            $field = $columns[$key];
            $idx = array_search($field, array_column($this->listColumns, 'field'));
            if($idx === false) return $carry;

            $item = $this->listColumns[$idx];

            $attributes = array_merge(
                $this->config->get("builder_list_base", []),
                $item['list_attributes'] ?? []
            );

            $label = is_empty($attributes, 'label')?$item['label']:$attributes['label'];
            if(!is_string($label) || $label === '') $label = $this->router->class.'.'.$field;

            if(sscanf($label, 'lang:%s', $line) === 1) $label = $line;

            if($this->lang->line_exists($label.'_list')) $label = $label.'_list';

            // 기본값 < 항목 값 < list_attributes (목록 전용 설정이 가장 우선 — 항목은 이미 기본값과 합쳐져 있어
            // 예전 순서(list_attributes, 항목)에서는 list_attributes 의 type·onclick·render 가 늘 무시됐음)
            $carry[] = array_merge($item, $item['list_attributes'] ?? [], [
                'field' => $field,
                'label' => $label ?? $this->router->class.'.'.$field,
            ]);

            return $carry;
        }, []);

        if(empty($list)) $this->logging(__METHOD__." : list columns for class '{$this->router->class}' are empty.");

        if($isRowNumber) {
            array_unshift($list,
                array_merge(
                    $this->config->get("builder_list_base", []),
                    [
                        'label' => 'lang:common.row_num',
                        'type' => 'row_num',
                    ]
                )
            );
        }

        if($listActionExist) {
            $list[] = array_merge(
                $this->config->get('builder_list_base', []),
                [
                    'label' => 'lang:common.actions',
                    'type' => 'actions',
                ]
            );
        }

        return $list;
    }

    protected function setListButtons($buttons): array
    {
        if(array_key_exists('add', $buttons) && $buttons['add']) {
            $buttons['add'] = is_auth_avaliable('create', $this->pageAuth);
        }
        if(array_key_exists('excel', $buttons) && $buttons['excel']) {
            // 가져오기 화면(excel method)이 있는 route 에서만
            $buttons['excel'] = route_has_excel_method($this->routeConfig['methods'] ?? []) && is_auth_avaliable('import', $this->pageAuth);
        }
        return $buttons;
    }

    protected function setListActions($actions): array
    {
        if(array_key_exists('view', $actions) && $actions['view']) {
            $actions['view'] = is_auth_avaliable('read', $this->pageAuth);
        }
        if(array_key_exists('edit', $actions) && $actions['edit']) {
            $actions['edit'] = is_auth_avaliable('update', $this->pageAuth);
        }
        if(array_key_exists('delete', $actions) && $actions['delete']) {
            $actions['delete'] = is_auth_avaliable('delete', $this->pageAuth);
        }
        // 복제는 새 행을 만드는 동작이므로 등록 권한이 필요
        if(array_key_exists('duplicate', $actions) && $actions['duplicate']) {
            $actions['duplicate'] = is_auth_avaliable('create', $this->pageAuth);
        }
        return reformat_bool_type_list($actions);
    }

    protected function setListExports($exports): array
    {
        $available = is_auth_avaliable('export', $this->pageAuth);
        if(!$available) {
            $exports = array_fill_keys(array_keys($exports), false);
        }
        return $exports;
    }

    protected function setFilterColumns(): array
    {
        $filterConfig = $this->resourceLoader->entityConfig($this->setConfigList(
            'filter', $this->methodConfig['properties']['filterConfig'] ?? null
        ), [], false);
        if(empty($filterConfig)) return [];

        $this->filterConfig = fill_config_properties($filterConfig, $this->config->get('builder_filter_config', []));

        return array_map(function($item) {
            if(!isset($item['colspan'])) $item['colspan'] = FILTER_BASE_COLSPAN;
            if($item['type'] === 'common') return $item;

            $item = array_merge($this->config->get("builder_form_filter_base"), $item);
            if(!isset($item['id'])) $item['id'] = get_form_item_id($item['field'], 'filter-');


            $item['name'] = $item['filter_attributes']['type'];
            if(!is_empty($item['filter_attributes'], 'subtype')) {
                $item['name'] .= '['.$item['filter_attributes']['subtype'].']';
            }else{
                $item['name'] .= '['.$item['field'].']';
            }

            if($item['type'] === 'select') {
                $item['options'] = [];
                $options = $this->getOptions($item['option_attributes']['option_field'] ?? $item['field'], $item['option_attributes']);
                if(in_array($item['option_attributes']['option_type'], ['default', 'bool', 'yn', 'gender'])) {
                    $item['options']['*'] = 'All';
                    foreach($options as $k=>$v) $item['options'][$k] = $v;
                }else{
                    $item['options'] = $options;
                }
            }

            // form attributes
            $item['form_attributes'] = array_merge(
                $this->config->get("builder_form_base_form_attributes", []),
                $item['form_attributes'] ?? []
            );

            return get_admin_form_attributes($item, 'common');
        }, $this->filterConfig['filters']);
    }

    protected function setViewColumns($name = null, $add = null, string $idPrefix = ''): array
    {
        $config = [];
        if(isset($name)) {
            $config = $this->config->get($name, []);
        }else{
            // view 설정: resourceConfig(또는 $add) → view_{class}_{method}_config → view_{class}_config 순
            $config = $this->resourceLoader->entityConfig($this->setConfigList('view', $add ?? ($this->methodConfig['resourceConfig'] ?: null)), [], false);
            if(empty($config)) {
                // view 설정이 없으면 form 설정의 필드를 읽기 전용(view)으로 보여 준다
                $config = array_values(array_map(function($item) {
                    if(!in_array($item['type'], ['hidden', 'common'], true)) {
                        $item['type'] = 'view';
                        $item['subtype'] = 'base';
                    }
                    return $item;
                }, array_filter($this->setFormColumns($this->methodConfig['properties']['formConfig'] ?: null), function ($item) {
                    return !array_key_exists('form', $item) || $item['form'];
                })));
            }
        }

        if(!empty($config)) {
            $config = array_map(function ($item) use ($idPrefix) {
                if(isset($item['subtype']) && $item['subtype'] === 'identifier') $item['type'] = 'hidden';
                if(!isset($item['category'])) $item['category'] = 'base';
                if(!isset($item['type'])) $item['type'] = 'view';
                if(!isset($item['subtype'])) $item['subtype'] = 'base';

                if($item['type'] === 'common') return $item;

                $item['id'] = $idPrefix.$item['field'];
                // 뷰 템플릿은 lang($item['label']) 로 출력하므로 'lang:' 접두어를 뗀다 (form 설정은 setFormColumn 이 이미 뗌)
                if(isset($item['label']) && is_string($item['label']) && sscanf($item['label'], 'lang:%s', $line) === 1) $item['label'] = $line;
                if($item['type'] === 'hidden'){
                    $item = array_merge($this->config->get('builder_view_hidden_config'), $item);
                }else{
                    if($item['type'] !== 'view') {
                        $item = $this->setFormColumn($item);
                        $item['attributes'] = get_admin_form_attributes($item, 'page');
                    }
                    $item = array_merge($this->config->get('builder_view_base'), $item);
                }

                return $item;
            }, $config);
        }

        return $this->viewColumns = $config;
    }

    protected function getExcelHeaders()
    {
        // excel 설정이 없으면 아래 else에서 form 컬럼으로 헤더를 만든다
        $config = $this->resourceLoader->entityConfig($this->setConfigList(
            'excel', $this->methodConfig['resourceConfig'] ?? null
        ), [], false);

        if($config) {
            return array_reduce($config, function($carry, $item) {
                if(isset($item['field'])) {
                    if(!array_key_exists('required', $item)) {
                        $item['required'] = false;
                    }
                    if(!array_key_exists('label', $item) || !$item['label']) {
                        $item['label'] = $item['field'];
                    }
                    $item['label'] = lang($item['label']);
                    $carry[] = $item;
                }
                return $carry;
            }, []);
        }else{
            $config = [];
            // excel 화면에서는 formColumns 를 미리 만들지 않으므로 여기서 form 설정을 읽는다 (form_{class}_excel → form_{class} 순)
            $formColumns = $this->formColumns ?: $this->setFormColumns($this->methodConfig['properties']['formConfig'] ?? null);
            foreach ($formColumns as $column) {
                if(!$column['form'] || !isset($column['field']) || in_array($column['type'], ['hidden', 'common', 'file', 'password'], true)) continue;
                if(in_array($column['field'], [CREATED_ID_COLUMN_NAME, CREATED_DT_COLUMN_NAME, UPDATED_ID_COLUMN_NAME, UPDATED_DT_COLUMN_NAME, DEL_YN_COLUMN_NAME, USE_YN_COLUMN_NAME, RECENT_DT_COLUMN_NAME])) continue;
                if(preg_match('/matches\[(.*?)\]/', $column['rules'], $matches)) continue;
                $config[] = [
                    'field' => $column['field'],
                    // 등록 기준 필수: required 또는 required_mod[...add...] (서버 validateExcelData 와 같은 기준)
                    'required' => (bool)preg_match('/(^|\|)required(\||$)|required_mod\[[^\]]*add/', $column['rules'] ?? ''),
                    'label' => lang($column['label']??$column['field']),
                ];
            }
            return $config;
        }
    }

    protected function getExcelSample($data): string
    {
        $sampleUri = '';
        $filename = $this->router->class.'_upload_sample.xlsx';
        $filepath = 'public'.DIRECTORY_SEPARATOR.'sample'.DIRECTORY_SEPARATOR;

        if(file_exists(FCPATH.$filepath.$filename) || count($data)) {
            $sampleUri = DIRECTORY_SEPARATOR.$filepath.$filename;

            if(!file_exists(FCPATH.$filepath.$filename) && count($data)) {
                $this->load->library('excel_lib');
                $this->load->helper('excel');
                $excel = $this->excel_lib->load();
                $excel->setActiveSheetIndex(0);
                $sheet = $excel->getActiveSheet();

                for($i = 0; $i < count($data); $i++) {
                    $alphabet = number_to_alphabet($i);
                    $sheet->setCellValue($alphabet.'1', $data[$i]['label']);

                    if($data[$i]['required']) {
                        $sheet->getStyle($alphabet.'1')
                            ->getFont()->setBold(true)
                            ->getColor()->setARGB(PHPExcel_Style_Color::COLOR_RED);
                    }

                    $sheet->getColumnDimension($alphabet)->setWidth(24);
                }
                $lastAlphabet = number_to_alphabet(count($data)-1);

                $sheet->getStyle('A1:'.$lastAlphabet.'1')->applyFromArray([
                    'alignment' => [
                        'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER, // 가로 가운데 정렬
                    ],
                    'fill' => [
                        'type' => PHPExcel_Style_Fill::FILL_SOLID,
                        'color' => ['rgb' => 'FFFF00'],
                    ],
                ]);
                $sheet->getStyle('A1:'.$lastAlphabet.'5')->applyFromArray([
                    'borders' => [
                        'allborders' => [
                            'style' => PHPExcel_Style_Border::BORDER_THIN,
                            'color' => array('rgb' => 'A6A6A6')
                        ],
                    ],
                ]);

                // 폴더가 없으면 생성
                if (!is_dir(FCPATH.$filepath)) make_directory($filepath, 0755);

                $writer = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
                $writer->save(FCPATH.$filepath . $filename);
            }
        }
        return $sampleUri;
    }

    protected function addListScripts($type)
    {
        switch ($type) {
            case 'datatable' :
                $this->addCSS[] = [
                    base_url('public/assets/builder/vendor/libs/datatables-bs5/datatables.bootstrap5.css'),
                    base_url('public/assets/builder/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css'),
                    base_url('public/assets/builder/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.css'),
                    base_url('public/assets/builder/vendor/libs/datatables-checkboxes-jquery/datatables.checkboxes.css'),
                ];

                $this->addJS['tail'][] = [
                    base_url('public/assets/builder/vendor/libs/datatables-bs5/datatables-bootstrap5.js'),
                    base_url('public/assets/builder/js/app-page-list.js'),
                ];
                break;
            default :
                break;
        }
    }

    protected function addFormScripts()
    {
        $this->addCSS[] = [
            base_url('public/assets/builder/vendor/libs/tagify/tagify.css'),
            base_url('public/assets/builder/vendor/libs/@form-validation/form-validation.css'),
            base_url('public/assets/builder/vendor/libs/bootstrap-maxlength/bootstrap-maxlength.css'),
        ];

        $this->addJS['tail'][] = [
            base_url('public/assets/builder/vendor/libs/autosize/autosize.js'),
            base_url('public/assets/builder/vendor/libs/tagify/tagify.js'),
            base_url('public/assets/builder/vendor/libs/@form-validation/popular.js'),
            base_url('public/assets/builder/vendor/libs/@form-validation/bootstrap5.js'),
            base_url('public/assets/builder/vendor/libs/@form-validation/auto-focus.js'),
            base_url('public/assets/builder/vendor/libs/bootstrap-maxlength/bootstrap-maxlength.js'),
            base_url('public/assets/builder/vendor/libs/jquery-repeater/jquery-repeater.js'),
            base_url('public/assets/builder/vendor/libs/jquery-repeater/jquery-repeater-plugins.js'),
        ];

        // wysiwig
        $this->addCSS[] = [
            base_url('public/assets/builder/vendor/libs/quill/typography.css'),
            base_url('public/assets/builder/vendor/libs/quill/katex.css'),
            base_url('public/assets/builder/vendor/libs/quill/editor.css'),
        ];

        // wysiwig (app-page-preset.js 의 toolbarDefault 를 app-page-form.js 가 quill 툴바로 쓴다 — head.php 에서 빠져 있어 quill 폼이 ReferenceError)
        $this->addJS['tail'][] = [
            base_url('public/assets/builder/vendor/libs/quill/katex.js'),
            base_url('public/assets/builder/vendor/libs/quill/quill.js'),
            base_url('public/assets/builder/js/app-page-preset.js'),
        ];
    }

    public function addFormAssets($assetName)
    {
        if(!in_array($assetName, $this->formAssets)) {
            $this->formAssets[] = $assetName;

            switch($assetName) {
                case 'select2' :
                    $this->addCSS[] = [
                        base_url(BUILDER_ASSET_VENDOR_URI.'/libs/select2/select2.css'),
                    ];

                    $this->addJS['tail'][] = [
                        base_url(BUILDER_ASSET_VENDOR_URI.'/libs/select2/select2.js'),
                    ];
                    break;
                case 'coloris' :
                    $this->addCSS[] = [
                        base_url(BUILDER_ASSET_LIBS_URI.'/coloris/coloris.min.css'),
                    ];

                    $this->addJS['tail'][] = [
                        base_url(BUILDER_ASSET_LIBS_URI.'/coloris/coloris.min.js'),
                    ];
                    break;
                case 'custom-toggle' :
                    $this->addCSS[] = [
                        base_url(BUILDER_ASSET_LIBS_URI.'/custom-toggle/style.css'),
                    ];

//                    $this->addJS['tail'][] = [
//                        base_url(BUILDER_ASSET_LIBS_URI.'/custom-toggle/common.js'),
//                    ];
                    break;
                case 'custom-carousel-simple' :
                    $this->addCSS[] = [
                        base_url(BUILDER_ASSET_LIBS_URI.'/custom-carousel-simple/style.css'),
                    ];

                    $this->addJS['tail'][] = [
                        base_url(BUILDER_ASSET_LIBS_URI.'/custom-carousel-simple/common.js'),
                    ];
                    break;
                case 'domain-register' :
                    $this->addCSS[] = [
                        base_url(BUILDER_ASSET_LIBS_URI.'/domain-register/style.css'),
                    ];

                    $this->addJS['tail'][] = [
                        base_url(BUILDER_ASSET_LIBS_URI.'/domain-register/common.js'),
                    ];
                    break;
                case 'ip-register' :
                    $this->addCSS[] = [
                        base_url(BUILDER_ASSET_LIBS_URI.'/ip-register/style.css'),
                    ];

                    $this->addJS['tail'][] = [
                        base_url(BUILDER_ASSET_LIBS_URI.'/ip-register/common.js'),
                    ];
                    break;
            }
        }
    }

    public function _remap($method, $params = [])
    {
        $this->checkLoggedIn();

        // route methods에 없는 method (setMethodConfig 참고): 로그인 검사 후 route index로
        if($this->methodNotInRoute) redirect($this->href);

        if($this->isBuilderAvailable()){
            $this->setupPlatform();

            if(!method_exists($this, $method)) {
                // 1) perform 메소드 실행
                if(!is_empty($this->methodConfig['properties'], 'perform')) {
                    if (method_exists($this, $this->methodConfig['properties']['perform'])) {
                        $this->{$this->methodConfig['properties']['perform']}();
                    }else{
                        show_error(__METHOD__.' : Performing Method is not found : '.$this->methodConfig['properties']['perform']);
                    }
                }

                // 2) redirect
                if(!is_empty($this->methodConfig['properties'], 'redirectUri')) {
                    redirect(base_url($this->methodConfig['properties']['redirectUri']));
                }
            }

            // 3) 본래 메소드 실행
            if (method_exists($this, $method)) {
                return call_user_func_array([$this, $method], $params);
            }

            trigger_error(__METHOD__." : Couldn't find the page you are looking for");
            $this->blank();
//            show_404();
        }
    }

    protected function checkLoggedIn()
    {
        // 다른 곳에서 같은 계정으로 로그인해 토큰이 바뀌었으면 로그아웃 (API 는 이미 그 토큰을 거부함)
        if($this->session->userdata('token') && !$this->isSessionTokenCurrent()) {
            $this->destroyUserData();
            alert(lang('Logged in elsewhere'), base_url($this->noLoginRedirect));
        }

        if($this->session->userdata('token')) {
            $this->sessionData = $this->session->userdata();
            $this->isSystemAdmin = $this->session->userdata('is_sys_admin') ?? false;
            $this->isLoggedIn = true;
        }else{
            $this->isLoggedIn = false;
        }

        if(empty($this->routeConfig)) {
            alert(lang('Incorrect Access'), base_url($this->noLoginRedirect));
        }

        if(!$this->isLoggedIn && !$this->routeConfig['properties']['allowNoLogin']) {
            $this->destroyUserData();
            // 토큰 만료로 끊긴 경우는 안내 문구를 다르게
            alert(lang($this->tokenExpired && !$this->session->userdata('token') ? 'Session expired' : 'Login Needed'), base_url($this->noLoginRedirect));
        }
    }

    protected function setupPlatform()
    {
        $this->menuList = $this->setupPlatformMenu();

        $this->setupPlatformRedirect();

        $this->setupPlatformPage();

        $this->setJSVariables();
    }

    protected function setupPlatformMenu(): array
    {
        $menuList = $this->setMenuList();

        if($this->isLoggedIn){
            // 메뉴 DB·권한을 쓰지 않는 모드: 현재 메뉴만 정하고 권한은 전체 허용(setupPlatformPage가 INIT_AUTH_CHAR 사용)
            if(!env('CACHING_MENU')) return $this->setupPlatformMenuWithoutAuth($menuList);

            if(!$this->isSystemAdmin) {
                $authList = $this->Model_Menu_Auth->getList([], [
                    'where' => [
                        'grade_cd' => $this->sessionData['user_cd'],
                        'is_show' => 1,
                    ],
                ]);

                $menuList = $this->setupPlatformMenuAuth($menuList, $authList);
            }
        }

        return $menuList;
    }

    /**
     * CACHING_MENU=false 일 때 메뉴 처리.
     * 메뉴 권한 데이터(menu_auth)가 없으므로 시스템 관리자가 아니면 isSuper 메뉴만 빼고,
     * 현재 메뉴(currentMenu)를 정해 setupPlatformPage()가 로그인 사용자를 계속 리다이렉트하지 않게 한다.
     */
    protected function setupPlatformMenuWithoutAuth($menuList): array
    {
        return array_values(array_reduce($menuList ?: [], function($acc, $item) {
            if(!$this->isSystemAdmin && !empty($item['isSuper'])) return $acc;

            if($item['isSubMenu'] && !empty($item['subMenu'])) {
                $item['subMenu'] = $this->setupPlatformMenuWithoutAuth($item['subMenu']);
            }

            $this->setCurrentMenu($item);
            $acc[] = $item;

            return $acc;
        }, []));
    }

    protected function setupPlatformMenuAuth($menuList, $authList = [])
    {
        $menuIdList = array_column($authList, 'menu_id');

        return array_reduce($menuList, function($acc, $item) use($authList, $menuIdList) {
            $valid = false;
            if($item['isAuth']) {
                if(in_array($item['menuId'], $menuIdList)) {
                    $idx = array_search($item['menuId'], $menuIdList);
                    $item['menuAuth'] = $authList[$idx]->menu_auth;
                    $item['scopeCd'] = $authList[$idx]->scope_cd;
                    if($item['isSubMenu']) {
                        $item['subMenu'] = $this->setupPlatformMenuAuth($item['subMenu'], $authList);
                    }
                    $valid = true;
                }
            }else{
                $valid = true;
            }

            if($valid) {
                $this->setCurrentMenu($item);
                $acc[] = $item;
            }

            return $acc;
        }, []);
    }

    protected function setCurrentMenu($menuData)
    {
        if(
            $this->router->class === $menuData['class']
        ) {
            $exact = $this->router->method === $menuData['method'];
            if(
                $exact
                ||
                $this->router->method === $this->routeConfig['properties']['baseMethod']
                ||
                in_array($this->router->method, array_keys($this->routeConfig['methods']))
            ) {
                // 같은 class 메뉴가 여럿이면(frameLogs/summary·traffics 등) method 가 같은 메뉴를 우선 (권한 행이 메뉴마다 다름)
                $currentExact = !empty($this->currentMenu) && $this->router->method === ($this->currentMenu['method'] ?? null);
                if($exact || !$currentExact) $this->currentMenu = $menuData;
            }
        }
    }

    protected function setupPlatformPage()
    {
        if($this->isLoggedIn && !$this->isSystemAdmin) {
            if(empty($this->currentMenu)) {
                redirect(base_url($this->loggedInRedirect));
            }

            $this->pageAuth = $this->currentMenu['menuAuth'] ?? INIT_AUTH_CHAR;
            $this->scope = $this->currentMenu['scopeCd'] ?? '';

            $baseMethod = $this->routeConfig['properties']['baseMethod'];
            $currMethod = $this->router->method === 'index' ? $baseMethod : $this->router->method;

            // 메뉴가 가리키는 화면(메뉴의 method 또는 기준 화면)은 메뉴 표시만 확인 — 표시 = 첫 화면 열람
            $menuMethod = $this->currentMenu['method'] ?? '';
            if(in_array($menuMethod, ['', 'index'], true)) $menuMethod = $baseMethod;
            if($currMethod === $baseMethod || $currMethod === $menuMethod) return;

            // 그 안에서 여는 등록·수정·상세 등은 API 권한 검사(MY_Builder_API::checkApiPermission)와 같은 기준. 'a|b' 는 하나만 켜져 있어도 통과
            $mode = $this->getPageAuthMode($currMethod);
            if($mode === '') return;
            foreach (explode('|', $mode) as $item) {
                if(is_auth_avaliable($item, $this->pageAuth)) return;
            }

            alert('Not Authorized', $this->href . DIRECTORY_SEPARATOR . $baseMethod);
        }
    }

    /**
     * method 설정에 mode 가 없을 때 method 이름으로 정하는 권한 자리
     */
    protected array $pageAuthMethodModes = [
        'add' => 'create', 'edit' => 'update', 'view' => 'read|update', 'excel' => 'import',
    ];

    /**
     * 화면을 여는 데 필요한 권한 자리. '' 는 메뉴 표시만 확인 (메뉴가 가리키는 화면은 이 함수 전에 통과)
     *  - 목록: '' (API 목록 GET 과 같음)
     *  - 상세(category view): 'read|update' (API 단건 GET 과 같음)
     *  - 그 밖: method 설정의 mode, 없으면 $pageAuthMethodModes. mode 'read' 는 상세 전용 자리라 '' 로 봄
     */
    protected function getPageAuthMode(string $method): string
    {
        $category = $this->methodConfig['category'] ?? '';
        if($category === 'list') return '';

        $mode = (string)($this->methodConfig['mode'] ?? '');
        if($category === 'view' && in_array($mode, ['', 'read'], true)) return 'read|update';
        if($mode === 'read') return '';
        if($mode !== '') return $mode;

        return $this->pageAuthMethodModes[$method] ?? '';
    }

    protected function setupPlatformRedirect()
    {
        if($this->isLoggedIn) {
            // menuList는 isUse 필터로 키가 비어 있을 수 있고 첫 항목이 하위 메뉴 묶음일 수 있음 → class 있는 첫 메뉴, 없으면 설정값 유지
            $firstMenu = null;
            foreach ($this->menuList as $menu) {
                foreach ($menu['isSubMenu'] ? ($menu['subMenu'] ?? []) : [$menu] as $candidate) {
                    if(!empty($candidate['class'])) {
                        $firstMenu = $candidate;
                        break 2;
                    }
                }
            }
            if($firstMenu) {
                $class = $firstMenu['class'];
                $method = $firstMenu['method'] === 'index' ? '' : $firstMenu['method'];
                $this->loggedInRedirect = $this->baseUri . DIRECTORY_SEPARATOR . $class . ($method ? DIRECTORY_SEPARATOR . $method : '');
            }

//            switch ($this->sessionData['user_cd']) {
//                case 'USR001':
//                    $this->loggedInRedirect = 'admin/administrators';
//                    break;
//                case 'USR002':
//                    $this->loggedInRedirect = 'admin/frames';
//                    break;
//            }
        }

        if(in_array(
            $this->router->class,
            $this->config->get('platform_config.notAllowAccess', [])
        )) {
            redirect(base_url($this->isLoggedIn?$this->loggedInRedirect:$this->noLoginRedirect));
        }

        $this->addJsVars([
            'LOGGED_IN_REDIRECT' => base_url($this->loggedInRedirect),
            'NO_LOGIN_REDIRECT' => base_url($this->noLoginRedirect),
        ]);
    }

    protected function addErrors($data)
    {
        $this->errors[] = $data;
    }
}
