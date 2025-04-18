<?php
function get_message_time($datetime) {
    if(strtotime($datetime) + 60 > time()) {
        // 1분 내
        return lang('just now');
    }else {
        $div = strtotime($datetime) - time();
        if(strtotime($datetime) + 60*60 > time()) {
            // 1시간 내
            $div = floor(abs($div)/60);
            return $div.lang('m ago');
        }else if(strtotime($datetime) + 60*60*24 > time()) {
            // 1일 내
            $div = floor(abs($div) / (60 * 60));
            return $div . lang('h ago');
        }else{
            // 수일
            $div = floor(abs($div)/(60*60*24));
            if($div > 1) {
                return $div.lang('days ago');
            }else{
                return $div.lang('day ago');
            }
        }
    }
}

function get_starred_id($id) {
    $len = strlen($id);
    $res = substr($id, 0, 2);
    $res .= substr($id, 2, min($len-2,3));
    if(strlen($id) > 5) {
        $res .= substr($id, 5);
    }
    return $res;
}

function get_starred_password($password) {
    $len = strlen($password);
    $res = substr($password, 0, 2);
    $res .= substr($password, 2, min($len-2,5));
    if(strlen($password) > 7) {
        $res .= substr($password, 7);
    }
    return $res;
}

if (!function_exists('generate_uuid_v4')) {
    function generate_uuid_v4()
    {
        // 16바이트(128비트) 난수 생성
        $data = openssl_random_pseudo_bytes(16);

        // UUID version 4 설정 (0100xxxx)
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        // UUID variant 설정 (10xxxxxx)
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        // 16진수 문자열로 포맷
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}