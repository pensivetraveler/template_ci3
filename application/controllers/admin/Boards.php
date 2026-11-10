<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once __DIR__.'/Common.php';

class Boards extends Common
{
    public function __construct()
    {
        parent::__construct();

        $this->titleList[] = 'Boards Management';
        $this->addJsVars([
            'API_URI' => $this->apiUri.'boards',
            'API_PARAMS' => [
            ]
        ]);
    }
}
