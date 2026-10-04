<?php
function auth_char_map()
{
    return [
        0 => 'create',
        1 => 'read',
        2 => 'update',
        3 => 'delete',
        4 => 'export',
        5 => 'import',
    ];
}

function get_auth_value($mode, $auth, $default = true)
{
    $auth_map = auth_char_map();
    if(!in_array($mode, $auth_map)) return $default;
    $key = array_search($mode, $auth_map);
    return (int)substr($auth, $key, 1);
}

function is_auth_avaliable($mode, $auth, $default = true)
{
    $auth_map = auth_char_map();
    if(!in_array($mode, $auth_map)) return $default;
    $key = array_search($mode, $auth_map);
    return (int)substr($auth, $key,1) > 0;
}

function modify_auth_char($mode, $auth, $value)
{
    $auth_map = auth_char_map();
    if(!in_array($mode, $auth_map)) return $auth;
    $key = array_search($mode, $auth_map);
    $value = (string)$value;

    if($key === 0) {
        $prefix = '';
        $subfix = substr($auth, $key+1);
    }else if($key === strlen($auth) - 1) {
        $prefix = substr($auth, 0, $key);
        $subfix = '';
    }else {
        $prefix = substr($auth, 0, $key);
        $subfix = substr($auth, $key+1);
    }

    return $prefix.$value.$subfix;
}
