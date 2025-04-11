<?php
// --------------------------------------------------------------------

if ( ! function_exists('format_phone'))
{
    function format_phone($phone)
    {
        $phone = preg_replace("/[^0-9]/", "", $phone);
        $length = strlen($phone);
        switch($length){
            case 11 :
                return preg_replace("/([0-9]{3})([0-9]{4})([0-9]{4})/", "$1-$2-$3", $phone);
            case 10:
                return preg_replace("/([0-9]{3})([0-9]{3})([0-9]{4})/", "$1-$2-$3", $phone);
            default :
                return $phone;
        }
    }
}

// --------------------------------------------------------------------

if ( ! function_exists('format_date'))
{
    function format_date($birth)
    {
        return date('Y-m-d', strtotime($birth));
    }
}

if ( ! function_exists('is_list_type'))
{
	function is_list_type($data): bool
	{
		if(!empty($data) && count($data) > 0) {
			$keys = array_keys($data);
			return array_reduce($keys, function($result, $key) {
				return $result && is_numeric($key);
			}, true);
		}else{
			return true;
		}
	}
}

if ( ! function_exists('array_to_brackets'))
{
	function array_to_brackets($array): string
	{
		$result = array_shift($array);
		foreach ($array as $value) {
			$result .= "[$value]";
		}
		return $result;
	}
}

if ( ! function_exists('array_to_hyphens'))
{
	function array_to_hyphens($array): string
	{
		return implode('-', $array);
	}
}
