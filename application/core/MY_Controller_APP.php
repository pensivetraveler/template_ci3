<?php
defined('BASEPATH') or exit('No direct script access allowed');

class MY_Controller_APP extends MY_Controller_WEB
{
    public function __construct()
    {
        parent::__construct();

        $this->baseViewPath = 'app/layout/index';
    }

    protected function setTitleList($data = [])
    {
        $this->titleList = $data?:[$this->categoryTitle, $this->headerTitle];
    }
}