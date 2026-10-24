<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * 빌더 리소스(엔티티) 설정을 찾는 곳.
 *
 * WEB(MY_Builder_WEB)과 API(MY_Builder_API)가 엔티티별 설정을 찾을 때 모두 이 클래스를 거친다.
 * - route 설정  : route_config[{class}]
 * - 엔티티 설정 : {kind}_{name}_config (kind = form | list | filter | view | excel)
 *
 * 지금은 분할형(종류별 파일: admin_form_config.php 등)만 읽는다.
 * 엔티티당 파일 하나인 통합형은 이 클래스에 추가해서, WEB·API 코드는 바꾸지 않고 지원할 예정이다.
 * 설정 이름 규칙은 builder_config_name() (클래스·메서드명을 snake 형태로).
 *
 * 로드: BuilderCommonTrait::loadConfigs() 가 $this->resourceLoader 로 올린다.
 */
class Resource_loader
{
    /** @var CI_Controller */
    protected $CI;

    /**
     * 마지막으로 찾은 엔티티 설정의 키 (예: 'form_administrators_config', 못 찾으면 '')
     * 어떤 설정을 읽었는지 확인할 때 쓴다.
     */
    public string $lastConfigName = '';

    public function __construct()
    {
        $this->CI =& get_instance();
    }

    /* --------------------------------------------------------------- */
    /* route 설정                                                       */
    /* --------------------------------------------------------------- */

    /**
     * route_config 에서 클래스에 해당하는 키. 원래 이름 → 소문자 순으로 찾고, 없으면 ''.
     */
    public function routeKey(string $class): string
    {
        $config = $this->CI->config->item('route_config') ?: [];

        $key = '';
        if(!is_empty($config, $class)) $key = $class;
        if(!is_empty($config, strtolower($class))) $key = strtolower($class);

        return $key;
    }

    /**
     * route_config 의 원본 항목 (기본값 채우기 전). 없으면 [].
     */
    public function routeEntry(string $class): array
    {
        $key = $this->routeKey($class);
        if($key === '') return [];

        return ($this->CI->config->item('route_config') ?: [])[$key] ?? [];
    }

    /* --------------------------------------------------------------- */
    /* 엔티티 설정 (form / list / filter / view / excel)                 */
    /* --------------------------------------------------------------- */

    /**
     * 엔티티 설정 키 하나. 예: entityConfigName('form', 'partnerAuth') → 'form_partner_auth_config'
     * $name 이 이미 snake 형태(resourceConfig 값 등)여도 결과는 같다.
     */
    public function entityConfigName(string $kind, string $name): string
    {
        return "{$kind}_".builder_config_name($name)."_config";
    }

    /**
     * WEB 화면에서 엔티티 설정을 찾는 후보 키 목록 (앞에서부터 먼저 찾음).
     *   1. {kind}_{add}_config            (add: resourceConfig 등 지정한 이름)
     *   2. {kind}_{class}_{method}_config
     *   3. {kind}_{class}_config
     *   4. {kind}_{method}_config
     * method 가 index 면 kind 로 바꿔 찾는다 (filter 제외). 예: list 화면 → form_{class}_list_config
     */
    public function entityConfigNames(string $kind, string $class, string $method, ?string $add = null): array
    {
        $class = builder_config_name($class);
        $method = builder_config_name($method);
        if($method === 'index' && $kind !== 'filter') $method = $kind;

        $names = [];
        if(!empty($add)) $names[] = "{$kind}_{$add}_config";

        return array_merge($names, [
            "{$kind}_{$class}_{$method}_config",
            "{$kind}_{$class}_config",
            "{$kind}_{$method}_config",
        ]);
    }

    /* --------------------------------------------------------------- */
    /* 통합형 리소스 파일 (엔티티당 파일 하나)                            */
    /* --------------------------------------------------------------- */

    /**
     * 읽어 들인 통합형 리소스: [클래스 => 파일 경로]
     */
    public array $mergedResources = [];

