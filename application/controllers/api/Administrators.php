<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once __DIR__.'/Common.php';

class Administrators extends Common
{
    function __construct()
    {
        parent::__construct();

        $this->routeTitle = '관리자';

        $this->validateModel('Model_User', 'Model_Parent');
        $this->validateModel('Model_Administrator', 'Model');

        $this->setProperties($this->Model);

        $this->defaultList = [
            'user_cd' => 'USR001',
            'use_yn' => 'Y',
            'del_yn' => 'N',
            'approve_yn' => 'Y',
            'withdraw_yn' => 'N',
        ];
    }

    protected function beforeAdd($dto, $model = null): array
    {
        parent::beforeAdd($dto, $model);
        $dto['user_api_key'] = generate_api_key();
        return $dto;
    }
}
