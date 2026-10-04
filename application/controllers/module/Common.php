<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Common extends MY_Controller
{
    public array $accessWays = [];

    function __construct()
    {
        parent::__construct();

        if(!$this->isValidAccess()) show_404('Incorrect access');
    }

    protected function isValidAccess(): bool
    {
        $valid = false;

        foreach (['cli', 'ajax', 'page'] as $way) {
            if($valid) continue;
            if(!in_array($way, $this->accessWays)) continue;
            switch($way) {
                case 'cli':
                    $valid = is_cli();
                    break;
                case 'ajax':
                    $transport = strtolower(
                        trim((string) $this->input->post('transport', true))
                    );
                    $is_beacon = $transport === 'beacon';
                    $is_ajax = is_ajax();
                    $valid = $is_ajax||$is_beacon;
                    break;
                case 'page':
                    $valid = is_web();
                    break;
            }
        }

        return $valid;
    }

    protected function json_response($success, $message = '', $data = [], $statusCode = 200)
    {
        $this->output
            ->set_status_header($statusCode)
            ->set_output(json_encode([
                'success' => $success,
                'message' => $message,
                'data' => $data
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return;
    }

    protected function handle_cors()
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Accept, Authorization, X-Requested-With');
        header('Access-Control-Max-Age: 86400');

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }
}
