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
