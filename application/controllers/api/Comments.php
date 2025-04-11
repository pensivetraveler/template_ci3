<?php defined('BASEPATH') or exit('No direct script access allowed');

require_once __DIR__.'/Common.php';

class Comments extends Common
{
	function __construct()
	{
		parent::__construct();

		$this->load->model('Model_Comment', 'Model');
		$this->load->model('Model_Message');
		$this->load->model('Model_Article');

		$this->setProperties($this->Model);

		$this->defaultList = [
			'del_yn' => 'N',
		];
	}

	protected function list($data = [])
	{
		$data = $this->listBefore($data);

		$data['where']['depth'] = 0;

		$list = $this->Model->getList(
			$data['select'] ?? [],
			$data['where'] ?? [],
			$data['like'] ?? [],
			$data['limit'] ?? [],
			$data['order_by'] ?? [],
			$data['filter'] ?? [],
		);

		$adminList = $this->Model_User->getList(['user_id'], [
			'user_cd' => 'USR001'
		]);

		foreach ($list as $item) {
			$item->creater = $this->Model_User->getData([
				'name', 'id'
			], [
				'user_id' => $item->created_id,
			]);

			$item->is_admin = in_array($item->created_id, $adminList);
			$item->mine_yn = (int)$this->session->userdata('user_id') === (int)$item->created_id;
			$replyList = $this->Model->getList([], [
				'parent_id' => $item->comment_id,
				'depth' => 1,
			], [], [], [
				'created_dt' => 'desc'
			]);
			foreach ($replyList as $reply) {
				$reply->mine_yn = (int)$this->session->userdata('user_id') === (int)$reply->created_id;
				$reply->is_admin = in_array($reply->created_id, $adminList);
				$reply->creater = $this->Model_User->getData([
					'name', 'id'
				], [
					'user_id' => $reply->created_id,
				]);
			}
			$item->reply_list = $replyList;
		}

		$this->response([
			'code' => DATA_RETRIEVED,
			'data' => $this->listAfter($list),
			'extra' => $data['extraFields'] ?? [],
		]);
	}

	protected function afterAddData($dto)
	{
		$article = $this->Model_Article->getData([], [
			'article_id' => $dto['article_id'],
		]);
		if((int)$article->board_id !== 3) return $dto;

		if(!$dto['parent_id']) {
			// 댓글
			if((int)$article->created_id !== (int)$this->session->userdata('user_id')) {
				$this->Model_Message->addData([
					'user_id' => $article->created_id,
					'article_id' => $dto['article_id'],
					'comment_id' => $dto['comment_id'],
					'content' => '내 게시글에 새 댓글이 달렸어요.',
				]);
			}
		}else{
			$parentComment = $this->Model->getData([], [
				'article_id' => $dto['article_id'],
				'comment_id' => $dto['parent_id'],
			]);

			// 답글
			if((int)$parentComment->created_id !== (int)$this->session->userdata('user_id')) {
				// 답글 대상 댓글 작성자와 답글 작성자가 서로 다른 경우
				$this->Model_Message->addData([
					'user_id' => $parentComment->created_id,
					'article_id' => $dto['article_id'],
					'comment_id' => $dto['comment_id'],
					'content' => '내 댓글에 새 답글이 달렷어요.',
				]);
			}
		}

		return $dto;
	}
}
