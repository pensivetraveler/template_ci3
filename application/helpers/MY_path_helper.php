<?php
if ( ! function_exists('get_filepath_from_link'))
{
    // path 로부터 link return (S3에 저장한 파일은 s3://버킷/키)
    function get_filepath_from_link($path): string
    {
        if (strpos((string)$path, 's3://') === 0) {
            $ci =& get_instance();
            $ci->load->library('storage');
            return $ci->storage->url($ci->storage->keyFromUri($path));
        }
        return base_url('/'.str_replace(FCPATH, '', $path));
    }
}

if ( ! function_exists('get_file_key_from_path'))
{
    /**
     * 업로드 파일의 full_path(로컬 절대 경로 또는 s3://)를 저장소 키(예: public/uploads/articles/2026/a.png)로.
     * tbl_file.file_link 에는 이 키를 저장한다 (도메인·포트·하위 경로가 바뀌어도 깨지지 않도록). 주소는 읽을 때 get_file_url()
     */
    function get_file_key_from_path($full_path): string
    {
        $ci =& get_instance();
        $ci->load->library('storage');
        return $ci->storage->keyFromUri($full_path);
    }
}

if ( ! function_exists('get_file_url'))
{
    /**
     * tbl_file.file_link 값을 화면에서 쓸 주소로. 저장소 키면 지금 서버 기준 주소(로컬 base_url, S3 CDN),
     * 예전에 저장한 전체 주소(http(s)://, //)는 그대로 돌려준다
     */
    function get_file_url($link): string
    {
        $link = (string)$link;
        if ($link === '' || preg_match('#^(https?:)?//#i', $link)) return $link;

        $ci =& get_instance();
        $ci->load->library('storage');
        try {
            return $ci->storage->url($link);
        } catch (InvalidArgumentException $e) {
            log_message('error', __FUNCTION__.' : '.$e->getMessage());
            return '';
        }
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
                include APPPATH.'config'.DIRECTORY_SEPARATOR.'extra'.DIRECTORY_SEPARATOR.'builder'.DIRECTORY_SEPARATOR.'builder_base_constants.php';
                return VIEWPATH.BUILDER_FLAGNAME.DIRECTORY_SEPARATOR.'errors'.DIRECTORY_SEPARATOR;
            }
        }
    }
}

if( ! function_exists('get_safe_path'))
{
    function get_safe_path($path, $base_path = FCPATH, $must_be_directory = false): string
    {
        $base_real_path = realpath($base_path);

        if (!$base_real_path) return false;

        if ($must_be_directory) {
            $real_path = realpath($path);
        } else {
            $real_path = realpath($path);
        }

        if (!$real_path) {
            return false;
        }

        /**
         * basePath 밖으로 나가는지 확인
         */
        if (strpos($real_path, $base_real_path) !== 0) {
            return false;
        }

        return $real_path;
    }
}
