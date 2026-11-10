<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once __DIR__.'/Common.php';

class Partners extends Common
{
    public function __construct()
    {
        parent::__construct();

        $this->titleList[] = 'Partners Management';
        $this->addJsVars([
            'API_URI' => $this->apiUri.'partners',
            'API_PARAMS' => [
//                'user_cd' => 'USR001'
            ]
        ]);
    }
}
