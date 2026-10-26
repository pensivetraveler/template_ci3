<?php
if (!function_exists('get_route_method_base_config')) {
    function get_route_method_base_config($method_config): array
    {
        $CI =& get_instance();

        $type = $method_config['type'] ?? '';
        $subtype = $method_config['subtype'] ?? 'base';

        if ($type === 'list' && $subtype === 'datatable') {
            return $CI->config->item('route_list_datatable_config');
        }

        if ($type === 'list') {
            return $CI->config->item('route_list_base_config');
        }

        if ($type === 'view') {
            return $CI->config->item('route_view_base_config');
        }

        if ($type === 'form' && $subtype === 'auth') {
            return $CI->config->item('route_form_auth_config');
        }

        if ($type === 'form') {
            return $CI->config->item('route_form_base_config');
        }

        if ($type === 'excel') {
            return $CI->config->item('route_excel_base_config');
        }

        return $CI->config->item('route_method_base_config');
    }
}

if (!function_exists('normalize_route_config')) {
    function normalize_route_config($route_config): array
    {
        $CI =& get_instance();

        $route_base_config = $CI->config->item('route_base_config');

        /**
         * 1. route-level 기본값 병합
         */
        $normalized = array_replace_recursive(
            $route_base_config,
            $route_config
        );

        /**
         * 2. methods가 없으면 그대로 반환
         */
        if (empty($normalized['methods']) || !is_array($normalized['methods'])) {
            $normalized['methods'] = [];
            return $normalized;
        }

        /**
         * 3. method-level 기본값 병합
         */
        foreach ($normalized['methods'] as $method_name => $method_config) {
            $method_base_config = get_route_method_base_config($method_config);

            $normalized['methods'][$method_name] = array_replace_recursive(
                $method_base_config,
                $method_config
            );
        }

        return $normalized;
    }
}

if (!function_exists('fill_config_properties')) {
    /**
     * 설정값을 기본값으로 채운다. 연관배열 기본값에 대해
     *  - []    : 기본값을 그대로 씀
     *  - false : 묶음 전체 끄기 — 하위 bool 값을 모두 false 로 채운 배열 (bool 이 아닌 값이 섞인 묶음은 기본값 + 로그)
     *  - 일부  : ['excel' => false] 처럼 적은 키만 바꿈
     */
    function fill_config_properties(array $config = [], array $base = []): array
    {
        if(empty($base)) return $config;
        foreach ($base as $key=>$val) {
            if(is_array($val) && !is_list_type($val)) {
                if(array_key_exists($key, $config) && $config[$key] === false) {
                    $off = config_bool_group_off($val);
                    if($off === null) {
                        log_message('error', __FUNCTION__." : '{$key}' has non-boolean defaults and cannot be turned off with false, defaults used.");
                        $config[$key] = fill_config_properties([], $val);
                    }else{
                        $config[$key] = $off;
                    }
                    continue;
                }
                // 기본값은 연관배열인데 설정값이 그 밖의 스칼라면 TypeError 대신 설정값을 그대로 두고 로그
                if(isset($config[$key]) && !is_array($config[$key])) {
                    log_message('error', __FUNCTION__." : '{$key}' should be an array, ".gettype($config[$key])." given.");
                    continue;
                }
                $config[$key] = fill_config_properties($config[$key]??[], $val);
            }else{
                if(!array_key_exists($key, $config)) $config[$key] = $val;
            }
        }
        return $config;
    }
}

if(!function_exists('config_bool_group_off')) {
    /**
     * 기본값 묶음의 bool 값을 모두 false 로 (하위 연관배열도 같은 규칙). bool 이 아닌 값이 있으면 null
     */
    function config_bool_group_off(array $base): ?array
    {
        $result = [];
        foreach ($base as $key => $val) {
            if(is_bool($val)) {
                $result[$key] = false;
            }elseif(is_array($val) && !is_list_type($val) && count($val)) {
                $sub = config_bool_group_off($val);
                if($sub === null) return null;
                $result[$key] = $sub;
            }else{
                return null;
            }
        }
        return $result;
    }
}
