<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Common extends MY_Controller_APP
{
    function __construct()
    {
        parent::__construct();

        $this->load->library('Jwt_token', ['config' => 'extra/jwt_config']);
    }

    public function index()
    {
        $userId = $this->input->post('userId');
        $role = 'admin';
        $deviceId = $this->input->post('device_id');

        if (empty($deviceId)) {
            $deviceId = generate_uuid_v4();
        }

        $accessToken = $this->jwt_token->generateAccessToken([
            'user_id' => $userId,
            'role'    => $role,
        ]);

        $refreshToken = $this->opaque_token->generateBase64Url(64);
        $refreshTokenHash = $this->opaque_token->hash($refreshToken);

        $this->db->insert('user_refresh_token', [
            'user_id'      => $userId,
            'token_hash'   => $refreshTokenHash,
            'token_family' => generate_uuid_v4(),
            'device_id'    => $deviceId,
            'device_name'  => $this->input->post('device_name'),
            'platform'     => $this->input->post('platform'),
            'user_agent'   => $this->input->user_agent(),
            'ip_address'   => $this->input->ip_address(),
            'expires_at'   => date('Y-m-d H:i:s', strtotime('+30 days')),
            'created_dt'   => date('Y-m-d H:i:s'),
        ]);
    }
}