    /** 통합형 파일에서 쓸 수 있는 최상위 키 */
    public const MERGED_KEYS = ['route', 'nav', 'form', 'list', 'filter', 'view', 'excel', 'methods', 'model'];

    /** 통합형 파일 안의 엔티티 설정 종류 */
    public const ENTITY_KINDS = ['form', 'list', 'filter', 'view', 'excel'];

    /**
     * config/extra/{flag}/resources/{클래스}.php 를 읽어, 분할형과 같은 설정 키로 넣는다.
     * 그래서 설정을 읽는 쪽(WEB·API·메뉴)은 형식을 몰라도 된다.
     *   route              → route_config[{클래스}]
     *   form|list|...      → {kind}_{클래스}_config
     *   methods[m][kind]   → {kind}_{클래스}_{m}_config
     *   nav                → {flag}_nav_menu 의 해당 클래스 항목 (트리·순서는 nav 설정이 정함)
     * 같은 엔티티가 분할형에도 있으면 개발 환경에서는 에러, 그 밖에는 error 로그 후 통합형을 건너뛴다.
     * BuilderCommonTrait::loadConfigs() 가 분할형 설정을 읽은 뒤 호출한다.
     */
    public function loadMergedResources(string $flag): void
    {
        foreach (glob(APPPATH."config/extra/{$flag}/resources/*.php") ?: [] as $file) {
            $class = basename($file, '.php');
            $data = $this->readMergedFile($file);

            $conflicts = $this->findSplitConflicts($class, $data);
            if(count($conflicts)) {
                $message = "Resource '{$class}' is defined in both merged file ({$file}) and split config: ".implode(', ', $conflicts);
                if(ENVIRONMENT === 'development') show_error($message);
                log_message('error', $message);
                continue;
            }

            $this->injectMergedResource($flag, $class, $data);
            $this->mergedResources[$class] = $file;
        }
    }

    /**
     * 통합형 파일을 읽는다. 파일은 `return [...];` 로 배열을 돌려줘야 한다.
     */
    public function readMergedFile(string $file): array
    {
        $data = include $file;
        if(!is_array($data)) show_error("Merged resource file must return an array: {$file}");

        $unknown = array_diff(array_keys($data), self::MERGED_KEYS);
        if(count($unknown)) show_error("Unknown keys in merged resource file {$file}: ".implode(', ', $unknown));

        return $data;
    }

    /**
     * 통합형이 넣으려는 키가 분할형에 이미 있으면 그 키 목록을 돌려준다.
     * 메뉴는 트리에서 클래스를 참조만 하는 게 정상이므로 충돌로 보지 않는다.
     */
    public function findSplitConflicts(string $class, array $data): array
    {
        $conflicts = [];

        if(isset($data['route']) && $this->routeKey($class) !== '') $conflicts[] = "route_config[{$this->routeKey($class)}]";

        foreach (array_keys($this->mergedConfigKeys($class, $data)) as $key) {
            if($this->CI->config->item($key) !== null) $conflicts[] = $key;
        }

        return $conflicts;
    }

    /**
     * 통합형 데이터에서 만들어질 엔티티 설정 키와 값: [키 => 설정]
     */
    public function mergedConfigKeys(string $class, array $data): array
    {
        $keys = [];
        foreach (self::ENTITY_KINDS as $kind) {
            if(isset($data[$kind]) && is_array($data[$kind])) $keys[$this->entityConfigName($kind, $class)] = $data[$kind];
        }
        foreach (($data['methods'] ?? []) as $method => $kinds) {
            foreach (self::ENTITY_KINDS as $kind) {
                if(isset($kinds[$kind]) && is_array($kinds[$kind])) {
                    $keys["{$kind}_".builder_config_name($class).'_'.builder_config_name($method).'_config'] = $kinds[$kind];
                }
            }
        }
        return $keys;
    }

