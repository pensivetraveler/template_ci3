<?php
/**
 * 관리자·회원 권한, 없는 행, 비밀번호 로그, 시스템 코드, 업체, 파트너, 메뉴 권한
 */
class AdminRecordTest extends ApiTestCase
{
    const PLAIN = 'Qq7!plainPw1';

    private static bool $granted = false;

    protected function setUp(): void
    {
        parent::setUp();
        if (!self::$granted) {
            self::grant('USR001', 'administrators');
            self::grant('USR001', 'articles');
            self::$granted = true;
        }
    }

    private function adminData(array $override = []): array
    {
        $id = self::uniqueId('ad');
        return array_merge([
            'user_cd' => 'USR001', 'id' => $id, 'password' => self::PLAIN, 'password_confirm' => self::PLAIN,
            'name' => '관리', 'email' => $id.'@example.test', 'tel' => '01000001111',
        ], $override);
    }

    public function testPasswordNeverReachesResponseOrLog(): void
    {
        $sys = $this->systemAdmin();
        $ok = $sys->request('POST', '/admin/api/administrators', $this->adminData());
        $this->assertSame(201, $ok->status, $ok->body);
        $this->assertNoPlainPassword(self::PLAIN, $ok);

        $bad = $sys->request('POST', '/admin/api/administrators', $this->adminData(['password_confirm' => 'Zz7!otherPw1']));
        $this->assertSame(422, $bad->status);
        $this->assertNoPlainPassword(self::PLAIN, $bad);
        $this->assertStringNotContainsString('Zz7!otherPw1', $bad->body);
    }

    public function testUserLogKeepsOnlyIdentifier(): void
    {
        if (!self::devMode()) $this->markTestSkipped('운영 모드는 시스템 관리자 기록을 남기지 않는다');
        $sys = $this->systemAdmin();
        $sys->request('POST', '/admin/api/administrators', $this->adminData());
        $id = (int)TestDb::value('SELECT administrator_id FROM tbl_administrator ORDER BY administrator_id DESC LIMIT 1');
        $this->assertSame(200, $sys->request('PATCH', "/admin/api/administrators/$id", ['name' => '바꿈'])->status);
        $this->assertSame(json_encode(['administrator_id' => (string)$id]), TestDb::value("SELECT params FROM tbl_user_logs WHERE class = 'administrators' AND type = 'update' ORDER BY id DESC LIMIT 1"));
    }

    public function testExpiredLogsArePurgedAcrossRequests(): void
    {
        [$id] = self::makeUser('USR001');
        $client = $this->login($id);
        $old = time() - 40 * 86400;
        $oldDate = date('Y-m-d H:i:s', $old);
        TestDb::exec("DELETE FROM tbl_rest_logs");
        TestDb::exec("DELETE FROM tbl_user_logs");
        // 1000행 넘게 밀린 요청 로그는 한 번에 다 지워지지 않고 다음 요청에서 이어진다
        for ($i = 0; $i < 11; $i++) {
            TestDb::exec("INSERT INTO tbl_rest_logs (uri, method, api_key, time) VALUES ".implode(',', array_fill(0, 100, "('old', 'get', '', $old)")));
        }
        TestDb::exec("INSERT INTO tbl_rest_logs (uri, method, api_key, time) VALUES ('keep', 'get', '', ".(time() - 86400).")");
        TestDb::exec("INSERT INTO tbl_user_logs (log_id, user_id, class, method, type, title, datetime) VALUES (0, 1, 'old', 'index', '', '', '$oldDate'), (0, 1, 'keep', 'index', '', '', NOW())");
        $this->setUserCfg(['LOG_KEEP_DAYS' => '30']);
        @unlink(APPPATH.'cache/user_logs_purged');
        @unlink(APPPATH.'cache/rest_logs_purged');

        try {
            $this->assertSame(200, $client->request('GET', '/admin/api/administrators')->status);
            $this->assertSame('100', TestDb::value("SELECT COUNT(*) FROM tbl_rest_logs WHERE uri = 'old'"));
            $this->assertSame('0', TestDb::value("SELECT COUNT(*) FROM tbl_user_logs WHERE class = 'old'"));

            $this->assertSame(200, $client->request('GET', '/admin/api/administrators')->status);
            $this->assertSame('0', TestDb::value("SELECT COUNT(*) FROM tbl_rest_logs WHERE uri = 'old'"));
            $this->assertSame('1', TestDb::value("SELECT COUNT(*) FROM tbl_rest_logs WHERE uri = 'keep'"));
            $this->assertSame('1', TestDb::value("SELECT COUNT(*) FROM tbl_user_logs WHERE class = 'keep'"));
        } finally {
            $this->clearUserCfg();
        }
    }

