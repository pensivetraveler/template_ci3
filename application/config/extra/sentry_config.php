<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| Sentry
| needs php >= 8.2.0
| $ composer require sentry/sentry
| -------------------------------------------------------------------------
*/
//Do not edit if possible, since this is the default setting.
$config['sentry_log'] = false;
$config['sentry_path'] = FCPATH . 'vendor/sentry/sentry/lib/Raven/Autoloader.php';
$config['sentry_logging_levels'] = array('INFO', 'WARNING', 'DEBUG', 'ERROR', 'FATAL');
$config['sentry_client'] = '';
$config['sentry_config'] = array();