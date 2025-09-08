<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'traits/BuilderInitTrait.php';
require_once APPPATH . 'traits/BuilderCommonTrait.php';

class MY_Builder_WEB extends MY_Controller_WEB
{
    use BuilderInitTrait;
    use BuilderCommonTrait;

    public string $flag = '';
    public string $apiFlag = '';
    public string $baseUri = '';
    public string $apiUri = '';
    public array $routeConfig = [];
    public array $methodConfig = [];
    public array $pageConfig = [];
    public string $pageType = 'form';
    public bool $listForm = false;
    public array $userData = [];
    public array $headerData = [];
    public object $loginData;
    public string $href;
    public array $listColumns = [];
    public array $filterConfig = [];
    public array $formColumns = [];
    public array $viewColumns = [];
    public string $viewPath;
    public array $menuList = [];
    public array $menuAuth = [];
    public bool $isLogin = false;
    public bool $isAdmin = false;

    public function __construct()
    {
        parent::__construct();

        $this->config->load('extra/autologin_config', false);

        if(!$this->flag) show_error("Platform flag is not set.");

        $this->loadConfigs(['builder_base_config', 'builder_form_config', 'builder_nav_config', 'builder_page_config', 'builder_list_config', 'builder_filter_config', 'builder_view_config']);

        $this->baseViewPath = BUILDER_FLAGNAME."/layout/index";
        $this->baseUri = $this->flag === $this->router->routes['default_platform'] ? '' : $this->flag;
        $this->apiUri = base_url($this->flag . DIRECTORY_SEPARATOR . $this->apiFlag . DIRECTORY_SEPARATOR);
        $this->isLoginRedirect = "$this->baseUri/{$this->config->item('platform_config.isLoginRedirect')}";
        $this->noLoginRedirect = "$this->baseUri/{$this->config->item('platform_config.noLoginRedirect')}";

        $this->titleList = [ucfirst($this->flag)];
        $this->href = base_url("$this->baseUri/{$this->router->class}");
        $this->viewPath = "$this->flag/{$this->router->class}";
        $this->jsVars = [
            'TITLE' => $this->router->class,
            'API_BASE_URI' => $this->apiUri,
            'API_URI' => '',
            'API_PARAMS' => [],
        ];

        $this->setRouteConfig();
        $this->setMethodConfig();
    }

    public function index()
    {
        parent::index();

        if($this->routeConfig['properties']['noIndex']) show_404();

        if(empty($this->routeConfig['methods'])) {
            $data['subPage'] = '';
            $data['backLink'] = WEB_HISTORY_BACK;
            $this->viewApp($data);
        }else{
            if(!$this->routeConfig['properties']['allowNoLogin'] && !$this->isLogin){
                redirect($this->noLoginRedirect);
            }

            if($this->router->class === 'common') {
                redirect("$this->baseUri/$this->defaultController");
            }

            $this->{"{$this->routeConfig['properties']['baseMethod']}"}();
        }
    }

    public function list()
    {
        $this->titleList[] = 'List';

        $data['backLink'] = WEB_HISTORY_BACK;
        $data = $this->prepareListData($data);

        $this->addJS['tail'][] = [
            base_url('public/assets/builder/js/app-page-list.js'),
        ];

        $this->viewApp($data);
    }

    public function view($key = 0)
    {
        $this->checkIdentifierExist($key);

        $this->titleList[] = 'View';

        $data['backLink'] = WEB_HISTORY_BACK;
        $data = $this->prepareViewData($data);

        $this->addJS['tail'][] = [
            base_url('public/assets/builder/js/app-page-view.js'),
        ];

        $this->viewApp($data);
    }