    public function testMissingAndDeletedRowsAre404(): void
    {
        $sys = $this->systemAdmin();
        foreach (['/admin/api/articles/999999', '/admin/api/boards/999999', '/admin/api/sysCode/ZZZ999', '/admin/api/administrators/999999'] as $path) {
            $this->assertSame(404, $sys->request('PATCH', $path, ['name' => 'x', 'subject' => 'x', 'cd_name' => 'x', 'board_name' => 'x'])->status, $path);
        }
        TestDb::exec("INSERT INTO tbl_board (board_id, board_cd, board_name, created_id) VALUES (9001, 'BRD001', 'b', 1) ON DUPLICATE KEY UPDATE board_name = 'b'");
        TestDb::exec("INSERT INTO tbl_article (article_id, board_id, subject, del_yn, created_id) VALUES (9001, 9001, '원래', 'Y', 1) ON DUPLICATE KEY UPDATE subject = '원래', del_yn = 'Y'");
        $this->assertSame(404, $sys->request('PATCH', '/admin/api/articles/9001', ['subject' => '바꿈'])->status);
        $this->assertSame('원래', TestDb::value('SELECT subject FROM tbl_article WHERE article_id = 9001'));
    }

    public function testBodyKeyCannotRetargetRows(): void
    {
        $sys = $this->systemAdmin();
        $created = $this->adminData();
        $sys->request('POST', '/admin/api/administrators', $created);
        $row = TestDb::row("SELECT a.administrator_id, u.user_id FROM tbl_administrator a JOIN tbl_user u USING(user_id) WHERE u.id = '{$created['id']}'");

        $response = $sys->request('PATCH', "/admin/api/administrators/{$row['administrator_id']}", ['user_id' => 1, 'name' => 'x']);
        $this->assertSame(403, $response->status, $response->body);
        $this->assertSame('body_key', $response->reason());

        $same = $sys->request('PATCH', "/admin/api/administrators/{$row['administrator_id']}", ['user_id' => $row['user_id'], 'name' => '같음']);
        $this->assertSame(200, $same->status, $same->body);
    }

    public function testGradeCannotBeRaised(): void
    {
        [$id] = self::makeUser('USR001');
        $admin = $this->login($id);
        $response = $admin->request('POST', '/admin/api/administrators', $this->adminData(['user_cd' => 'USR000']));
        $this->assertSame(403, $response->status, $response->body);
        $this->assertSame('grade', $response->reason());
    }

    public function testMenuPermissionsApply(): void
    {
        [$plain] = self::makeUser('USR003');
        $this->assertSame(403, $this->login($plain)->request('GET', '/admin/api/administrators')->status);
        [$admin] = self::makeUser('USR001');
        $this->assertSame(200, $this->login($admin)->request('GET', '/admin/api/administrators')->status);
        $this->assertSame(403, $this->login($admin)->request('GET', '/admin/api/sysCfg')->status);
        $this->assertSame(401, (new ApiClient())->request('GET', '/admin/api/administrators')->status);
    }

