<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once __DIR__.'/Common.php';

class Company extends Common
{
    public function __construct()
    {
        parent::__construct();

        $this->titleList[] = 'Company Management';
        $this->addJsVars([
            'API_URI' => '/api/company',
            'API_PARAMS' => [
            ]
        ]);
    }
}
