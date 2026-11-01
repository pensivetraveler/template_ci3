<?php
/**
 * 환경값 SYS_USER 카테고리와 아이디·비밀번호 찾기
 */
class PasswordFindTest extends ApiTestCase
{
    const FIND_PASSWORD = '/admin/api/auth/findPassword';
    const FIND_ID = '/admin/api/auth/findId';
    const RESET = '/admin/api/auth/resetPassword';
    const MAIL_FILE = '/tmp/template_test_mail.txt';

    protected function setUp(): void
    {
        parent::setUp();
        $this->clearUserCfg();
        TestDb::exec('DELETE FROM tbl_password_reset');
        @unlink(self::MAIL_FILE);
    }

    protected function tearDown(): void
    {
        $this->clearUserCfg();
    }

    private function mailEnv(): array
    {
        return ['MAIL_HOST' => '127.0.0.1', 'MAIL_PORT' => '2526', 'MAIL_USERNAME' => '', 'MAIL_PASSWORD' => '', 'MAIL_ENCRYPTION' => '', 'MAIL_FROM_ADDRESS' => 'no-reply@example.test', 'MAIL_BCC' => 'bcc@example.test', 'APP_URL' => 'https://app.example.test'];
    }

    private function identity(string $id): array
    {
        $row = TestDb::row("SELECT email, tel FROM tbl_user WHERE id = '$id'");
        return ['id' => $id, 'email' => $row['email'], 'tel' => $row['tel']];
    }

    private function token(ApiResponse $response): string
    {
        $this->assertSame(1, preg_match('/token=([0-9a-f]{64})/', $response->body, $m), $response->body);
        return $m[1];
    }

    public function testDefaultAnswerWithHashedPasswordAsksAdministrator(): void
    {
        [$id] = self::makeUser('USR003');
        $response = (new ApiClient())->request('POST', self::FIND_PASSWORD, $this->identity($id));
        $this->assertSame(400, $response->status);
        $this->assertStringContainsString('관리자', (string)$response->json['msg']);
    }

    public function testFieldSettingsDisableFinders(): void
    {
        [$id] = self::makeUser('USR003', ['name' => '이름찾기']);
        $identity = $this->identity($id);

        $this->setUserCfg(['SIGNUP_TEL_YN' => 'N']);
        $this->assertSame(4044, (new ApiClient())->request('POST', self::FIND_ID, $identity)->code());
        $this->assertSame(404, (new ApiClient())->request('GET', '/admin/auth/findId')->status);

        $this->clearUserCfg();
        $this->setUserCfg(['SIGNUP_EMAIL_YN' => 'N']);
        $this->assertSame(4044, (new ApiClient())->request('POST', self::FIND_PASSWORD, $identity)->code());
        $this->assertSame(404, (new ApiClient())->request('GET', '/admin/auth/findPassword')->status);

        // 이메일을 쓰지 않으면 아이디는 이름과 전화번호로 찾는다
        $found = (new ApiClient())->request('POST', self::FIND_ID, ['name' => '이름찾기', 'tel' => $identity['tel']]);
        $this->assertSame(200, $found->status, $found->body);
    }

    public function testFindIdAcceptsExistingEmail(): void
    {
        [$id] = self::makeUser('USR003');
        $identity = $this->identity($id);
        $response = (new ApiClient())->request('POST', self::FIND_ID, ['email' => $identity['email'], 'tel' => $identity['tel']]);
        $this->assertSame(200, $response->status, $response->body);
    }

    public function testResetWithoutMailShowsScreenButNotForSystemAdmin(): void
    {
        $this->setUserCfg(['PASSWORD_FIND_MODE' => 'reset']);
        [$id] = self::makeUser('USR001');
        [$sys] = self::makeUser('USR000');

        $this->assertSame(400, (new ApiClient())->request('POST', self::FIND_PASSWORD, $this->identity($sys))->status);
        $this->assertSame(404, (new ApiClient())->request('POST', self::FIND_PASSWORD, ['id' => 'nobody', 'email' => 'a@b.test', 'tel' => '0'])->status);

        $response = (new ApiClient())->request('POST', self::FIND_PASSWORD, $this->identity($id));
        $token = $this->token($response);
        $this->assertSame(0, (int)TestDb::value("SELECT COUNT(*) FROM tbl_password_reset WHERE token_hash = '$token'"), '원문을 저장하면 안 됨');
        $this->assertSame(200, (new ApiClient())->request('GET', '/admin/auth/resetPassword?token='.$token)->status);
    }

