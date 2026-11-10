<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Common extends MY_Controller_WEB
{
    function __construct()
    {
        $this->flag = 'web';
        $this->defaultController = 'home';
        $this->baseViewPath = "web/layout/index";

        parent::__construct();
    }
}
