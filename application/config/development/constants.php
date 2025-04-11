<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| Custom Constants
| -------------------------------------------------------------------------
*/
foreach (glob(APPPATH . 'config/extra/*_constants.php') as $file) {
    require_once $file;
}