    public function testResetChangesPasswordOnceAndClosesOldSession(): void
    {
        $this->setUserCfg(['PASSWORD_FIND_MODE' => 'reset']);
        [$id, $userId] = self::makeUser('USR001');
        $old = $this->login($id);
        $token = $this->token((new ApiClient())->request('POST', self::FIND_PASSWORD, $this->identity($id)));
        $new = 'Nn9!resetOne';

        $this->assertSame(400, (new ApiClient())->request('POST', self::RESET, ['token' => 'bad', 'password' => $new, 'password_confirm' => $new])->status);
        $this->assertSame(422, (new ApiClient())->request('POST', self::RESET, ['token' => $token, 'password' => $new, 'password_confirm' => 'Zz9!other000'])->status);

        $done = (new ApiClient())->request('POST', self::RESET, ['token' => $token, 'password' => $new, 'password_confirm' => $new]);
        $this->assertSame(200, $done->status, $done->body);
        $this->assertNoPlainPassword($new, $done);
        $this->assertSame(400, (new ApiClient())->request('POST', self::RESET, ['token' => $token, 'password' => $new, 'password_confirm' => $new])->status, '재사용');

        $this->assertSame(0, (int)TestDb::value("SELECT COUNT(*) FROM tbl_user_token WHERE user_id = $userId"));
        $this->assertNotSame(200, $old->request('GET', '/admin/api/administrators')->status, '이전 토큰');
        $this->assertNotSame(200, (new ApiClient())->request('POST', '/admin/api/auth/login', ['id' => $id, 'password' => self::PASSWORD])->status);
        $this->login($id, $new);
    }

    public function testExpiredTokenIsRejected(): void
    {
        $this->setUserCfg(['PASSWORD_FIND_MODE' => 'reset']);
        [$id] = self::makeUser('USR001');
        $token = $this->token((new ApiClient())->request('POST', self::FIND_PASSWORD, $this->identity($id)));
        TestDb::exec('UPDATE tbl_password_reset SET expires_dt = NOW() - INTERVAL 1 MINUTE');
        $new = 'Nn9!resetOne';
        $this->assertSame(400, (new ApiClient())->request('POST', self::RESET, ['token' => $token, 'password' => $new, 'password_confirm' => $new])->status);
    }

    public function testMailFlow(): void
    {
        $this->setUserCfg(['PASSWORD_FIND_MODE' => 'reset']);
        [$id] = self::makeUser('USR000');
        $env = $this->mailEnv();

        $unknown = (new ApiClient())->request('POST', self::FIND_PASSWORD, ['id' => 'nobody', 'email' => 'a@b.test', 'tel' => '0'], $env);
        $this->assertFileDoesNotExist(self::MAIL_FILE);

        $known = (new ApiClient())->request('POST', self::FIND_PASSWORD, $this->identity($id), $env);
        $this->assertSame($unknown->status, $known->status);
        $this->assertSame($unknown->json['msg'], $known->json['msg'], '계정 유무로 응답이 달라지면 안 됨');

        $mail = quoted_printable_decode((string)file_get_contents(self::MAIL_FILE));
        $this->assertStringContainsString('https://app.example.test/admin/auth/resetPassword?token=', $mail);
        $this->assertStringNotContainsString('bcc@example.test', $mail);

        (new ApiClient())->request('POST', self::FIND_PASSWORD, $this->identity($id), $env);
        $this->assertSame(1, substr_count((string)file_get_contents(self::MAIL_FILE), '====='), '60초 안에 다시 보내지 않음');
    }

    private function saveCfg(ApiClient $client, string $sml, string $value): ApiResponse
    {
        $this->setUserCfg([$sml => '']);
        return $client->request('PUT', '/admin/api/sysCfg/SYS_USER_'.$sml, [
            'cmb_cfg' => 'SYS_USER_'.$sml, 'big_cfg' => 'SYS_USER', 'sml_cfg' => $sml, 'cfg_name' => $sml, 'cfg_type' => 'text', 'cfg_val' => $value,
        ]);
    }

    public function testShowModeRejectedWhileNotDecryptable(): void
    {
        $this->assertSame(400, $this->saveCfg($this->systemAdmin(), 'PASSWORD_FIND_MODE', 'show')->status);
    }

