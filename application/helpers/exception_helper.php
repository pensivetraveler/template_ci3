<?php
if(!function_exists('base_url'))
{
	function base_url($uri = '', $protocol = NULL)
	{
		return BASE_URL.$uri;
	}
}

if ( ! function_exists('doctype'))
{
	/**
	 * Doctype
	 *
	 * Generates a page document type declaration
	 *
	 * Examples of valid options: html5, xhtml-11, xhtml-strict, xhtml-trans,
	 * xhtml-frame, html4-strict, html4-trans, and html4-frame.
	 * All values are saved in the doctypes config file.
	 *
	 * @param	string	type	The doctype to be generated
	 * @return	string
	 */
	function doctype($type = 'xhtml1-strict')
	{
		static $doctypes;

		if ( ! is_array($doctypes))
		{
			if (file_exists(APPPATH.'config/doctypes.php'))
			{
				include(APPPATH.'config/doctypes.php');
			}

			if (file_exists(APPPATH.'config/'.ENVIRONMENT.'/doctypes.php'))
			{
				include(APPPATH.'config/'.ENVIRONMENT.'/doctypes.php');
			}

			if (empty($_doctypes) OR ! is_array($_doctypes))
			{
				$doctypes = array();
				return FALSE;
			}

			$doctypes = $_doctypes;
		}

		return isset($doctypes[$type]) ? $doctypes[$type] : FALSE;
	}
}

if ( ! function_exists('print_data'))
{
	function print_data($data, $exit = false)
	{
		print_r('<pre>');
		print_r($data);
		print_r('</pre>');
		if($exit) exit;
	}
}

if ( ! function_exists('is_ajax'))
{
	function is_ajax(): bool
	{
		return (
			isset($_SERVER['HTTP_X_REQUESTED_WITH'])
			&&
			!empty($_SERVER['HTTP_X_REQUESTED_WITH'])
			&&
			strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'
		);
	}
}

if ( ! function_exists('get_path'))
{
	function get_path(): string
	{
        include APPPATH.'config/extra/custom_routes.php';

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
                if(in_array($arr[0], $folder_list)) {
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

if ( ! function_exists('is_api_call'))
{
	function is_api_call()
	{
		return in_array(get_path(), API_CALL_PATH);
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

if ( ! function_exists('get_error_response'))
{
	function get_error_response($preset, $error): array
	{
		$msg = null;
		if(isset($error['msg'])) {
			if(is_array($error['msg'])) {
				$msg = $error['msg'];
			}else{
				$msg = addslashes($error['msg']);
			}
		}

		if(get_path() === 'api') {
			$response = [
				'errorCode' => $preset['code'],
				'errorMessage' => $preset['msg'],
				'errorBody' => [
					'Type' => addslashes($error['type']),
					'Message' => $msg,
					'Filename' => $error['location']??null,
					'Line Number' => $error['line']??null,
				],
			];
		}else{
			$response = array_merge($preset, [
				'errors' => [[
					'location' => $error['location']??null,
					'param' => null,
					'value' => $error['line']??null,
					'type' => addslashes($error['type']),
					'msg' => $msg,
				]]
			]);
		}

		return $response;
	}
}

if ( ! function_exists('filename_to_classname'))
{
	function filename_to_classname($filename): string
	{
		// 파일명에서 확장자 제거
		$classname = pathinfo($filename, PATHINFO_FILENAME);

		// 클래스 이름은 대문자로 시작해야 하므로 ucfirst 사용
		return ucfirst($classname);
	}
}

if ( ! function_exists('response_error'))
{
	function response_error($error, $code)
	{
		include APPPATH.'language/'.config_item('language').'/status_code_lang.php';

		$status_code = (int)floor($code/10);
		if($status_code === 404 || $status_code === 500) {
			$preset = $status_code === 404?PRESET_API_NOT_EXIST:PRESET_ERR_OCCUR;
		}else{
			$preset = ['code' => $status_code];
		}
		if(isset($lang['status_code'], $lang['status_code'][$code])) $preset['msg'] = $lang['status_code'][$code];
		$preset['data'] = [];

		$response = get_error_response($preset, $error);

		if(array_search('API_NOT_EXIST', $response)) $response[array_search('API_NOT_EXIST', $response)] = API_NOT_EXIST;
		if(array_search('INTERNAL_SERVER_ERROR', $response)) $response[array_search('INTERNAL_SERVER_ERROR', $response)] = INTERNAL_SERVER_ERROR;

		header('Content-Type: application/json');
		set_status_header($status_code);
		echo json_encode($response, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
		exit(4);
	}
}
