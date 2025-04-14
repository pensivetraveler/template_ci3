<?php
defined('BASEPATH') or exit('No direct script access allowed');

class MY_Builder_WEB extends MY_Controller_WEB
{
    public string $flag = '';
    public string $baseUri = '';
    public array $pageConfig;
    public string $pageType;
    public bool $sideForm;
    public array $headerData;
    public object $loginData;
    public string $href;
    public array $listConfig;
    public array $listColumns;
    public array $listFilters;
    public array $formConfig;
    public array $formColumns;
    public string $viewPath;
    public array $navAuth;
    public bool $isLogin = false;
    public bool $isAdmin = false;

    public function __construct()
    {
        parent::__construct();

        $this->config->load('extra/autologin_config', false);

        foreach (['builder_base_config', 'builder_form_config', 'builder_nav_config', 'builder_page_config'] as $config) {
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

        $this->baseViewPath = BUILDER_FLAGNAME."/layout/index";
        $this->baseUri = $this->flag === $this->router->routes['default_platform'] ? '' : $this->flag;
        $this->isLoginRedirect = "$this->baseUri/{$this->config->item('platform_config.isLoginRedirect')}";
        $this->noLoginRedirect = "$this->baseUri/{$this->config->item('platform_config.noLoginRedirect')}";

        $this->titleList = [ucfirst($this->flag)];
        $this->pageConfig = [];
        $this->pageType = 'form';
        $this->sideForm = false;
        $this->headerData = [];
        $this->href = base_url("$this->baseUri/{$this->router->class}");
        $this->listConfig = $this->formConfig = [];
        $this->listColumns = $this->formColumns = [];
        $this->viewPath = "$this->flag/{$this->router->class}";
        $this->jsVars = [
            'TITLE' => $this->router->class,
            'API_URI' => '',
            'API_PARAMS' => [],
        ];

        $this->isLogin = $this->checkLogin();
        if($this->router->class !== 'common') $this->setProperties();
        if(ENVIRONMENT === 'development') $this->output->enable_profiler(TRUE);
    }

    public function index()
    {
        $this->setupBuilderDB();

        parent::index();

        if(empty($this->pageConfig)) {
            $data['subPage'] = '';
            $data['backLink'] = WEB_HISTORY_BACK;
            $this->viewApp($data);
        }else{
            if(!$this->pageConfig['properties']['allowNoLogin'] && !$this->isLogin){
                redirect($this->noLoginRedirect);
            }

            if($this->router->class === 'common') {
                redirect("$this->baseUri/$this->defaultController");
            }

            $this->{"{$this->pageConfig['properties']['baseMethod']}"}();
        }
    }

    public function list()
    {
        $this->titleList[] = 'List';

        $data['backLink'] = WEB_HISTORY_BACK;
        $data['filters'] = $this->jsVars['LIST_FILTERS']??[];
        $data['filterHelpBlock'] = $this->filterConfig['help_block'] ?? [];
        $data['columns'] = $this->jsVars['LIST_COLUMNS']??[];

        $formData = [];
        if(array_key_exists('FORM_DATA', $this->jsVars)) {
            $formData = restructure_admin_form_data($this->jsVars['FORM_DATA'], $this->sideForm?'side':'page');
        }
        $data['formData'] = $formData;

        $data['isCheckbox'] = $this->pageConfig['listProperties']['isCheckbox'];

        $this->viewApp($data);
    }

    public function view($key = 0)
    {
        if(!$key) alert(lang('Incorrect Access'));

        $this->phptojs->append(['KEY' => $key]);

        $this->titleList[] = 'View';

        $data['backLink'] = WEB_HISTORY_BACK;
        $data['viewData'] = restructure_admin_form_data($this->jsVars['FORM_DATA']);
        $data['identifier'] = null;
        if(!is_null(array_search('identifier', array_column($data['viewData'], 'view')))) {
            $data['identifier'] = $data['viewData'][array_search('identifier', array_column($data['viewData'], 'view'))];
        }

        $data['isComments'] = $this->pageConfig['viewProperties']['isComments'];
        if($data['isComments']) {
            $this->addJS['tail'][] = [
                base_url('public/assets/builder/js/app-page-comment.js')
            ];
        }

        $data['actions'] = reformat_bool_type_list($this->pageConfig['viewProperties']['actions']);
        foreach ($data['actions'] as $i=>$action) {
            if($action === 'delete') continue;
            if(!in_array($action, $this->pageConfig['properties']['allows'])) unset($data['actions'][$i]);
        }
        $data['actions'] = array_values($data['actions']);

        $data['viewType'] = $this->pageConfig['viewProperties']['viewType'];

        $this->viewApp($data);
    }

    public function add()
    {
        if($this->sideForm) show_404();

        $this->titleList[] = 'Add';

        $data['backLink'] = WEB_HISTORY_BACK;
        $data['formData'] = restructure_admin_form_data($this->jsVars['FORM_DATA'], $this->sideForm?'side':'page');
        $data['formType'] = 'page';

        $data['buttons'] = [];
        if($this->pageConfig['properties']['listExist']) $data['buttons'][] = 'list';

        $this->viewApp($data);
    }

    public function edit($key = 0)
    {
        if($this->sideForm) show_404();

        if(!$key) alert(lang('Incorrect Access'));

        $this->phptojs->append(['KEY' => $key]);

        $this->titleList[] = 'Edit';

        $data['backLink'] = WEB_HISTORY_BACK;
        $data['formData'] = restructure_admin_form_data($this->jsVars['FORM_DATA'], $this->sideForm?'side':'page');
        $data['formType'] = 'page';

        $data['buttons'] = [];
        if($this->pageConfig['properties']['listExist']) $data['buttons'][] = 'list';

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

        $data['excelHeaders'] = $this->getExcelHeaders();
        $data['sampleFile'] = $this->getExcelSample($data['excelHeaders']);
        $data['backLink'] = WEB_HISTORY_BACK;

        if(!count($data['excelHeaders'])) show_error('Please Check The Excel Header List', 500);

        $this->viewApp($data);
    }

    protected function viewApp($data)
    {
        if(!array_key_exists('subPage', $data)) {
            $view = null;
            $method = $this->router->method === 'index'?$this->pageConfig['properties']['baseMethod']:$this->router->method;

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

        $data['hideBack'] = element('hideBack', $data);
        $data['headerData'] = $this->headerData;
        $data['includes'] = $this->pageConfig['properties']['includes'];

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

        $data['platformName'] = PLATFORM_NAME??'builder';

        // builder attributes
        $data['htmlAttrs'] = get_builder_html_attributes($this->flag);
        $data['bodyAttrs'] = get_builder_body_attributes(ENVIRONMENT !== 'production');

        parent::viewApp($data);
    }

    protected function setProperties($data = [])
    {
        $pageConfig = [];
        if(
            !is_empty($this->config->item("page_config"), $this->router->class)
            ||
            !is_empty($this->config->item("page_config"), strtolower($this->router->class))
        ){
            $pageConfig = $this->config->get("page_config")[$this->router->class]??$this->config->get("page_config")[strtolower($this->router->class)];
            if(is_empty($pageConfig, 'properties')) $pageConfig['properties'] = [];
            if(!array_key_exists( 'allows', $pageConfig['properties'])) $pageConfig['properties']['allows'] = [];
            if(empty($pageConfig['properties']['allows'])) $pageConfig['properties']['allows'][] = $pageConfig['properties']['baseMethod'];
        }
        foreach ($this->config->get("page_base_config", []) as $key=>$val) {
            if(!array_key_exists($key, $pageConfig)) {
                $pageConfig[$key] = $val;
            }else{
                if(is_array($val)) {
                    foreach ($val as $subKey=>$subVal) {
                        if(!array_key_exists($subKey, $pageConfig[$key])) {
                            $pageConfig[$key][$subKey] = $subVal;
                            continue;
                        }
                        if(is_array($subVal)) {
                            $pageConfig[$key][$subKey] = array_merge($subVal, $pageConfig[$key][$subKey]);
                        }
                    }
                }else{
                    $pageConfig[$key] = $pageConfig[$key]??$val;
                }
            }
        }
        $this->pageConfig = $pageConfig;

        $this->pageType = $this->pageConfig['type'];
        if($this->pageConfig['properties']['formExist']) $this->sideForm = $this->pageConfig['formProperties']['formType'] === 'side';

        if($this->pageConfig['properties']['formExist']) {
            $this->formColumns = $this->setFormColumns(
                $this->pageConfig['formProperties']['formConfig'] ? : strtolower($this->router->class)
            );
            $this->addJsVars([
                'IDENTIFIER' => $this->setIdentifier(),
                'FORM_DATA' => $this->setFormData(),
                'FORM_REGEXP' => $this->config->item('regexp'),
            ]);
        }

        if($this->pageConfig['properties']['listExist']) {
            $this->addJsVars([
                'PAGE_LIST_URI' => $this->href,
                'LIST_COLUMNS' => $this->setListColumns(),
                'LIST_PLUGIN' => $this->pageConfig['listProperties']['plugin'],
                'LIST_FILTERS' => $this->setListFilters(),
                'LIST_BUTTONS' => $this->pageConfig['listProperties']['buttons'],
                'LIST_ACTIONS' => reformat_bool_type_list($this->pageConfig['listProperties']['actions']),
                'LIST_EXPORTS' => reformat_bool_type_list($this->pageConfig['listProperties']['exports']),
                'LIST_CHEKBOX' => $this->pageConfig['listProperties']['isCheckbox'],
            ]);
        }

        if($this->pageConfig['properties']['formExist'] && $this->pageConfig['properties']['listExist']) {
            $viewType = $this->pageConfig['viewProperties']['viewType'];
            $this->addJsVars([
                'PAGE_VIEW_URI' => $viewType!=='modal'?$this->href.DIRECTORY_SEPARATOR.'view':'',
                'PAGE_ADD_URI' => $this->sideForm?'':$this->href.DIRECTORY_SEPARATOR.'add',
                'PAGE_EDIT_URI' => $this->sideForm?'':$this->href.DIRECTORY_SEPARATOR.'edit',
                'PAGE_EXCEL_URI' => $this->href.DIRECTORY_SEPARATOR.'excel',
                'SIDE_FORM' => $this->sideForm,
            ]);
        }

        $this->addJsVars($data);
    }

    protected function setFormColumns($configData = null): array
    {
        $config = [];
        if(is_array($configData)) {
            $config = $configData;
        }elseif(is_string($configData)){
            $config = $this->config->item('form_'.$configData.'_config');
        }

        if(is_null($configData) || empty($config)){
            $this->logger("setFormColumns : config does not exist.", E_USER_WARNING, false);
            return [];
        }else{
            return array_reduce($config, function($carry, $item) {
                if(isset($item['field'])) {
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
                        $this->config->get("builder_form_base_list_attributes", []),
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
                    if($this->sideForm && $item['category'] === 'basic' && $item['type'] === 'textarea' && $item['subtype'] === 'quill'){
                        $item['subtype'] = 'autosize';
                    }

                    if($item['type'] === $item['subtype']) $item['subtype'] = 'basic';

                    $carry[] = $item;
                }
                return $carry;
            }, []);
        }
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
                if(in_array($this->router->method, explode('|', $matches[1])))
                    $item['rules'] = str_replace($matches[0], 'required', $item['rules']);
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

    protected function setIdentifier(): string
    {
        $idx = array_search('identifier', array_column($this->formColumns, 'subtype'));
        return $idx === false?'':$this->formColumns[$idx]['field'];
    }

    protected function setFormData(): array
    {
        $result = [];
        $groups = [];
        $attr = [];
        foreach ($this->formColumns as $i=>$item) {
            if (!$item['form']) continue;

            if ($item['subtype'] === 'identifier' && !in_array($this->router->method, ['index', 'list'])){
                // page type form 에 identifier default 값 부여
                if(end($this->uri->segments) !== $this->router->method)
                    $item['default'] = end($this->uri->segments);
            }

            if ($item['category'] === 'group' && $item['group']) {
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
                $item['group'] = '';
                if($item['category'] === 'group') $item['category'] = 'basic';
                $item['group_attributes'] = [];

                // view type
                $item['view'] = $item['subtype'];

                $item['id'] = ($this->sideForm?$this->config->item('form_side_prefix'):$this->config->item('form_page_prefix')).$item['field'];
                $item['name'] = $item['field'];
            }

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
            if($name = $this->pageConfig['listProperties']['listConfig']) {
                $config = $this->config->get('list_'.$name.'_config', []);
            }
        }

        $this->listColumns = array_reduce($config, function($carry, $item) {
            $item = array_merge($this->config->get("builder_form_base_list_attributes", []), $item);
            if ($item['list']) $carry[] = $item;
            return $carry;
        }, []);

        return array_column($this->listColumns, 'field');
    }

    protected function setListColumns(): array
    {
        $columns = $this->getListColumns();

        $baseData = empty($this->listColumns) ? $this->formColumns : $this->listColumns;

        $list = array_reduce(array_keys($columns), function($carry, $key) use($columns, $baseData) {
            $field = $columns[$key];
            $idx = array_search($field, array_column($baseData, 'field'));
            if($idx === false) return $carry;

            $item = $baseData[$idx];

            $attributes = array_merge(
                $this->config->get("builder_form_base_list_attributes", []),
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
                $this->config->get("builder_form_base_list_attributes", []),
                [
                    'label' => 'common.row_num',
                    'format' => 'row_num',
                ]
            )
        );

        if(!empty(array_filter($this->pageConfig['listProperties']['actions'], function ($value) {
            return $value === true;
        }))) {
            $list[] = array_merge(
                $this->config->get('builder_form_base_list_attributes', []),
                [
                    'label' => 'common.actions',
                    'format' => 'actions',
                ]
            );
        }

        return $list;
    }

    protected function setListFilters(): array
    {
        $this->filterConfig = $this->config->get('filter_'.$this->pageConfig['listProperties']['listConfig'].'_config', [], false);
        if(empty($this->filterConfig) || empty($this->filterConfig['filters'])) return [];

        $filters = array_map(function($item) {
            if(!isset($item['colspan'])) $item['colspan'] = FILTER_BASE_COLSPAN;
            if($item['type'] === 'filter') return $item;

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

            $item['attributes'] = get_admin_form_attributes($item, 'filter');

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
                ['type' => 'filter', 'subtype' => 'space', 'colspan' => 9],
            ];
            $lastRowColumns = 9;
        }

        $remains = 12 - $lastRowColumns - FILTER_BASE_COLSPAN;
        if($remains > 0) {
            $list[$rowIdx][] = ['type' => 'filter', 'subtype' => 'space', 'colspan' => $remains];
        }

        $list[$rowIdx][] = [
            'type' => 'filter',
            'subtype' => 'submit',
            'search_btn' => true,
            'reset_btn' => true,
        ];

        return $list;
    }

    protected function getExcelHeaders()
    {
        $config = $this->config->item('excel_'.strtolower($this->router->class).'_config');

        if($config) {
            return array_reduce($config, function($carry, $item) {
                if(isset($item['field'])) {
                    if(!array_key_exists('required', $item)) {
                        $item['required'] = false;
                    }
                    if(!array_key_exists('label', $item) || !$item['label']) {
                        $item['label'] = $item['field'];
                    }
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
                    'label' => $column['label']??$column['field'],
                ];
            }
            return $config;
        }
    }

    protected function getExcelSample($data)
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
                    $sheet->setCellValue($alphabet.'1', lang($data[$i]['label']));

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
                if (!file_exists($filepath)) mkdir($filepath, 0755, true);

                $writer = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
                $writer->save($filepath . $filename);
            }
        }
        return $sampleUri;
    }

    protected function setupBuilderDB()
    {
        if($this->Model_Common->getTableCount()) {
            $this->addSystemUser();
            return;
        }

        if($this->input->post('sql')){
            $this->load->library('sql_parser');
            $sql = $this->sql_parser->parsing($this->input->post('sql'));
            foreach (explode(';', $sql) as $qry) {
                try {
                    $this->db->query($qry);
                }catch (Exception $e) {
                    $this->input->raw_input_stream = null; // 원본 요청 데이터 초기화
//					$this->Model_Common->deleteAllTables();
                    show_error($e->getMessage(), 500);
                    break;
                }
            }

            $_POST[] = '';
            redirect($this->baseUri);
        }else{
            $this->formColumns = $this->setFormColumns([
                [
                    'field' => 'sql',
                    'label' => 'sql',
                    'rules' => 'required',
                ]
            ]);
            $this->addJsVars([
                'FORM_DATA' => $this->setFormData(),
                'FORM_REGEXP' => $this->config->item('regexp'),
            ]);

            $data['platformName'] = BUILDER_FLAGNAME;
            $data['subPage'] = 'builder/setup/set_db';
            $data['backLink'] = WEB_HISTORY_BACK;
            $data['formData'] = restructure_admin_form_data($this->jsVars['FORM_DATA'], false);
            $data['includes'] = [
                'head' => true,
                'header' => false,
                'modalPrepend' => true,
                'modalAppend' => true,
                'footer' => false,
                'tail' => true,
            ];

            parent::viewApp($data);
        }
    }

    public function addSystemUser()
    {
        $cnt = $this->db
            ->where(['user_cd' => 'USR000'])
            ->from(USER_TABLE_NAME)
            ->count_all_results();
        if($cnt) return;

        $userColumns = [];
        $columns = $this->Model_Common->getNotNullColumns(USER_TABLE_NAME);
        if(empty($columns)) show_error(lang('Check The User Table'));

        foreach ($columns as $field) {
            if(in_array($field, [USER_ID_COLUMN_NAME, USER_CD_COLUMN_NAME, CREATED_ID_COLUMN_NAME, CREATED_DT_COLUMN_NAME, UPDATED_ID_COLUMN_NAME, UPDATED_DT_COLUMN_NAME, DEL_YN_COLUMN_NAME, USE_YN_COLUMN_NAME])) continue;
            $userColumns[] = [
                'field' => $field,
                'label' => $field,
            ];
        }

        if($this->input->post()) {
            $set = array_merge([
                USER_CD_COLUMN_NAME => 'USR000',
            ], array_intersect_key($this->input->post(), array_flip($columns)));
            if(array_key_exists('password', $set))
                $set['password'] = $this->encryption->encrypt($this->input->post('password'));

            $this->db->set($set)->insert(USER_TABLE_NAME);

            redirect($this->baseUri);
        }else{
            $this->formColumns = $this->setFormColumns($userColumns);
            $this->addJsVars([
                'FORM_DATA' => $this->setFormData(),
                'FORM_REGEXP' => $this->config->item('regexp'),
            ]);

            $data['platformName'] = BUILDER_FLAGNAME;
            $data['subPage'] = 'builder/setup/add_system_user';
            $data['backLink'] = WEB_HISTORY_BACK;
            $data['formData'] = restructure_admin_form_data($this->jsVars['FORM_DATA'], false);
            $data['includes'] = [
                'head' => true,
                'header' => false,
                'modalPrepend' => true,
                'modalAppend' => true,
                'footer' => false,
                'tail' => true,
            ];

            parent::viewApp($data);
        }
    }

    protected function checkLogin(): bool
    {
        if($this->session->userdata('token')) {
            $this->loginData = $this->validateToken();
            if(!property_exists($this->loginData, 'user_cd')) return false;
            return in_array($this->loginData->user_cd, ['USR000', 'USR001']);
        }else{
            $this->destroyUserData();
            return false;
        }
    }

    protected function destroyUserData()
    {
        delete_cookie('autologin');

        if(count($this->session->userdata())) {
            foreach ($this->session->userdata() as $key=>$val) {
                $this->session->unset_userdata($key);
            }
            $this->session->sess_destroy();
        }

        // 세션 쿠키 삭제
        if (isset($_COOKIE[$this->config->item('sess_cookie_name')])) {
            setcookie($this->config->item('sess_cookie_name'), '', time() - 3600, '/');
        }
    }
}
