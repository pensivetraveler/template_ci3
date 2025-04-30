<?php

class MY_Hooks
{
	protected array $config;

	function __construct()
	{
		$this->config =& get_config();
	}

	public function getConfig()
	{
		print_r('<pre>');
		print_r($this->config);
		exit;
	}

	public function loadEnv()
	{
		$dotenv = new Symfony\Component\Dotenv\Dotenv();
		$dotenv->usePutenv();
		if(getenv('CI_ENV') && file_exists(FCPATH.'.env.'.getenv('CI_ENV')))
			$dotenv->load(FCPATH.'.env.'.getenv('CI_ENV'));

		define("APP_NAME", getenv('APP_NAME'));
		define("APP_NAME_KR", getenv('APP_NAME_KR'));
	}

	public function systemOfInspection()
	{
		if(getenv('SYSTEM_INSPECTION') === 'true') {
			echo '시스템 점검 중입니다.';
			exit;
		}
	}

	public function checkPermission()
	{
		$CI =& get_instance();

		//Check if lib is loaded or not, and if not loaded, then load it here
		if(!isset($CI->session)){
			$CI->load->library('session');
		}
		if(!isset($CI->PHPtoJS)){
			$CI->load->library('PHPtoJS', $CI->config->item('phptojs.namespace')?['namespace' => $CI->config->item('phptojs.namespace')]:[]);
		}

		// autologin
		if($CI->config->item('autologin_config_loaded')){
			$aulCookie = $CI->config->item('autologin_cookie_name');
			$aulTable = $CI->config->item('autologin_table');
			$aulColumns = $CI->config->item('autologin_columns');
			$aulLifetime = $CI->config->item('autologin_cookie_lifetime') ?: 86400 * 30;
			$userTable = $CI->config->item('user_table');
			$userLimit = $CI->config->item('user_limit_conditions');

			if (get_cookie($aulCookie) && ! $CI->session->userdata(USER_ID_COLUMN_NAME)) {
				$autoLogin = $CI->db
					->order_by($aulColumns['date'], 'desc')
					->where([$aulColumns['key'] => get_cookie($aulCookie)])->get($aulTable)->result_array();
				if(count($autoLogin)) {
					$autoLogin = $autoLogin[0];

					if ( ! element(USER_ID_COLUMN_NAME, $autoLogin)) {
						delete_cookie($aulCookie);
					}else{
						$valid = true;
						if ( ! element($aulColumns['date'], $autoLogin) OR (strtotime(element($aulColumns['date'], $autoLogin)) < ctimestamp() - $aulLifetime)) {
							$valid = false;
						} elseif ($CI->input->ip_address() !== element($aulColumns['ip'], $autoLogin)) {
							$valid = false;
						} else {
							$userData = $CI->db
								->select([USER_ID_COLUMN_NAME, ...array_keys($userLimit)])
								->where([USER_ID_COLUMN_NAME => element(USER_ID_COLUMN_NAME, $autoLogin)])
								->get($userTable)->result_array();

							if(count($userData)) {
								$userData = $userData[0];
								if(element(USER_ID_COLUMN_NAME, $userData)) {
									foreach ($userData as $key => $val) {
										if($key === USER_ID_COLUMN_NAME) continue;
										if($val === $userLimit[$key]) $valid = false;
									}
								}
							}
						}

						if(!$valid) {
							$CI->db->delete($aulTable, [$aulColumns['id'] => element($aulColumns['id'], $autoLogin)]);
							delete_cookie($aulCookie);
						}else{
							$CI->session->set_userdata(USER_ID_COLUMN_NAME, element(USER_ID_COLUMN_NAME, $autoLogin));
						}
					}
				}
			}
		}
	}