    public function add()
    {
        if($this->listForm) show_404();

        $this->titleList[] = 'Add';

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

        $data['backLink'] = WEB_HISTORY_BACK;
        $data = $this->prepareFormData($data);

        $this->addJS['tail'][] = [
            base_url('public/assets/builder/js/app-page-edit.js'),
        ];

        $this->viewApp($data);
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
        $data['filters'] = $this->jsVars['LIST_FILTERS']??[];
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
        $data['formType'] = $this->methodConfig['subtype'];
        $data['formData'] = restructure_form_data_by_type($this->jsVars['FORM_DATA'], $data['formType']);

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
        // common
        $data['userData'] = $this->userData;
        $data['headerData'] = $this->headerData;
        $data['includes'] = $this->routeConfig['properties']['includes'];
        $data['platformName'] = PLATFORM_NAME??'builder';
        $data['hideBack'] = element('hideBack', $data);

        // builder attributes
        $data['htmlAttrs'] = get_builder_html_attributes($this->flag);
        $data['bodyAttrs'] = get_builder_body_attributes(ENVIRONMENT !== 'production');

        // menu
        $data['menus'] = $this->menuList;

        return $data;
    }

    protected function afterViewApp($data = []): array
    {
        if(!file_exists(PLATFORM_ASSET_CSS_PATH.'style.css')){
            $file = fopen(PLATFORM_ASSET_CSS_PATH.'style.css',"w");
            if(!$file) trigger_error("viewApp : Unable to open file!", E_USER_ERROR);
            fclose($file);
        }
        $this->addCSS[] = [
            base_url(PLATFORM_ASSET_CSS_URI.'style.css'),
        ];

        if(!file_exists(PLATFORM_ASSET_JS_PATH.'common.js')){
            $file = fopen(PLATFORM_ASSET_JS_PATH.'common.js',"w");
            if(!$file) trigger_error("viewApp : Unable to open file!", E_USER_ERROR);
            fclose($file);
        }
        $this->addJS['tail'][] = [
            base_url(PLATFORM_ASSET_JS_URI.'common.js'),
        ];

        foreach (['_preset', '_onload'] as $filename) {
            if(!file_exists(PLATFORM_ASSET_JS_PATH.strtolower($this->router->class).$filename.'.js')){
                $file = fopen(PLATFORM_ASSET_JS_PATH.strtolower($this->router->class).$filename.'.js',"w");
                if(!$file) trigger_error("viewApp : Unable to open file!", E_USER_ERROR);
                fclose($file);
            }
        }
        $this->addJS['head'][] = [
            base_url(PLATFORM_ASSET_JS_URI.strtolower($this->router->class).'_preset.js'),
        ];
        $this->addJS['tail'][] = [
            base_url(PLATFORM_ASSET_JS_URI.strtolower($this->router->class).'_onload.js'),
        ];

        return $data;
    }

    protected function viewApp($data = [])
    {
        $data = $this->beforeViewApp($data);

        if(!array_key_exists('subPage', $data)) {
            $view = null;
            $method = $this->router->method === 'index'?$this->routeConfig['properties']['baseMethod']:$this->router->method;

            foreach ([get_path(), BUILDER_FLAGNAME] as $firstPath) {
                if(!file_exists(VIEWPATH.$firstPath)) continue;
                foreach ([$this->router->class, 'layout'] as $secondPath) {
                    $path = $firstPath.DIRECTORY_SEPARATOR.$secondPath.DIRECTORY_SEPARATOR;
                    if(file_exists(VIEWPATH.$path.$method.'.php')) $view = $path.$method;
                    if($view) break;
                }
            }

            if(is_null($view) || !file_exists(VIEWPATH.$view.'.php')){
                trigger_error("viewApp : View file for {$this->router->class}:{$method} does not exist.", E_USER_ERROR);
            }else{
                $data['subPage'] = $view;
            }
        }

        if($this->baseViewPath===$data['subPage']) trigger_error('view file is not set.', E_USER_ERROR);

        $data = $this->afterViewApp($data);

        parent::viewApp($data);
    }

    protected function fillRouteConfigProperties($config = []): array
    {
        foreach ($this->config->get("page_base_config", []) as $key=>$val) {
            if(!array_key_exists($key, $config)) {
                $config[$key] = $val;
            }else{
                if(is_array($val)) {
                    foreach ($val as $subKey=>$subVal) {
                        if(!array_key_exists($subKey, $config[$key])) {
                            $config[$key][$subKey] = $subVal;
                            continue;
                        }
                        if(is_array($subVal)) {
                            $config[$key][$subKey] = array_merge($subVal, $config[$key][$subKey]);
                        }
                    }
                }else{
                    $config[$key] = $config[$key]??$val;
                }
            }
        }
        $config['properties']['allows'] = array_keys($config['methods']);
        return $config;
    }

