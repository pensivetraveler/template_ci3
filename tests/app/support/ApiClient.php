<?php
/**
 * 시험 서버 HTTP 요청. 사용자마다 쿠키를 따로 둔다 (운영 모드의 Secure 쿠키도 그대로 보낸다).
 */
class ApiClient
{
    const BASE = 'http://127.0.0.1:8081';

    public array $cookies = [];
    public string $token = '';

    /**
     * @param array $env 이 요청에서만 쓸 환경 변수 (tests/app/router.php 가 적용)
     */
    public function request(string $method, string $path, array $data = [], array $env = []): ApiResponse
    {
        $headers = ['X-Requested-With: XMLHttpRequest'];
        if ($this->token) $headers[] = 'Authorization: '.$this->token;
        if ($this->cookies) $headers[] = 'Cookie: '.implode('; ', array_map(function ($k, $v) { return "$k=$v"; }, array_keys($this->cookies), $this->cookies));
        if ($env) $headers[] = 'X-Test-Env: '.base64_encode(json_encode($env));

        $options = ['method' => $method, 'ignore_errors' => true, 'follow_location' => 0, 'timeout' => 30];
        if ($data) {
            $headers[] = 'Content-Type: application/x-www-form-urlencoded';
            $options['content'] = http_build_query($data);
        }
        $options['header'] = implode("\r\n", $headers);

        $body = file_get_contents(self::BASE.$path, false, stream_context_create(['http' => $options]));
        $responseHeaders = $http_response_header ?? [];

        foreach ($responseHeaders as $line) {
            if (preg_match('/^Set-Cookie:\s*([^=]+)=([^;]*)/i', $line, $m)) {
                if ($m[2] === 'deleted' || $m[2] === '') unset($this->cookies[$m[1]]);
                else $this->cookies[$m[1]] = $m[2];
            }
        }
        return new ApiResponse($responseHeaders, (string)$body);
    }
}

class ApiResponse
{
    public int $status = 0;
    public array $headers;
    public string $body;
    public ?array $json;

    public function __construct(array $headers, string $body)
    {
        $this->headers = $headers;
        $this->body = $body;
        foreach ($headers as $line) {
            if (preg_match('#^HTTP/\S+\s+(\d+)#', $line, $m)) $this->status = (int)$m[1];
        }
        $this->json = json_decode($body, true);
    }

    public function code(): ?int
    {
        return $this->json['code'] ?? null;
    }

    public function errorTypes(): array
    {
        return array_map(function ($e) { return ($e['param'] ?? '').':'.($e['type'] ?? ''); }, $this->json['errors'] ?? []);
    }

    public function reason(): ?string
    {
        return $this->json['errors'][0]['param']['reason'] ?? null;
    }
}
