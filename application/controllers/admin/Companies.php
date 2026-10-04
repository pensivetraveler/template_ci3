<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once __DIR__.'/Common.php';

class Companies extends Common
{
    public function __construct()
    {
        parent::__construct();

        $this->titleList[] = 'Companies Management';
        $this->addJsVars([
            'API_URI' => $this->apiUri.'companies',
            'API_PARAMS' => [
            ]
        ]);
    }
}
