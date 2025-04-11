<?php
function is_admin_active_page($menu, $current_uri = ''): bool
{
    if(isset($menu['subMenu'])){
        $activated = false;
        foreach ($menu['subMenu'] as $submenu) {
            if(is_active_page($submenu['route'], $submenu['params'], $current_uri)) $activated = true;
        }
        return $activated;
    }else{
        return is_active_page($menu['route'], $menu['params'], $current_uri);
    }
}

function get_admin_breadcrumbs($title_list): string
{
    $CI =& get_instance();

    $html = '';
    foreach ($title_list as $title) {
        $title = $CI->lang->line('nav.'.$title);
        $html .= "<li class='breadcrumb-item'><a href='javascript:void(0);'>{$title}</a></li>";
    }
    return $html;
}

function get_icon($icon_class_name, $svg = false, $size = 18, $classname = ''): string
{
	if(!$icon_class_name) return '';
	if($classname) {
		if(is_array($classname)) {
			$classname = implode('.', $classname);
		}else{
			$classname = str_replace(' ', '.', $classname);
		}
		if($classname && substr($classname, 0, 1) === '.') $classname = substr($classname, 1);
	}
	if(!$size) $size = 18;
	return $svg?file_get_contents(BUILDER_ASSET_SVG_PATH.'icons/'.$icon_class_name.'.svg'):convert_selector_to_html("i.$icon_class_name.$classname.ri-{$size}px");
}

function get_icon_by_type($type, $svg = false, $size = 18): string
{
	$classname = get_icon_classname_by_type($type);
	return get_icon($classname, $svg, $size);
}

function get_icon_classname_by_type($type): string
{
	switch (strtolower($type)) {
		case "file" :
			return 'ri-attachment-line';
		case "zipcode" :
			return 'ri-building-line';
		case "text" :
			return 'ri-text';
		case "tel" :
			return 'ri-phone-fill';
		case "textarea" :
			return 'ri-chat-4-line';
		case "date" :
			return 'ri-calendar-line';
		case "time" :
			return 'ri-time-line';
		case "pdf" :
			return 'ri-file-pdf-2-fill';
		default :
			return '';
	}
}