    public function testSettingsRequireSystemAdmin(): void
    {
        [$id] = self::makeUser('USR001');
        $this->assertSame(403, $this->saveCfg($this->login($id), 'SIGNUP_TEL_YN', 'N')->status);
        $this->assertSame(401, $this->saveCfg(new ApiClient(), 'SIGNUP_TEL_YN', 'N')->status);
        $response = $this->saveCfg($this->systemAdmin(), 'SIGNUP_TEL_YN', 'N');
        $this->assertSame(200, $response->status, $response->body);
        $this->assertSame('N', TestDb::value("SELECT cfg_val FROM tbl_sys_cfg WHERE cmb_cfg = 'SYS_USER_SIGNUP_TEL_YN'"));
    }

    public function testUserSettingValuesAreValidated(): void
    {
        $sys = $this->systemAdmin();
        $this->assertSame(400, $this->saveCfg($sys, 'LOG_KEEP_DAYS', 'abc')->status);
        $this->assertSame(400, $this->saveCfg($sys, 'SIGNUP_EMAIL_YN', 'yes')->status);
        $this->assertSame(400, $this->saveCfg($sys, 'PASSWORD_FIND_MODE', 'mail')->status);
        $this->assertSame(200, $this->saveCfg($sys, 'PASSWORD_FIND_MODE', 'reset')->status);
        $this->assertSame(200, $this->saveCfg($sys, 'LOG_KEEP_DAYS', '')->status);
    }

    public function testUserCategoryAndMenuMoved(): void
    {
        $sys = $this->systemAdmin();
        $this->setUserCfg(['LOG_KEEP_DAYS' => '']);
        $page = $sys->request('GET', '/admin/system/sysCfg');
        $this->assertSame(200, $page->status);
        $this->assertStringContainsString('SYS_USER_LOG_KEEP_DAYS', $page->body);
        $this->assertSame(404, $sys->request('POST', '/admin/api/userCfg/save', [])->status);
    }

    public function testDropdownTypeRequiresOptions(): void
    {
        $sys = $this->systemAdmin();
        $sys->request('POST', '/admin/api/sysCfg', ['cmb_cfg' => 'TEST_DD_000', 'big_cfg' => 'TEST_DD', 'sml_cfg' => '000', 'cfg_name' => '시험', 'cfg_type' => 'text', 'cfg_val' => '', 'cfg_desc' => '']);
        $value = ['cmb_cfg' => 'TEST_DD_ONE', 'big_cfg' => 'TEST_DD', 'sml_cfg' => 'ONE', 'cfg_name' => '값', 'cfg_val' => 'Y'];

        $this->assertSame(400, $sys->request('POST', '/admin/api/sysCfg', $value + ['cfg_type' => 'dropdown', 'cfg_options' => ''])->status, '선택지 없음');
        $this->assertSame(422, $sys->request('POST', '/admin/api/sysCfg', $value + ['cfg_type' => 'nope'])->status, '없는 유형');

        $options = "Y|사용\r\nN|사용 안 함";
        $added = $sys->request('POST', '/admin/api/sysCfg', $value + ['cfg_type' => 'dropdown', 'cfg_options' => $options]);
        $this->assertSame(201, $added->status, $added->body);
        $this->assertSame("Y|사용\nN|사용 안 함", TestDb::value("SELECT cfg_options FROM tbl_sys_cfg WHERE cmb_cfg = 'TEST_DD_ONE'"), '줄바꿈 유지');

        // 수정 화면은 바뀐 칸만 보낸다
        $edited = $sys->request('POST', '/admin/api/sysCfg?cmb_cfg=TEST_DD_ONE', ['cmb_cfg' => 'TEST_DD_ONE', 'big_cfg' => 'TEST_DD', 'sml_cfg' => 'ONE', 'cfg_name' => '값', 'cfg_type' => 'dropdown', 'cfg_val' => 'N']);
        $this->assertSame(200, $edited->status, $edited->body);
        $this->assertSame('N', TestDb::value("SELECT cfg_val FROM tbl_sys_cfg WHERE cmb_cfg = 'TEST_DD_ONE'"));

        // 값은 선택지 중 하나만. 보내지 않은 칸은 저장된 값으로 본다
        $url = '/admin/api/sysCfg?cmb_cfg=TEST_DD_ONE';
        $base = ['cmb_cfg' => 'TEST_DD_ONE', 'big_cfg' => 'TEST_DD', 'sml_cfg' => 'ONE', 'cfg_name' => '값', 'cfg_type' => 'dropdown'];
        $this->assertSame(400, $sys->request('POST', $url, $base + ['cfg_val' => 'X'])->status, '없는 값');
        $this->assertSame(400, $sys->request('POST', $url, $base + ['cfg_val' => ''])->status, '비움 선택지 없음');
        $this->assertSame(400, $sys->request('POST', $url, $base + ['cfg_options' => 'Y|사용'])->status, '저장된 값 N 이 선택지에서 빠짐');
        $this->assertSame(200, $sys->request('POST', $url, $base + ['cfg_options' => "|비움\nY|사용", 'cfg_val' => ''])->status, '비움 선택지');

        $page = $sys->request('GET', '/admin/system/sysCfg');
        $this->assertStringContainsString('value="비움"', $page->body, '탭에는 표시명');

        $this->assertSame(200, $sys->request('DELETE', '/admin/api/sysCfg/TEST_DD_ONE')->status);
        $this->assertSame(200, $sys->request('DELETE', '/admin/api/sysCfg/TEST_DD_000')->status);
    }