    protected function setRouteConfig(): void
    {
        $routeConfig = [];
        if(
            !is_empty($this->config->item("page_config"), $this->router->class)
            ||
            !is_empty($this->config->item("page_config"), strtolower($this->router->class))
        ){
            $routeConfig = $this->config->get("page_config")[$this->router->class]??$this->config->get("page_config")[strtolower($this->router->class)];
            if(is_empty($routeConfig, 'properties')) $routeConfig['properties'] = [];
            if(!array_key_exists( 'allows', $routeConfig['properties'])) $routeConfig['properties']['allows'] = [];
            if(empty($routeConfig['properties']['allows'])) $routeConfig['properties']['allows'][] = $routeConfig['properties']['baseMethod'];
        }

        $this->routeConfig = $this->fillRouteConfigProperties($routeConfig);
    }

    protected function setMethodConfig($data = []): void
    {
        if(empty($this->routeConfig['methods'])) return;

        $method = $this->router->method === 'index' ? $this->routeConfig['properties']['baseMethod'] : $this->router->method;
        if(!array_key_exists($method, $this->routeConfig['methods'])) show_404();

        $methodConfig = $this->routeConfig['methods'][$method];
        if($methodConfig['type'] === 'action') {
            $this->methodConfig = $methodConfig;
            return;
        }

        $type = array_key_exists('type', $methodConfig)?$methodConfig['type']:'base';
        $subtype = array_key_exists('subtype', $methodConfig)?$methodConfig['subtype']:'base';

        $baseMethodConfig = [];
        if($this->config->item("page_{$method}_base_config")) {
            $baseMethodConfig = $this->config->item("page_{$method}_base_config");
        }elseif ($this->config->item("page_{$type}_{$subtype}_config")) {
            $baseMethodConfig = $this->config->item("page_{$type}_{$subtype}_config");
        }elseif ($this->config->item("page_{$type}_base_config")) {
            $baseMethodConfig = $this->config->item("page_{$type}_base_config");
        }
        if(!count($baseMethodConfig)) show_error('Method Config is empty');

        foreach ($baseMethodConfig as $key=>$value) {
            if(array_key_exists($key, $methodConfig)){
                if(is_array($value)) {
                    $methodConfig[$key] = array_merge($value, $methodConfig[$key]);
                }
            }else{
                $methodConfig[$key] = $value;
            }
        }
        $this->methodConfig = $methodConfig;

        // method 별.
        switch ($methodConfig['type']) {
            case 'list' :
                $this->addJsVars([
                    'LIST_COLUMNS' => $this->setListColumns(),
                    'LIST_PLUGIN' => $methodConfig['properties']['plugin'],
                    'LIST_FILTERS' => $this->setListFilters(),
                    'LIST_BUTTONS' => $methodConfig['buttons'],
                    'LIST_ACTIONS' => reformat_bool_type_list($methodConfig['actions']),
                    'LIST_OPTIONS' => $methodConfig['properties'],
                    'LIST_EXPORTS' => reformat_bool_type_list($methodConfig['properties']['exports']),
                    'LIST_CHEKBOX' => $methodConfig['properties']['isCheckbox'],
                    'LIST_PAGING' => true,
                ]);

                if($methodConfig['properties']['formExist']) {
                    $this->listForm = true;
                    $this->formColumns = $this->setFormColumns($methodConfig['properties']['formConfig']);
                    $this->addJsVars([
                        'FORM_DATA' => $this->setFormData(),
                        'FORM_REGEXP' => $this->config->item('regexp'),
                        'FORM_TYPE' => $methodConfig['properties']['formType'],
                        'FORM_EXIST' => true,
                    ]);
                }
                break;
            case 'view' :
                $this->addJsVars([
                    'VIEW_COLUMNS' => $this->setViewColumns(),
                    'VIEW_TYPE' => $methodConfig['subtype'],
                ]);
                break;
            case 'form' :
                $this->formColumns = $this->setFormColumns($methodConfig['config']);
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
            $add = true;
            if(array_key_exists($action, $methodButtons)) {
                if($methodButtons[$action]){
                    switch ($action) {
                        case 'add' :
                        case 'edit' :
                            if($methodConfig['type'] === 'list' && !empty($methodConfig['properties']['formConfig'])) {
                                $add = false;
                            }
                            break;
                        case 'view' :
                            if($methodConfig['type'] === 'list' && !empty($methodConfig['properties']['viewConfig'])) {
                                $add = false;
                            }
                            break;
                    }
                }else{
                    $add = false;
                }
            }

            if($add && in_array($action, $this->routeConfig['properties']['allows'])) {
                $uris['PAGE_'.strtoupper($action).'_URI'] = $this->href.DIRECTORY_SEPARATOR.$action;
            }else{
                $uris['PAGE_'.strtoupper($action).'_URI'] = '';
            }
        }

        $this->addJsVars(array_merge($uris, $data));

        if(ENVIRONMENT === 'development') $this->output->enable_profiler(TRUE);
    }

