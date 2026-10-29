<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once __DIR__.'/Common.php';

class Boards extends Common
{
    function __construct()
    {
        parent::__construct();

        $this->routeTitle = '게시판';

        $this->validateModel('Model_Board', 'Model');
        $this->load->model('Model_Article');
        $this->load->model('Model_Sys_Code');

        $this->setProperties($this->Model);
    }

    protected function beforeList($data): array
    {
        $data = parent::beforeList($data);
        if(empty($data['orderBy'])) $data['orderBy'] = ['board_id' => 'ASC'];
        return $data;
    }

    protected function beforePost($key = 0, $model = null): array
    {
        list($key, $data) = parent::beforePost($key, $model);
        $this->checkBoardCode($data);
        return [$key, $data];
    }

    protected function beforePut($key = 0, $model = null): array
    {
        list($key, $data) = parent::beforePut($key, $model);
        $this->checkBoardCode($data);
        return [$key, $data];
    }

    protected function beforePatch($key = 0, $model = null): array
    {
        list($key, $data) = parent::beforePatch($key, $model);
        $this->checkBoardCode($data);
        return [$key, $data];
    }

    /**
     * 게시판 구분은 사용 중인 시스템 코드 BRD 만 (일반 등록·수정에는 선택지 값 검사가 없어 엔티티에서 확인)
     */
    protected function checkBoardCode(array $data): void
    {
        if(!isset($data['board_cd']) || $data['board_cd'] === '') return;

        $exists = $this->Model_Sys_Code->getCntWhere([
            'cmb_cd' => $data['board_cd'],
            'big_cd' => 'BRD',
            'use_yn' => 'Y',
        ]);
        if($exists && substr((string)$data['board_cd'], -3) !== '000') return;

        $this->responseError([
            'location' => __METHOD__,
            'param' => 'board_cd',
            'value' => $data['board_cd'],
            'msg' => '게시판 구분은 시스템 코드 BRD 에서 고르세요.',
        ], [], RestController::HTTP_BAD_REQUEST);
    }

    /**
     * 게시글이 남아 있는 게시판은 지우지 않는다 (게시글이 어느 게시판에도 속하지 않게 됨). 지운(del_yn=Y) 게시글은 세지 않는다
     */
    protected function beforeDelete($key = 0): array
    {
        $result = parent::beforeDelete($key);

        $baseRow = $this->getDeleteBaseRow();
        if($baseRow && $this->Model_Article->getCntWhere(['board_id' => $baseRow->board_id]) > 0) {
            $this->responseError([
                'location' => __METHOD__,
                'param' => 'board_id',
                'value' => $baseRow->board_id,
                'msg' => '게시글이 있는 게시판은 지울 수 없습니다. 게시글을 먼저 지우세요.',
            ], [], RestController::HTTP_CONFLICT);
        }

        return $result;
    }
}
