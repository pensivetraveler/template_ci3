<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$route['default_platform'] = 'web';
$route['default_controller'] = 'common';
$route['except_folders'] = ['app', 'adm', 'admin', 'module', 'web', 'api'];

// `default_platform`을 `except_folders`에서 안전하게 제거
if (($key = array_search($route['default_platform'], $route['except_folders'])) !== false) {
	unset($route['except_folders'][$key]);
}

$route['default_controller_filename'] = preg_replace('/' . preg_quote(substr($route['default_controller'],0,1), '/') . '/', strtoupper(substr($route['default_controller'],0,1)), $route['default_controller'], 1).'.php';
foreach ($route['except_folders'] as $name) {
	if(!file_exists(APPPATH.'controllers'.DIRECTORY_SEPARATOR.$name.DIRECTORY_SEPARATOR.$route['default_controller_filename'])){
		$route[$name] = function() {
			show_404();
		};
	}else{
        if(!in_array($name, $route['api_folders'])){
            $route[$name] = $name.DIRECTORY_SEPARATOR.$route['default_controller'];

            foreach ($route['api_folders'] as $api) {
                // 컨트롤러/메서드/파라미터 (숫자) 지원
                $route["$name/$api/(:any)/(:any)/(:num)"] = "api/$1/$2/$3";
                $route["$name/$api/(:any)/(:num)"] = "api/$1/index/$2";
                // 컨트롤러/메서드 구조 지원
                $route["$name/$api/(:any)/(:any)"] = "api/$1/$2";
                $route["$name/$api/(:any)"] = "api/$1";
            }

            // 컨트롤러/메서드/파라미터 (숫자) 지원
            $route["$name/(:any)/(:any)/(:num)"] = "$name/$1/$2/$3";
            $route["$name/(:any)/(:num)"] = "$name/$1/index/$2";
            // 컨트롤러/메서드 구조 지원
            $route["$name/(:any)/(:any)"] = "$name/$1/$2";
            $route["$name/(:any)"] = "$name/$1";
        }else{
            $route[$name] = $name.DIRECTORY_SEPARATOR.$route['default_controller'];
            // 컨트롤러/메서드/파라미터 (숫자) 지원
            $route["$name/(:any)/(:any)/(:num)"] = "$name/$1/$2/$3";
            $route["$name/(:any)/(:num)"] = "$name/$1/index/$2";
            // 컨트롤러/메서드 구조 지원
            $route["$name/(:any)/(:any)"] = "$name/$1/$2";
            $route["$name/(:any)"] = "$name/$1";
        }
	}
}

/*
|--------------------------------------------------------------------------
| WEB
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| API
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| ADMIN
|--------------------------------------------------------------------------
*/
$route['admin/api/(:any)/(:any)'] = 'api/$1/$2';

/*
|--------------------------------------------------------------------------
| APP
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| MODULE
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| Default Platform
|--------------------------------------------------------------------------
 */
if($route['default_platform']) {
	$except_routes = join('|', array_merge($route['except_folders']));
	// 숫자와 매칭되는 웹 경로
	$route["(?!{$except_routes})([^/]+)/(:num)"] = 'web/$1/index/$2';
	$route["(?!{$except_routes})([^/]+)/(:any)/(:any)"] = 'web/$1/$2/$3';
	// 포괄적인 웹 경로 (마지막에 선언)
	$route["(?!{$except_routes}).*"] = 'web/$0';
	$route['default_controller'] = $route['default_platform'].DIRECTORY_SEPARATOR.$route['default_controller'];
}
