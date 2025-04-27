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
            switch (gettype($data)) {
                case 'object' :
                    if(!property_exists($data, $key)) return true;
                    return empty($data->{$key});
                case 'array' :
                    if(!array_key_exists($key, $data)) return true;
                    return empty($data[$key]);
                default :
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