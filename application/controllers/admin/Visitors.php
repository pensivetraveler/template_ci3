<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once __DIR__.'/Common.php';

class Visitors extends Common
{
    public function __construct()
    {
        parent::__construct();

        $this->titleList[] = 'Visitors Management';
        $this->addJsVars([
            'API_URI' => $this->apiUri.'visitors',
            'API_PARAMS' => [
            ]
        ]);
    }
}