    public function testSysCodeKeysAndCategories(): void
    {
        $sys = $this->systemAdmin();
        $code = ['big_cd' => 'BRD', 'sml_cd' => '901', 'cd_name' => '코드', 'cd_srt' => 1, 'use_yn' => 'Y'];
        $this->assertSame(422, $sys->request('POST', '/admin/api/sysCode', $code)->status, '키 없음');
        $this->assertSame(201, $sys->request('POST', '/admin/api/sysCode', $code + ['cmb_cd' => 'BRD901'])->status);
        $this->assertSame(422, $sys->request('PATCH', '/admin/api/sysCode/BRD901', ['cmb_cd' => 'BRD901'])->status);
        $this->assertSame(200, $sys->request('PATCH', '/admin/api/sysCode/BRD901', ['cd_name' => '바꿈'])->status);
        $this->assertSame(200, $sys->request('DELETE', '/admin/api/sysCode/BRD901')->status);

        $category = ['sml_cd' => '000', 'cd_srt' => 0, 'use_yn' => 'N', 'cd_val' => '', 'cd_desc' => '', 'cd_nick' => ''];
        $big = 'Z'.chr(65 + getmypid() % 26).chr(65 + (int)(microtime(true)) % 26);
        $this->assertSame(422, $sys->request('POST', '/admin/api/sysCode', $category + ['cmb_cd' => 'zzz000', 'big_cd' => 'zzz', 'cd_name' => 'x'])->status);
        $this->assertSame(201, $sys->request('POST', '/admin/api/sysCode', $category + ['cmb_cd' => $big.'000', 'big_cd' => $big, 'cd_name' => '카테고리'])->status);
        $this->assertSame(409, $sys->request('POST', '/admin/api/sysCode', $category + ['cmb_cd' => $big.'000', 'big_cd' => $big, 'cd_name' => '중복'])->status);
        $this->assertSame(200, $sys->request('DELETE', "/admin/api/sysCode/{$big}000")->status);
    }

    public function testCompanyCrud(): void
    {
        $sys = $this->systemAdmin();
        $code = str_pad((string)(getmypid() % 900000 + 100000), 6, '0');
        $data = ['company_cd' => 'COM002', 'company_code' => $code, 'company_name' => '업체'];

        $this->assertSame(422, $sys->request('POST', '/admin/api/companies', ['company_cd' => 'COM999'] + $data)->status);
        $this->assertSame(422, $sys->request('POST', '/admin/api/companies', ['company_code' => ''] + $data)->status);
        $this->assertSame(201, $sys->request('POST', '/admin/api/companies', $data)->status);
        $this->assertSame(409, $sys->request('POST', '/admin/api/companies', $data)->status);
        $this->assertSame(200, $sys->request('GET', '/admin/api/companies')->status);

        $id = (int)TestDb::value("SELECT company_id FROM tbl_company WHERE company_code = '$code'");
        $this->assertSame(200, $sys->request('PATCH', "/admin/api/companies/$id", ['company_name' => '바꿈'])->status);
        $this->assertSame(200, $sys->request('DELETE', "/admin/api/companies/$id")->status);
        $this->assertSame(201, $sys->request('POST', '/admin/api/companies', $data)->status, '삭제한 코드는 다시 쓸 수 있다');
    }

