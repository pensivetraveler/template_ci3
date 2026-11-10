<?php
// 시험 DB 에 메뉴 행을 만든다 (빌더가 첫 관리자 화면에서 설정으로 채운다).
require __DIR__.'/bootstrap.php';

$id = 'seedadmin';
TestDb::exec("INSERT INTO tbl_user (user_cd,id,password,name,withdraw_yn,del_yn,created_id) VALUES ('USR000','$id','".TestDb::esc(password_hash(ApiTestCase::PASSWORD, PASSWORD_BCRYPT))."','시드','N','N',1)");
$client = new ApiClient();
$client->request('GET', '/admin/auth');
$login = $client->request('POST', '/admin/api/auth/login', ['id' => $id, 'password' => ApiTestCase::PASSWORD]);
if ($login->status !== 200) { fwrite(STDERR, "seed login failed: {$login->status} {$login->body}\n"); exit(1); }
$page = $client->request('GET', '/admin/dashboard');
$count = (int)TestDb::value('SELECT COUNT(*) FROM tbl_menu');
if ($count < 1) { fwrite(STDERR, "menu seed failed: {$page->status}\n"); exit(1); }
echo "menu rows: $count\n";
