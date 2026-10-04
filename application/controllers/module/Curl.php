<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once __DIR__.'/Common.php';

class Curl extends Common
{
    public function __construct()
    {
        $this->accessWays = ['ajax'];

        parent::__construct();

        $this->load->library('curl_lib');
    }

    protected function validateRequest()
    {
        $url = $this->input->post('url');
        if(!is_url_exists($url)) {
            $this->response([
                'code' => BAD_REQUEST,
                'msg' => lang('The URL is not valid.'),
                'data' => [
                    'url' => $url,
                ]
            ]);
        }

        $method = $this->input->post('method') ?? 'get';
        if(!in_array($method, ['get', 'post', 'put', 'patch', 'delete'])) {
            $this->response([
                'code' => BAD_REQUEST,
                'msg' => lang('The Method is not valid.'),
                'data' => [
                    'url' => $url,
                    'method' => $method,
                ]
            ]);
        }

        $base_url = get_origin_url($url);

        return [$url, $method, $base_url];
    }

    public function index()
    {
        list($url, $method, $base_url) = $this->validateRequest();

        $max_try = $this->input->post('max_try')?:3;
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
                ->connect_timeout($this->input->post('connect_timeout')?:10)
                ->timeout($this->input->post('timeout')?:30)
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
