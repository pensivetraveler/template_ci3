<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once __DIR__.'/Common.php';

class MenuList extends Common
{
    function __construct()
    {
        parent::__construct();

        $this->routeTitle = '메뉴';

        $this->load->model('Model_Menu', 'Model');
        $this->load->model('Model_Menu_Auth');

        $this->setProperties($this->Model);

        if($this->flag !== 'admin') show_404();
    }

    // 메뉴·권한 테이블을 비우는 동작이라 GET(링크·프리페치로 실행될 수 있음)이 아닌 POST로만 받음
    public function reset_post()
    {
        $this->Model->truncate();
        $this->Model_Menu_Auth->truncate();

        /**
         * TODO : Model_Partner_Auth 관련 커스텀 동작
         */
        $this->load->model('Model_Partner_Auth');
        $this->Model_Partner_Auth->truncate();

        $this->cache->file->delete('menu_done');
        $this->response(['code' => DATA_PROCESSED]);
    }

    /**
     * 메뉴 기본 권한(base_auth)을 지금 route·method 설정으로 다시 계산한다 (menu_id·등급별·파트너별 권한은 그대로).
     * 설정을 바꾼 뒤(엑셀·상세 화면 추가 등) 메뉴 권한 화면에서 새 자리를 켤 수 있게 한다. 줄어든 자리는 기존 권한에서 끄지 않는다
     */
    public function recalcBaseAuth_post()
    {
        $config = $this->getMenuRouteConfigs();
        $changed = [];

        foreach ($this->Model->getList([], []) as $row) {
            $item = [
                'isSubMenu' => (bool)$row->is_sub_menu,
                'class' => (string)$row->class,
                'method' => ($row->method ?? '') !== '' ? $row->method : 'index',
            ];
            $baseAuth = $this->getMenuBaseAuth($item, $config);
            if($baseAuth === (string)$row->base_auth) continue;

            $this->Model->modData(['base_auth' => $baseAuth], ['menu_id' => $row->menu_id]);
            $changed[] = [
                'menu_id' => $row->menu_id,
                'title' => $row->title,
                'before' => $row->base_auth,
                'after' => $baseAuth,
            ];
        }

        if(count($changed)) $this->cachingMenu();

        $this->response([
            'code' => DATA_PROCESSED,
            'data' => $changed,
        ]);
    }

    /**
     * nav 설정({flag}_nav_menu)에 있는데 DB(tbl_menu)에 없는 메뉴를 추가한다. 있는 메뉴는 고치거나 지우지 않는다.
     * 메뉴는 처음 한 번 설정에서 DB 로 들어가고 그 뒤로는 메뉴 목록 화면에서 편집하므로, 나중에 설정에 추가한 메뉴는
     * 이 버튼으로 가져온다. 같은 메뉴: 일반 메뉴는 class·method, 하위 메뉴 묶음은 같은 깊이의 title
     */
    public function syncFromConfig_post()
    {
        $config = $this->getMenuRouteConfigs();
        $rows = $this->Model->getList([], ['orderBy' => ['depth' => 'asc', 'srt' => 'asc']]);

        $added = [];
        foreach ($this->getMenuList() as $item) {
            $parent = $this->findSavedMenu($rows, $item, 1, null);
            if(!$parent) {
                $parent = $this->addConfigMenu($item, $config, $rows, 1, 0);
                $added[] = $item['title'];
            }

            foreach ($item['isSubMenu'] ? $item['subMenu'] : [] as $subItem) {
                if($this->findSavedMenu($rows, $subItem, 2, (int)$parent->menu_id)) continue;
                $this->addConfigMenu($subItem, $config, $rows, 2, (int)$parent->menu_id);
                $added[] = $item['title'].' > '.$subItem['title'];
            }
        }

        if(count($added)) $this->cachingMenu();

        $this->response(['code' => DATA_PROCESSED, 'data' => $added]);
    }

    protected function findSavedMenu(array $rows, array $item, int $depth, ?int $parentId)
    {
        foreach ($rows as $row) {
            if((int)$row->depth !== $depth) continue;
            if($parentId !== null && (int)$row->parent_id !== $parentId) continue;
            if($item['isSubMenu']) {
                if((int)$row->is_sub_menu === 1 && (string)$row->title === (string)$item['title']) return $row;
            }else{
                $method = ($row->method ?? '') !== '' ? $row->method : 'index';
                if((string)$row->class === (string)$item['class'] && $method === $item['method']) return $row;
            }
        }
        return null;
    }

    /**
     * 설정 메뉴 하나를 같은 단계 맨 뒤(srt 최대+1)에 추가하고 $rows 에도 넣는다
     */
    protected function addConfigMenu(array $item, array $config, array &$rows, int $depth, int $parentId)
    {
        $srt = 0;
        foreach ($rows as $row) {
            if((int)$row->depth === $depth && (int)($row->parent_id ?? 0) === $parentId) $srt = max($srt, (int)$row->srt);
        }

        $subMenu = $item['subMenu'];
        $item['subMenu'] = [];
        $data = $this->setMenuData($item, $config, $srt + 1, $parentId);
        $menuId = $this->Model->addData($this->transformMenuData($data));

        $row = $this->Model->getDataWhere([], ['menu_id' => $menuId]);
        $rows[] = $row;
        return $row;
    }

