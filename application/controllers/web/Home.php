<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once __DIR__.'/Common.php';

class Home extends Common
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $data['subPage'] = 'web/page/home';

        $this->viewApp($data);
    }
}
