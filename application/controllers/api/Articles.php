<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once __DIR__.'/Common.php';

class Articles extends Common
{
	function __construct()
	{
		parent::__construct();

		$this->load->model('Model_Article', 'Model');
		$this->load->model('Model_Comment');
		$this->load->model('Model_User');
		$this->load->model('Model_Board');
		$this->load->model('Model_Article_Attachment');
		$this->load->model('Model_Article_Prefer');

		$this->setProperties($this->Model);

		$this->defaultList = [
			'board_id' => 1,
			'del_yn' => 'N',
		];
	}

	protected function transformView($data): object
	{
		$data->uploads = $this->getArticleFileList(['article_id' => $data->article_id]);

		$this->db->where_in('created_id', $this->Model_User->getListWhere(['user_id'], [
			'user_cd' => 'USR001'
		]));
		$data->reply_yn = $this->Model_Comment->getCntWhere([
			'article_id' => $data->article_id,
		]) > 0;

		return parent::viewAfter($data);
	}

	protected function afterModify($key, $dto)
	{
		$boardData = $this->Model_Board->getDataWhere([], ['board_id' => $dto['board_id']]);

		$fileList = $this->uploader('uploads');
		if(count($fileList) > 0) {
			if($boardData->attach_max) {
				if($this->Model_Article_Attachment->getCntWhere(['article_id' => $key]) >= (int)$boardData->attach_max) {
					$this->Model_Article_Attachment->delData(['article_id' => $key]);
				}
			}
			$set = array_map(function($item) use ($key) {
				$item['article_id'] = $key;
				return $item;
			}, $fileList);
			$this->Model_Article_Attachment->addList($set);
		}

		parent::afterModify($key, $dto);
	}
}
