<?php
if ( ! function_exists('validation_errors_array'))
{
    /**
     * Validation Error Array
     *
     * @return	array
     */
    function validation_errors_array()
    {
        if (FALSE === ($OBJ =& _get_validation_object()))
        {
            return '';
        }

        return $OBJ->error_array();
    }
}

if ( ! function_exists('form_custom'))
{
    /**
     * Custom Input Field
     *
     * @param	mixed
     * @param	string
     * @return	string
     */
    function form_custom($data, $value): string
    {
        return '';
    }
}

if ( ! function_exists('form_options_by_field'))
{
    /**
     * form_options_by_field
     * @param string $field
     * @return array
     */
    function form_options_by_field(string $field = 'default'): array
    {
		$CI =& get_instance();
		return $CI->config->get(implode('.', ['options', $field]), [], false);
    }
}


if ( ! function_exists('get_field_option_data'))
{
	/**
	 * get_field_option_data
	 * @param string $field
	 * @param $key
	 * @param $value
	 * @return mixed
	 */
	function get_field_option_data(string $field = 'default', $key = null, $value = null): mixed
	{
		$CI =& get_instance();
		$options = $CI->config->get(implode('.', ['options', $field]), [], false);

		if($key) {
			return array_key_exists($key, $options)?$options[$key]:null;
		}

		if($value) {
			return array_search($value, $options);
		}

		return null;
	}
}

if ( ! function_exists('get_group_field_name'))
{
    function get_group_field_name($group_attr, $group_name, $field_name, $index = 0): string
    {
        $list = [];
        if ($group_attr['envelope_name']) {
            $list[] = $group_name;
            if ($group_attr['group_repeater']) $list[] = $index;
            $list[] = $field_name;
        } else {
            $list[] = $field_name;
            if ($group_attr['group_repeater']) $list[] = $index;
        }
        return array_to_brackets($list);
    }
}

if ( ! function_exists('replace_field_name_index'))
{
    function replace_field_name_index($original, $group_attr, $group_name, $field_name, $index = 0): string
    {
        if ($group_attr['envelope_name']) {
            $regex = "/({$group_name}\\[)\\d+(\\]\\[{$field_name}\\])/";
            return preg_replace($regex, "$1$index$2", $original);
        } else {
            $regex = "/({$group_name}\\[)\\d+(\\])/";
            return preg_replace($regex, "$1$index$2", $original);
        }
    }
}

if ( ! function_exists('get_group_field_id'))
{
    function get_group_field_id($group_attr, $group_name, $field_name, $index = 0): string
    {
		$CI =& get_instance();

        $list = [];
        if ($group_attr['envelope_name']) {
            $list[] = $group_name;
            if ($group_attr['group_repeater']) $list[] = $index;
            $list[] = $field_name;
        } else {
            $list[] = $field_name;
            if ($group_attr['group_repeater']) $list[] = $index;
        }

		$prefix = $CI->sideForm?$CI->config->item('form_side_prefix'):$CI->config->item('form_page_prefix');
        return $prefix.array_to_hyphens($list);
    }
}

if ( ! function_exists('replace_field_id_index'))
{
    function replace_field_id_index($original, $group_attr, $group_name, $field_name, $index = 0): string
    {
        if ($group_attr['envelope_name']) {
            $regex = "/(-{$group_name}-)\\d+(-{$field_name})/";
            return preg_replace($regex, "$1$index$2", $original);
        } else {
            $regex = "/(-{$field_name}-)\\d+/";
            return preg_replace($regex, "$1$index", $original);
        }
    }
}

if ( ! function_exists('custom_password_verify'))
{
	function custom_password_verify($password, $hash, $decryption = false)
	{
		if(!$decryption) return password_verify($password, $hash);

		$CI =& get_instance();
		return $CI->encryption->decrypt($password) === $hash;
	}

}

