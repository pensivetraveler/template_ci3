<?php
/**
 * 플랫폼 첫 주소(/admin): 로그인 전이면 로그인 화면, 로그인 뒤면 첫 화면으로 보낸다.
 * 'Incorrect Access' alert 를 띄우면 안 된다 (최초 관리자 등록 뒤 /admin 으로 이동하면서 나던 것)
 */
class PlatformRootTest extends ApiTestCase
{
    private function location(ApiResponse $response): string
    {
        foreach ($response->headers as $line) {
            if (preg_match('/^Location:\s*(\S+)/i', $line, $m)) return $m[1];
        }
        return '';
    }

    public function testAdminRootRedirectsByLogin(): void
    {
        $guest = new ApiClient();
        $response = $guest->request('GET', '/admin');
        $this->assertStringNotContainsString('alert(', $response->body);
        $this->assertStringEndsWith('/admin/auth', $this->location($response));

        $response = $this->systemAdmin()->request('GET', '/admin');
        $this->assertStringNotContainsString('alert(', $response->body);
        $this->assertStringEndsWith('/admin/dashboard', $this->location($response));
    }
}
