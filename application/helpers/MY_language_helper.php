<?php
function choose_eulreul($word)
{
    // 한글의 마지막 글자를 얻습니다.
    $last_char = mb_substr($word, -1);
    // 유니코드 상에서 한글의 시작과 끝을 정의합니다.
    $hangul_start = 0xAC00;
    $hangul_end = 0xD7A3;
    // 마지막 글자의 유니코드 값을 얻습니다.
    $last_char_code = uniord($last_char);

    if ($last_char_code >= $hangul_start && $last_char_code <= $hangul_end) {
        // 한글의 마지막 글자가 받침이 있는지 확인합니다.
        $index = $last_char_code - $hangul_start;
        $jongseong = $index % 28;

        // 받침이 있으면 "을", 없으면 "를"을 반환합니다.
        if ($jongseong > 0) {
            return "을";
        } else {
            return "를";
        }
    } else {
        // 한글이 아닌 경우 기본값으로 "를"을 반환합니다.
        return "를";
    }
}

// 유니코드 문자를 코드 포인트로 변환하는 함수입니다.
function uniord($u)
{
    $k = mb_convert_encoding($u, 'UCS-2LE', 'UTF-8');
    $k1 = ord(substr($k, 0, 1));
    $k2 = ord(substr($k, 1, 1));
    return $k2 * 256 + $k1;
}

function utf8_to_array($str)
{
    $re_arr = array();
    $re_icount = 0;
    for ($i = 0, $m = strlen($str); $i < $m; $i++) {
        $ch = ord($str[$i]);
        if ($ch < 128) {
            $re_arr[$re_icount++] = substr($str, $i, 1);
        } else if ($ch < 224) {
            $re_arr[$re_icount++] = substr($str, $i, 2);
            $i += 1;
        } else if ($ch < 240) {
            $re_arr[$re_icount++] = substr($str, $i, 3);
            $i += 2;
        } else if ($ch < 248) {
            $re_arr[$re_icount++] = substr($str, $i, 4);
            $i += 3;
        }
    }
    return $re_arr;
}

function utf8_strlen($str)
{
    return count(utf8_to_array($str));
}

function utf8_substr($str, $start, $length = null)
{
    return implode('', array_slice(utf8_to_array($str), $start, $length));
}

function make_ellipsis($str, $length)
{
    $str_length = utf8_strlen($str);
    if ($str_length > $length) {
        $str = utf8_substr($str, 0, $length);
        $str = $str . '...';
    }
    return $str;
}