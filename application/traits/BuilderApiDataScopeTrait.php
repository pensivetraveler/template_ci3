<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * 데이터 범위: 회사·담당자 등 요청자가 다룰 수 있는 행 (MY_Builder_API)
 */
trait BuilderApiDataScopeTrait
{
    /**
     * 이 엔티티의 범위 필드. 프로젝트의 getDataScope() 가 이 목록을 보고 값을 채운다. 예: ['company_id']
     */
    protected array $scopeFields = [];

    /**
     * 첫 인자가 이 엔티티의 식별자인 이름 있는 엔드포인트 (범위 검사 대상). 프로젝트 엔드포인트는 엔티티에서 추가
     */
    protected array $scopeKeyEndpoints = ['duplicate', 'deleteFile', 'deleteRepeater', 'isMyData'];

    private ?array $dataScope = null;

    /**
     * 요청자가 다룰 수 있는 범위: [필드 => 허용 값 목록]. 빈 배열이면 제한 없음 (기본).
     * 프로젝트의 api/Common 에서 세션(등급·담당 회사 등)과 $scopeFields 로 구현한다.
     * 적용: 목록·내보내기 조건, 식별자로 지정한 행(단건·수정·삭제·$scopeKeyEndpoints), 등록·수정 값, 엑셀 가져오기 행
     */
    protected function getDataScope(): array
    {
        return [];
    }

    protected function getDataScopeOnce(): array
    {
        if($this->dataScope === null) {
            $this->dataScope = [];
            foreach ($this->getDataScope() as $field => $values) {
                $values = is_array($values) ? $values : explode(',', (string)$values);
                $this->dataScope[$field] = array_values(array_unique(array_map('strval', array_filter($values, function ($v) {
                    return $v !== null && $v !== '';
                }))));
            }
        }
        return $this->dataScope;
    }

    /**
     * 목록·내보내기 조건에 범위를 붙인다. 이미 같은 필드 조건이 있으면 교집합
     */
    protected function applyDataScope(array $data): array
    {
        foreach ($this->getDataScopeOnce() as $field => $values) {
            if(isset($data['whereIn'][$field])) {
                $current = $data['whereIn'][$field];
                $current = is_array($current) ? $current : explode(',', (string)$current);
                $values = array_values(array_intersect($values, array_map('strval', $current)));
            }
            // 허용 값이 없으면 아무 행도 나오지 않게 (IN (NULL))
            $data['whereIn'][$field] = count($values) ? $values : [null];
        }
        return $data;
    }

    /**
     * 식별자로 지정한 행이 범위 안에 있는지
     */
    protected function isInDataScope(array $idData): bool
    {
        $scope = $this->getDataScopeOnce();
        if(!count($scope) || !count($idData)) return true;
        return $this->checkDataExist($this->applyDataScope(['where' => $idData]));
    }

    protected function checkDataScopeTarget(string $http, string $object, array $arguments): void
    {
        if(!count($this->getDataScopeOnce()) || !property_exists($this, 'Model') || !$this->Model) return;

        if($object !== 'index' && method_exists($this, "{$object}_{$http}")) {
            if(!in_array($object, $this->scopeKeyEndpoints, true)) return;
            $key = $arguments[0] ?? 0;
        }else{
            $key = $object !== 'index' ? $object : ($arguments[0] ?? 0);
        }

        if(!$this->isInDataScope($this->checkIdentifierExist($key))) $this->denyDataScope();
    }

    /**
     * 저장할 값이 범위 안인지. 등록은 범위 필드가 빠져도 거부
     */
    protected function checkDataScopeValues(array $dto, bool $isAdd): void
    {
        foreach ($this->getDataScopeOnce() as $field => $values) {
            if(!array_key_exists($field, $dto)) {
                if($isAdd) $this->denyDataScope();
                continue;
            }
            if(!in_array((string)$dto[$field], $values, true)) $this->denyDataScope();
        }
    }

    /**
     * 범위 밖은 있는지조차 알리지 않도록 404
     */
    protected function denyDataScope(): void
    {
        $this->response([
            'code' => DATA_NOT_EXIST,
        ], RestController::HTTP_NOT_FOUND);
    }
}
