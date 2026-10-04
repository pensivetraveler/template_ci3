<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once __DIR__.'/Common.php';

class Logger extends Common
{
    protected string $baseUrlHost = '';

    public function __construct()
    {
        $this->accessWays = ['ajax'];

        parent::__construct();

        $this->lang->load('status_code', $this->config->item('language'));

        $this->load->library('mobile_detect');
        $this->load->library('geo_ip');

        $base = parse_url(base_url());
        $this->baseUrlHost = $base['host'];
    }

    public function visit()
    {
        $this->load->model('Model_Visitor', 'Model');

        $uaData = $this->getUserAgentData(true);

        $now = date('Y-m-d H:i:s');
        $visitDate = date('Y-m-d');
        $visitTime = date('H:i:s');
        $visitKey = hash('sha256', implode('|', [
            $uaData['ip'],
            $uaData['agent'],
            $visitDate,
        ]));

        $data = $this->Model->getData([], [
            'where' => [
                'vi_visit_key' => $visitKey,
            ]
        ]);

        if($data) {
            $this->Model->modNumb('vi_hit_count', 1, [
                'vi_visit_key' => $visitKey,
            ]);
        }else{
            $this->Model->addData([
                'vi_visit_key' => $visitKey,
                'vi_first_at'  => $now,
                'vi_last_at'   => $now,

                'vi_ip'        => $uaData['ip'],
                'vi_date'      => $visitDate,
                'vi_time'      => $visitTime,

                'vi_referrer'   => $uaData['referer'] ?? $this->input->post('referrer', true),

                'vi_agent'     => mb_substr($uaData['agent'], 0, 3000, 'UTF-8'),
                'vi_browser'   => $uaData['browser'],
                'vi_os'        => $uaData['os'],
                'vi_device'    => $uaData['device'],

                'vi_location'  => $uaData['location'],
            ]);
        }

        $this->response([
            'code' => DATA_PROCESSED
        ]);
    }

    public function visit_frame()
    {
        $this->load->model('Model_Frame_Visitor', 'Model');

        $uuid = $this->input->post('uuid');
        if (!$uuid) {
            $this->response([
                'code' => BAD_REQUEST,
                'msg'  => 'ID가 존재하지 않습니다.',
            ]);
        }

        $data = $this->validateAccess([
            'uuid' => $uuid,
            'sourceDomain' => trim((string)$this->input->post('sourceDomain', true)),
        ], true);

        $this->Model->addData([
            'company_id'   => $data['company_id'],
            'frame_id'     => $data['frame_id'],
            'domain'       => $data['domain'],

            'vi_ip'        => $data['ip'],
            'vi_agent'     => $data['agent'],
            'vi_browser'   => $data['browser'],
            'vi_os'        => $data['os'],
            'vi_device'    => $data['device'],
            'vi_location'  => $data['location'],

            'vi_date'      => date('Y-m-d'),
            'vi_time'      => date('H:i:s'),
            'vi_referrer'  => $this->input->post('referrer', true),
        ]);

        $this->response([
            'code' => DATA_PROCESSED
        ]);
    }

    public function traffic()
    {
        $this->load->model('Model_Frame_Traffic', 'Model');

        if ($this->input->method(TRUE) !== 'POST') {
            $this->response([
                'code' => METHOD_NOT_ALLOWED,
            ]);
        }

        $session_uuid = trim( (string) $this->input->post('session_uuid', true) );

        if ( !preg_match('/^[a-f0-9-]{36}$/i', $session_uuid) ) {
            $this->response([
                'code' => BAD_REQUEST,
                'msg' => 'Invalid session UUID',
            ]);
        }

        $parsed = $this->parsePageUrl();

        $data = $this->validateAccess([
            'uuid' => $parsed['targetId'],
            'sourceDomain' => $parsed['sourceDomain'],
        ]);

        $this->Model->addTrafficData([
            'company_id'            => $data['company_id'],
            'frame_id'              => $data['frame_id'],
            'domain'                => $data['domain'],

            'session_uuid'          => $session_uuid,
            'source_url'            => trim( (string) $this->input->post('source_url', true) ),
            'peer_id'               => trim( (string) $this->input->post('peer_id', true) ),
            'tracker_connected'     => to_boolean_integer($this->input->post('tracker_connected')),
            'peer_count'            => max(0, (int) $this->input->post('peer_count')),
            'http_downloaded_kb'    => to_positive_number($this->input->post('http_downloaded_kb')),
            'p2p_downloaded_kb'     => to_positive_number($this->input->post('p2p_downloaded_kb')),
            'p2p_uploaded_kb'       => to_positive_number($this->input->post('p2p_uploaded_kb')),
            'p2p_speed_kbps'        => to_positive_number($this->input->post('p2p_speed_kbps')),

            'is_final'              => to_boolean_integer($this->input->post('is_final')),
            'final_reason'          => trim( (string) $this->input->post('final_reason', true) ),
            'error_code'            => trim( (string) $this->input->post('error_code', true) ),
            'error_message'         => trim( (string) $this->input->post('error_message', true) ),

            'client_ip'             => $data['ip'],
            'user_agent'            => $data['agent'],
        ]);

        $this->response([
            'code' => DATA_PROCESSED
        ]);
    }

    protected function validateAccess($data, $needLocationInfo = false): array
    {
        $this->load->model('Model_Frame');
        $this->load->model('Model_Domain');

        $frameData = $this->Model_Frame->getData([], [
            'where' => [
                'frame_uuid' => $data['uuid'],
            ]
        ]);

        if (!$frameData) {
            $this->response([
                'code' => BAD_REQUEST,
                'msg'  => '데이터가 존재하지 않습니다.',
            ]);
        }

        $paresd = parse_url($data['sourceDomain']);

        $domainList = $this->Model_Domain->getList([], [
            'where' => [
                'frame_id' => $frameData->frame_id,
            ]
        ]);

        $host = $paresd['host'] ?? $paresd['path'];

        $matched = array_filter($domainList, function ($item) use ($host) {
            return $item->domain === $host;
        });

        $base = parse_url(base_url());
        if (empty($matched) && !in_array($host, [$base['host'], 'localhost'])) {
            $this->response([
                'code' => BAD_REQUEST,
                'msg'  => '접근이 허용되지 않습니다.',
            ]);
        }

        return array_merge(
            [
                'company_id' => $frameData->company_id,
                'frame_id' => $frameData->frame_id,
                'domain' => $host,
            ],
            $this->getUserAgentData($needLocationInfo)
        );
    }

    protected function getUserAgentData($needLocationInfo = false): array
    {
        $agentInfo = $this->mobile_detect->get_agent_info();
        $location = $needLocationInfo ? $this->geo_ip->lookup($agentInfo['ip']) : '';

        return [
            'ip' => $agentInfo['ip'],
            'agent' => mb_substr($agentInfo['agent'], 0, 3000, 'UTF-8'),
            'browser' => $agentInfo['browser'],
            'os' => $agentInfo['os'],
            'device' => $agentInfo['device'],
            'referer' => $agentInfo['referer'],
            'location' => $location['city_name'] ?? '',
        ];
    }

    protected function parsePageUrl(): array
    {
        $page_url = trim( (string) $this->input->post('page_url', true) );
        $parsed = parse_url($page_url);
        $query = $parsed['query'];
        $exploded = explode('&', $query);
        $result = [];
        foreach ($exploded as $item) {
            $items = explode('=', $item);
            $result[$items[0]] = $items[1];
        }

        return $result;
    }
}
