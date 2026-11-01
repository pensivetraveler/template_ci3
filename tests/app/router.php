<?php
/**
 * 시험 서버 입구 (scripts/test.sh 가 php -S 로 띄운다. 배포 경로에서 쓰지 않는다).
 * .env.development 를 먼저 읽고 DB 만 template_test 로 바꾼다. 요청마다 X-Test-Env 로 환경 변수를 덮어쓸 수 있다.
 */
$root = dirname(__DIR__, 2);
$file = $root.parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (is_file($file) && substr($file, -4) !== '.php') return false;

require $root.'/vendor/autoload.php';
$env = (new Symfony\Component\Dotenv\Dotenv())->parse(file_get_contents($root.'/.env.development'));
$env = array_merge($env, [
    'DB_DATABASE' => 'template_test',
    'CACHING_MENU' => 'true',
    'REST_STRICT' => 'true',
    'CSP_REPORT_ONLY' => 'false',
    'WEB_AUTH_API' => 'false',
    'SIGNUP_USER_CD' => '',
    'MAIL_HOST' => '',
    'MAIL_BCC' => '',
]);
if (!empty($_SERVER['HTTP_X_TEST_ENV'])) {
    $env = array_merge($env, (array)json_decode(base64_decode($_SERVER['HTTP_X_TEST_ENV']), true));
}
foreach ($env as $name => $value) {
    $value = (string)$value;
    putenv("$name=$value");
    $_ENV[$name] = $value;
    $_SERVER[$name] = $value;
}

$_SERVER['CI_ENV'] = getenv('TEST_CI_ENV') ?: 'development';
if ($_SERVER['CI_ENV'] === 'production') $_SERVER['HTTPS'] = 'on';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = $root.'/index.php';
chdir($root);
require $root.'/index.php';
