<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once __DIR__.'/Common.php';

class Administrators extends Common
{
    public function __construct()
    {
        parent::__construct();

        $this->titleList[] = 'Administrators Management';
        $this->addJsVars([
            'API_URI' => $this->apiUri.'administrators',
            'API_PARAMS' => [
                'user_cd' => 'USR001'
            ]
        ]);
    }
}
