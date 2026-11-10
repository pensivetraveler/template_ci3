<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once __DIR__.'/Common.php';

/**
 * 관리자 API 설정 화면(apis_preset.js)의 외부 API 샘플 호출
 *
 * 서버가 요청받은 주소를 대신 호출하므로 SSRF·공개 프록시가 되지 않도록
 * - 관리자 로그인 세션 필수
 * - http/https 공개 주소만 (사설·예약·루프백·링크로컬(메타데이터) 주소 거부, 조회한 IP로만 접속, 리디렉션 안 따라감)
 * - 재시도·타임아웃 상한
 */
class Curl extends Common
{
    const MAX_TRY = 3;
    const MAX_TIMEOUT = 30;
    const MAX_CONNECT_TIMEOUT = 10;

    public function __construct()
    {
        $this->accessWays = ['ajax'];

        parent::__construct();

        if (!$this->session->userdata('token') || !$this->session->userdata('is_admin')) {
            $this->json_response(false, 'Login Needed', [], 401);
        }

        $this->load->library('curl_lib');
    }

    protected function badRequest($msg, array $data = [])
    {
        $this->response([
            'code' => BAD_REQUEST,
            'msg' => $msg,
            'data' => $data,
        ]);
    }

    /**
     * 공개 인터넷 주소인지 확인하고, 접속에 쓸 IP를 돌려준다 (DNS rebinding 방지를 위해 이 IP로만 접속)
     *
     * @return array [host, port, ip]
     */
    protected function resolvePublicTarget($url): array
    {
        $parts = parse_url((string)$url);
        $scheme = strtolower($parts['scheme'] ?? '');
        $host = $parts['host'] ?? '';

        if (!in_array($scheme, ['http', 'https'], true) || $host === '' || isset($parts['user']) || isset($parts['pass'])) {
            $this->badRequest(lang('The URL is not valid.'), ['url' => $url]);
        }

        $port = (int)($parts['port'] ?? ($scheme === 'https' ? 443 : 80));
        $host = trim($host, '[]');

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            $ips = [$host];
        } else {
            $ips = [];
            foreach (@dns_get_record($host, DNS_A | DNS_AAAA) ?: [] as $record) {
                if (!empty($record['ip'])) $ips[] = $record['ip'];
                if (!empty($record['ipv6'])) $ips[] = $record['ipv6'];
            }
            if (empty($ips) && ($ip = gethostbyname($host)) !== $host) $ips = [$ip];
        }

        if (empty($ips)) {
            $this->badRequest(lang('The URL is not valid.'), ['url' => $url]);
        }

        // 하나라도 내부 주소로 풀리면 거부 (IPv4가 들어간 IPv6 주소 ::ffff:a.b.c.d 는 IPv4로 다시 검사)
        foreach ($ips as $ip) {
            if (preg_match('/^::ffff:(\d{1,3}(?:\.\d{1,3}){3})$/i', $ip, $mapped)) $ip = $mapped[1];
            if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                $this->badRequest('Internal addresses are not allowed.', ['url' => $url]);
            }
        }

        return [$host, $port, $ips[0]];
    }

    protected function validateRequest()
    {
        $url = (string)$this->input->post('url');

        list($host, $port, $ip) = $this->resolvePublicTarget($url);

        $method = $this->input->post('method') ?? 'get';
        if(!in_array($method, ['get', 'post', 'put', 'patch', 'delete'], true)) {
            $this->badRequest(lang('The Method is not valid.'), [
                'url' => $url,
                'method' => $method,
            ]);
        }

        $base_url = get_origin_url($url);

        return [$url, $method, $base_url, $host, $port, $ip];
    }

    protected function boundedInt($name, $default, $max): int
    {
        $value = (int)$this->input->post($name);
        if ($value <= 0) $value = $default;

        return min($value, $max);
    }

    public function index()
    {
        list($url, $method, $base_url, $host, $port, $ip) = $this->validateRequest();

        $max_try = $this->boundedInt('max_try', self::MAX_TRY, self::MAX_TRY);
        $connect_timeout = $this->boundedInt('connect_timeout', self::MAX_CONNECT_TIMEOUT, self::MAX_CONNECT_TIMEOUT);
        $timeout = $this->boundedInt('timeout', self::MAX_TIMEOUT, self::MAX_TIMEOUT);
        $resolve = $host . ':' . $port . ':' . (strpos($ip, ':') !== false ? '[' . $ip . ']' : $ip);
        $response = null;

        for ($i = 1; $i <= $max_try; $i++) {
            $response = $this->curl_lib
                ->create($url)
                ->user_agent('Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36')
                ->http_header('Accept', 'application/json, text/plain, */*')
                ->http_header('Referer', "$base_url/")
                ->http_header('Origin', $base_url)
                ->http_header('Accept-Language', 'ko-KR,ko;q=0.9,en-US;q=0.8,en;q=0.7')
                ->http_header('Cache-Control', 'no-cache')
                ->connect_timeout($connect_timeout)
                ->timeout($timeout)
                ->option(CURLOPT_FOLLOWLOCATION, false)
                ->option(CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS)
                ->option(CURLOPT_RESOLVE, [$resolve])
                ->{$method}()
                ->execute_with_meta(false);

            if ((int) $response['status_code'] === 200) {
                break;
            }

            usleep(500000 * $i); // 0.5초, 1초, 1.5초
        }

        $this->output
            ->set_status_header(200)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
            ->_display();
        exit;
    }
}
