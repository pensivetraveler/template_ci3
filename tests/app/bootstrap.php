<?php
// 프로젝트 시험 공통. 단위 시험은 CI 없이 도는 함수만, 통합 시험은 시험 서버로 HTTP 요청을 보낸다.
define('BASEPATH', __DIR__.'/../../system/');
define('FCPATH', dirname(__DIR__, 2).'/');
define('APPPATH', FCPATH.'application/');

// vendor 는 운영과 같게 개발 패키지 없이 설치하므로, 시험 도구는 .dev-vendor 에 따로 둔다 (tests/app/README.md)
require FCPATH.'.dev-vendor/autoload.php';
require BASEPATH.'core/Common.php';
require APPPATH.'helpers/common_helper.php';
require APPPATH.'helpers/MY_path_helper.php';
require __DIR__.'/support/TestDb.php';
require __DIR__.'/support/ApiClient.php';
require __DIR__.'/support/ApiTestCase.php';
