<?php
defined('BASEPATH') OR exit('No direct script access allowed');

trait BuilderCommonTrait
{
    protected function destroyUserData(): void
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
    }

    protected function setMenuList(): array
    {
        if($this->cache->file->get('menu_done')){
            $menuList = $this->cache->file->get('menu_done');
        }else{
            $menuList = $this->getMenuList();
            if(env('CACHING_MENU')) $this->saveMenuList($menuList);
        }
        return $this->menuList = $this->setMenuData($menuList);
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
        return array_map(function ($item) use ($conf, $depth) {
            foreach ($conf as $key=>$val) {
                if(is_array($val)) {
                    if(array_key_exists($key, $item)) {
                        if(is_bool($item[$key]) || is_null($item[$key])) $item[$key] = [];
                        $item[$key] = array_merge($val, $item[$key]);
                    }else{
                        $item[$key] = $val;
                    }
                }else{
                    if(!array_key_exists($key, $item)) $item[$key] = $val;
                }
            }
            $item['depth'] = $depth;
            $item['method'] = !$item['method']&&$item['class']?'index':$item['method'];
            $item['isSubMenu'] = count($item['subMenu']) > 0;
            if($item['isSubMenu']) $item['subMenu'] = $this->fillMenuConf($item['subMenu'], 2);

            return $item;
        }, $configData);
    }

    protected function setMenuData($menuList = []): array
    {
        return array_map(function ($item) {
            $item = (array)$item;

            if($item['isSubMenu']) {
                $item['attr']['href'] = '';
                $item['attr']['className'] = array_merge($item['attr']['className'], [
                    'menu-toggle', 'waves-effect'
                ]);
                $item['subMenu'] = $this->setMenuData($item['subMenu']);
            }else{
                if($item['class'] && $item['method']) {
                    $flags = [$this->flag, $item['class']];
                    if($item['method'] !== 'index') $flags[] = $item['method'];
                    $item['attr']['href'] = '/'.implode('/', $flags);
                }
            }

            $item['listClassName'] = [];
            if(is_admin_active_page($item)) {
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

        $this->Model_Menu->truncate();

        function transformMenuData($data, $srt = 1, $parentId = 0)
        {
            $result = [];
            foreach ($data as $key=>$val) {
                if(is_array($val)) {
                    $val = serialize($val);
                }elseif (is_bool($val)) {
                    $val = !$val?'0':'1';
                }
                $result[snakeize($key)] = $val;
            }
            $result['parent_id'] = $parentId;
            $result['srt'] = $srt;
            return $result;
        }

        foreach ($menuList as $i=>$item) {
            $menuId = $this->Model_Menu->addData(transformMenuData($item, $i+1));
            if(count($item['subMenu'])) {
                foreach ($item['subMenu'] as $j=>$subitem) {
                    $subitem = transformMenuData($subitem, $j+1, $menuId);
                    $this->Model_Menu->addData($subitem);
                }
            }
        }

        if(env('CACHING_MENU')) $this->cache->file->save('menu_done', $menuList, 0);
    }

    protected function deformMenuList($menuList)
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

    protected function deformMenuData($menuData)
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
            $classes[strtolower($className)] = $className;
        }

        return $classes;
    }

    public function getMethodList($className): array
    {
        $config = $this->config->get("page_config")[$className]??$this->config->get("page_config")[strtolower($className)];

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
}
