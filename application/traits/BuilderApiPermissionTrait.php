<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * API 권한 검사 (메뉴 권한) (MY_Builder_API)
 */
trait BuilderApiPermissionTrait
{
    /**
     * 메뉴 권한을 검사할 플랫폼(flag). 기본은 관리자(/admin/api/*)만. 공개 /api/* 는 로그인·api_method_config 의 auth 만
     */
    protected array $permissionFlags = ['admin'];

    /**
     * 이름 있는 엔드포인트의 권한 자리. 없으면 HTTP 메서드로 판단 (GET 메뉴 표시만, POST·PUT·PATCH 수정, DELETE 삭제)
     */
    protected array $permissionEndpointModes = [
        'prepareExports' => 'export', 'downloadExports' => 'export',
        'validateExcel' => 'import', 'uploadExcel' => 'import', 'deleteExcelFile' => 'import',
        'duplicate' => 'create',
    ];

    /**
     * 요청이 메뉴 권한에 맞는지 검사한다. 맞지 않으면 403.
     *  1. 시스템 관리자, 로그인 전 요청(auth none 엔드포인트), permission.check === false, permission.public → 통과
     *  2. permission.super 또는 연결된 메뉴가 isSuper → 시스템 관리자만
     *  3. 메뉴와 연결되지 않은 API → 관리자 등급(is_admin)만
     *  4. CACHING_MENU=false(권한 데이터 없음) 또는 메뉴 isAuth=false → 통과 (화면과 같음)
     *  5. 메뉴 권한 행이 없거나 숨김 → 403, 요청 권한 자리(create·read·update·delete·export·import)가 꺼져 있으면 403
     *     권한 자리가 'read|update' 처럼 여럿이면 하나만 켜져 있어도 통과, '' 이면 메뉴 표시(is_show)만 확인
     * 메뉴 연결: api_method_config 의 permission.menu ('class' 또는 'class/method'), 없으면 API 클래스와 같은 이름의 메뉴
     */
    protected function checkApiPermission(string $http, string $object, array $arguments): void
    {
        if(!in_array($this->flag, $this->permissionFlags, true)) return;

        $permission = $this->apiMethodFeature['permission'] ?? [];
        if(array_key_exists('check', $permission) && $permission['check'] === false) return;
        if(!empty($permission['public'])) return;
        if(!$this->session->userdata('user_id')) return;
        if($this->session->userdata('is_sys_admin')) return;

        if(!empty($permission['super'])) $this->denyApiPermission('super');

        $menu = $this->findApiMenu($permission['menu'] ?? null, $object);
        if(!count($menu)) {
            if(!$this->session->userdata('is_admin')) $this->denyApiPermission('admin');
            return;
        }
        if(!empty($menu['isSuper'])) $this->denyApiPermission('super');
        if(!env('CACHING_MENU')) return;
        if(array_key_exists('isAuth', $menu) && !$menu['isAuth']) return;

        $auth = $this->getApiMenuAuth($menu);
        if(!$auth || (int)($auth->is_show ?? 0) !== 1) $this->denyApiPermission('menu');

        $mode = $permission['mode'] ?? $this->getApiPermissionMode($http, $object, $arguments);
        if($mode === '' || $mode === null) return;
        $modes = is_array($mode) ? $mode : explode('|', (string)$mode);
        foreach ($modes as $item) {
            if(is_auth_avaliable($item, (string)$auth->menu_auth)) return;
        }
        $this->denyApiPermission(implode('|', $modes));
    }

    /**
     * 요청이 쓰는 권한 자리. '' 는 메뉴 표시만 확인
     *  - 목록 GET·이름 있는 GET(선택 목록·중복 확인 등): '' (화면 목록이 권한 자리를 검사하지 않는 것과 같음)
     *  - 단건 GET: 'read|update' (상세 화면은 read, 수정 화면도 단건 조회를 씀)
     */
    protected function getApiPermissionMode(string $http, string $object, array $arguments): string
    {
        if($object !== 'index' && method_exists($this, "{$object}_{$http}")) {
            if(array_key_exists($object, $this->permissionEndpointModes)) return $this->permissionEndpointModes[$object];
            return $http === 'get' ? '' : ($http === 'delete' ? 'delete' : 'update');
        }

        switch ($http) {
            case 'get' :
                $key = $object !== 'index' ? $object : ($arguments[0] ?? 0);
                return count($this->checkIdentifierExist($key)) ? 'read|update' : '';
            case 'delete' : return 'delete';
            case 'put' :
            case 'patch' : return 'update';
            case 'post' :
                // 식별자(URL 키 또는 쿼리)가 있으면 수정, 없으면 등록 (afterPost 와 같은 기준)
                $key = $object !== 'index' ? $object : ($arguments[0] ?? 0);
                return count($this->checkIdentifierExist($key)) ? 'update' : 'create';
        }
        return '';
    }

