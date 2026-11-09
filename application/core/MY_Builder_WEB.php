<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'traits/BuilderInitTrait.php';
require_once APPPATH . 'traits/BuilderCommonTrait.php';
require_once APPPATH . 'traits/BuilderColumnsTrait.php';
require_once APPPATH . 'traits/BuilderWebListTrait.php';
require_once APPPATH . 'traits/BuilderWebFormTrait.php';
require_once APPPATH . 'traits/BuilderWebExcelTrait.php';
require_once APPPATH . 'traits/BuilderWebPlatformTrait.php';

class MY_Builder_WEB extends MY_Controller_WEB
{
    use BuilderInitTrait;
    use BuilderCommonTrait;
    use BuilderColumnsTrait;
    use BuilderWebListTrait;
    use BuilderWebFormTrait;
    use BuilderWebExcelTrait;
    use BuilderWebPlatformTrait;

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
    // 권한 필터 전 전체 메뉴 (화면을 가리키는 메뉴가 따로 있는지 볼 때 씀)
    protected array $fullMenuList = [];
    public array $currentMenu = [];
    public array $formAssets = [];
    public bool $isLoggedIn = false;
    public bool $isAdmin = false;
    public string $pageAuth = INIT_AUTH_CHAR;
    public string $scope = '';
    public bool $isSystemAdmin = false;
    public array $errors = [];
    public bool $methodNotInRoute = false;
    /**
     * route methods 에 없어도 부를 수 있는 공통 메서드 (화면의 기준 메서드 설정·메뉴 권한을 빌려 씀).
     * downloader: 상세·수정 폼 첨부, 목록 download 셀의 {CURRENT_URI}/downloader/{file_id}
     */
    protected array $commonMethods = ['downloader'];
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

    /**
     * 파일 다운로드. 파일이 없거나 번호가 잘못되면 빈 화면 대신 알림 후 이전 화면으로
     */
    /**
     * 비공개 파일은 올린 화면의 주소로, 로그인한 사용자 중 그 화면 읽기 권한이 있어야 받는다
     */
    protected function canAccessPrivateFile($file, string $class): bool
    {
        if(!$this->isLoggedIn || strcasecmp($class, $this->router->class) !== 0) return false;
        if(!$this->isSystemAdmin && !is_auth_avaliable('read', $this->pageAuth)) return false;
        return $this->canDownloadFile($file);
    }

    /**
     * 행 단위 소유 확인이 필요한 화면은 재정의한다 (file_id 와 행의 연결은 엔티티마다 다르다)
     */
    protected function canDownloadFile($file): bool
    {
        return true;
    }

    public function downloader($key = 0)
    {
        $result = parent::downloader($key);
        if($result !== true) alert(lang('File Not Found'));
    }

    protected function checkIdentifierExist($key = 0)
    {
        if( !($this->routeConfig['properties']['noIdentifier'] || $this->methodConfig['properties']['noIdentifier']) ) {
            $idData = $this->getIdentifierData($key, $this->jsVars['IDENTIFIER']);

            if(empty($idData)) alert(lang('Incorrect Access'));

            $this->addJsVars(['KEY' => count($idData)===1?array_values($idData)[0]:$idData]);
        }
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

        if(!array_key_exists($method, $config) && empty($data) && in_array($method, $this->commonMethods, true)) {
            // 공통 메서드: 기준 화면 설정으로 (권한은 setupPlatformPage 가 그 화면 기준으로 확인)
            $method = $this->routeConfig['properties']['baseMethod'];
        }

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
                    'FORM_DATA' => $this->skipHiddenTabValidation($this->setFormData()),
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

    /**
     * method 설정에 mode 가 없을 때 method 이름으로 정하는 권한 자리
     */
    protected array $pageAuthMethodModes = [
        'add' => 'create', 'edit' => 'update', 'view' => 'read|update', 'excel' => 'import',
    ];

    protected function addErrors($data)
    {
        $this->errors[] = $data;
    }
}
