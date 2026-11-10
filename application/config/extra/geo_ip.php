<?php
defined('BASEPATH') or exit('No direct script access allowed');

$config['geo_ip'] = [
    /*
     * auto:
     * providers 순서대로 시도해서 성공한 provider 결과를 사용
     *
     * 특정 provider만 쓰고 싶으면:
     * ip_api, ipwhois, ipapi, freeipapi 중 하나 지정
     */
    'provider' => 'auto',

    'providers' => [
        'ipwhois',
        'freeipapi',
        'ipapi',
        'ip_api',
    ],

    'timeout'         => 3,
    'connect_timeout' => 2,

    /*
     * 나중에 DB 캐시 붙일 수 있도록 둔 옵션.
     * 지금 코드는 API switching 중심이라 캐시는 생략.
     */
    'cache_enabled' => true,
    'cache_ttl_days' => 30,
];
