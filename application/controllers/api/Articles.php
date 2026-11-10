<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once __DIR__.'/Common.php';

class Articles extends Common
{
	function __construct()
	{
		parent::__construct();

        $this->routeTitle = '아티클';

		$this->load->model('Model_Article', 'Model');
		$this->load->model('Model_Comment');
		$this->load->model('Model_User');
		$this->load->model('Model_Board');
		$this->load->model('Model_Article_Attachment');

		$this->setProperties($this->Model);

		// 게시판(board_id)은 요청으로 받는다 (예전에는 1 로 고정)
		$this->defaultList = [
			'open_yn' => 'Y',
			'del_yn' => 'N',
		];
	}

	protected function beforePost($key = 0, $model = null): array
	{
		list($key, $data) = parent::beforePost($key, $model);
		$this->checkBoardExist($data);
		return [$key, $data];
	}

	protected function beforePut($key = 0, $model = null): array
	{
		list($key, $data) = parent::beforePut($key, $model);
		$this->checkBoardExist($data);
		return [$key, $data];
	}

	protected function beforePatch($key = 0, $model = null): array
	{
		list($key, $data) = parent::beforePatch($key, $model);
		$this->checkBoardExist($data);
		return [$key, $data];
	}

	/**
	 * 보낸 board_id 가 있는 게시판인지 (없으면 400)
	 */
	protected function checkBoardExist(array $data): void
	{
		if(!isset($data['board_id']) || $data['board_id'] === '') return;
		if($this->Model_Board->getCntWhere(['board_id' => $data['board_id']])) return;

		$this->responseError([
			'location' => __METHOD__,
			'param' => 'board_id',
			'value' => $data['board_id'],
			'msg' => '없는 게시판입니다.',
		], [], RestController::HTTP_BAD_REQUEST);
	}

	protected function transformView($data): object
	{
		$data->uploads = $this->getArticleFileList(['article_id' => $data->article_id]);

		// 관리자(USR001)가 단 댓글이 있는지. 컬럼 1개 select 는 값 배열(user_id 목록)을 돌려준다
		$adminList = $this->Model_User->getListWhere(['user_id'], [
			'user_cd' => 'USR001'
		]);
		$data->reply_yn = count($adminList) > 0 && $this->Model_Comment->getCntWhere([
			'article_id' => $data->article_id,
			'created_id' => $adminList,
		]) > 0;

		return parent::transformView($data);
	}

	protected function afterAdd($dto)
	{
		// 등록 때도 첨부(uploads)를 저장 (예전에는 afterModify 에만 있어 등록 시 첨부가 유실됨)
		$this->saveAttachments($this->idData['article_id'] ?? ($dto['article_id'] ?? 0), $dto['board_id'] ?? null);

		parent::afterAdd($dto);
	}

	protected function afterModify($key, $dto)
	{
		$this->saveAttachments($this->idData['article_id'] ?? $key, $dto['board_id'] ?? null);

		parent::afterModify($key, $dto);
	}

	/**
	 * 게시글 첨부(article_attachment) 삭제: deleteFile/{article_id}?type=attachment
	 */
	protected function deleteOtherFile($key, string $type, string $fileId): bool
	{
		if($type !== 'attachment') return false;

		$idData = $this->checkIdentifierExist($key);
		$articleId = $idData['article_id'] ?? 0;
		if(!$articleId) $this->response(['code' => EMPTY_REQUIRED_DATA]);

		$where = ['article_id' => $articleId, 'file_id' => $fileId];
		if(!$this->Model_Article_Attachment->getCntWhere($where)) {
			$this->response(['code' => DATA_NOT_EXIST], RestController::HTTP_NOT_FOUND);
		}

		$this->Model_Article_Attachment->delData($where);
		$this->delFileData(['file_id' => $fileId]);
		return true;
	}

	/**
	 * 게시글 첨부 순서: reorder/{article_id}?type=attachment, file_ids 는 그 글의 첨부 전체(화면 목록 순서)
	 */
	protected function reorderFiles($key, string $type, array $fileIds): bool
	{
		if($type !== 'attachment') return false;

		$idData = $this->checkIdentifierExist($key);
		$articleId = $idData['article_id'] ?? 0;
		if(!$articleId) $this->response(['code' => EMPTY_REQUIRED_DATA]);
		if(!$this->Model->getCntWhere(['article_id' => $articleId])) {
			$this->response(['code' => DATA_NOT_EXIST], RestController::HTTP_NOT_FOUND);
		}

		// 컬럼 1개 select 는 값 배열을 돌려준다
		$current = array_map('strval', $this->Model_Article_Attachment->getListWhere(['file_id'], ['article_id' => $articleId]));
		$sorted = $fileIds;
		sort($current);
		sort($sorted);
		if($current !== $sorted) {
			$this->responseError([
				'location' => __METHOD__,
				'param' => 'file_ids',
				'msg' => '첨부 목록이 바뀌었습니다. 새로고침한 뒤 다시 정렬하세요.',
			], [], RestController::HTTP_CONFLICT);
		}

		foreach ($fileIds as $i => $fileId) {
			$this->Model_Article_Attachment->modData(['sort_order' => $i + 1], [
				'article_id' => $articleId,
				'file_id' => $fileId,
			]);
		}
		return true;
	}

	protected function saveAttachments($articleId, $boardId): void
	{
		if(!$articleId) return;

		$fileList = $this->uploader('uploads');
		if(count($fileList) > 0) {
			$boardData = $boardId ? $this->Model_Board->getDataWhere([], ['board_id' => $boardId]) : null;
			if($boardData && $boardData->attach_max) {
				if($this->Model_Article_Attachment->getCntWhere(['article_id' => $articleId]) >= (int)$boardData->attach_max) {
					$this->Model_Article_Attachment->delData(['article_id' => $articleId]);
				}
			}
			// 새 첨부는 기존 첨부 뒤 순서
			$start = (int)$this->Model_Article_Attachment->getCntWhere(['article_id' => $articleId]);
			$set = array_map(function($item, $i) use ($articleId, $start) {
				$item['article_id'] = $articleId;
				$item['sort_order'] = $start + $i + 1;
				return $item;
			}, $fileList, array_keys($fileList));
			$this->Model_Article_Attachment->addList($set);
		}
	}
}
