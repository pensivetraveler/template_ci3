<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class MY_Config extends CI_Config
{
    public function __construct()
    {
        parent::__construct();
	}

    public function get($item, $default = null, $triggerError = true)
    {
		$CI =& get_instance();

		if(empty($this->item($item))) $CI->logger("MY_Config-get : $item does not exist.", E_USER_ERROR, $triggerError);

        if($this->item($item) === null) {
            return $default;
        }else{
            if(empty($this->item($item))) {
                return $default === null ? $this->item($item) : $default;
            }else{
                return $this->item($item);
            }
        }
    }

    // . 으로 구성된 Config 처리
    public function item($item, $index = '')
    {
        if(!str_contains($item, '.') || str_ends_with($item, '.')){
            return parent::item($item, $index);
        }else{
            $config = $this->config;
            foreach (explode('.', $item) as $key) {
                if (isset($config[$key])) {
                    $config = $config[$key];
                } else {
                    // 해당 키가 존재하지 않으면 기본값을 반환
                    $config = null;
                    break;
                }
            }
            return $config;
        }
    }
}
