<?php
/**
 * 회원 설정과 아이디·비밀번호 찾기
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

    public function testShowModeRejectedWhileNotDecryptable(): void
    {
        $response = $this->systemAdmin()->request('POST', '/admin/api/userCfg/save', ['signup_email_yn' => 'Y', 'signup_tel_yn' => 'Y', 'password_find_mode' => 'show']);
        $this->assertSame(400, $response->status, $response->body);
    }

    public function testSettingsRequireSystemAdmin(): void
    {
        [$id] = self::makeUser('USR001');
        $data = ['signup_email_yn' => 'N', 'signup_tel_yn' => 'N', 'password_find_mode' => ''];
        $this->assertSame(403, $this->login($id)->request('POST', '/admin/api/userCfg/save', $data)->status);
        $this->assertSame(401, (new ApiClient())->request('POST', '/admin/api/userCfg/save', $data)->status);
        $this->assertSame(200, $this->systemAdmin()->request('POST', '/admin/api/userCfg/save', $data)->status);
    }

    public function testLogKeepDaysValidation(): void
    {
        $data = ['signup_email_yn' => 'Y', 'signup_tel_yn' => 'Y', 'password_find_mode' => '', 'log_keep_days' => 'abc'];
        $this->assertSame(400, $this->systemAdmin()->request('POST', '/admin/api/userCfg/save', $data)->status);
    }
}
