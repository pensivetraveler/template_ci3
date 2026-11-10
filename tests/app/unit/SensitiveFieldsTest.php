<?php
use PHPUnit\Framework\TestCase;

class SensitiveFieldsTest extends TestCase
{
    /** @dataProvider sensitiveNames */
    public function testSensitiveNames(string $name): void
    {
        $this->assertTrue(is_sensitive_field($name));
    }

    public function sensitiveNames(): array
    {
        return array_map(function ($n) { return [$n]; }, ['password', 'password_confirm', 'PASSWORD', 'new_password', 'user_password[password]', 'token', 'user_api_key', 'api_key', 'secret', 'client_secret', 'Authorization']);
    }

    /** @dataProvider ordinaryNames */
    public function testOrdinaryNames(string $name): void
    {
        $this->assertFalse(is_sensitive_field($name));
    }

    public function ordinaryNames(): array
    {
        return array_map(function ($n) { return [$n]; }, ['id', 'name', 'email', 'tokens_used', 'secretary', '']);
    }

    public function testMaskKeepsOtherValuesAndGoesDeep(): void
    {
        $masked = mask_sensitive_fields([
            'id' => 'a',
            'password' => 'x',
            'group' => ['password_confirm' => 'y', 'name' => 'b'],
            'list' => [(object)['token' => 'z', 'n' => 1]],
        ]);

        $this->assertSame('a', $masked['id']);
        $this->assertSame('***', $masked['password']);
        $this->assertSame(['password_confirm' => '***', 'name' => 'b'], $masked['group']);
        $this->assertSame(['token' => '***', 'n' => 1], $masked['list'][0]);
    }

    public function testMaskLeavesScalars(): void
    {
        $this->assertSame('text', mask_sensitive_fields('text'));
        $this->assertSame(1, mask_sensitive_fields(1));
    }
}
