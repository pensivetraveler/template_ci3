<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class MY_Exceptions extends CI_Exceptions
{
    protected object $CI;
    protected object $config;
    protected bool $postController;

    public function __construct()
    {
        parent::__construct();
        // Helper 파일을 직접 포함
        include APPPATH . 'helpers/exception_helper.php';

        if(empty($this->config)) $this->config = new stdClass();
        $this->config->config =& get_config();
        $this->postController = config_item('life_cycle') && config_item('life_cycle') === 'post_controller';

        if(!defined('BASE_URL')) define('BASE_URL', $this->config->config['base_url']);
    }

    function show_404($page = '', $log_error = TRUE)
    {
        if(is_ajax() || is_api_call()){
            response_error([
                'type' => 'API_NOT_EXIST',
                'msg' => 'API is not registered.',
                'location' => $_SERVER['REQUEST_URI'],
            ], API_NOT_EXIST);
        }else{
            parent::show_404($page);
        }
    }

    public function show_error($heading, $message, $template = 'error_general', $status_code = 500)
    {
        if(is_cli()) return parent::show_error($heading, $message, $template, $status_code);
        if(is_ajax() || is_api_call()) {
            response_error([
                'type' => $heading,
                'msg' => $message,
            ], $status_code*10);
        }else{
            if(config_item('life_cycle') || strlen(get_path()) > 0) {
                $this->config->config['error_occurs'] = true;
                $this->config->config['error_views_path'] = get_error_views_path();
            }
            return parent::show_error($heading, $message, $template, $status_code);
        }
    }

    public function show_exception($exception)
    {
        if(is_ajax() || is_api_call()) {
            response_error([
                'type' => get_class($exception),
                'msg' => $exception->getMessage(),
                'location' => str_replace(FCPATH, '', $exception->getFile()),
                'line' => $exception->getLine(),
            ], INTERNAL_SERVER_ERROR);
        }else{
            if(config_item('life_cycle')) {
                $this->CI =& get_instance();
                $this->CI->config->set_item('error_views_path', get_error_views_path());
            }
            parent::show_exception($exception);
        }
    }

    public function show_php_error($severity, $message, $filepath, $line)
    {
        if(is_ajax() || is_api_call()) {
            $severity = $this->levels[$severity] ?? $severity;
            response_error([
                'type' => 'PHP Error : '.$severity,
                'msg' => $message,
                'location' => str_replace(FCPATH, '', $filepath),
                'line' => $line,
            ], INTERNAL_SERVER_ERROR);
        }else{
            if(config_item('life_cycle')) {
                $this->CI =& get_instance();
                $this->CI->config->set_item('error_views_path', get_error_views_path());
            }
            parent::show_php_error($severity, $message, $filepath, $line);
        }
    }
}