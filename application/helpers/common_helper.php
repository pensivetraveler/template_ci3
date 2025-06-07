<?php
if ( ! function_exists('print_data'))
{
    function print_data($data, $exit = true)
    {
        print_r('<pre>');
        print_r($data);
        print_r('</pre>');
        if($exit) exit;
    }
}

if ( ! function_exists('is_empty'))
{
    function is_empty($data, $key = ''): bool
    {
        if(is_null($data)) return true;
        if(!trim($key)) {
            switch(gettype($data)) {
                case 'boolean' :
                    return !$data;
                case 'object' :
                    $data = get_object_vars($data);
                case 'array' :
                    if(empty($data)) return true;
                    break;
                default :
                    // 0 을 구분하기 위해 strlen 조건 추가
                    if(empty($data) && strlen((string)$data) === 0) return true;
            }
            return false;
        }else{
            if(gettype($data) === 'object' || gettype($data) === 'array') {
                if(gettype($data) === 'object') {
                    if(!property_exists($data, $key)) return true;
                    $data = get_object_vars($data);
                }else{
                    if(!array_key_exists($key, $data)) return true;
                }

                if(gettype($data[$key]) === 'array' || gettype($data[$key]) === 'object') {
                    if(gettype($data[$key]) === 'object') $data[$key] = get_object_vars($data[$key]);
                    return empty($data[$key]);
                }else{
                    return empty($data[$key])&&!strlen($data[$key]);
                }
            }else{
                return true;
            }
        }
    }
}

if ( ! function_exists('unravel_list'))
{
    function unravel_list($list) : array
    {
        $result = [];
        foreach ($list as $item) {
            if(is_array($item)) {
                foreach ($item as $subitem) {
                    $result[] = $subitem;
                }
            }else{
                $result[] = $item;
            }
        }
        return array_values(array_unique($result));
    }
}

if ( ! function_exists('get_yn'))
{
    function get_yn($bool): string
    {
        return $bool?'Y':'N';
    }
}

if ( ! function_exists('reformat_bool_type_list'))
{
    function reformat_bool_type_list($list) : array
    {
        return array_keys(array_filter($list, function ($value) {
            return $value === true || $value === 1;
        }));
    }
}

if ( ! function_exists('str_contains'))
{
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

if ( ! function_exists( 'str_ends_with' ) )
{
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

if ( ! function_exists('is_serialized_string'))
{
    function is_serialized_string( $data ) {
        // 문자열이 아니면 바로 false
        if ( ! is_string( $data ) ) {
            return false;
        }
        $data = trim( $data );

        // serialize 포맷의 최소 길이 (예: b:0;)
        if ( strlen( $data ) < 4 ) {
            return false;
        }

        // 시작 문자 및 마지막 세미콜론/중괄호 검사
        if ( ! preg_match( '/^(?:s|a|O|b|i|d):/', $data ) ) {
            return false;
        }
        $last = substr( $data, -1 );
        if ( $last !== ';' && $last !== '}' ) {
            return false;
        }

        // 실제로 unserialize 해보고 에러 없이 값이 돌아오면 true
        try {
            $unserialized = @unserialize( $data );
            return $unserialized !== false || $data === 'b:0;';
        } catch ( Exception $e ) {
            return false;
        }
    }
}

if ( ! function_exists('get_time_text'))
{
    function get_time_text($datetime) {
        if(strtotime($datetime) + 60 > time()) {
            // 1분 내
            return lang('just now');
        }else {
            $div = strtotime($datetime) - time();
            if(strtotime($datetime) + 60*60 > time()) {
                // 1시간 내
                $div = floor(abs($div)/60);
                return $div.lang('m ago');
            }else if(strtotime($datetime) + 60*60*24 > time()) {
                // 1일 내
                $div = floor(abs($div) / (60 * 60));
                return $div . lang('h ago');
            }else{
                // 수일
                $div = floor(abs($div)/(60*60*24));
                if($div > 1) {
                    return $div.lang('days ago');
                }else{
                    return $div.lang('day ago');
                }
            }
        }
    }
}

if ( ! function_exists('get_starred_id'))
{
    function get_starred_id($id) {
        $len = strlen($id);
        $res = substr($id, 0, 2);
        $res .= substr($id, 2, min($len-2,3));
        if(strlen($id) > 5) {
            $res .= substr($id, 5);
        }
        return $res;
    }
}

if ( ! function_exists('get_starred_password'))
{
    function get_starred_password($password) {
        $len = strlen($password);
        $res = substr($password, 0, 2);
        $res .= substr($password, 2, min($len-2,5));
        if(strlen($password) > 7) {
            $res .= substr($password, 7);
        }
        return $res;
    }
}
