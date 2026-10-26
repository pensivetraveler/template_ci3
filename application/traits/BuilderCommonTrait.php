<?php
defined('BASEPATH') OR exit('No direct script access allowed');

trait BuilderCommonTrait
{
    protected function destroyUserData(): void
    {
        // 자동 로그인 키 행도 함께 지운다 (쿠키만 지우면 행이 만료까지 남음)
        $cookieName = $this->config->item('autologin_cookie_name') ?: 'autologin';
        $key = get_cookie($cookieName);
        if($key && $this->config->item('autologin_table')) {
            $columns = $this->config->item('autologin_columns');
            $this->db->delete($this->config->item('autologin_table'), [$columns['key'] => $key]);
        }
        delete_cookie($cookieName);

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

        // 플랫폼 설정({flag}_base_config 의 platform_config)은 builder 기본값 위에 바꿀 키만 덮어쓴다
        // (config->load 는 최상위 키를 통째로 바꾸므로 따로 합침, 목록 값은 플랫폼 값이 그대로 이김)
        $builderPlatform = $this->config->item('platform_config') ?: [];
        foreach (glob(APPPATH . "config/extra/{$this->flag}/[!.]*_config.php") as $file) {
            $this->config->load("extra/{$this->flag}/" . substr(basename($file),0,strpos(basename($file),'.')));
        }
        $this->config->set_item('platform_config', fill_config_properties($this->config->item('platform_config') ?: [], $builderPlatform));
        foreach (glob(APPPATH . "config/extra/{$this->flag}/[!.]*_constants.php") as $file) {
            require_once $file;
        }
        foreach (glob(APPPATH.'language'.DIRECTORY_SEPARATOR.$this->config->item('language').DIRECTORY_SEPARATOR.$this->flag.DIRECTORY_SEPARATOR.'[!.]*_lang.php') as $file) {
            $this->lang->load($this->flag.DIRECTORY_SEPARATOR.str_replace('_lang.php', '', basename($file)), $this->config->item('language'));
        }

        // 엔티티(route·form·list·filter·view·excel) 설정은 모두 Resource_loader 로 찾는다
        $this->load->library('resource_loader', null, 'resourceLoader');
        // 엔티티당 파일 하나인 통합형(config/extra/{flag}/resources/*.php)을 분할형과 같은 키로 넣는다
        $this->resourceLoader->loadMergedResources($this->flag);
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

        // allows 를 적었으면 method 목록과의 교집합(method 가 있어도 버튼·링크는 숨김), 안 적었으면 method 전체
        $methodKeys = array_keys($config['methods']);
        $allows = $config['properties']['allows'] ?? [];
        $this->allowedMethods = $config['properties']['allows'] = (is_array($allows) && count($allows))
            ? array_values(array_intersect($methodKeys, $allows))
            : $methodKeys;

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
            // 플랫폼 기본값 위에 항목 값을 덮어씀 (항목별 layout 등 변경 가능)
            $item['params'] = array_merge($this->config->get('platform_config.navDefaultParams', [], false), $item['params']);
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

        // 메뉴가 이미 DB에 있으면(캐시만 사라진 경우) 다시 insert하지 않고 DB 내용으로 캐시를 만든다 (menu_id·메뉴 권한 유지)
        if($this->Model_Menu->getCnt()) {
            $this->cache->file->save('menu_done', $this->getSavedMenuList(), 0);
            return;
        }

        $config = $this->getMenuRouteConfigs();

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

    /**
     * 메뉴 기본 권한(base_auth) 계산에 쓰는 route 설정 (route·method 기본값을 채운 것)
     * methods 없는 route는 뺀다 (fillRouteConfigProperties의 E_USER_ERROR로 저장 전체가 실패하지 않게)
     */
    protected function getMenuRouteConfigs(): array
    {
        $routeConfigs = array_filter($this->config->item("route_config") ?: [], function ($item) {
            return !empty($item['methods']);
        });
        return array_combine(
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
    }

    /**
     * DB(tbl_menu)에 저장된 메뉴를 menu_done 캐시와 같은 형태로 읽는다. (api/MenuList::cachingMenu와 같은 구조)
     */
    protected function getSavedMenuList(): array
    {
        $list = $this->Model_Menu->getList([], [
            'where' => ['depth' => 1],
            'orderBy' => ['srt' => 'asc'],
        ]);

        foreach ($list as $item) {
            $item->sub_menu = !$item->is_sub_menu ? [] : $this->Model_Menu->getList([], [
                'where' => [
                    'depth' => 2,
                    'parent_id' => $item->menu_id,
                ],
                'orderBy' => ['srt' => 'asc'],
            ]);
        }

        return $this->deformMenuList($list);
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
        // 메뉴 class는 route 키와 대소문자가 다를 수 있음 (myInfo 메뉴 ↔ myinfo route) → setRouteConfig와 같은 규칙으로 찾음
        $routeKey = $item['isSubMenu'] ? '' : $this->resourceLoader->routeKey((string)$item['class']);
        if($routeKey !== '' && array_key_exists($routeKey, $config)) {
            $routeConfig = $config[$routeKey];
            $method = $item['method'] === 'index' ? $routeConfig['properties']['baseMethod'] : $item['method'];

            // route methods에 없는 메뉴 method는 setMethodConfig처럼 baseMethod 기준으로 계산
            $methodConfig = $routeConfig['methods'][$method] ?? $routeConfig['methods'][$routeConfig['properties']['baseMethod']];

            $baseAuth = make_base_auth_from_method($methodConfig, $routeConfig['methods']);

            // 같은 route의 action 메서드(다운로드 등)가 요구하는 권한도 메뉴 권한 화면에서 줄 수 있게 합침
            foreach ($routeConfig['methods'] as $otherMethod) {
                if (($otherMethod['category'] ?? '') === 'action') {
                    $baseAuth = merge_auth($baseAuth, make_base_auth_from_method($otherMethod));
                }
            }

            return $baseAuth;
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
        foreach (glob(APPPATH . 'controllers/' . $this->flag . '/[!.]*.php') as $filePath)
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
        $config = $this->resourceLoader->routeEntry($className);

        if(is_empty($config, 'properties')) $config['properties'] = [];
        if(is_empty($config['properties'], 'noIndex')) $config['properties']['noIndex'] = false;
        if(is_empty($config, 'methods')) $config['methods'] = ['index'];

        // 이 클래스(ChildClass)에서 선언된 메서드만 필터
        $methodList = [];
        if(!$config['properties']['noIndex']) {
            $methodList['index'] = 'index';
        }else{
            // baseMethod 생략 시 fillRouteConfigProperties와 같이 첫 method
            $baseMethod = ($config['properties']['baseMethod'] ?? '') ?: array_key_first($config['methods']);
            if($baseMethod)
                $methodList[$baseMethod] = $baseMethod;
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
