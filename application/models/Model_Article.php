<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once __DIR__.'/Model_Common.php';

class Model_Article extends Model_Common
{
	public string  $table = 'article';
	public string  $identifier = 'article_id';
	public array   $primaryKeyList = ['article_id','board_id'];
	public array   $uniqueKeyList = [];
	public array   $notNullList = ['article_id','board_id','subject','content','del_yn','open_yn'];
	public array   $nullList = ['thumbnail','view_count'];
	public array   $strList = ['subject','content','del_yn','open_yn'];
	public array   $intList = ['article_id','board_id','view_count'];
	public array   $fileList = ['thumbnail'];

	public bool    $isAutoincrement = true;
	public bool    $isDelYn = true;
	public bool    $isCreatedDt = true;
	public bool    $isCreatedId = true;
	public bool    $isUpdatedDt = true;

	function __construct()
	{
		parent::__construct();
	}

	public function getDashboardArticleList($adminList)
	{
		$myId = $this->session->userdata('user_id');
		$adminList = array_diff($adminList, [$myId]);

		$this->db->select("(SELECT COUNT(*) FROM tbl_article_prefer WHERE tbl_article_prefer.article_id = tbl_article.article_id AND tbl_article_prefer.user_id IN ($myId)) AS my_feedback");
		if(count($adminList) > 0) {
			$adminList = implode(',', $adminList);
			$this->db->select("(SELECT COUNT(*) FROM tbl_article_prefer WHERE tbl_article_prefer.article_id = tbl_article.article_id AND tbl_article_prefer.user_id IN ($adminList)) AS feedback_cnt");
		}

		$articleList = $this->Model_Article->getList([], [
			'board_id' => 3,
			'open_yn' => 'Y',
		], [], [], ['created_dt' => 'desc']);

		$list = [];
		$cnt = 0;
		foreach ($articleList as $i=>$item) {
			if($cnt === 5) break;
			if(!property_exists($item, 'feedback_cnt')) $item->feedback_cnt = 0;
			if((int)$item->my_feedback > 0) continue;
			if((int)$item->feedback_cnt > 3) continue;
			$list[] = $item;
			$cnt++;
		}

		return $list;
	}
}