    public function testPartnerCompanyIdsLimit(): void
    {
        $sys = $this->systemAdmin();
        TestDb::exec("INSERT IGNORE INTO tbl_company (company_id, company_name, company_cd, company_code, created_id) VALUES (7101, '가', 'COM001', '710100', 1), (7102, '나', 'COM002', '710200', 1)");
        $partner = function (string $companies) {
            $id = self::uniqueId('pa');
            return ['user_cd' => 'USR002', 'id' => $id, 'password' => self::PLAIN, 'password_confirm' => self::PLAIN, 'name' => '파트너', 'email' => $id.'@example.test', 'tel' => '01000002222', 'company_ids' => $companies];
        };

        $this->assertSame(201, $sys->request('POST', '/admin/api/partners', $partner('7101,7102'))->status);
        $list = $sys->request('GET', '/admin/api/partners?_mode=list');
        $this->assertSame(200, $list->status, $list->body);
        $this->assertStringContainsString('가,나', $list->body, '목록은 회사명');
        $this->assertSame(422, $sys->request('POST', '/admin/api/partners', $partner('9999'))->status);

        $long = implode(',', array_fill(0, 60, '7101'));
        $this->assertGreaterThan(255, strlen($long));
        $this->assertSame(422, $sys->request('POST', '/admin/api/partners', $partner($long))->status);
        $fit = implode(',', array_fill(0, 51, '7101'));
        $this->assertLessThanOrEqual(255, strlen($fit));
        $saved = $partner($fit);
        $this->assertSame(201, $sys->request('POST', '/admin/api/partners', $saved)->status);
        $this->assertSame(strlen($fit), (int)TestDb::value("SELECT CHAR_LENGTH(p.company_ids) FROM tbl_partner p JOIN tbl_user u USING(user_id) WHERE u.id = '{$saved['id']}'"));
    }

    public function testMenuAuthOnlyAcceptsListedMenus(): void
    {
        $sys = $this->systemAdmin();
        $count = function () { return (int)TestDb::value('SELECT COUNT(*) FROM tbl_menu_auth'); };
        $before = $count();
        $super = (int)TestDb::value('SELECT menu_id FROM tbl_menu WHERE is_super = 1 LIMIT 1');
        foreach ([999999, $super] as $menuId) {
            $this->assertSame(404, $sys->request('PATCH', "/admin/api/menuAuth?grade_cd=USR002&menu_id=$menuId", ['is_show' => 1])->status);
        }
        $this->assertSame($before, $count());

        $menu = self::menuId('administrators');
        TestDb::exec("DELETE FROM tbl_menu_auth WHERE grade_cd = 'USR002' AND menu_id = $menu");
        $this->assertSame(200, $sys->request('PATCH', "/admin/api/menuAuth?grade_cd=USR002&menu_id=$menu", ['is_show' => 1])->status);
        $this->assertSame('1', TestDb::value("SELECT is_show FROM tbl_menu_auth WHERE grade_cd = 'USR002' AND menu_id = $menu"));
    }

    public function testMessageReadIs404WithoutRow(): void
    {
        $this->assertSame(404, $this->systemAdmin()->request('PATCH', '/admin/api/boards/message_read/999999', ['x' => '1'])->status);
    }

    public function testExcelExportWritesReadableFile(): void
    {
        $sys = $this->systemAdmin();
        $added = $sys->request('POST', '/admin/api/administrators', $this->adminData(['name' => '=1+1']));
        $this->assertSame(201, $added->status, $added->body);

        $response = $sys->request('GET', '/admin/api/administrators/prepareExports?exportType=xlsx');
        $this->assertSame(201, $response->status, $response->body);
        $file = APPPATH.'cache/exports/'.basename(json_decode($response->body, true)['data'][0]['filename']);
        $this->assertFileExists($file);

        $sheet = (new \PhpOffice\PhpSpreadsheet\Reader\Xlsx())->load($file)->getActiveSheet();
        $this->assertNotSame('', (string)$sheet->getCell('A1')->getValue());
        $cells = array_merge(...$sheet->toArray(null, false, false));
        $this->assertContains('=1+1', $cells, '수식이 아니라 글자로 저장');
        @unlink($file);
    }

