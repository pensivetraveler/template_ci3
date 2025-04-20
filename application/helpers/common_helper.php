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
