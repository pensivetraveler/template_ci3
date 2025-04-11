<?php defined('BASEPATH') or exit('No direct script access allowed');

require_once __DIR__.'/Common.php';

class Administrators extends Common
{
	function __construct()
	{
		parent::__construct();

		$this->load->model('Model_User', 'Model');

		$this->setProperties($this->Model);

		$this->defaultList = [
			'user_cd' => 'USR001',
			'del_yn' => 'N',
			'approve_yn' => 'Y',
			'withdraw_yn' => 'N',
		];
	}
}
