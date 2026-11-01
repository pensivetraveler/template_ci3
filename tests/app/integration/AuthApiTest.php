<?php
/**
 * /api/auth 공개 범위와 회원가입
 */
class AuthApiTest extends ApiTestCase
{
    const NOT_REGISTERED = 4044;

    public function testApiAuthIsClosedByDefault(): void
    {
        foreach (['login', 'findId', 'findPassword', 'resetPassword'] as $method) {
            $response = (new ApiClient())->request('POST', '/api/auth/'.$method, ['id' => 'x', 'password' => 'y']);
            $this->assertSame(self::NOT_REGISTERED, $response->code(), $method);
        }
        $this->assertSame(self::NOT_REGISTERED, (new ApiClient())->request('GET', '/api/auth/dupCheck?key=id&value=x')->code());
        // 로그인이 필요한 API 는 세션 검사가 먼저라 401
        $this->assertSame(401, (new ApiClient())->request('POST', '/api/auth/passwordCheck', ['password' => 'y'])->status);
    }

    public function testUrlCaseDoesNotBypass(): void
    {
        $this->assertSame(self::NOT_REGISTERED, (new ApiClient())->request('POST', '/api/Auth/login', ['id' => 'x', 'password' => 'y'])->code());
    }

    public function testWebAuthApiOpensIt(): void
    {
        [$id] = self::makeUser('USR003');
        $response = (new ApiClient())->request('POST', '/api/auth/login', ['id' => $id, 'password' => self::PASSWORD], ['WEB_AUTH_API' => 'TRUE']);
        $this->assertSame(200, $response->status, $response->body);
    }

    public function testAdminAuthKeepsWorking(): void
    {
        [$id] = self::makeUser('USR001');
        $this->assertInstanceOf(ApiClient::class, $this->login($id));
    }

    public function testSignupIsClosedWithoutSetting(): void
    {
        $this->assertSame(self::NOT_REGISTERED, (new ApiClient())->request('POST', '/api/auth/signup', $this->signupData())->code());
    }

    public function testSignupForcesConfiguredGrade(): void
    {
        $data = $this->signupData(['user_cd' => 'USR000']);
        $response = (new ApiClient())->request('POST', '/api/auth/signup', $data, ['SIGNUP_USER_CD' => 'USR003']);

        $this->assertSame(201, $response->status, $response->body);
        $this->assertSame('USR003', TestDb::value("SELECT user_cd FROM tbl_user WHERE id = '{$data['id']}'"));
        $this->assertNoPlainPassword($data['password'], $response);
        $this->login($data['id'], $data['password']);
    }

    public function testSignupRejectsMissingPasswordAndDuplicates(): void
    {
        $env = ['SIGNUP_USER_CD' => 'USR003'];
        $data = $this->signupData();
        unset($data['password'], $data['password_confirm']);
        $this->assertSame(422, (new ApiClient())->request('POST', '/api/auth/signup', $data, $env)->status);

        [$existing] = self::makeUser('USR003');
        $duplicate = $this->signupData(['id' => $existing]);
        $this->assertSame(422, (new ApiClient())->request('POST', '/api/auth/signup', $duplicate, $env)->status);
    }

    public function testSignupErrorsShowLabelsNotLanguageKeys(): void
    {
        $data = $this->signupData();
        unset($data['password']);
        $response = (new ApiClient())->request('POST', '/api/auth/signup', $data, ['SIGNUP_USER_CD' => 'USR003']);
        $this->assertSame(422, $response->status);
        $messages = implode(' ', array_column($response->json['errors'], 'msg'));
        $this->assertStringNotContainsString('user.', $messages, $messages);
        $this->assertStringContainsString('비밀번호', $messages, $messages);
    }

    public function testSignupNotOpenOnAdminPath(): void
    {
        $response = (new ApiClient())->request('POST', '/admin/api/auth/signup', $this->signupData(), ['SIGNUP_USER_CD' => 'USR003']);
        $this->assertSame(404, $response->status);
    }

    public function testPasswordCheckNeedsLoginAndIsLimited(): void
    {
        $this->assertSame(401, (new ApiClient())->request('POST', '/admin/api/auth/passwordCheck', ['password' => 'x'])->status);

        [$id] = self::makeUser('USR001');
        $client = $this->login($id);
        $this->assertSame(200, $client->request('POST', '/admin/api/auth/passwordCheck', ['password' => self::PASSWORD])->status);
        for ($i = 0; $i < 4; $i++) {
            $this->assertSame(4043, $client->request('POST', '/admin/api/auth/passwordCheck', ['password' => 'wrong'])->code());
        }
        $this->assertSame(401, $client->request('POST', '/admin/api/auth/passwordCheck', ['password' => self::PASSWORD])->status, '6번째는 제한');
    }

    private function signupData(array $override = []): array
    {
        $id = self::uniqueId('su');
        return array_merge([
            'id' => $id,
            'password' => 'Ss1!signupPw',
            'password_confirm' => 'Ss1!signupPw',
            'name' => '가입',
            'email' => $id.'@example.test',
            'tel' => '01055550000',
        ], $override);
    }
}
