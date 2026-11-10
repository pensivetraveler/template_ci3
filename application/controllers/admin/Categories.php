<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once __DIR__.'/Common.php';

class Categories extends Common
{
    public function __construct()
    {
        parent::__construct();

        $this->titleList[] = 'Categories Management';
        $this->addJsVars([
            'API_URI' => $this->apiUri.'categories',
            'API_PARAMS' => [
            ]
        ]);
    }
}
