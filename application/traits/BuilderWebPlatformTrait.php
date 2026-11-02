<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * 메뉴·페이지 권한과 플랫폼별 화면 준비 (MY_Builder_WEB)
 */
trait BuilderWebPlatformTrait
{
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
        $this->fullMenuList = $menuList;

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
                ||
                in_array($this->router->method, $this->commonMethods, true)
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

            $baseMethod = $this->routeConfig['properties']['baseMethod'];
            $currMethod = $this->router->method === 'index' ? $baseMethod : $this->router->method;

            // 이 화면을 가리키는 메뉴가 따로 있으면 그 메뉴를 볼 수 있어야 한다 (같은 class 의 다른 메뉴로 대신하지 않음)
            if(count($this->findPageMenus($this->fullMenuList, $currMethod, $baseMethod))) {
                $ownMenus = $this->findPageMenus($this->menuList, $currMethod, $baseMethod);
                if(!count($ownMenus)) alert('Not Authorized', base_url($this->loggedInRedirect));
                $this->currentMenu = $ownMenus[0];
            }

            $this->pageAuth = $this->currentMenu['menuAuth'] ?? INIT_AUTH_CHAR;
            $this->scope = $this->currentMenu['scopeCd'] ?? '';

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
     * 메뉴 목록에서 지금 class 의 이 화면을 가리키는 항목 (메뉴 method 가 비었거나 index 면 기준 화면)
     */
    protected function findPageMenus(array $menus, string $method, string $baseMethod): array
    {
        $found = [];
        foreach ($menus as $menu) {
            if(!empty($menu['subMenu'])) $found = array_merge($found, $this->findPageMenus($menu['subMenu'], $method, $baseMethod));
            if(($menu['class'] ?? '') !== $this->router->class) continue;
            $menuMethod = $menu['method'] ?? '';
            if(($menuMethod === '' || $menuMethod === 'index' ? $baseMethod : $menuMethod) === $method) $found[] = $menu;
        }
        return $found;
    }

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
}
