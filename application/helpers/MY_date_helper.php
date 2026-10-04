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
    function ctimestamp(): int
    {
        defined('TIMESTAMP') or define('TIMESTAMP', time());
        return TIMESTAMP;
    }
}

if ( ! function_exists('get_time_taken_as_string') )
{
    function get_time_taken_as_string($datetime): string
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

if ( ! function_exists('make_date_list') )
{
    /**
     * @throws Exception
     */
    function make_date_list($start_date, $end_date, $unit = 'daily'): array
    {
        $start = new DateTimeImmutable($start_date);
        $end = new DateTimeImmutable($end_date);

        if ($start > $end) {
            return [];
        }

        $unit = strtolower($unit);

        $current = get_period_start_date($start, $unit);
        $endBoundary = get_period_start_date($end, $unit);

        $result = [];

        while ($current <= $endBoundary) {
            $result[] = format_period_label($current, $unit);
            $current = add_period_interval($current, $unit);
        }

        return $result;
    }
}

if ( ! function_exists('get_period_start_date') )
{
    function get_period_start_date(DateTimeImmutable $date, string $unit): DateTimeImmutable
    {
        switch ($unit) {
            case 'yearly':
                return $date
                    ->setDate((int) $date->format('Y'), 1, 1)
                    ->setTime(0, 0, 0);

            case 'quarterly':
                $month = (int) $date->format('n');
                $quarterStartMonth = ((int) floor(($month - 1) / 3) * 3) + 1;

                return $date
                    ->setDate((int) $date->format('Y'), $quarterStartMonth, 1)
                    ->setTime(0, 0, 0);

            case 'monthly':
                return $date
                    ->setDate((int) $date->format('Y'), (int) $date->format('n'), 1)
                    ->setTime(0, 0, 0);

            case 'weekly':
                // ISO week 기준 월요일
                return $date
                    ->modify('monday this week')
                    ->setTime(0, 0, 0);

            case 'daily':
            default:
                return $date->setTime(0, 0, 0);
        }
    }
}

if ( ! function_exists('add_period_interval') )
{
    function add_period_interval(DateTimeImmutable $date, string $unit): DateTimeImmutable
    {
        switch ($unit) {
            case 'yearly':
                return $date->modify('+1 year');

            case 'quarterly':
                return $date->modify('+3 months');

            case 'monthly':
                return $date->modify('+1 month');

            case 'weekly':
                return $date->modify('+1 week');

            case 'daily':
            default:
                return $date->modify('+1 day');
        }
    }
}

if ( ! function_exists('format_period_label') )
{
    function format_period_label(DateTimeImmutable $date, string $unit): string
    {
        switch ($unit) {
            case 'yearly':
                return $date->format('Y');

            case 'quarterly':
                $quarter = (int) ceil(((int) $date->format('n')) / 3);
                return $date->format('Y') . '-Q' . $quarter;

            case 'monthly':
                return $date->format('Y-m');

            case 'weekly':
                // ISO week-year + ISO week number
                return $date->format('o-\WW');

            case 'daily':
            default:
                return $date->format('Y-m-d');
        }
    }
}

if ( ! function_exists('get_period_key') )
{
    function get_period_key($date, $mode = 'daily')
    {
        if (empty($date)) return null;

        $dt = new DateTimeImmutable($date);

        switch ($mode) {
            case 'yearly':
                return $dt->format('Y');

            case 'quarterly':
                $quarter = (int) ceil(((int) $dt->format('n')) / 3);
                return $dt->format('Y') . '-Q' . $quarter;

            case 'monthly':
                return $dt->format('Y-m');

            case 'weekly':
                // ISO week 기준
                return $dt->format('o-\WW');

            case 'daily':
            default:
                return $dt->format('Y-m-d');
        }
    }
}