    public function testCategoryRules(): void
    {
        $sys = $this->systemAdmin();
        $category = ['cmb_cfg' => 'TEST_CAT_000', 'big_cfg' => 'TEST_CAT', 'sml_cfg' => '000', 'cfg_name' => '시험', 'cfg_type' => 'text', 'cfg_val' => '', 'cfg_desc' => ''];
        $this->assertSame(422, $sys->request('POST', '/admin/api/sysCfg', ['big_cfg' => 'bad key', 'cmb_cfg' => 'bad key_000'] + $category)->status, '키 형식');
        $added = $sys->request('POST', '/admin/api/sysCfg', $category);
        $this->assertSame(201, $added->status, $added->body);

        $value = ['big_cfg' => 'TEST_CAT', 'sml_cfg' => 'ONE', 'cmb_cfg' => 'TEST_CAT_ONE', 'cfg_name' => '값', 'cfg_type' => 'text', 'cfg_val' => '1'];
        $this->assertSame(201, $sys->request('POST', '/admin/api/sysCfg', $value)->status);
        $this->assertSame(422, $sys->request('PUT', '/admin/api/sysCfg/TEST_CAT_ONE', ['sml_cfg' => '000', 'cmb_cfg' => 'TEST_CAT_ONE'] + $value)->status, '자기 키로 값 행을 카테고리로');
        $this->assertSame(400, $sys->request('PUT', '/admin/api/sysCfg/TEST_CAT_ONE', ['sml_cfg' => '000', 'cmb_cfg' => 'TEST_CAT_000'] + $value)->status, '값 행을 카테고리로');
        $this->assertSame(400, $sys->request('POST', '/admin/api/sysCfg/TEST_CAT_ONE', ['sml_cfg' => '000', 'cmb_cfg' => 'TEST_CAT_000'] + $value)->status, 'POST 로 값 행을 카테고리로');
        $this->assertSame(200, $sys->request('PATCH', '/admin/api/sysCfg/TEST_CAT_ONE', ['cfg_val' => '2'] + $value)->status);
        $this->assertSame(400, $sys->request('PATCH', '/admin/api/sysCfg/SYS_USER_TOKEN_LIFETIME', ['cmb_cfg' => 'SYS_USER_TOKEN_LIFETIME', 'big_cfg' => 'SYS_USER', 'sml_cfg' => 'TOKEN_LIFETIME', 'cfg_name' => '토큰', 'cfg_type' => 'text', 'cfg_val' => 'abc'])->status, 'PATCH 값 검사');
        $this->assertSame('ONE', TestDb::value("SELECT sml_cfg FROM tbl_sys_cfg WHERE cmb_cfg = 'TEST_CAT_ONE'"));

        $sys->request('DELETE', '/admin/api/sysCfg/TEST_CAT_000');
        $this->assertSame('1', TestDb::value("SELECT COUNT(*) FROM tbl_sys_cfg WHERE cmb_cfg = 'TEST_CAT_000'"), '값이 남으면 삭제 안 됨');
        $this->assertSame(200, $sys->request('DELETE', '/admin/api/sysCfg/TEST_CAT_ONE')->status);
        $this->assertSame(200, $sys->request('DELETE', '/admin/api/sysCfg/TEST_CAT_000')->status);
    }
}