    protected function injectMergedResource(string $flag, string $class, array $data): void
    {
        $config = $this->CI->config;

        if(isset($data['route']) && is_array($data['route'])) {
            $routes = $config->item('route_config') ?: [];
            $routes[$class] = $data['route'];
            $config->set_item('route_config', $routes);
        }

        foreach ($this->mergedConfigKeys($class, $data) as $key => $value) {
            $config->set_item($key, $value);
        }

        if(isset($data['nav']) && is_array($data['nav'])) {
            $navKey = "{$flag}_nav_menu";
            $config->set_item($navKey, $this->injectNavItem($config->item($navKey) ?: [], $class, $data['nav']));
        }
    }

    /**
     * 메뉴 트리에 리소스 메뉴를 넣는다.
     * - 트리에 같은 class 항목이 있으면: 트리 값은 그대로 두고 빈 키만 nav 값으로 채움 (예: ['class' => 'projects'] 만 적어 둔 경우)
     * - 없으면: nav.parent(상위 메뉴의 class 또는 title) 아래, 없으면 최상위에 nav.order 위치(0부터, 없으면 맨 끝)로 넣음
     * parent·order 는 메뉴 항목 키가 아니므로 넣기 전에 뺀다.
     */
    public function injectNavItem(array $menu, string $class, array $nav): array
    {
        $parent = $nav['parent'] ?? null;
        $order = $nav['order'] ?? null;
        unset($nav['parent'], $nav['order']);
        $item = array_merge(['class' => $class], $nav);

        $found = false;
        $menu = $this->fillNavItem($menu, $class, $item, $found);
        if($found) return $menu;

        if($parent !== null && $parent !== '') {
            $inserted = false;
            $menu = $this->insertNavChild($menu, $parent, $item, $order, $inserted);
            if($inserted) return $menu;
            log_message('error', "Resource '{$class}' nav parent '{$parent}' not found; added to top level.");
        }

        return $this->insertAt($menu, $item, $order);
    }

    protected function fillNavItem(array $menu, string $class, array $item, bool &$found): array
    {
        foreach ($menu as $idx => $menuItem) {
            if(($menuItem['class'] ?? '') === $class) {
                $menu[$idx] = $menuItem + $item;
                $found = true;
            }elseif(!empty($menuItem['subMenu']) && is_array($menuItem['subMenu'])) {
                $menu[$idx]['subMenu'] = $this->fillNavItem($menuItem['subMenu'], $class, $item, $found);
            }
        }
        return $menu;
    }

    protected function insertNavChild(array $menu, string $parent, array $item, $order, bool &$inserted): array
    {
        foreach ($menu as $idx => $menuItem) {
            if($inserted) break;
            if(($menuItem['class'] ?? null) === $parent || ($menuItem['title'] ?? null) === $parent) {
                $menu[$idx]['subMenu'] = $this->insertAt($menuItem['subMenu'] ?? [], $item, $order);
                $inserted = true;
            }elseif(!empty($menuItem['subMenu']) && is_array($menuItem['subMenu'])) {
                $menu[$idx]['subMenu'] = $this->insertNavChild($menuItem['subMenu'], $parent, $item, $order, $inserted);
            }
        }
        return $menu;
    }

    protected function insertAt(array $list, array $item, $order): array
    {
        $list = array_values($list);
        if($order === null || !is_numeric($order) || (int)$order >= count($list)) {
            $list[] = $item;
        }else{
            array_splice($list, max(0, (int)$order), 0, [$item]);
        }
        return $list;
    }

    /**
     * 클래스가 어느 형식으로 정의돼 있는지: 'merged' | 'split' | ''
     */
    public function resourceSource(string $class): string
    {
        if(isset($this->mergedResources[$class])) return 'merged';
        return $this->routeKey($class) !== '' ? 'split' : '';
    }

    /**
     * 후보 키 중 처음 존재하는 설정을 돌려준다. 없으면 $default.
     * $names 는 키 하나(string) 또는 후보 목록(array).
     */
    public function entityConfig($names, $default = [], bool $triggerError = false)
    {
        $this->lastConfigName = '';

        foreach ((array)$names as $name) {
            if($this->CI->config->item($name) !== null) {
                $this->lastConfigName = $name;
                break;
            }
        }

        return $this->CI->config->get($names, $default, $triggerError);
    }
}
