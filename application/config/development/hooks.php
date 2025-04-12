<?php
/*
| -------------------------------------------------------------------------
| Hooks
| -------------------------------------------------------------------------
| This file lets you define "hooks" to extend CI without hacking the core
| files.  Please see the user guide for info:
|
|	https://codeigniter.com/user_guide/general/hooks.html
|
| -------------------------------------------------------------------------
| Custom Hooks
| -------------------------------------------------------------------------
|
| pre_system 			        시스템 작동초기입니다.벤치마크와 후킹클래스들만 로드된 상태로서, 라우팅을 비롯한 어떤 다른 프로세스도 진행되지않은 상태입니다.
| pre_controller 		        컨트롤러가 호출되기 직전입니다. 모든 기반클래스(base classes), 라우팅 그리고 보안점검이 완료된 상태입니다.
| post_controller_constructor 	컨트롤러가 인스턴스화 된 직후입니다. 즉 사용준비가 완료된 상태가 되겠죠.
|                               하지만, 인스턴스화 된 후 메소드들이 호출되기 직전입니다.
| post_controller 		        컨트롤러가 완전히 수행된 직후입니다.
| display_override              _display() 함수를 재정의 합니다. 최종적으로 브라우저에 페이지를 전송할 때 사용됩니다.
|                               이로서 당신만의 표시 방법(display methodology)을 사용할 수 있습니다.
|                               주의 : CI 부모객체(superobject)를 $this->CI =& get_instance() 로 호출하여 사용한 후에 최종데이터 작성은 $this->CI->output->get_output() 함수를 호출하여 할 수 있습니다.
| cache_override 		        출력라이브러리(Output Library) 에 있는 _display_cache() 함수 대신 당신의 스크립트를 호출할 수 있도록 해줍니다. 이로서 당신만의 캐시 표시 메커니즘(cache display mechanism)을 적용할 수 있습니다.
| post_system 			        최종 렌더링 페이지가 브라우저로 보내진후에 호출됩니다.
|
|
| -------------------------------------------------------------------------
| Hooks Parameters
| -------------------------------------------------------------------------
| filepath                      APP_PATH 내 filename이 존재하는 경로
| filename                      class의 파일 명
| class                         호출 class name
| function                      호출 메소드
| params                        array. 전달 파라미터
*/

/**
 * Load ENV File
 * Use this code if your .env files on *CodeIgniter ROOT* folder
 */
$hook['pre_system'][] = array(
	'class'    => 'MY_Hooks',
	'function' => 'loadEnv',
	'filename' => 'MY_Hooks.php',
	'filepath' => 'hooks',
	'params'   => array()
);


/**
 * System Of Inspection
 * 서버 점검 시, 모든 라우트에 대한 접근 제한을 해아할 경우
 */
$hook['pre_controller'][] = array(
	'class'    => 'MY_Hooks',
	'function' => 'systemOfInspection',
	'filename' => 'MY_Hooks.php',
	'filepath' => 'hooks',
	'params'   => array()
);


/**
 * Permission check
 * https://www.cikorea.net/bbs/view/tip?idx=8009
 */
$hook['post_controller_constructor'][] = array(
	'class'    => 'MY_Hooks',
	'function' => 'checkPermission',
	'filename' => 'MY_Hooks.php',
	'filepath' => 'hooks',
	'params'   => array()
);


/**
 * set PHP variables as javascript
 * https://blog.naver.com/awspro/222017107778
 */
$hook['post_controller_constructor'][] = array(
	'class'    => 'MY_Hooks',
	'function' => 'setPHPVars',
	'filename' => 'MY_Hooks.php',
	'filepath' => 'hooks',
	'params'   => array()
);


/**
 * setFormValidation
 * config에 정의된 regexp 값을 토대로 form valdation method 추가
 */
$hook['post_controller_constructor'][] = array(
	'class'    => 'MY_Hooks',
	'function' => 'setFormValidation',
	'filename' => 'MY_Hooks.php',
	'filepath' => 'hooks',
	'params'   => array()
);


/**
 * setUploadMaxSize
 * 업로드 가능 파일 크기를 define
 */
$hook['post_controller_constructor'][] = array (
	'class' => 'MY_Hooks',
	'function' => 'setUploadMaxSize',
	'filename' => 'MY_Hooks.php',
	'filepath' => 'hooks'
);


/**
 * header security
 */
$hook['post_controller'][] = array(
	'class'    => 'MY_Hooks',
	'function' => 'setHeaderSecure',
	'filename' => 'MY_Hooks.php',
	'filepath' => 'hooks',
	'params'   => array()
);

/**
 * set Layout
 * https://gauryan.blogspot.com/2010/03/codeigniter.html
 */
//$hook['display_override'][] = array(
//    'class'    => 'Yield',
//    'function' => 'doYield',
//    'filename' => 'Yield.php',
//    'filepath' => 'hooks'
//);

/**
 * Compress output only in production mode.
 * https://github.com/bkader/ci-starter-kit/
 */
if (ENVIRONMENT == 'production')
{
	$hook['display_override'][] = array(
		'class'    => 'MY_Hooks',
		'function' => 'compress',
		'filename' => 'MY_Hooks.php',
		'filepath' => 'hooks',
		'params'   => array(),
	);
}
