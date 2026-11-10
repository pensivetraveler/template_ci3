<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once __DIR__.'/Common.php';

class Articles extends Common
{
    public function __construct()
    {
        parent::__construct();

        $this->titleList[] = 'Articles Management';
        $this->addJsVars([
            'API_URI' => $this->apiUri.'articles',
            'API_PARAMS' => [
            ]
        ]);
    }

    public function view($key = 0)
    {
        // 상세를 열 때 조회수·읽음 기록 (api/articleRead PUT)
        $this->addJS['tail'][] = [
            base_url('public/assets/builder/js/app-page-article.js'),
        ];

        parent::view($key);
    }
}
