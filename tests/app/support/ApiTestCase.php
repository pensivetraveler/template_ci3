<?php
use PHPUnit\Framework\TestCase;

/**
 * 통합 시험 공통: 계정 만들기, 로그인, 권한 넣기. 만든 값은 시험마다 고유한 이름을 쓴다.
 */
abstract class ApiTestCase extends TestCase
{
    const PASSWORD = 'Tt1!testPass';

    private static int $seq = 0;
    private static array $clients = [];

    protected function setUp(): void
    {
        // 요청 횟수 제한은 시험마다 비운다
        TestDb::exec('DELETE FROM tbl_user_limits');
    }

    protected static function uniqueId(string $prefix = 'u'): string
    {
        return $prefix.getmypid().'x'.(++self::$seq);
    }

    /**
     * 계정 행을 직접 만든다. 돌려주는 값은 [id, user_id]
     */
    protected static function makeUser(string $grade, array $columns = []): array
    {
        $id = self::uniqueId();
        $set = array_merge([
            'user_cd' => $grade,
            'id' => $id,
            'password' => password_hash(self::PASSWORD, PASSWORD_BCRYPT),
            'name' => '시험',
            'email' => $id.'@example.test',
            'tel' => '010'.str_pad((string)self::$seq, 8, '0', STR_PAD_LEFT),
            'withdraw_yn' => 'N',
            'del_yn' => 'N',
            'created_id' => 1,
        ], $columns);
        $values = array_map(function ($v) { return $v === null ? 'NULL' : "'".TestDb::esc((string)$v)."'"; }, $set);
        TestDb::exec('INSERT INTO tbl_user ('.implode(',', array_keys($set)).') VALUES ('.implode(',', $values).')');
        return [$set['id'], (int)TestDb::conn()->insert_id];
    }

    protected function login(string $id, string $password = self::PASSWORD, array $env = []): ApiClient
    {
        $client = new ApiClient();
        $client->request('GET', '/admin/auth', [], $env);
        $response = $client->request('POST', '/admin/api/auth/login', ['id' => $id, 'password' => $password], $env);
        $this->assertSame(200, $response->status, 'login '.$id.' : '.$response->body);
        $client->token = (string)TestDb::value("SELECT t.token FROM tbl_user_token t JOIN tbl_user u ON u.user_id = t.user_id WHERE u.id = '".TestDb::esc($id)."'");
        return $client;
    }

    protected function systemAdmin(): ApiClient
    {
        if (!isset(self::$clients['sys'])) {
            [$id] = self::makeUser('USR000');
            self::$clients['sys'] = $this->login($id);
        }
        return self::$clients['sys'];
    }

    protected static function menuId(string $class, string $method = 'index'): int
    {
        return (int)TestDb::value("SELECT menu_id FROM tbl_menu WHERE class = '$class' AND method = '$method'");
    }

    /**
     * 등급에 메뉴 권한을 넣는다 (상위 메뉴도 보이게)
     */
    protected static function grant(string $grade, string $class, string $auth = '111111', string $method = 'index'): void
    {
        $row = TestDb::row("SELECT menu_id, parent_id FROM tbl_menu WHERE class = '$class' AND method = '$method'");
        foreach (array_filter([$row['parent_id'], $row['menu_id']]) as $menuId) {
            $value = (int)$menuId === (int)$row['menu_id'] ? $auth : '111111';
            TestDb::exec("REPLACE INTO tbl_menu_auth (menu_id, grade_cd, is_show, menu_auth, scope_cd) VALUES ($menuId, '$grade', 1, '$value', 'SCP001')");
        }
    }

    protected function setUserCfg(array $values): void
    {
        foreach ($values as $sml => $value) {
            $key = 'USER_'.$sml;
            TestDb::exec("REPLACE INTO tbl_sys_cfg (cmb_cfg, big_cfg, sml_cfg, cfg_name, cfg_val, cfg_type, created_id) VALUES ('$key', 'USER', '$sml', '$sml', '".TestDb::esc($value)."', 'text', 1)");
        }
    }

    protected function clearUserCfg(): void
    {
        TestDb::exec("DELETE FROM tbl_sys_cfg WHERE big_cfg = 'USER'");
    }

    protected function assertNoPlainPassword(string $password, ApiResponse $response): void
    {
        $this->assertStringNotContainsString($password, $response->body, '응답에 평문 비밀번호');
        $this->assertSame('0', (string)TestDb::value("SELECT COUNT(*) FROM tbl_rest_logs WHERE params LIKE '%".TestDb::esc($password)."%'"), '요청 로그에 평문 비밀번호');
    }

    protected static function devMode(): bool
    {
        return getenv('TEST_CI_ENV') !== 'production';
    }
}