    /**
     * API 에 연결된 메뉴 항목(nav 설정의 항목 배열). 없으면 [].
     * 같은 class 가 여러 메뉴면 method 가 같은 항목 → method 없는(index) 항목 순.
     * URL 대소문자로 메뉴 연결을 피하지 못하도록 class 는 대소문자 없이 비교한다
     */
    protected function findApiMenu(?string $spec, string $object): array
    {
        if($spec) {
            [$class, $method] = array_pad(explode('/', $spec, 2), 2, '');
        }else{
            $class = $this->router->class;
            $method = $object !== 'index' && method_exists($this, "{$object}_".strtolower($this->input->method())) ? $object : '';
        }

        $items = [];
        $walk = function ($menu) use (&$walk, &$items, $class) {
            foreach ($menu as $item) {
                if(strcasecmp($item['class'] ?? '', $class) === 0) $items[] = $item;
                if(!empty($item['subMenu'])) $walk($item['subMenu']);
            }
        };
        $walk($this->config->item("{$this->flag}_nav_menu") ?: []);
        if(!count($items)) return [];

        foreach ($items as $item) if($method !== '' && ($item['method'] ?? '') === $method) return $item;
        foreach ($items as $item) if(in_array($item['method'] ?? '', ['', 'index'], true)) return $item;
        return $items[0];
    }

    /**
     * 메뉴 권한 행 {menu_auth, is_show}. 기본은 회원 등급(user_cd)별 menu_auth.
     * 파트너별 권한처럼 다른 기준을 쓰는 프로젝트는 오버라이드한다.
     */
    protected function getApiMenuAuth(array $menu): ?object
    {
        $this->load->model('Model_Menu');
        $this->load->model('Model_Menu_Auth');

        $method = $menu['method'] ?? '';
        $row = $this->Model_Menu->getData(['menu_id'], [
            'where' => [
                'class' => $menu['class'],
                'method' => $method !== '' ? $method : 'index',
            ],
        ]);
        if(!$row) return null;

        $auth = $this->Model_Menu_Auth->getData(['menu_auth', 'is_show'], [
            'where' => [
                'grade_cd' => $this->session->userdata('user_cd'),
                'menu_id' => is_object($row) ? $row->menu_id : $row,
            ],
        ]);
        return is_object($auth) ? $auth : null;
    }

    /**
     * 본문의 식별자·부모 키는 URL·쿼리로 정한 수정 대상과 같아야 한다 (등록은 부모 키를 받지 않음). 다르면 403.
     * 수정 대상은 본문으로 바뀌지 않지만 is_unique 가 이 값으로 자기 행을 제외하므로, 그대로 두면 중복 검사를 피할 수 있다.
     * 비어 있거나 없으면 통과 (폼의 hidden 값)
     */
    protected function checkBodyKeys(string $http, string $object, array $arguments): void
    {
        if(!in_array($http, ['post', 'put', 'patch'], true) || !property_exists($this, 'Model') || !$this->Model) return;
        if($object !== 'index' && method_exists($this, "{$object}_{$http}")) return;

        $idData = $this->checkIdentifierExist($object !== 'index' ? $object : ($arguments[0] ?? 0));
        $expected = [];
        if($this->Model->isAutoIncrement && $this->Model->identifier && count($idData)) {
            $expected[$this->Model->identifier] = $idData[$this->Model->identifier] ?? '';
        }
        if(property_exists($this, 'Model_Parent')) {
            $row = count($idData) ? $this->Model->getDataWhere([], $idData) : null;
            $field = $this->Model_Parent->identifier;
            $expected[$field] = is_object($row) ? ($row->{$field} ?? '') : '';
        }

        $body = ($http === 'patch' ? $this->patch() : $this->input->post_put()) ?: [];
        foreach ($expected as $field => $value) {
            if(!array_key_exists($field, $body) || $body[$field] === '') continue;
            if(!is_scalar($body[$field]) || trim((string)$body[$field]) !== (string)$value) $this->denyApiPermission('body_key');
        }
    }

    /**
     * 자기보다 높은 회원 등급은 저장할 수 없다 (등록·수정·복제·엑셀 값). 코드 숫자가 작을수록 높다. 시스템 관리자는 제한 없음
     */
    protected function checkUserGradeValue(array $dto): void
    {
        $grade = (string)($dto[USER_CD_COLUMN_NAME] ?? '');
        if($grade === '' || $this->session->userdata('is_sys_admin')) return;
        if(strcmp($grade, (string)$this->session->userdata('user_cd')) < 0) $this->denyApiPermission('grade');
    }

    /**
     * 자기보다 높은 등급의 회원 행은 수정·삭제할 수 없다 (회원 등급 컬럼이 있는 엔티티만)
     */
    protected function checkUserGradeTarget(): void
    {
        if(!count($this->idData) || $this->session->userdata('is_sys_admin')) return;

        $models = array_filter([$this->Model, $this->Model_Parent ?? null]);
        $hasGrade = array_filter($models, function ($model) {
            return in_array(USER_CD_COLUMN_NAME, array_merge($model->notNullList, $model->nullList), true);
        });
        if(!count($hasGrade)) return;

        $row = $this->Model->getDataWhere([], $this->idData);
        if(is_object($row)) $this->checkUserGradeValue((array)$row);
    }

    protected function denyApiPermission(string $reason): void
    {
        $this->responseError([
            'location' => __METHOD__,
            'param' => ['class' => $this->router->class, 'reason' => $reason],
            'msg' => 'Permission Denied',
        ], [
            'code' => FORBIDDEN_ACCESS,
        ], RestController::HTTP_FORBIDDEN);
    }
}