    public function caching_get()
    {
        $this->cachingMenu();

        $this->response(['code' => DATA_PROCESSED]);
    }

    public function generateMenuCode_get()
    {
        $menuList = $this->list([], false)['list'];

        $prefix = 'M';
        foreach ($menuList as $menu) {
            $code = $prefix.str_pad($menu->srt, 2, '0', STR_PAD_LEFT);
            $this->Model->modData([
                'code' => $code.'0000',
            ], [
                'menu_id' => $menu->menu_id,
            ]);

            if(count($menu->sub_menu)) {
                foreach ($menu->sub_menu as $subMenu) {
                    $subCode = $code.str_pad($subMenu->srt, 2, '0', STR_PAD_LEFT);
                    $this->Model->modData([
                        'code' => $subCode.'00',
                    ], [
                        'menu_id' => $subMenu->menu_id,
                    ]);
                }
            }
        }

        $this->cachingMenu();

        $this->response(['code' => DATA_PROCESSED]);
    }

    public function saveAll_post()
    {
        $data = $this->input->json();

        foreach ($data as $item) {
            $item['is_sub_menu'] = in_array($item['menu_id'], array_column($data, 'parent_id'));

            $params = [
                'parent_id' => $item['parent_id'],
                'depth' => $item['depth'],
                'srt' => $item['srt'],
                'is_sub_menu' => $item['is_sub_menu']?'1':'0',
            ];

            if($item['is_sub_menu']) {
                $params = array_merge($params, [
                    'class' => '',
                    'method' => '',
                ]);
            }

            $this->Model->modData($params, [
                'menu_id' => $item['menu_id'],
            ]);
        }

        $this->cachingMenu();

        $this->response(['code' => DATA_PROCESSED]);
    }

    protected function beforeList($data): array
    {
        $data['where']['depth'] = 1;
        $data['orderBy'] = ['srt' => 'asc'];
        return parent::beforeList($data); // TODO: Change the autogenerated stub
    }

    protected function executeList($data, $model = null): array
    {
        $data = parent::executeList($data, $model);

        foreach ($data['list'] as $item) {
            if($item->is_sub_menu) {
                $subList = $this->Model->getList([], [
                    'where' => [
                        'depth' => 2,
                        'parent_id' => $item->menu_id,
                    ],
                    'orderBy' => $data['orderBy'],
                ]);

                $item->sub_menu = $this->transformList($subList);
            }else{
                $item->sub_menu = [];
            }
        }

        return $data;
    }

    protected function transformView($data): object
    {
        $data->attr = @unserialize($data->attr);
        $data->params = @unserialize($data->params);
        $data->auth_params = @unserialize($data->auth_params);

        return parent::transformView($data);
    }

    protected function beforePost($key = 0, $model = null): array
    {
        list($key, $data) = parent::beforePost($key, $model); // TODO: Change the autogenerated stub

        if(empty($data['attr']['className'])) {
            $data['attr']['className'] = [];
        }else if(is_string($data['attr']['className'])){
            $data['attr']['className'] = explode(',', $data['attr']['className']);
        }

        foreach (['attr', 'params', 'auth_params'] as $field) {
            $data[$field] = serialize($data[$field]??[]);
        }

        return [$key, $data];
    }

    protected function afterModify($key, $dto)
    {
        $cnt = $this->Model->getCnt([
            'where' => [
                'parent_id' => $key,
            ],
        ]);

        if($cnt > 0) {
            $this->Model->modData([
                'is_login' => $dto['is_login'],
                'is_auth' => $dto['is_auth'],
                'is_super' => $dto['is_super'],
                'is_use' => $dto['is_use'],
            ], [
                'parent_id' => $key,
            ]);
        }
        parent::afterModify($key, $dto); // TODO: Change the autogenerated stub
    }

    protected function beforeResponse($data, $isFetch = false)
    {
        if(!$isFetch) $this->cachingMenu();

        return $data;
    }

    protected function cachingMenu()
    {
        $this->cache->file->delete('menu_done');

        $menuList = $this->deformMenuList($this->list([], false)['list']);

        $this->cache->file->save('menu_done', $menuList, 0);
    }

    public function options_get()
    {
        $class = $this->input->get('class');

        $routeConfig = $this->config->get('route_config');

        $data = [];

        $filtered = array_filter($routeConfig, function($v, $k) use ($class) {
            return preg_match("/^" . preg_quote($class, "/") . "$/i", $k);
        }, ARRAY_FILTER_USE_BOTH);

        if($filtered) {
            $conf = array_values($filtered)[0];
            $noIndex = $conf['properties']['noIndex']??false;

            if(!$noIndex) {
                $data[] = [
                    'id' => 'index',
                    'text' => 'Index',
                ];
            }

            // baseMethod 생략 시 fillRouteConfigProperties와 같이 첫 method
            $baseMethod = ($conf['properties']['baseMethod'] ?? '') ?: array_key_first($conf['methods'] ?? []);
            foreach (array_keys($conf['methods'] ?? []) as $method) {
                if(!$noIndex && $method === $baseMethod) continue;
                $data[] = [
                    'id' => $method,
                    'text' => ucfirst($method),
                ];
            }
        }

        $this->response(['code' => DATA_PROCESSED, 'data' => $data]);
    }
}
