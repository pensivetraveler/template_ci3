<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once __DIR__.'/Common.php';

class SysCfg extends Common
{
    function __construct()
    {
        parent::__construct();

        $this->load->model('Model_Sys_Cfg', 'Model');

        $this->setProperties($this->Model);

        if($this->flag !== 'admin') show_404();
    }
}