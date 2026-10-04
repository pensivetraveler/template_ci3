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
    function fill_config_properties(array $config = [], array $base = []): array
    {
        if(empty($base)) return $config;
        foreach ($base as $key=>$val) {
            if(is_array($val) && !is_list_type($val)) {
                $config[$key] = fill_config_properties($config[$key]??[], $val);
            }else{
                if(!array_key_exists($key, $config)) $config[$key] = $val;
            }
        }
        return $config;
    }
}
