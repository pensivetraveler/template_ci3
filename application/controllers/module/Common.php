<?php defined('BASEPATH') or exit('No direct script access allowed');

class Common extends MY_Controller
{
    function __construct()
    {
        parent::__construct();

        if (php_sapi_name() !== 'cli') {
            show_error('Direct access is not allowed');
        }
    }
}
