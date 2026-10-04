<?php
defined('BASEPATH') or exit('No direct script access allowed');

class MY_Output extends CI_Output
{
    public function set_cors_headers($allowedOrigins = array())
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

//        if (in_array($origin, $allowedOrigins, true)) {
            header('Access-Control-Allow-Origin: ' . $origin);
            header('Vary: Origin');
//        }

        header('Access-Control-Allow-Methods: POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Accept, X-API-KEY, Authorization, X-Requested-With');
        header('Access-Control-Max-Age: 86400');
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }
}
