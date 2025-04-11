<?php defined('BASEPATH') or exit('No direct script access allowed');

require_once __DIR__.'/Common.php';

class Users extends Common
{
	function __construct()
	{
		parent::__construct();

		$this->load->model('Model_User', 'Model');

		$this->setProperties($this->Model);
	}
}
