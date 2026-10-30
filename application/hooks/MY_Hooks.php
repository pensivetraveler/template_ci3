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
        if(ENVIRONMENT && file_exists(FCPATH.'.env.'.ENVIRONMENT))
            $dotenv->load(FCPATH.'.env.'.ENVIRONMENT);

        define("APP_NAME", getenv('APP_NAME'));
        define("APP_NAME_KR", getenv('APP_NAME_KR'));
        define("APP_GIT", getenv('APP_GIT'));
        define("COMP_NAME", getenv('COMP_NAME'));
        define("COMP_NAME_KR", getenv('COMP_NAME_KR'));
        define("COMP_URL", getenv('COMP_URL'));
        define("PASSWORD_DECRYPTABLE", filter_var(getenv('PASSWORD_DECRYPTABLE'), FILTER_VALIDATE_BOOLEAN));
        define("SYSTEM_INSPECTION", filter_var(getenv('SYSTEM_INSPECTION'), FILTER_VALIDATE_BOOLEAN));

        // encryption key 변경
        $key = getenv('ENCRYPTION_KEY');
        if ($key === false || $key === '') {
            show_error('ENCRYPTION_KEY is not set in .env');
        }
        if (strpos($key, 'hex:') === 0) {
            $key = hex2bin(substr($key, 4));
        }
        $this->config['encryption_key'] = $key;

        // 세션 설정: config.php 는 .env 를 읽기 전에 평가되므로 여기서 덮어쓴다 (세션 라이브러리는 이후에 로드됨)
        $sessExpiration = getenv('SESS_EXPIRATION');
        if ($sessExpiration !== false && ctype_digit($sessExpiration) && (int)$sessExpiration > 0) {
            $this->config['sess_expiration'] = (int)$sessExpiration;
        }
        // 저장 폴더는 files 드라이버일 때만 (database 드라이버의 sess_save_path 는 테이블 이름)
        $sessSavePath = getenv('SESS_SAVE_PATH');
        if ($sessSavePath !== false && $sessSavePath !== '' && ($this->config['sess_driver'] ?? 'files') === 'files') {
            $this->config['sess_save_path'] = $sessSavePath;
        }
    }

    public function systemOfInspection()
    {
        if(SYSTEM_INSPECTION) {
            $allowedIps = json_decode((string)getenv('ALLOWED_IPS'), true) ?: [];
            // 로드밸런서 뒤에서는 REMOTE_ADDR가 로드밸런서 주소이므로 CI ip_address() (proxy_ips + X-Forwarded-For)
            $clientIp = get_instance()->input->ip_address();
            if(!in_array($clientIp, $allowedIps, true)) {
                builder_misc_view('');
            }
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

        // 자동 로그인은 MY_Controller::loginWithAutologinCookie (MY_Builder_WEB 생성자에서 호출)로 옮김.
        // hook 에서는 토큰을 발급할 수 없어 세션에 user_id 만 들어가고, 화면이 로그인 안 됨으로 보고 세션·쿠키를 지웠다.
    }

    public function setPHPVars()
    {
        $CI =& get_instance();
        $token_prefix = $CI->config->item('token_prefix')?$CI->config->item('token_prefix').' ':'';

        $data = [
            'HOST_URI' => base_url(),
            'BASE_URI' => base_url().get_path(),
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
            // 같은 사이트만 iframe 허용 (목록 셀 onclick popup 이 같은 사이트 화면을 띄움). 다른 사이트의 iframe(클릭재킹)은 막음
            ->set_header("X-Frame-Options: SAMEORIGIN")
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
