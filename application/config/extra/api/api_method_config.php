<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| API 엔드포인트별 기능 설정 (api 폴더: application/controllers/api)
| -------------------------------------------------------------------------
| custom_routes.php 의 api_folders 에 있는 폴더마다 config/extra/{폴더}/{폴더}_method_config.php 를 둔다.
| 그 폴더의 컨트롤러는 모두 MY_Controller_API 를 상속하므로(MY_Builder_API 는 선택) 빌더 여부와 상관없이 적용된다.
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
|   permission : 메뉴 권한 검사 (MY_Builder_API::checkApiPermission, /admin/api/* 에만 적용)
|     menu  : 대응하는 메뉴 'class' 또는 'class/method' (예: 'system/sysCfg'). 생략하면 API 클래스명과 같은 메뉴
|             (같은 class 메뉴가 여럿이면 API 메서드와 같은 method 메뉴). 메뉴와 연결되지 않으면 관리자 등급(is_admin)만
|     mode  : 검사할 권한 자리. create | read | update | delete | export | import (생략하면 요청으로 판단:
|             GET 조회, 식별자 없는 POST 등록, 식별자 있는 POST·PUT·PATCH 수정, DELETE 삭제, 내보내기 export, 엑셀 import)
|     super : true 면 시스템 관리자만 허용 (연결된 메뉴가 isSuper 여도 같음)
|     check : false 면 권한 검사 생략 (로그인만 확인)
|     public: true 면 메뉴 권한을 검사하지 않는 플랫폼(/api/* 등)에도 연다. 없으면 그 경로는 404.
|             관리자 경로(/admin/api/*)에서도 권한 검사를 건너뛴다
|     web   : false 면 public 이어도 /api/* 에서는 닫는다 (관리자 경로에는 영향 없음)
|   resource   : 이 API 가 쓸 엔티티 설정 이름 (form·list·view, MY_Builder_API). 생략하면 API 클래스명.
|                화면이 route 의 resourceConfig 로 다른 설정을 쓰면 API 도 같은 값을 준다 (예: users → myinfo)
|
| 예시
|   'sysCfg' => [
|       '*' => ['*' => ['permission' => ['menu' => 'system/sysCfg']]],
|   ],
|   'auth' => [
|       'login' => ['post' => ['auth' => 'none', 'key' => false, 'log' => false, 'limit' => 10, 'time' => 60]],
|   ],
*/

// 회원가입으로 만들 회원 등급. 비어 있으면 회원가입을 받지 않는다(404). 관리자 등급(USR000·USR001·USR002)을 넣지 말 것
$config['signup_user_cd'] = getenv('SIGNUP_USER_CD') ?: '';
// 비밀번호 재설정 화면 경로. 웹 로그인 화면이 있는 프로젝트는 그쪽으로 바꾼다
$config['password_reset_path'] = 'admin/auth/resetPassword';

$config['api_method_config'] = [
    // 시스템 설정 화면(route system 의 메서드)이 쓰는 API → 메뉴 system/{메서드} (isSuper 메뉴라 시스템 관리자만)
    // 내 정보 화면(myinfo route, resourceConfig 'myinfo')이 쓰는 API → 같은 form 설정으로 서버 검증
    // 메뉴가 없고 일반 등급도 쓰므로 권한 검사 생략 (본인 제한은 api/Users)
    'users'    => ['*' => ['*' => ['resource' => 'myinfo', 'permission' => ['check' => false]]]],
    'sysCfg'   => ['*' => ['*' => ['permission' => ['menu' => 'system/sysCfg']]]],
    'userCfg'  => ['*' => ['*' => ['permission' => ['menu' => 'system/userCfg']]]],
    'sysCode'  => ['*' => ['*' => ['permission' => ['menu' => 'system/sysCode']]]],
    'menuList' => ['*' => ['*' => ['permission' => ['menu' => 'system/menuList']]]],
    'menuAuth' => ['*' => ['*' => ['permission' => ['menu' => 'system/menuAuth']]]],

    // 인증·회원 API: 로그인 전에 쓰는 엔드포인트는 auth none, 키·로그 생략
    // 웹 로그인 화면이 없으면 쓰는 곳이 없어 /api/auth/* 는 .env WEB_AUTH_API 를 켠 프로젝트만 연다
    'auth' => [
        '*'             => ['*' => ['permission' => ['public' => true, 'web' => filter_var(getenv('WEB_AUTH_API'), FILTER_VALIDATE_BOOLEAN)]]],
        'dupCheck'      => ['get'  => ['auth' => 'none', 'key' => false, 'log' => false]],
        'idCheck'       => ['get'  => ['auth' => 'none', 'key' => false, 'log' => false]],
        'emailCheck'    => ['get'  => ['auth' => 'none', 'key' => false, 'log' => false]],
        // 로그인·아이디/비밀번호 찾기: IP 별 60초에 10회 (무차별 대입 방지, RestController user_limits).
        // log 는 끈 채 둔다 — rest_logs 는 요청 파라미터를 그대로 저장해 비밀번호가 남는다
        'login'         => ['post' => ['auth' => 'none', 'key' => false, 'log' => false, 'limit' => 10, 'time' => 60]],
        // 가입은 SIGNUP_USER_CD 로 따로 연다 (signup_post 가 값이 없으면 404)
        'signup'        => ['post' => ['auth' => 'none', 'key' => false, 'log' => false, 'limit' => 10, 'time' => 60, 'permission' => ['public' => true, 'web' => true]]],
        'findId'        => ['post' => ['auth' => 'none', 'key' => false, 'log' => false, 'limit' => 10, 'time' => 60]],
        // 비밀번호 찾기·재설정은 계정을 바꿀 수 있어 IP 별 300초에 5회
        'findPassword'  => ['post' => ['auth' => 'none', 'key' => false, 'log' => false, 'limit' => 5, 'time' => 300]],
        'resetPassword' => ['post' => ['auth' => 'none', 'key' => false, 'log' => false, 'limit' => 5, 'time' => 300]],
        'passwordCheck' => ['post' => ['auth' => 'none', 'key' => false, 'log' => false]],
        // 로그인 상태에서 쓰는 엔드포인트: 인증은 rest_auth(session) 그대로
        'withdraw'      => ['post' => ['key' => false, 'log' => false]],
        'logout'        => ['post' => ['key' => false, 'log' => false]],
    ],

    // 설치 API: 설치 전에는 로그인할 계정이 없어 auth none (전에는 세션 인증을 요구해 빈 DB 에서 설치할 수 없었음).
    // 설치가 끝나면(표와 USR000 이 있으면) Setup 생성자가 403, addTables 는 production 에서 403. 비밀번호를 받으므로 log 끔
    'setup' => [
        '*'             => ['*' => ['permission' => ['public' => true]]],
        'addSystemUser' => ['post' => ['auth' => 'none', 'key' => false, 'log' => false, 'limit' => 10, 'time' => 60]],
        'addTables'     => ['post' => ['auth' => 'none', 'key' => false, 'log' => false, 'limit' => 10, 'time' => 60]],
    ],
];
