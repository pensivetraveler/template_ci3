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

        if(is_null($this->item($item))) $CI->logger("MY_Config-get : $item does not exist.", E_USER_ERROR, $triggerError);

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

        if ( ! function_exists('str_contains')) {
            /*
             * str_contains() 함수의 polyfill 예제
             * 출처: https://core.trac.wordpress.org/browser/trunk/src/wp-includes/compat.php#L423
            */
            function str_contains($haystack, $needle) {
                if ('' === $needle) {
                    return true;
                }

                return false !== strpos($haystack, $needle);
            }
        }

        if ( ! function_exists( 'str_ends_with' ) ) {
            /**
             * PHP 8.0에서 추가된 `str_ends_with()` 함수의 폴리필.
             *
             * 대소문자를 구분하여 주어진 문자열(haystack)이
             * 특정 부분 문자열(needle)로 끝나는지 확인합니다.
             *

             * @param string $haystack 검색할 문자열.
             * @param string $needle   `$haystack`에서 검색할 부분 문자열.
             * @return bool `$haystack`가 `$needle`로 시작하면 true를, 그렇지 않으면 false를 반환.
             */
            function str_ends_with( $haystack, $needle ) {
                if ( '' === $haystack ) {
                    return '' === $needle;
                }

                $len = strlen( $needle );

                return mb_substr( $haystack, -$len, $len ) === $needle;
            }
        }

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
