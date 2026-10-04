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


    public function __construct()
    {
        parent::__construct();

        $this->config->load('extra/autologin_config', false);

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
        if($this->methodConfig['subtype'] === 'view') {
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

        if(!array_key_exists('subPage', $data))
            $data['subPage'] = 'builder/layout/list';

        $this->addListScripts($this->methodConfig['subtype']);

        return $data;
    }

    protected function prepareViewData($data): array
    {
        $data['viewType'] = $this->methodConfig['subtype'];

        $data['viewData'] = reformat_form_data_by_type($this->jsVars['VIEW_COLUMNS'], $data['viewType']);

        $data['identifier'] = array_filter($this->viewColumns, function ($item) {
            return array_key_exists('field', $item)&&in_array($item['field'], $this->jsVars['IDENTIFIER']);
        });

        $data['actions'] = array_values(array_filter(reformat_bool_type_list($this->methodConfig['actions']), function ($action) {
            return $action === 'delete' || in_array($action, $this->routeConfig['properties']['allows']);
        }));
        $data['buttons'] = $this->methodConfig['buttons']??[];

        if(count(array_filter($this->viewColumns, function($item) {
            return $item['type'] !== 'view';
        }))) {
            $this->addFormScripts();
        }

        $data['isComments'] = $this->methodConfig['properties']['isComments'];
        if($data['isComments']) {
            $this->addJS['tail'][] = [
                base_url('public/assets/builder/js/app-page-comment.js')
            ];
        }

        if(!array_key_exists('subPage', $data))
            $data['subPage'] = 'builder/layout/view';

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
            if(in_array('dropzone',array_column($this->formColumns, 'subtype'))) {
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
        if(!file_exists(PLATFORM_ASSET_CSS_PATH.'style.css')){
            $file = fopen(PLATFORM_ASSET_CSS_PATH.'style.css',"w");
            if(!$file) show_error("viewApp : Unable to open file!", E_USER_ERROR);
            fclose($file);
        }
        $this->addCSS[] = [
            base_url(PLATFORM_ASSET_CSS_URI.'style.css'),
        ];

        if(!file_exists(PLATFORM_ASSET_JS_PATH.'common.js')){
            $file = fopen(PLATFORM_ASSET_JS_PATH.'common.js',"w");
            if(!$file) show_error("viewApp : Unable to open file!", E_USER_ERROR);
            fclose($file);
        }
        $this->addJS['tail'][] = [
            base_url(PLATFORM_ASSET_JS_URI.'common.js'),
        ];

        foreach (['_preset', '_onload'] as $subfix) {
            $filename = snakeize($this->router->class);
            if(!in_array(snakeize($this->router->method), ['index','list','add','edit','excel'])) {
                $filename .= '_'.snakeize($this->router->method);
            }
            $filename .= $subfix.'.js';

            if(!file_exists(PLATFORM_ASSET_JS_PATH.$filename)){
                $file = fopen(PLATFORM_ASSET_JS_PATH.$filename,"w");
                if(!$file) show_error("viewApp : Unable to open file!", E_USER_ERROR);
                fclose($file);
            }

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
                    if(file_exists(VIEWPATH.$path.$method.'.php')) $view = $path.$method;
                    if($view) break;
                }
            }

            if(is_null($view) || !file_exists(VIEWPATH.$view.'.php')){
                $this->addErrors([
                    'message' => "viewApp : View file for {$this->router->class}:{$method} does not exist.",
                    'code' => E_USER_ERROR,
                ]);
//                trigger_error("viewApp : View file for {$this->router->class}:{$method} does not exist.", E_USER_ERROR);
                $view['subPage'] = BUILDER_FLAGNAME.DIRECTORY_SEPARATOR.'layout'.DIRECTORY_SEPARATOR.'blank';
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

        $className = '';
        if(!is_empty($config, $this->router->class)) {
            $className = $this->router->class;
        }
        if(!is_empty($config, strtolower($this->router->class))) {
            $className = strtolower($this->router->class);
        }

        if($className === '')
            trigger_error(__METHOD__." : Class '{$className}' does not exist in route config.");


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
                trigger_error(__METHOD__." : Method '$method' does not exist in route config.");
            }
            $this->index();
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
            $this->methodConfig = $this->config->get("method_base_config", []);
            return;
        }

        if($methodConfig['category'] === 'action') {
            $this->methodConfig = $methodConfig;
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
                        'LIST_PAGING' => true,
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
                break;
            case 'view' :
                if($this->methodConfig['subtype'] === 'view') {
                    $this->addJsVars([
                        'VIEW_COLUMNS' => $this->setViewColumns(),
                        'VIEW_TYPE' => $methodConfig['subtype'],
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
            $config = $this->config->get($this->setConfigList(
                'list', $this->methodConfig['resourceConfig'] ?? null
            ), [], false);
        }
        if(empty($config)) show_error(__METHOD__." : List Config is Empty For ".$this->router->location);

        $this->listColumns = array_reduce($config, function($carry, $item) {
            $item = array_merge($this->config->get("builder_list_base", []), $item);

            if(!is_empty($item, 'option_attributes'))
                $item['options'] = $this->getOptions($item['field'], $item['option_attributes']);

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

            if(sscanf($label, 'lang:%s', $line) === 1) $label = $line;

            if($this->lang->line_exists($label.'_list')) $label = $label.'_list';

            $carry[] = array_merge($attributes, $item, [
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
            $buttons['excel'] = is_auth_avaliable('import', $this->pageAuth);
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
        $filterConfig = $this->config->get($this->setConfigList(
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

    protected function setViewColumns($name = null): array
    {
        $config = [];
        if(isset($name)) {
            $config = $this->config->get($name, []);
        }else{
            if($name = $this->methodConfig['resourceConfig']) {
                $config = $this->config->get('view_'.$name.'_config', []);
            }else{
                $config = array_map(function($item) {
                    if($item['type'] !== 'hidden') {
                        $item['type'] = 'view';
                        $item['subtype'] = 'base';
                    }
                    return $item;
                }, $this->jsVars['FORM_DATA']);
            }
        }

        if(!empty($config)) {
            $config = array_map(function ($item) {
                if(isset($item['subtype']) && $item['subtype'] === 'identifier') $item['type'] = 'hidden';
                if(!isset($item['category'])) $item['category'] = 'base';
                if(!isset($item['type'])) $item['type'] = 'view';
                if(!isset($item['subtype'])) $item['subtype'] = 'base';

                if($item['type'] === 'common') return $item;

                $item['id'] = $item['field'];
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
        $this->filterConfig = $this->config->get($this->setConfigList(
            'excel', $this->methodConfig['resourceConfig'] ?? null
        ), [], false);
        if(empty($config)) show_error(__METHOD__." : Excel Config is Empty For ".$this->router->location);

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
            foreach ($this->formColumns as $column) {
                if(!$column['form'] || !isset($column['field']) || $column['type'] === 'hidden') continue;
                if(in_array($column['field'], [CREATED_ID_COLUMN_NAME, CREATED_DT_COLUMN_NAME, UPDATED_ID_COLUMN_NAME, UPDATED_DT_COLUMN_NAME, DEL_YN_COLUMN_NAME, USE_YN_COLUMN_NAME, RECENT_DT_COLUMN_NAME])) continue;
                if(preg_match('/matches\[(.*?)\]/', $column['rules'], $matches)) continue;
                $config[] = [
                    'field' => $column['field'],
                    'required' => strpos($column['rules'], 'required')!==false,
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

        // wysiwig
        $this->addJS['tail'][] = [
            base_url('public/assets/builder/vendor/libs/quill/katex.js'),
            base_url('public/assets/builder/vendor/libs/quill/quill.js'),
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
            alert(lang('Login Needed'), base_url($this->noLoginRedirect));
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
            if(!env('CACHING_MENU')) return $menuList;

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
        });
    }

    protected function setCurrentMenu($menuData)
    {
        if(
            $this->router->class === $menuData['class']
        ) {
            if(
                $this->router->method === $menuData['method']
                ||
                $this->router->method === $this->routeConfig['properties']['baseMethod']
                ||
                in_array($this->router->method, array_keys($this->routeConfig['methods']))
            ) {
                $this->currentMenu = $menuData;
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

            if($this->pageAuth === BASE_AUTH_CHAR) return;

            $baseMethod = $this->routeConfig['properties']['baseMethod'];
            $currMethod = $this->router->method === 'index' ? $baseMethod : $this->router->method;
            if($this->routeConfig['methods'][$baseMethod]['category'] === 'list') {
                if($currMethod !== $baseMethod) {
                    if(!get_auth_value($this->methodConfig['mode'], $this->pageAuth)) {
                        alert('Not Authorized', $this->href . DIRECTORY_SEPARATOR . $this->routeConfig['properties']['baseMethod']);
                    }
                }
            }
        }
    }

    protected function setupPlatformRedirect()
    {
        if($this->isLoggedIn) {
            $firstMenu = $this->menuList[0];
            $class = $firstMenu['class'];
            $method = $firstMenu['method'] === 'index' ? '' : $firstMenu['method'];
            $this->loggedInRedirect = $this->baseUri . DIRECTORY_SEPARATOR . $class . ($method ? DIRECTORY_SEPARATOR . $method : '');

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
