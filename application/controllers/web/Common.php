<?php defined('BASEPATH') or exit('No direct script access allowed');

class Common extends MY_Builder_WEB
{
	function __construct()
	{
		$this->flag = 'web';

		parent::__construct();
	}
}
