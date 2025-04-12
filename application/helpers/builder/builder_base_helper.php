<?php
if ( ! function_exists('builder_view'))
{
	function builder_view($path, $vars = array(), $return = false)
	{
		$CI = &get_instance();
		if(!file_exists(VIEWPATH.$path.'.php')) {
			$path = preg_replace('/^[^\/]+/', BUILDER_FLAGNAME, $path);
		}
		$CI->load->view($path, $vars, $return);
	}
}

function get_builder_html_attributes($flag): string
{
	$classList = [];
	$template = '';
	switch ($flag) {
		case 'admin' :
			$classList = ['light-style','layout-navbar-fixed','layout-menu-fixed','layout-compact'];
			$template = 'vertical-menu-template-starter';
			break;
		case 'web' :
			$classList = ['light-style','layout-navbar-fixed','layout-wide'];
			$template = 'front-pages';
			break;
	}

	$attrs = [
		'lang' => get_language_code(config_item('language')),
		'class' => implode(' ', $classList),
		'dir' => 'ltr',
		'data-theme' => 'theme-default',
		'data-assets-path' => '/'.BUILDER_ASSET_URI,
		'data-template' => $template,
		'data-style' => 'light',
	];

	return implode(' ', array_map(
		function ($key, $value) {
			return $key . '="' . $value . '"';
		},
		array_keys($attrs),
		$attrs
	));
}

function get_builder_body_attributes($production = false): string
{
	$append = [
//		'oncontextmenu' => 'return true',
//		'onselectstart' => 'return true',
//		'ondragstart' => 'return true',
//		'onkeydown' => 'return true',
	];

	$CI =& get_instance();
	$attrs = array_merge([
		'data-class' => $CI->router->class,
		'data-method' => $CI->router->method,
		'data-onload' => 'false',
	], $production ? $append : []);

	return implode(' ', array_map(
		function ($key, $value) {
			return $key . '="' . $value . '"';
		},
		array_keys($attrs),
		$attrs
	));
}