    protected function setFormColumns($configData = null): array
    {
        $config = [];
        if(is_array($configData) && count($configData)) {
            $config = $configData;
        }elseif(is_string($configData) && strlen($configData)){
            $config = $this->config->get('form_'.$configData.'_config', []);
        }

        if(empty($config)){
            $method = $this->router->method;
            if($this->router->method === 'index' && $this->routeConfig['properties']['baseMethod']) {
                $method = $this->routeConfig['properties']['baseMethod'];
            }
            $config = $this->config->get2(
                'form_'.snakeize($this->router->class).'_config'
                , 'form_'.$method.'_config'
                , [], false);

            if(empty($config)) {
                $this->logger("setFormColumns : config does not exist.", E_USER_WARNING, false);
                return $config;
            }
        }

        return array_reduce($config, function($carry, $item) {
            if(isset($item['field']) || $item['type'] === 'common') {
                $carry[] = $this->setFormColumn($item);
            }
            return $carry;
        }, []);
    }

    protected function setFormColumn($item)
    {
        if(isset($item['type']) && $item['type'] === 'common') return $item;

        $item = array_merge(
            $this->config->get("builder_form_base", []),
            ['label' => 'lang:'.$this->router->class.'.'.$item['field']],
            $item
        );

        if(sscanf($item['label'], 'lang:%s', $line) === 1)
            $item['label'] = $line;

        $item = $this->setColumnErrors($item);

        // list attributes
        $item['list_attributes'] = array_merge(
            $this->config->get("builder_list_base", []),
            $item['list_attributes']
        );

        // option attributes
        if(isset($item['option_attributes']) && count($item['option_attributes'])) {
            $item['option_attributes'] = array_merge(
                $this->config->get("builder_form_base_option_attributes", []),
                $item['option_attributes']
            );
            $item['options'] = $this->getOptions($item['option_attributes']['option_field'] ?? $item['field'], $item['option_attributes']);
        }

        // form attributes
        $item['form_attributes'] = array_merge(
            $this->config->get("builder_form_base_form_attributes", []),
            $item['form_attributes']
        );

        /**
         * 예외 처리
         */
        // textarea 가 wysiwyg quill 인 경우
        if($this->listForm && $item['type'] === 'textarea' && $item['subtype'] === 'quill'){
            $item['subtype'] = 'autosize';
        }

        if($item['type'] === $item['subtype']) $item['subtype'] = 'base';

        return $item;
    }