	public function setPHPVars()
	{
		$CI =& get_instance();
		$token_prefix = $CI->config->item('token_prefix')?$CI->config->item('token_prefix').' ':'';

		$data = [
			'BASE_URI' => base_url(),
			'CURRENT_URI' => base_url().get_path().'/'.$CI->router->class,
			'HOOK_PHPTOJS_VAR_ISLOGIN' => $CI->session->userdata('logged_in'),
			'HOOK_PHPTOJS_VAR_TOKEN' => $token_prefix.$CI->session->userdata('token'),
			'HOOK_PHPTOJS_VAR_DIALOG' => $CI->session->flashdata('dialog'),
		];

		if(property_exists($CI, 'jsVars')) $data = array_merge($data, $CI->jsVars);
		if(property_exists($CI, 'lang')) $data = array_merge($data, [
			'LOCALE' => $CI->lang->language
		]);

		if(!array_key_exists('ERRORS', $data)) $data['ERRORS'] = [];

		$CI->phptojs->put($data);

		$CI->config->set_item('life_cycle', 'post_controller');
	}
	public function setFormValidation()
	{
		$CI =& get_instance();

		foreach ($CI->config->item('regexp') as $name => $regexp)
			if(!method_exists($CI->form_validation, $name))
				$CI->form_validation->addMethod($name, $regexp);
	}

	public function setUploadMaxSize()
	{
		$uploadMaxSize = ini_get('upload_max_filesize');
		$postMaxSize = ini_get('post_max_size');

		define('POST_MAX_SIZE', convert_to_bytes($postMaxSize));
		if(convert_to_bytes($uploadMaxSize) > convert_to_bytes($postMaxSize)) {
			define('UPLOAD_MAX_FILESIZE', convert_to_bytes($postMaxSize));
			define('UPLOAD_MAX_FILESIZE_TXT', $postMaxSize);
		}else{
			define('UPLOAD_MAX_FILESIZE', convert_to_bytes($uploadMaxSize));
			define('UPLOAD_MAX_FILESIZE_TXT', $uploadMaxSize);
		}
	}

	public function setHeaderSecure()
	{
		// Get CI instance
		$CI =& get_instance();

		// Only allow HTTPS cookies (no JS)
		$CI->config->set_item('cookie_secure', TRUE);
		$CI->config->set_item('cookie_httponly', TRUE);

		// Set headers
		$CI->output->set_header("Strict-Transport-Security: max-age=31536000")
			->set_header("X-Content-Type-Options: nosniff")
			->set_header("Referrer-Policy: strict-origin")
			->set_header("X-Frame-Options: DENY")
			->set_header("X-XSS-Protection: 1; mode=block");
	}

	public function doYield()
	{
		global $OUT;
		$CI =& get_instance();
		$output = $CI->output->get_output();
		$CI->yield = isset($CI->yield) ? $CI->yield : TRUE;
		$CI->layout = isset($CI->layout) ? $CI->layout : 'default';
		if ($CI->yield === TRUE) {
			if (!preg_match('/(.+).php$/', $CI->layout)) {
				$CI->layout .= '.php';
			}
			$requested = APPPATH . 'views/layouts/' . $CI->layout;
			$layout = $CI->load->file($requested, true);
			$view = str_replace("{yield}", $output, $layout);
		} else {
			$view = $output;
		}
		$OUT->_display($view);
	}

	function compress()
	{
		ini_set("pcre.recursion_limit", "16777");
		$CI =& get_instance();
		$buffer = $CI->output->get_output();

		$re = '%# Collapse whitespace everywhere but in blacklisted elements.
        (?>             # Match all whitespans other than single space.
          [^\S ]\s*     # Either one [\t\r\n\f\v] and zero or more ws,
        | \s{2,}        # or two or more consecutive-any-whitespace.
        ) # Note: The remaining regex consumes no text at all...
        (?=             # Ensure we are not in a blacklist tag.
          [^<]*+        # Either zero or more non-"<" {normal*}
          (?:           # Begin {(special normal*)*} construct
            <           # or a < starting a non-blacklist tag.
            (?!/?(?:textarea|pre|script)\b)
            [^<]*+      # more non-"<" {normal*}
          )*+           # Finish "unrolling-the-loop"
          (?:           # Begin alternation group.
            <           # Either a blacklist start tag.
            (?>textarea|pre|script)\b
          | \z          # or end of file.
          )             # End alternation group.
        )  # If we made it here, we are not in a blacklist tag.
        %Six';

		$new_buffer = preg_replace($re, " ", $buffer);

		// We are going to check if processing has working
		if ($new_buffer === null)
		{
			$new_buffer = $buffer;
		}

		$CI->output->set_output($new_buffer);
		$CI->output->_display();
	}
}
