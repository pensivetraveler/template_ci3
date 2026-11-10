<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once __DIR__.'/Common.php';

/**
 * 회원 API. 관리자 화면에서는 내 정보(myInfo)가 쓴다.
 * 시스템 관리자가 아니면
 *  - 목록·이름 있는 GET(옵션·중복 확인 등): 허용
 *  - 단건 조회·수정: 본인만, 수정 때 $selfProtectedFields 는 저장하지 않음(등급·상태·아이디·API 키)
 *  - 등록·삭제·이름 있는 쓰기 엔드포인트: 403
 * 계정 생성·관리는 administrators·partners API 가 맡는다.
 */
class Users extends Common
{
    protected array $selfProtectedFields = ['user_cd', 'id', 'del_yn', 'withdraw_yn', 'withdraw_dt', 'user_api_key'];

	function __construct()
	{
		parent::__construct();

        $this->routeTitle = '사용자';

		$this->load->model('Model_User', 'Model');

		$this->setProperties($this->Model);
	}

    protected function checkApiPermission(string $http, string $object, array $arguments): void
    {
        parent::checkApiPermission($http, $object, $arguments);

        if(!$this->isSelfScoped()) return;

        if($object !== 'index' && method_exists($this, "{$object}_{$http}")) {
            if($http !== 'get') $this->denyApiPermission('self');
            return;
        }

        $key = $object !== 'index' ? $object : ($arguments[0] ?? 0);
        $idData = $this->checkIdentifierExist($key);
        $targetId = $idData[$this->Model->identifier] ?? null;

        if($http === 'get' && $targetId === null) return;
        if($http === 'delete' || $targetId === null) $this->denyApiPermission('self');
        if((string)$targetId !== (string)$this->session->userdata('user_id')) $this->denyApiPermission('self');
    }

    protected function beforePost($key = 0, $model = null): array
    {
        list($key, $data) = parent::beforePost($key, $model);
        return [$key, $this->removeSelfProtectedFields($data)];
    }

    protected function beforePut($key = 0, $model = null): array
    {
        list($key, $data) = parent::beforePut($key, $model);
        return [$key, $this->removeSelfProtectedFields($data)];
    }

    protected function beforePatch($key = 0, $model = null): array
    {
        list($key, $data) = parent::beforePatch($key, $model);
        return [$key, $this->removeSelfProtectedFields($data)];
    }

    /**
     * 본인 데이터만 다루는 요청자인지 (로그인했고 시스템 관리자가 아님)
     */
    protected function isSelfScoped(): bool
    {
        return $this->session->userdata('user_id') && !$this->session->userdata('is_sys_admin');
    }

    protected function removeSelfProtectedFields($data)
    {
        if(!is_array($data) || !$this->isSelfScoped()) return $data;
        foreach ($this->selfProtectedFields as $field) unset($data[$field]);
        return $data;
    }
}
