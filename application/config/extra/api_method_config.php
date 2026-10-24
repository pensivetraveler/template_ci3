<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| API 엔드포인트별 기능 설정
| -------------------------------------------------------------------------
| RestController(REST 라이브러리)가 요청마다 제어하는 기능을 엔드포인트 단위로 정한다.
| MY_Controller_API::applyApiMethodConfig()가 읽어서
|   - auth  → rest_config 의 auth_override_class_method_http 로 넣고 (RestController 생성자 안에서 인증 검사)
|   - 나머지 → $this->methods['{메서드}_{HTTP}'] 로 넣는다 (RestController::_remap 에서 사용)
| 이 파일 값이 rest_config 의 같은 항목보다 우선한다.
| 컨트롤러에서 $this->methods 를 직접 지정하면 그 값이 이 파일보다 우선한다 (기존 코드 호환).
|
| 키 구조: API 클래스 → 메서드 → HTTP 메서드
|   API 클래스 : URL 세그먼트 그대로 (/admin/api/partnerAuth → 'partnerAuth')
|   메서드     : URL 세그먼트 그대로 (/admin/api/auth/dupCheck → 'dupCheck'), 기본 메서드는 'index', 전체는 '*'
|   HTTP 메서드: get | post | put | patch | delete, 전체는 '*'
| 같은 요청에 여러 항목이 맞으면 [*][*] → [*][http] → [메서드][*] → [메서드][http] 순으로 덮어쓴다.
|
| 기능 키
|   auth       : 인증 방식 덮어쓰기. none | session | basic | digest | whitelist (생략하면 rest_auth)
|   key        : API 키 검사 여부 (rest_enable_keys 가 켜져 있을 때만 의미)
|   log        : rest_logs 기록 여부 (rest_enable_logging)
|   limit      : time 초 동안 허용할 요청 수 (rest_enable_limits)
|   time       : limit 기준 시간(초). 기본 3600
|   level      : 필요한 API 키 레벨
|   permission : [예정 - 아직 읽는 코드 없음] API 권한 검사 (TODO.md 2장, 메뉴 권한과 API 대응)
|     menu  : 대응하는 메뉴 'class' 또는 'class/method' (예: 'system/sysCfg'). 생략하면 API 클래스명과 같은 메뉴
|     mode  : 검사할 권한 자리. create | read | update | delete | export | import (생략하면 HTTP 메서드로 판단)
|     super : true 면 시스템 관리자만 허용
|     check : false 면 권한 검사 생략 (로그인만 확인)
|
| 예시
|   'sysCfg' => [
|       '*' => ['*' => ['permission' => ['menu' => 'system/sysCfg']]],
|   ],
|   'auth' => [
|       'login' => ['post' => ['auth' => 'none', 'key' => false, 'log' => false, 'limit' => 10, 'time' => 60]],
|   ],
*/

$config['api_method_config'] = [
    // 인증·회원 API: 로그인 전에 쓰는 엔드포인트는 auth none, 키·로그 생략
    'auth' => [
        'dupCheck'      => ['get'  => ['auth' => 'none', 'key' => false, 'log' => false]],
        'idCheck'       => ['get'  => ['auth' => 'none', 'key' => false, 'log' => false]],
        'emailCheck'    => ['get'  => ['auth' => 'none', 'key' => false, 'log' => false]],
        'login'         => ['post' => ['auth' => 'none', 'key' => false, 'log' => false]],
        'signup'        => ['post' => ['auth' => 'none', 'key' => false, 'log' => false]],
        'findId'        => ['post' => ['auth' => 'none', 'key' => false, 'log' => false]],
        'findPassword'  => ['post' => ['auth' => 'none', 'key' => false, 'log' => false]],
        'passwordCheck' => ['post' => ['auth' => 'none', 'key' => false, 'log' => false]],
        // 로그인 상태에서 쓰는 엔드포인트: 인증은 rest_auth(session) 그대로
        'withdraw'      => ['post' => ['key' => false, 'log' => false]],
        'logout'        => ['post' => ['key' => false, 'log' => false]],
    ],
];
