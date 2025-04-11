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