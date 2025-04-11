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
		$routes = $ci->router->routes;
		$default_platform = array_key_exists('default_platform', $routes)?$routes['default_platform']:'';
		$folder_list = $routes['except_folders'];

		if(CLI_YN) {
			return '';
		}else{
			$whole_uri = _HTTP.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
			$path_info = explode('/', str_replace(function_exists('base_url')?base_url():BASE_URL, '', $whole_uri));
			$arr = array_values(array_filter($path_info));
			return count($arr) > 0 ? in_array($arr[0], $folder_list)?$arr[0]:$default_platform : '';
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
