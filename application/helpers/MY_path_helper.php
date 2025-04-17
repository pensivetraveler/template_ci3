<?php
if ( ! function_exists('get_filepath_from_link'))
{
    // path 로부터 link return
    function get_filepath_from_link($path): string
    {
        return base_url('/'.str_replace(FCPATH, '', $path));
    }
}

if ( ! function_exists('get_path'))
{
    function get_path(): string
    {
        $ci =& get_instance();
        $route = $ci->router->routes;
        $default_platform = array_key_exists('default_platform', $route)?$route['default_platform']:'';
        $folder_list = $route['except_folders'];
        $api_folders = $route['api_folders'];

        if(CLI_YN) {
            return '';
        }else{
            $whole_uri = _HTTP.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
            $path_info = explode('/', str_replace(function_exists('base_url')?base_url():BASE_URL, '', $whole_uri));
            $arr = array_values(array_filter($path_info));
            if(count($arr) > 0){
                if(in_array($arr[0], $folder_list) || $arr[0] === $default_platform) {
                    if(count($arr) > 1 && in_array($arr[1], $api_folders)){
                        return $arr[1];
                    }else{
                        return $arr[0];
                    }
                }else{
                    return $default_platform;
                }
            }else{
                return $default_platform;
            }
        }
    }
}

if ( ! function_exists('get_error_views_path'))
{
    function get_error_views_path(): string
    {
        if(is_dir(VIEWPATH.get_path().DIRECTORY_SEPARATOR.'errors'.DIRECTORY_SEPARATOR.'html')) {
            return VIEWPATH.get_path().DIRECTORY_SEPARATOR.'errors'.DIRECTORY_SEPARATOR;
        }else{
            if(defined('BUILDER_FLAGNAME')) {
                return VIEWPATH.BUILDER_FLAGNAME.DIRECTORY_SEPARATOR.'errors'.DIRECTORY_SEPARATOR;
            }else{
                return '';
            }
        }
    }
}
