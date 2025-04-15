<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once __DIR__ . '/Common.php';

class Auth extends Common
{
    public function __construct()
    {
        parent::__construct();
    }

    public function login()
    {
		if($this->isLogin) redirect($this->isLoginRedirect);

		$this->formColumns = $this->setFormColumns('login');
		$this->addJsVars([
			'API_URI' => $this->apiUri.'auth',
			'API_URI_ADD' => 'login',
			'FORM_DATA' => $this->setFormData(),
			'FORM_REGEXP' => $this->config->item('regexp'),
			'REDIRECT_URI' => '/admin/dashboard'
		]);

		$this->addCSS[] = [
			base_url('public/assets/builder/vendor/css/pages/page-auth.css'),
			base_url('public/assets/builder/vendor/libs/@form-validation/form-validation.css'),
			base_url('public/assets/builder/vendor/libs/bootstrap-maxlength/bootstrap-maxlength.css'),
		];

		$this->addJS['tail'][] = [
			base_url('public/assets/builder/vendor/libs/@form-validation/popular.js'),
			base_url('public/assets/builder/vendor/libs/@form-validation/bootstrap5.js'),
			base_url('public/assets/builder/vendor/libs/@form-validation/auto-focus.js'),
			base_url('public/assets/builder/vendor/libs/bootstrap-maxlength/bootstrap-maxlength.js'),
		];

		$this->addJS['tail'][] = [
			base_url('public/assets/builder/js/app-page-auth.js'),
		];

		$data['subPage'] = 'admin/auth/login';
		$data['backLink'] = WEB_HISTORY_BACK;
		$data['formData'] = restructure_admin_form_data($this->jsVars['FORM_DATA'], $this->sideForm?'side':'page');

		$this->viewApp($data);
    }

    public function logout()
    {
        if(!$this->isLogin) redirect($this->noLoginRedirect);

        $this->destroyUserData();

        redirect($this->noLoginRedirect);
    }

	public function findId()
	{
		if($this->isLogin) redirect($this->isLoginRedirect);

		$this->formColumns = $this->setFormColumns('find_id');
		$this->addJsVars([
			'API_URI' => $this->apiUri.'auth/',
			'API_URI_ADD' => 'findId',
			'FORM_DATA' => $this->setFormData(),
			'FORM_REGEXP' => $this->config->item('regexp'),
			'REDIRECT_URI' => '/admin/auth/login'
		]);

		$this->addCSS[] = [
			base_url('public/assets/builder/vendor/css/pages/page-auth.css'),
			base_url('public/assets/builder/vendor/libs/@form-validation/form-validation.css'),
			base_url('public/assets/builder/vendor/libs/bootstrap-maxlength/bootstrap-maxlength.css'),
		];

		$this->addJS['tail'][] = [
			base_url('public/assets/builder/vendor/libs/@form-validation/popular.js'),
			base_url('public/assets/builder/vendor/libs/@form-validation/bootstrap5.js'),
			base_url('public/assets/builder/vendor/libs/@form-validation/auto-focus.js'),
			base_url('public/assets/builder/vendor/libs/bootstrap-maxlength/bootstrap-maxlength.js'),
		];

		$this->addJS['tail'][] = [
			base_url('public/assets/builder/js/app-page-auth.js'),
		];

		$data['subPage'] = 'admin/auth/find_id';
		$data['backLink'] = WEB_HISTORY_BACK;
		$data['formData'] = restructure_admin_form_data($this->jsVars['FORM_DATA'], $this->sideForm?'side':'page');

		$this->viewApp($data);
	}

	public function findPassword()
	{
		if($this->isLogin) redirect($this->isLoginRedirect);

		$this->formColumns = $this->setFormColumns('find_password');
		$this->addJsVars([
			'API_URI' => $this->apiUri.'auth/',
			'API_URI_ADD' => 'findPassword',
			'FORM_DATA' => $this->setFormData(),
			'FORM_REGEXP' => $this->config->item('regexp'),
			'REDIRECT_URI' => '/admin/auth/login'
		]);

		$this->addCSS[] = [
			base_url('public/assets/builder/vendor/css/pages/page-auth.css'),
			base_url('public/assets/builder/vendor/libs/@form-validation/form-validation.css'),
			base_url('public/assets/builder/vendor/libs/bootstrap-maxlength/bootstrap-maxlength.css'),
		];

		$this->addJS['tail'][] = [
			base_url('public/assets/builder/vendor/libs/@form-validation/popular.js'),
			base_url('public/assets/builder/vendor/libs/@form-validation/bootstrap5.js'),
			base_url('public/assets/builder/vendor/libs/@form-validation/auto-focus.js'),
			base_url('public/assets/builder/vendor/libs/bootstrap-maxlength/bootstrap-maxlength.js'),
		];

		$this->addJS['tail'][] = [
			base_url('public/assets/builder/js/app-page-auth.js'),
		];

		$data['subPage'] = 'admin/auth/find_password';
		$data['backLink'] = WEB_HISTORY_BACK;
		$data['formData'] = restructure_admin_form_data($this->jsVars['FORM_DATA'], $this->sideForm?'side':'page');

		$this->viewApp($data);
	}
}
