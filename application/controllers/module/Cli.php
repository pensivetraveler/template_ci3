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
        foreach (glob(APPPATH . "config/extra/{$flag}/[!.]*_config.php") as $file) {
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

        foreach (glob(APPPATH."config/extra/{$flag}/resources/[!.]*.php") ?: [] as $file) {
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
        }, glob(APPPATH."controllers/{$flag}/[!.]*.php") ?: []);
        foreach (array_keys($this->config->item('route_config') ?: []) as $class) {
            if(!in_array(strtolower($class), $controllers, true)) $problems[] = "route {$class}: controllers/{$flag}/".ucfirst($class).".php not found";
        }

        echo count($problems) ? implode(PHP_EOL, $problems).PHP_EOL : "OK".PHP_EOL;
        if(count($problems)) exit(1);
    }

    /* --------------------------------------------------------------- */
    /* 리소스 형식 변환                                                  */
    /*   php index.php module/cli resource_to_merged admin projects       */
    /*   php index.php module/cli resource_to_merged admin projects apply */
    /*   php index.php module/cli resource_to_split admin projects apply  */
    /* 마지막 인자가 apply 가 아니면 바꿀 내용만 출력한다 (dry run).       */
    /* --------------------------------------------------------------- */

    /**
     * 분할형 → 통합형: 분할형 파일({flag}/*_config.php)에서 엔티티 설정을 지우고 resources/{class}.php 를 만든다.
     * 메뉴 트리는 그대로 둔다(트리 값이 우선하므로 화면은 같다).
     */
    public function resource_to_merged($flag = 'admin', $class = '', $mode = 'dry')
    {
        if($class === '') { echo "usage: php index.php module/cli resource_to_merged {flag} {class} [apply]".PHP_EOL; return; }

        $this->loadBuilderPlatform($flag, false);
        $this->load->library('resource_converter', null, 'resourceConverter');
        $loader = $this->resourceLoader;
        $converter = $this->resourceConverter;

        $mergedFile = APPPATH."config/extra/{$flag}/resources/{$class}.php";
        if(file_exists($mergedFile)) $this->cliFail("already merged: {$mergedFile}");

        // 1. 옮길 설정 모으기 (실행 중 설정 값 기준)
        $data = [];
        $configKeys = [];
        $routeKey = $loader->routeKey($class);
        if($routeKey !== '') $data['route'] = $loader->routeEntry($class);

        $methods = array_unique(array_merge(array_keys($data['route']['methods'] ?? []), Resource_loader::ENTITY_KINDS));
        foreach (Resource_loader::ENTITY_KINDS as $kind) {
            $key = $loader->entityConfigName($kind, $class);
            if($this->config->item($key) !== null) { $data[$kind] = $this->config->item($key); $configKeys[] = $key; }
            foreach ($methods as $method) {
                $key = "{$kind}_".builder_config_name($class).'_'.builder_config_name($method).'_config';
                if($this->config->item($key) !== null) { $data['methods'][$method][$kind] = $this->config->item($key); $configKeys[] = $key; }
            }
        }
        if(!count($data)) $this->cliFail("no split config found for '{$class}'");

        // 2. 분할형 파일에서 지울 위치 찾기
        $changes = [];
        $located = [];
        foreach (glob(APPPATH."config/extra/{$flag}/[!.]*_config.php") as $file) {
            $code = file_get_contents($file);
            [$newCode, $removed] = $converter->removeEntity($code, $configKeys, $routeKey);
            if(count($removed)) {
                $changes[$file] = $newCode;
                foreach ($removed as $item) $located[] = basename($file).': '.$item;
            }
        }
        $expected = count($configKeys) + ($routeKey !== '' ? 1 : 0);
        if(count($located) !== $expected) {
            $this->cliFail("found {$expected} settings but located ".count($located)." in config/extra/{$flag}/ (defined in builder files or written in an unsupported form?)\n  keys: ".implode(', ', array_merge($configKeys, $routeKey !== '' ? ["route_config[{$routeKey}]"] : []))."\n  located: ".implode(', ', $located));
        }

        echo "resource_to_merged {$flag} {$class}".PHP_EOL;
        echo "  create : ".str_replace(APPPATH, 'application/', $mergedFile).PHP_EOL;
        foreach ($located as $item) echo "  remove : {$item}".PHP_EOL;
        echo "  nav    : 메뉴 트리({$flag}_nav_config.php)는 그대로 둠".PHP_EOL;

        if($mode !== 'apply') { echo "dry run - 'apply'를 붙이면 실행".PHP_EOL; return; }

        $changes[$mergedFile] = $converter->mergedFileContent($class, $data);
        $this->writeChangesOrRestore($changes);
        echo "done".PHP_EOL;
    }

    /**
     * 통합형 → 분할형: resources/{class}.php 의 섹션을 분할형 파일 끝에 설정 문장으로 붙인다.
     * nav 는 분할형에 자리가 없으므로(트리는 nav 설정) 통합형 파일에 nav 만 남긴다. nav 가 없으면 파일을 지운다.
     */
    public function resource_to_split($flag = 'admin', $class = '', $mode = 'dry')
    {
        if($class === '') { echo "usage: php index.php module/cli resource_to_split {flag} {class} [apply]".PHP_EOL; return; }

        $this->loadBuilderPlatform($flag, false);
        $this->load->library('resource_converter', null, 'resourceConverter');
        $loader = $this->resourceLoader;
        $converter = $this->resourceConverter;

        $mergedFile = APPPATH."config/extra/{$flag}/resources/{$class}.php";
        if(!file_exists($mergedFile)) $this->cliFail("merged file not found: {$mergedFile}");

        $data = $loader->readMergedFile($mergedFile);
        $conflicts = $loader->findSplitConflicts($class, $data);
        if(count($conflicts)) $this->cliFail("split config already has: ".implode(', ', $conflicts));

        // 분할형 파일별로 붙일 문장
        $append = [];
        if(isset($data['route'])) $append['route'][] = [['route_config', $class], $data['route']];
        foreach ($loader->mergedConfigKeys($class, $data) as $key => $value) {
            $kind = explode('_', $key, 2)[0];
            $append[$kind][] = [[$key], $value];
        }
        if(!count($append)) $this->cliFail("nothing to move (only nav/model in {$mergedFile})");

        $changes = [];
        echo "resource_to_split {$flag} {$class}".PHP_EOL;
        foreach ($append as $kind => $statements) {
            $file = APPPATH."config/extra/{$flag}/{$flag}_".Resource_converter::KIND_FILES[$kind].'_config.php';
            $code = file_exists($file) ? file_get_contents($file) : $converter->newSplitFileContent();
            $changes[$file] = $converter->appendStatements($code, $statements, "{$class}: resource_to_split 로 통합형에서 옮김");
            foreach ($statements as [$keys]) echo "  append : ".basename($file).": \$config['".implode("']['", $keys)."']".PHP_EOL;
        }

        $rest = array_intersect_key($data, array_flip(['nav', 'model']));
        if(isset($rest['nav'])) {
            $changes[$mergedFile] = $converter->mergedFileContent($class, $rest);
            echo "  keep   : ".str_replace(APPPATH, 'application/', $mergedFile)." (nav".(isset($rest['model']) ? ', model' : '')." 만 남김)".PHP_EOL;
        }else{
            echo "  delete : ".str_replace(APPPATH, 'application/', $mergedFile).PHP_EOL;
        }

        if($mode !== 'apply') { echo "dry run - 'apply'를 붙이면 실행".PHP_EOL; return; }

        $this->writeChangesOrRestore($changes, isset($rest['nav']) ? [] : [$mergedFile]);
        echo "done".PHP_EOL;
    }

    /**
     * 파일을 쓰고 php -l 로 확인한다. 하나라도 실패하면 전부 원래대로 되돌린다.
     */
    protected function writeChangesOrRestore(array $changes, array $deletes = []): void
    {
        $backup = [];
        foreach (array_merge(array_keys($changes), $deletes) as $file) $backup[$file] = file_exists($file) ? file_get_contents($file) : null;

        $restore = function () use ($backup) {
            foreach ($backup as $file => $content) {
                if($content === null) { if(file_exists($file)) unlink($file); }
                else file_put_contents($file, $content);
            }
        };

        foreach ($changes as $file => $content) {
            if(!is_dir(dirname($file))) mkdir(dirname($file), 0755, true);
            file_put_contents($file, $content);
            exec(escapeshellarg(PHP_BINARY).' -l '.escapeshellarg($file).' 2>&1', $output, $code);
            if($code !== 0) {
                $restore();
                $this->cliFail("syntax check failed for {$file}, all files restored:\n".implode("\n", $output));
            }
        }
        foreach ($deletes as $file) if(file_exists($file)) unlink($file);
    }

    protected function cliFail(string $message): void
    {
        echo "ERROR: {$message}".PHP_EOL;
        exit(1);
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
