<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once __DIR__.'/Common.php';

class Cli extends Common
{
    public function __construct()
    {
        parent::__construct();

        if (php_sapi_name() !== 'cli') {
            show_error('Direct access is not allowed');
        }
    }
}