    protected function setColumnErrors($item)
    {
        $rules = preg_split('/\|(?![^\[]*\])/', $item['rules']);

        if($matches = preg_grep('/^required$/', $rules)) {
            $item['attributes']['required'] = $matches[1]??$matches[0];
        }

        if($matches = preg_grep('/^required_mod\[(.*?)\]$/', $rules)) {
            $option = reset($matches);
            if (preg_match('/^required_mod\[(.*?)\]$/', $option, $matches)) {
                $item['attributes']['required-mod'] = $matches[1];
                if(in_array($this->router->method, explode('|', $matches[1]))){
                    $item['rules'] = str_replace($matches[0], 'required', $item['rules']);
                    $item['attributes']['required'] = 'required';
                }
            }
        }

        // 전처리 이후 에러 메세지 셋업
        $rules = preg_split('/\|(?![^\[]*\])/', $item['rules']);

        $item['errors'] = array_reduce($rules, function($carry, $rule) use ($item) {
            $param = null;
            if(count(preg_split('/\[/', $rule)) > 1) {
                preg_match('/(.*?)\[(.*)\]/', $rule, $match);
                $rule = $match[1];
                $param = $match[2];
            }
            if($error_msg = $this->form_validation->get_error_msg($rule, $item['label'], $param, $item['errors'])){
                $carry[$rule] = $error_msg;
            }
            return $carry;
        }, []);

        return $item;
    }

