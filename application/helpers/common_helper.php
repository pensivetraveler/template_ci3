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
					return is_empty($data->{$key});
				case 'array' :
					if(!array_key_exists($key, $data)) return true;
					return is_empty($data[$key]);
				default :
					return true;
			}
		}
	}
}
