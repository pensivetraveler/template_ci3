<?php
defined('BASEPATH') OR exit('No direct script access allowed');

trait BuilderCommonTrait
{
    protected function destroyUserData(): void
    {
        delete_cookie('autologin');

        parent::destroyUserData();
    }

    protected function getIdentifierData($key, $identifiers): array
    {
        $data = [];
        if(count($identifiers) > 0){
            if(count($identifiers) === 1) {
                $data[$identifiers[0]] = $key;
            }else{
                foreach ($identifiers as $field) {
                    $data[$field] = $this->input->get($field);
                }
            }
        }
        return $data;
    }

    protected function loadConfigs($builderConfigs = []): void
    {
        foreach ($builderConfigs as $config) $this->config->load('extra/builder/'.$config, false);

        require_once APPPATH . 'config/extra/builder/builder_base_constants.php';
        $this->load->helper(["builder/builder_web","builder/builder_base","builder/builder_menu","builder/builder_route","builder/builder_form","builder/builder_api","builder/builder_auth"]);
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

    protected function fillRouteConfigProperties($className, $config = [], $base = []): array
    {
        if(empty($base)) {
            $type = array_key_exists('type', $config)?$config['type']:'base';
            $subtype = array_key_exists('subtype', $config)?$config['subtype']:'base';

            $base = $this->config->item("route_base_config");
            if($this->config->item("route_{$type}_base_config")) {
                $base = fill_config_properties($this->config->item("route_{$type}_base_config"), $base);
            }
            if($this->config->item("route_{$type}_{$subtype}_config")) {
                $base = fill_config_properties($this->config->item("route_{$type}_{$subtype}_config"), $base);
            }
        }

        $config = fill_config_properties($config, $base);

        if(!isset($config['methods']) || !count($config['methods'])){
            trigger_error(__METHOD__.' : method is registered for the '.ucfirst($className), E_USER_ERROR);
        }

        if(empty($config['properties']['baseMethod'])) {
            $config['properties']['baseMethod'] = array_key_first($config['methods']);
        }else{
            if(!in_array($config['properties']['baseMethod'], array_keys($config['methods']))) {
                trigger_error(__METHOD__.' : baseMethod is not in method list', E_USER_ERROR);
            }
        }

        $this->allowedMethods = $config['properties']['allows'] = array_keys($config['methods']);

        return $config;
    }

    protected function fillMethodConfigProperties($config = []): array
    {
        $category = array_key_exists('category', $config)?$config['category']:'base';
        $type = array_key_exists('type', $config)?$config['type']:'base';
        $subtype = array_key_exists('subtype', $config)?$config['subtype']:'base';

        $base = $this->config->item("method_base_config");
        if($this->config->item("method_{$category}_base_config")) {
            $base = fill_config_properties($this->config->item("method_{$category}_base_config"), $base);
        }
        if($this->config->item("method_{$category}_{$type}_config")) {
            $base = fill_config_properties($this->config->item("method_{$category}_{$type}_config"), $base);
        }
        if($this->config->item("method_{$category}_{$type}_{$subtype}_config")) {
            $base = fill_config_properties($this->config->item("method_{$category}_{$type}_{$subtype}_config"), $base);
        }

        return fill_config_properties($config, $base);
    }

    protected function setMenuList(): array
    {
        if($this->cache->file->get('menu_done')){
            $menuList = $this->cache->file->get('menu_done');
        }else{
            $menuList = $this->getMenuList();
            if(env('CACHING_MENU')) {
                $this->saveMenuList($menuList);
                $menuList = $this->cache->file->get('menu_done');
            }
        }

        return $this->setMenuAttributes($menuList);
    }

    protected function getMenuList($configName = ''): array
    {
        if(!$configName) {
            $configs = $this->config->get("{$this->flag}_nav_menu", $this->config->get('builder_nav_menu_sample', []), false);
        }else{
            $configs = $this->config->get($configName, [], false);
        }

        return $this->fillMenuConf($configs);
    }

    protected function fillMenuConf($configData = [], $depth = 1): array
    {
        $conf = $this->config->get('builder_nav_menu_base', []);
        if(!is_list_type($configData)) show_error('Menu Config is written wrong way.');

        return array_map(function ($item) use ($conf, $depth) {
            $item = fill_config_properties($item, $conf);
            $item['params'] = array_merge($item['params'], $this->config->get('platform_config.navDefaultParams', [], false));
            $item['depth'] = $depth;
            $item['method'] = !$item['method']&&$item['class']?'index':$item['method'];
            $item['isSubMenu'] = count($item['subMenu']) > 0 ? 1 : 0;
            if($item['isSubMenu']) $item['subMenu'] = $this->fillMenuConf($item['subMenu'], 2);
            return $item;
        }, $configData);
    }

    protected function setMenuAttributes($menuList = []): array
    {
        return array_map(function ($item) {
            $item = (array)$item;

            if($item['isSubMenu']) {
                $item['attr']['href'] = '';
                $item['attr']['className'] = array_merge($item['attr']['className'], [
                    'menu-toggle', 'waves-effect'
                ]);
                $item['subMenu'] = $this->setMenuAttributes($item['subMenu']);
            }else{
                if($item['class'] && $item['method']) {
                    $flags = [$this->flag, $item['class']];
                    if($item['method'] !== 'index') $flags[] = $item['method'];
                    $item['attr']['href'] = '/'.implode('/', $flags);
                }
            }

            $item['listClassName'] = [];
            if(is_active_page($item)) {
                if($item['isSubMenu']) $item['listClassName'][] = 'open';
                $item['listClassName'][] = 'active';
            }

            $item['listClassName'] = implode(' ', $item['listClassName']??[]);
            $item['attr']['className'] = implode(' ', $item['attr']['className']??[]);

            return $item;
        }, array_filter($menuList, function ($item) {
            return $item['isUse'];
        }));
    }

    protected function saveMenuList($menuList)
    {
        $this->load->model('Model_Menu');

        $routeConfigs = $this->config->item("route_config");
        $config = array_combine(
            array_keys($routeConfigs),
            array_map(function ($className, $item) {
                foreach ($item['methods'] as $method=>$config) {
                    $item['methods'][$method] = $this->fillMethodConfigProperties($config);
                }
                return $this->fillRouteConfigProperties($className, $item);
            },
                array_keys($routeConfigs),
                $routeConfigs
            )
        );

        foreach ($menuList as $i=>$item) {
            $item = $this->setMenuData($item, $config, $i+1);
            $item['menuId'] = $this->Model_Menu->addData($this->transformMenuData($item));

            if($item['isSubMenu']) {
                foreach ($item['subMenu'] as $j=>$subItem) {
                    $subItem = $this->setMenuData($subItem, $config, $j+1, $item['menuId']);
                    $subItem['menuId'] = $this->Model_Menu->addData($this->transformMenuData($subItem));
                    $item['subMenu'][$j] = $subItem;
                }
            }

            $menuList[$i] = $item;
        }

        $this->cache->file->save('menu_done', $menuList, 0);
    }

    protected function setMenuData($data, $config, $srt = 1, $parentId = 0)
    {
        $data = array_map(function ($value) {
            if(is_bool($value)) $value = !$value?0:1;
            return $value;
        }, $data);

        $data['baseAuth'] = $this->getMenuBaseAuth($data, $config);
        $data['srt'] = $srt;
        $data['parentId'] = $parentId;
        return $data;
    }

    function getMenuBaseAuth($item, $config): string
    {
        if(!$item['isSubMenu'] && array_key_exists($item['class'], $config)) {
            $routeConfig = $config[$item['class']];
            $method = $item['method'] === 'index' ? $routeConfig['properties']['baseMethod'] : $item['method'];

            $methodConfig = $routeConfig['methods'][$method];

            return make_base_auth_from_method($methodConfig);
        }else{
            return BASE_AUTH_CHAR;
        }
    }

    function transformMenuData($data): array
    {
        $set = [];
        foreach ($data as $key=>$val) {
            if(is_array($val)) $val = serialize($val);
            $set[snakeize($key)] = $val;
        }
        return $set;
    }

    protected function deformMenuList($menuList): array
    {
        $list = [];
        foreach ($menuList as $i => $item) {
            $item = $this->deformMenuData($item);
            if(count($item['subMenu'])) {
                foreach ($item['subMenu'] as $j => $subItem) {
                    $item['subMenu'][$j] = $this->deformMenuData($subItem);
                }
            }
            $list[$i] = $item;
        }
        return $list;
    }

    protected function deformMenuData($menuData): array
    {
        $data = [];
        foreach ($menuData as $key=>$val) {
            if(is_serialized_string($val)) {
                $data[camelize($key)] = @unserialize($val);
            }else{
                $data[camelize($key)] = $val;
            }
        }
        return $data;
    }

    public function getClassList(): array
    {
        $classes = [];
        foreach (glob(APPPATH . 'controllers/' . $this->flag . '/*.php') as $filePath)
        {
            $className = basename($filePath, '.php');
            if($className === 'Common') continue;
            // 파일명에서 .php 제거
            $classes[lcfirst($className)] = $className;
        }

        return $classes;
    }

    public function getMethodList($className): array
    {
        $config = $this->config->get("route_config")[$className]??$this->config->get("route_config")[strtolower($className)];

        if(is_empty($config, 'properties')) $config['properties'] = [];
        if(is_empty($config['properties'], 'noIndex')) $config['properties']['noIndex'] = false;
        if(is_empty($config, 'methods')) $config['methods'] = ['index'];

        // 이 클래스(ChildClass)에서 선언된 메서드만 필터
        $methodList = [];
        if(!$config['properties']['noIndex']) {
            $methodList['index'] = 'index';
        }else{
            if($config['properties']['baseMethod'])
                $methodList[$config['properties']['baseMethod']] = $config['properties']['baseMethod'];
        }

        foreach (array_keys($config['methods']) as $method) {
            if(in_array($method, ['index','list','add','edit','view'])) continue;
            $methodList[$method] = $method;
        }

        return $methodList;
    }

    public function reorderList($sortColumn, $idColumn, $cateColumn, $addCondition = [], $target = [], $model = null): void
    {
        if(is_null($model)) $model = $this->Model;

        $condition = [
            'where' => is_empty($target, $cateColumn) ? [] : [$cateColumn => $target[$cateColumn]],
            'whereNot' => is_empty($target, $idColumn) ? [] : [$idColumn => $target[$idColumn]],
            'orderBy' => [$sortColumn => 'ASC'],
        ];

        foreach (array_keys($condition) as $key) {
            if(array_key_exists($key, $addCondition) && !empty($addCondition[$key])) {
                $condition[$key] = array_merge($condition[$key], $addCondition[$key]);
            }
        }

        if($model->getCnt($condition)) {
            $list = $model->getList([], $condition);

            $list = reorder($list, $target, $sortColumn);

            foreach ($list as $item) {
                if(!empty($target) && $item[$idColumn] === $target[$idColumn]) continue;
                $model->modData([
                    $sortColumn => $item[$sortColumn],
                ], [
                    $idColumn => $item[$idColumn],
                ]);
            }
        }
    }}
