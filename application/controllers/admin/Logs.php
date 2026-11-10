<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once __DIR__.'/Common.php';

class Logs extends Common
{
    public function __construct()
    {
        parent::__construct();
    }

    public function userAccess()
    {
        $this->titleList[] = 'UserAccess';
        $this->addJsVars([
            'API_URI' => $this->apiUri.'logs/userAccess',
            'API_PARAMS' => [
            ],
        ]);

        $this->addJS['tail'][] = [
            base_url('public/assets/builder/js/class/CommonLogList.js'),
        ];

        $this->list();
    }

    public function errors()
    {
        $this->titleList[] = 'ErrorLogs';
        $this->addJsVars([
            'API_URI' => $this->apiUri.'logs/errors',
            'API_PARAMS' => [
            ],
        ]);

        $this->addJS['tail'][] = [
            base_url('public/assets/builder/js/class/CommonLogList.js'),
        ];

        $this->list();
    }

    protected function prepareListData($data): array
    {
        $data['subPage'] = 'builder/logs/'.snakeize($this->router->method);

        return parent::prepareListData($data);
    }
}
