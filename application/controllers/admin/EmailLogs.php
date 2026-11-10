<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once __DIR__.'/Common.php';

class EmailLogs extends Common
{
    public function __construct()
    {
        parent::__construct();

        $this->titleList[] = 'EmailLogs';
        $this->addJsVars([
            'API_URI' => $this->apiUri.'emailLogs',
        ]);
    }
}