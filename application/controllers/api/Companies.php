<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once __DIR__.'/Common.php';

class Companies extends Common
{
    function __construct()
    {
        parent::__construct();

        $this->routeTitle = '업체';

        $this->validateModel('Model_Company', 'Model');

        $this->setProperties($this->Model);
    }
}