    public function testMenuWithSubMenusCannotBeDeleted(): void
    {
        TestDb::exec("INSERT INTO tbl_menu (parent_id, code, title, class, method, depth, srt, is_sub_menu, is_login, is_auth, is_super, is_use, base_auth) VALUES (0, '', 'tmpparent', '', '', 1, 99, 1, 1, 1, 0, 1, '111111')");
        $parent = (int)TestDb::conn()->insert_id;
        TestDb::exec("INSERT INTO tbl_menu (parent_id, code, title, class, method, depth, srt, is_sub_menu, is_login, is_auth, is_super, is_use, base_auth) VALUES ($parent, '', 'tmpchild', 'tmpchild', 'index', 2, 1, 0, 1, 1, 0, 1, '111111')");
        $child = (int)TestDb::conn()->insert_id;

        $sys = $this->systemAdmin();
        $sys->request('DELETE', "/admin/api/menuList/$parent");
        $this->assertSame('1', TestDb::value("SELECT COUNT(*) FROM tbl_menu WHERE menu_id = $parent"), '하위 메뉴가 있으면 지워지지 않음');

        $this->assertSame(200, $sys->request('DELETE', "/admin/api/menuList/$child")->status);
        $this->assertSame(200, $sys->request('DELETE', "/admin/api/menuList/$parent")->status);
        $this->assertSame('0', TestDb::value("SELECT COUNT(*) FROM tbl_menu WHERE menu_id = $parent"));
    }

    public function testCommentSavedWithoutMessageTable(): void
    {
        [$writerId, $writerPk] = self::makeUser('USR001');
        TestDb::exec("INSERT INTO tbl_board (board_name, board_cd, created_id) VALUES ('시험', 'BRD001', 1)");
        $board = (int)TestDb::conn()->insert_id;
        TestDb::exec("INSERT INTO tbl_article (board_id, subject, content, created_id) VALUES ($board, '글', '내용', $writerPk)");
        $article = (int)TestDb::conn()->insert_id;

        $response = $this->systemAdmin()->request('POST', '/admin/api/comments', ['article_id' => $article, 'content' => '댓글', 'parent_id' => 0, 'depth' => 0]);
        $this->assertSame(201, $response->status, $response->body);
        $this->assertSame('1', TestDb::value("SELECT COUNT(*) FROM tbl_comment WHERE article_id = $article"));
    }

    public function testDeletingMenuRemovesAuthRows(): void
    {
        TestDb::exec("INSERT INTO tbl_menu (parent_id, code, title, class, method, depth, srt, is_sub_menu, is_login, is_auth, is_super, is_use, base_auth) VALUES (0, '', 'tmp', 'tmpmenu', 'index', 1, 99, 0, 1, 1, 0, 1, '111111')");
        $menu = (int)TestDb::conn()->insert_id;
        TestDb::exec("INSERT INTO tbl_menu_auth (menu_id, grade_cd, is_show, menu_auth) VALUES ($menu, 'USR002', 1, '111111')");
        TestDb::exec("INSERT INTO tbl_partner_auth (menu_id, partner_id, is_show, menu_auth) VALUES ($menu, 1, 1, '111111')");

        $response = $this->systemAdmin()->request('DELETE', "/admin/api/menuList/$menu");
        $this->assertSame(200, $response->status, $response->body);
        $this->assertSame('0', TestDb::value("SELECT COUNT(*) FROM tbl_menu WHERE menu_id = $menu"));
        $this->assertSame('0', TestDb::value("SELECT COUNT(*) FROM tbl_menu_auth WHERE menu_id = $menu"));
        $this->assertSame('0', TestDb::value("SELECT COUNT(*) FROM tbl_partner_auth WHERE menu_id = $menu"));
    }

    public function testServerRunsInRequestedMode(): void
    {
        // 개발 모드 화면에만 CI 프로파일러가 붙는다 (Secure 쿠키는 두 모드 모두라 구분이 안 된다)
        $body = (new ApiClient())->request('GET', '/admin/auth')->body;
        $this->assertSame(self::devMode(), strpos($body, 'codeigniter_profiler') !== false);
    }

    public function testCspHeaderOnlyWhenEnabled(): void
    {
        $has = function (ApiResponse $response) {
            return count(preg_grep('/^Content-Security-Policy-Report-Only:/i', $response->headers)) > 0;
        };
        $this->assertFalse($has((new ApiClient())->request('GET', '/admin/auth')));
        $this->assertTrue($has((new ApiClient())->request('GET', '/admin/auth', [], ['CSP_REPORT_ONLY' => 'TRUE'])));
    }
}
