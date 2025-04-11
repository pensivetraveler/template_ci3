<?php defined('BASEPATH') OR exit('No direct script access allowed');

require_once __DIR__.'/Common.php';

class Dashboard extends Common
{
	public function __construct()
	{
		parent::__construct();

		$this->load->model('Model_Article');
		$this->load->model('Model_User');

		$this->titleList[] = 'Home';
		$this->href = base_url('/admin/'.$this->router->class);
		$this->viewPath = 'admin/'.$this->router->class;
	}

	public function view($key = 0)
	{
		$data['subPage'] = $this->viewPath.'/index';
		$data['backLink'] = WEB_HISTORY_BACK;

		$this->viewApp($data);
	}
}
