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

function get_menu_list_tree($data): string
{
    $html = '';
    foreach($data as $i=>$item):
        $html .= get_menu_item_tree($item, 1, $i+1);
    endforeach;
    return $html;
}

function get_menu_item_tree($item, $depth, $srt = 1)
{
    $html = '<div class="menu-item" data-depth="'.$depth.'" data-srt="'.$srt.'">';
    $html .= '<div class="menu-header">';
    $html .= '<span class="menu-title">';
    if($item['icon'])
        $html .= '<i class="icon-base me-2 '.$item['icon'].'"></i>';
    $html .= $item['title'].'</span>';
    $html .= '</div>'; // menu-header
    $html .= '<div class="menu-container submenu-container">';
    if(!empty($item['subMenu']) && is_array($item['subMenu'])) {
        foreach($item['subMenu'] as $j=>$subItem) {
            $html .= get_menu_item_tree($subItem, $depth+1, $j+1);
        }
    }
    $html .= '</div>'; // submenu-container
    $html .= '</div>'; // menu-item
    return $html;
}

function get_menu_href($href = '', $params = [])
{
    $url = 'javascript:void(0)';
    if($href) {
        $parsed = parse_url($href);
        if(!array_key_exists('host', $parsed)) {
            $url = base_url(
                $href.'?'.http_build_query($params)
            );
        }else{
            $url = $href;
            if(count($params)) $url .= '?'.http_build_query($params);
        }
    }
    return $url;
}