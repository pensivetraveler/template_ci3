<?php
if ( ! function_exists('get_dow_list') )
{
    function get_dow_list()
    {
        return [
            0 => [
                'ko' => [
                    'short' => '일',
                    'long' => '일요일',
                ],
                'en' => [
                    'short' => 'sun',
                    'long' => 'sunday',
                ],
            ],
            1 => [
                'ko' => [
                    'short' => '월',
                    'long' => '월요일',
                ],
                'en' => [
                    'short' => 'mon',
                    'long' => 'monday',
                ],
            ],
            2 => [
                'ko' => [
                    'short' => '화',
                    'long' => '화요일',
                ],
                'en' => [
                    'short' => 'tue',
                    'long' => 'tuesday',
                ],
            ],
            3 => [
                'ko' => [
                    'short' => '수',
                    'long' => '수요일',
                ],
                'en' => [
                    'short' => 'wed',
                    'long' => 'wednesday',
                ],
            ],
            4 => [
                'ko' => [
                    'short' => '목',
                    'long' => '목요일',
                ],
                'en' => [
                    'short' => 'thu',
                    'long' => 'thursday',
                ],
            ],
            5 => [
                'ko' => [
                    'short' => '금',
                    'long' => '금요일',
                ],
                'en' => [
                    'short' => 'fri',
                    'long' => 'friday',
                ],
            ],
            6 => [
                'ko' => [
                    'short' => '토',
                    'long' => '토요일',
                ],
                'en' => [
                    'short' => 'sat',
                    'long' => 'saturday',
                ],
            ],
        ];
    }
}

if ( ! function_exists('str_to_dow'))
{
    function str_to_dow($data)
    {
        if(is_numeric($data)) return null;

        $dow_list = [];
        foreach (get_dow_list() as $index=>$dow_data) {
            $dow_list[$index] = [];
            foreach (array_keys($dow_data) as $key) {
                $dow_list[$index] = array_merge($dow_list[$index], [
                    $dow_data[$key]['short'],
                    $dow_data[$key]['long'],
                ]);
            }
        }

        foreach ($dow_list as $index=>$dow_data) {
            if(in_array(strtolower($data), $dow_data)) {
                return $index;
            }
        }

        return null;
    }
}

if ( ! function_exists('dow_to_str'))
{
    function dow_to_str($data, $lang = 'en', $long = false)
    {
        if(!is_numeric($data)) return null;

        $dow_list = get_dow_list();
        if(array_key_exists((int)$data, $dow_list)) {
            $dow_data = $dow_list[(int)$data];
            if(array_key_exists($lang, $dow_data)) {
                $flag = $long?'long':'short';
                return $dow_data[$lang][$flag];
            }
        }

        return null;
    }
}

/**
 * DATE 함수의 약간 변형
 */
if ( ! function_exists('cdate'))
{
    function cdate($date, $timestamp = '')
    {
        defined('TIMESTAMP') or define('TIMESTAMP', time());
        return $timestamp ? date($date, $timestamp) : date($date, TIMESTAMP);
    }
}


/**
 * TIMESTAMP 불러오기
 */
if ( ! function_exists('ctimestamp'))
{
    function ctimestamp()
    {
        defined('TIMESTAMP') or define('TIMESTAMP', time());
        return TIMESTAMP;
    }
}

if ( ! function_exists('get_time_taken_as_string') )
{
    function get_time_taken_as_string($datetime)
    {
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
}
