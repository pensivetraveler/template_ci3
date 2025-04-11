<?php
function get_breadcrumbs($title_list)
{
    $html = '';
    foreach ($title_list as $title) {
        $html .= "<li><span>{$title}</span></li>";
    }
    return $html;
}