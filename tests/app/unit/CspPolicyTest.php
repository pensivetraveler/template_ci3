<?php
use PHPUnit\Framework\TestCase;

class CspPolicyTest extends TestCase
{
    public function testDefaultIsUnchanged(): void
    {
        // 화면별 추가가 없을 때는 전과 글자 하나까지 같다
        $expected = implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'nonce-N' https://t1.daumcdn.net https://cdnjs.cloudflare.com",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com",
            "font-src 'self' data: https://fonts.gstatic.com https://cdnjs.cloudflare.com",
            "worker-src 'self' blob:",
            "img-src 'self' data: blob: https:",
            "connect-src 'self'",
            "frame-src 'self' https://www.youtube.com https://*.daum.net",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
        ]);
        $this->assertSame($expected, csp_policy('N'));
        $this->assertSame($expected, csp_policy('N', []));
    }

    public function testExtraAddsSources(): void
    {
        $policy = csp_policy('N', ['media-src' => ['blob:', 'https:'], 'connect-src' => ['https:', "'self'"]]);
        // 있는 지시문은 뒤에 붙고 중복은 빠진다
        $this->assertStringContainsString("connect-src 'self' https:;", $policy);
        // 없는 지시문은 default-src 에서 시작해 맨 뒤에 붙는다
        $this->assertStringEndsWith("form-action 'self'; media-src 'self' blob: https:", $policy);
        // 문자열 하나도 받는다
        $this->assertStringEndsWith("media-src 'self' blob:", csp_policy('N', ['media-src' => 'blob:']));
    }
}
