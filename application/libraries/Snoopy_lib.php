<?php
defined('BASEPATH') or exit('No direct script access allowed');

// Include PHP Third Party files
require_once APPPATH . '/third_party/Snoopy/Snoopy.php';

class Snoopy_lib extends Snoopy
{
    private string $platform;
    private object $obj;

    function __construct()
    {
        parent::__construct();
        log_message('Debug', 'Snoopy Library is loaded.');

        $this->platform = '';
        $this->obj = null;
    }

    function load()
    {
        log_message('Debug', 'Third Party Snoopy is loaded newly.');
        return $this->obj = new PHPExcel();
    }

    function parse($url, $regexp)
    {
        $o = '';
        $txt = $this->obj->fetch($url);
        preg_match_all($regexp, $txt, $o);
        return $o[0][0];
    }
}