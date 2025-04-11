<?php
defined('BASEPATH') or exit('No direct script access allowed');

// Include PHP Third Party files
require_once APPPATH . '/third_party/PHPtoJS/PHPtoJS.php';

class PHPtoJS_lib extends PHPtoJS
{
    function __construct()
    {
        parent::__construct();
        log_message('Debug', 'Snoopy Library is loaded.');
    }

    function load()
    {
        log_message('Debug', 'Third Party Snoopy is loaded newly.');
        return new PHPtoJS();
    }
}