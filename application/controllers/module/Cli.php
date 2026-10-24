<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once __DIR__.'/Common.php';

class Cli extends Common
{
    public function __construct()
    {
        $this->accessWays = ['cli'];

        parent::__construct();
    }

    public function clear_expired_geoip_cache()
    {
        $this->load->model('Model_Geoip_Cache');

        $this->Model_Geoip_Cache->delete_expired();

        echo "Expired GeoIP cache deleted." . PHP_EOL;
    }

    /* --------------------------------------------------------------- */
    /* 빌더 리소스 확인                                                  */
    /*   php index.php module/cli resource_dump admin administrators     */
    /*   php index.php module/cli resource_check admin                   */
    /* --------------------------------------------------------------- */

    /**
     * 플랫폼(flag)의 빌더 설정을 화면 요청과 같은 순서로 읽는다 (BuilderCommonTrait::loadConfigs 와 같음, 통합형은 충돌 검사 없이)
     */
    protected function loadBuilderPlatform(string $flag, bool $loadMerged = true): void
    {
        foreach (['builder_base_config', 'builder_form_config', 'builder_nav_config', 'builder_route_config', 'builder_method_config', 'builder_list_config', 'builder_filter_config', 'builder_view_config'] as $config) {
            $this->config->load('extra/builder/'.$config, false);
        }
        require_once APPPATH . 'config/extra/builder/builder_base_constants.php';
        $this->load->helper(['inflector', 'builder/builder_base', 'builder/builder_route']);
        foreach (glob(APPPATH . "config/extra/{$flag}/*_config.php") as $file) {
            $this->config->load("extra/{$flag}/" . basename($file, '.php'));
        }
        $this->load->library('resource_loader', null, 'resourceLoader');
        if($loadMerged) $this->resourceLoader->loadMergedResources($flag);
    }

    /**
     * 리소스 하나가 어디에 정의돼 있고, 각 설정을 어떤 키로 읽는지 출력한다.
     */
    public function resource_dump($flag = 'admin', $class = '')
    {
        if($class === '') { echo "usage: php index.php module/cli resource_dump {flag} {class}".PHP_EOL; return; }

        $this->loadBuilderPlatform($flag);
        $loader = $this->resourceLoader;

        $source = $loader->resourceSource($class);
        echo "resource : {$class} ({$flag})".PHP_EOL;
        echo "source   : ".($source ?: 'not found').($source === 'merged' ? ' - '.$loader->mergedResources[$class] : '').PHP_EOL;

        $route = $loader->routeEntry($class);
        $methods = array_keys($route['methods'] ?? []);
        echo "route    : ".($route ? 'route_config['.$loader->routeKey($class).'], methods: '.implode(', ', $methods) : '-').PHP_EOL;

        foreach (array_unique(array_merge(['index'], $methods)) as $method) {
            echo PHP_EOL."[{$method}]".PHP_EOL;
            foreach (Resource_loader::ENTITY_KINDS as $kind) {
                $config = $loader->entityConfig($loader->entityConfigNames($kind, $class, $method), null);
                $name = $loader->lastConfigName ?: '-';
                $count = is_array($config) ? count($config).' items' : '';
                echo sprintf("  %-7s %s %s", $kind, $name, $count).PHP_EOL;
            }
        }

        echo PHP_EOL."api      : ".implode(', ', array_map(function ($kind) use ($loader, $class) {
            $name = $loader->entityConfigName($kind, $class);
            return $name.($this->config->item($name) !== null ? '' : ' (none)');
        }, ['form', 'list', 'view'])).PHP_EOL;

        $nav = $this->findNavItem($this->config->item("{$flag}_nav_menu") ?: [], $class);
        echo "nav      : ".($nav ? json_encode(array_intersect_key($nav, array_flip(['class', 'method', 'title', 'icon'])), JSON_UNESCAPED_UNICODE) : '-').PHP_EOL;
    }

    /**
     * 플랫폼 전체 리소스 설정을 검사한다. 문제가 있으면 종료 코드 1.
     *  - 통합형 파일: 최상위 키, 분할형과 같은 엔티티 중복
     *  - 메뉴: class 가 route 에 없는 항목
     *  - route: admin 컨트롤러 파일이 없는 클래스
     */
    public function resource_check($flag = 'admin')
    {
        $this->loadBuilderPlatform($flag, false);
        $loader = $this->resourceLoader;
        $problems = [];

        foreach (glob(APPPATH."config/extra/{$flag}/resources/*.php") ?: [] as $file) {
            $class = basename($file, '.php');
            try {
                $data = $loader->readMergedFile($file);
            } catch (Throwable $e) {
                $problems[] = "merged {$class}: ".$e->getMessage();
                continue;
            }
            $conflicts = $loader->findSplitConflicts($class, $data);
            if(count($conflicts)) $problems[] = "merged {$class}: also defined in split config - ".implode(', ', $conflicts);
        }

        $loader->loadMergedResources($flag);

        foreach ($this->collectNavClasses($this->config->item("{$flag}_nav_menu") ?: []) as $class) {
            if($loader->routeKey($class) === '') $problems[] = "nav {$class}: no route_config entry";
        }

        // route 키는 소문자로 적기도 하므로(myinfo ↔ MyInfo.php) 컨트롤러 파일명은 대소문자 없이 비교
        $controllers = array_map(function ($file) {
            return strtolower(basename($file, '.php'));
        }, glob(APPPATH."controllers/{$flag}/*.php") ?: []);
        foreach (array_keys($this->config->item('route_config') ?: []) as $class) {
            if(!in_array(strtolower($class), $controllers, true)) $problems[] = "route {$class}: controllers/{$flag}/".ucfirst($class).".php not found";
        }

        echo count($problems) ? implode(PHP_EOL, $problems).PHP_EOL : "OK".PHP_EOL;
        if(count($problems)) exit(1);
    }

    protected function findNavItem(array $menu, string $class): array
    {
        foreach ($menu as $item) {
            if(($item['class'] ?? '') === $class) return $item;
            if(!empty($item['subMenu'])) {
                $found = $this->findNavItem($item['subMenu'], $class);
                if($found) return $found;
            }
        }
        return [];
    }

    protected function collectNavClasses(array $menu): array
    {
        $classes = [];
        foreach ($menu as $item) {
            if(!empty($item['class'])) $classes[] = $item['class'];
            if(!empty($item['subMenu'])) $classes = array_merge($classes, $this->collectNavClasses($item['subMenu']));
        }
        return array_values(array_unique($classes));
    }
}
