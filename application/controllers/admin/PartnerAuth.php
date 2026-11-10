<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once __DIR__.'/Common.php';

class PartnerAuth extends Common
{
    public function __construct()
    {
        parent::__construct();

        $this->titleList[] = 'Partner Auth';
        $this->addJsVars([
            'API_URI' => $this->apiUri.'partnerAuth',
            'API_PARAMS' => []
        ]);

        $this->addJS['tail'][] = [
            base_url('public/assets/builder/js/app-page-menu-auth.js'),
        ];
    }
}