    protected function setIdentifier(): array
    {
        $identifiers = [];
        if (count($this->routeConfig['properties']['identifier'])) {
            $identifiers = $this->routeConfig['properties']['identifier'];
        } elseif (count($this->methodConfig['properties']['identifier'])) {
            $identifiers = $this->methodConfig['properties']['identifier'];
        } else {
            if(property_exists($this, $this->methodConfig['type'].'Columns')){
                return array_values(array_map(function ($item) {
                    return $item['field'];
                }, array_filter($this->{$this->methodConfig['type'].'Columns'}, function ($item) {
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
                $item['id'] = ($this->listForm?$this->config->item('form_side_prefix'):$this->config->item('form_page_prefix')).$item['field'];
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
        $config = [];
        if(isset($name)) {
            $config = $this->config->get($name, []);
        }else{
            $name = 'list_'.$this->methodConfig['config'].'_config';
            $config = $this->config->get2($name
                , 'list_'.snakeize($this->router->class).'_config'
                , [], false);
        }
        if(empty($config)) show_error("getListColumns: List Config '$name' is Empty");

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

    protected function setListColumns(): array
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

        if(empty($list)) $this->logger("setListColumns : list columns for class '{$this->router->class}' are empty.");

        array_unshift($list,
            array_merge(
                $this->config->get("builder_list_base", []),
                [
                    'label' => 'common.row_num',
                    'type' => 'row_num',
                ]
            )
        );

        if(!empty(array_filter($this->methodConfig['actions'], function ($value) {
            return $value === true;
        }))) {
            $list[] = array_merge(
                $this->config->get('builder_list_base', []),
                [
                    'label' => 'common.actions',
                    'type' => 'actions',
                ]
            );
        }

        return $list;
    }

    protected function setListFilters(): array
    {
        $this->filterConfig = $this->config->get2(
            'filter_'.$this->methodConfig['properties']['filterConfig'].'_config'
            , 'filter_'.snakeize($this->router->class).'_config'
            , [], false);
        if(empty($this->filterConfig) || empty($this->filterConfig['filters'])) return [];

        $filters = array_map(function($item) {
            if(!isset($item['colspan'])) $item['colspan'] = FILTER_BASE_COLSPAN;
            if($item['type'] === 'common') return $item;

            $item = array_merge($this->config->get("builder_form_filter_base"), $item);
            if(!isset($item['id'])) $item['id'] = 'filter-'.$item['field'];

            $item['name'] = $item['filter_attributes']['type'];
            if(!is_empty($item['filter_attributes'], 'subtype')) {
                $item['name'] .= '['.$item['filter_attributes']['subtype'].']';
            }else{
                $item['name'] .= '['.$item['field'].']';
            }

            if($item['type'] === 'select') {
                $item['options'] = $this->getOptions($item['option_attributes']['option_field'] ?? $item['field'], $item['option_attributes']);
            }

            // form attributes
            $item['form_attributes'] = array_merge(
                $this->config->get("builder_form_base_form_attributes", []),
                $item['form_attributes'] ?? []
            );

            $item['attributes'] = get_admin_form_attributes($item, 'common');

            return $item;
        }, $this->filterConfig['filters']);

        $rowColumns = 0;
        $rowIdx = 0;
        $list = [];
        foreach ($filters as $idx=>$filter) {
            if($rowColumns >= 12) {
                $rowColumns = 0;
                $rowIdx++;
            }

            $list[$rowIdx][] = $filter;
            $rowColumns += $filter['colspan'];
        }

        // lastRow
        $lastRowColumns = $rowColumns;
        if($lastRowColumns + FILTER_BASE_COLSPAN > 12) {
            $rowIdx++;
            $list[$rowIdx] = [
                ['type' => 'common', 'subtype' => 'space', 'colspan' => 9],
            ];
            $lastRowColumns = 9;
        }

        $remains = 12 - $lastRowColumns - FILTER_BASE_COLSPAN;
        if($remains > 0) {
            $list[$rowIdx][] = ['type' => 'common', 'subtype' => 'space', 'colspan' => $remains];
        }

        $list[$rowIdx][] = [
            'type' => 'common',
            'subtype' => 'submit',
            'search_btn' => true,
            'reset_btn' => true,
        ];

        return $list;
    }

    protected function setViewColumns($name = null): array
    {
        $config = [];
        if(isset($name)) {
            $config = $this->config->get($name, []);
        }else{
            if($name = $this->methodConfig['config']) {
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
        $config = $this->config->get2(
            'excel_'.$this->methodConfig['config'].'_config'
            ,'excel_'.snakeize($this->router->class).'_config'
            , [], false);
        if(empty($config)) $config = $this->config->item('excel_'.strtolower($this->router->class).'_config');

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
                if (!file_exists($filepath)) make_directory($filepath, 0755);

                $writer = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
                $writer->save($filepath . $filename);
            }
        }
        return $sampleUri;
    }

    protected function checkLogin(): bool
    {
        if($this->session->userdata('token')) {
            $this->loginData = $this->validateToken();
            return true;
        }else{
            if(!$this->routeConfig['properties']['allowNoLogin']) {
                $this->destroyUserData();
                redirect(base_url($this->noLoginRedirect));
            }
            return false;
        }
    }

    protected function addListScripts($type)
    {
        switch ($type) {
            default :
                $this->addCSS[] = [
                    base_url('public/assets/builder/vendor/libs/datatables-bs5/datatables.bootstrap5.css'),
                    base_url('public/assets/builder/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css'),
                    base_url('public/assets/builder/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.css'),
                    base_url('public/assets/builder/vendor/libs/datatables-checkboxes-jquery/datatables.checkboxes.css'),
                ];

                $this->addJS['tail'][] = [
                    base_url('public/assets/builder/vendor/libs/datatables-bs5/datatables-bootstrap5.js'),
                ];
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
            base_url('public/assets/builder/vendor/libs/sortablejs/sortable.js'),
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

    public function _remap($method, $params = [])
    {
        if($this->isBuilderAvailable()){
            $this->isLogin = $this->checkLogin();
            $this->setMenuList();

            if(!method_exists($this, $method)) {
                // 1) perform 메소드 실행
                if(!is_empty($this->methodConfig['properties'], 'perform')) {
                    if (method_exists($this, $this->methodConfig['properties']['perform'])) {
                        $this->{$this->methodConfig['properties']['perform']}();
                    }else{
                        show_error('Performing Method is not defined : '.$this->methodConfig['properties']['perform']);
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

            show_404();
        }
    }

    protected function checkIdentifierExist($key = 0)
    {
        if( !($this->routeConfig['properties']['noIdentifier'] || $this->methodConfig['properties']['noIdentifier']) ) {
            $idData = $this->getIdentifierData($key, $this->jsVars['IDENTIFIER']);

            if(empty($idData)) alert(lang('Incorrect Access'));

            $this->addJsVars(['KEY' => count($idData)===1?array_values($idData)[0]:$idData]);
        }
    }
}
