<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * 등록·수정·삭제 처리와 저장. 엔티티는 before·execute·after 훅을 오버라이드한다 (MY_Builder_API)
 */
trait BuilderApiCrudTrait
{
    protected function setData($data = [], $model = null)
    {
        if(is_null($model)) $model = $this->Model;

        $checkboxes = array_values(array_filter($this->formConfig, function ($item) {
            return $item['type'] === 'checkbox';
        }));

        $onoff = array_values(array_filter($checkboxes, function ($item) {
            return $item['subtype'] === 'onoff';
        }));

        $columnList = array_unique(array_merge($model->notNullList, $model->nullList));
        if($model->isDelYn) $columnList[] = DEL_YN_COLUMN_NAME;
        if($model->isUseYn) $columnList[] = USE_YN_COLUMN_NAME;

        foreach ($data as $field => $value) {
            if(!in_array($field, $columnList)){
                unset($data[$field]);
                continue;
            }

            if(!is_object($value) && !is_array($value)) {
                // 줄 구분이 뜻을 가지는 칸(모델의 multilineList)은 공백을 합치지 않는다. 브라우저가 보내는 CRLF 만 LF 로 맞춘다
                $data[$field] = in_array($field, $model->multilineList, true)
                    ? trim(str_replace("\r\n", "\n", $value))
                    : trim(preg_replace('/\s\s+/', ' ', $value));
            }
            // 빈 값(null·''·false·[])만 ''로 맞춘다. empty()는 '0'·0 도 비워서 문자열 컬럼에 0 을 저장할 수 없었음
            if(in_array($field, $model->strList) && empty($value) && !is_numeric($value)) $data[$field] = '';
            if(in_array($field, $model->intList)) {
                if(array_key_exists($field, $data)) {
                    $data[$field] = (int)$data[$field];
                }else{
                    $data[$field] = in_array($field, $model->notNullList) ? 0 : null;
                }
            }

            switch ($field) {
                case 'gender' :
                    $data[$field] = strtoupper($data[$field]);
                    break;
                case 'password' :
                    if($value) {
                        $data[$field] = create_hash($value);
                    }else{
                        unset($data[$field]);
                    }
                    break;
            }

            if(in_array($field, array_column($checkboxes, 'field'))) {
                $index = array_search($field, array_column($checkboxes, 'field'));
                $config = $checkboxes[$index];
                if($config['subtype'] === 'onoff') {
                    $data[$field] = $value;
                }else{
                    $seperator = $config['option_attributes']['seperator'] ?? ',';
                    // 체크박스가 하나면 문자열로 올 수 있어 join 전에 배열로 맞춤 (PHP 8 TypeError 방지)
                    $data[$field] = join($seperator, (array)$value);
                }
            }
        }

        foreach ($onoff as $config) {
            $field = $config['field'];
            if(!array_key_exists($field, $data)) $data[$field] = 'N';
        }

        return $data;
    }

    protected function setModData($data = [], $model = null)
    {
        if(is_null($model)) $model = $this->Model;

        $data = $this->setData($data, $model);

        $idData = [];
        if($model->isAutoIncrement) {
            if(isset($data[$model->identifier]) && $data[$model->identifier] !== '') {
                $idData[$model->identifier] = $data[$model->identifier];
            }
        }else if(count($model->primaryKeyList) > 0){
            $idData = array_reduce(array_merge($model->primaryKeyList, $model->uniqueKeyList), function ($carry, $item) use ($data) {
                if(isset($data[$item])) {
                    $carry[$item] = $data[$item];
                    unset($data[$item]);
                }
                return $carry;
            }, []);
        }

        return [$data, $idData];
    }

    /**
     * 수정 조건(WHERE)이 식별자를 모두 갖췄는지 확인한다.
     * 비어 있거나 일부 키가 빠진 조건으로 UPDATE 하면 여러 행(최악은 테이블 전체)이 바뀌므로 400으로 멈춘다.
     */
    protected function checkModifyIdData(array $idData, $model): void
    {
        if($model->isAutoIncrement) {
            $requiredKeys = $model->identifier ? [$model->identifier] : [];
        }else{
            $requiredKeys = $model->primaryKeyList;
        }

        $missing = array_values(array_filter($requiredKeys, function ($field) use ($idData) {
            return !isset($idData[$field]) || $idData[$field] === '';
        }));

        if(!count($requiredKeys) || count($missing)) {
            $this->responseError([
                'location' => __METHOD__,
                'param' => [
                    'table' => $model->table,
                    'missing' => $missing,
                ],
                'msg' => "Identifier value is missing",
            ], [], RestController::HTTP_BAD_REQUEST);
        }
    }

    protected function add($dto, $bool)
    {
        $dto = $this->beforeAdd($dto);

        $this->checkDataScopeValues($dto, true);
        $this->checkUserGradeValue($dto);

        $dto = $this->executeAdd($dto, $bool);

        $this->afterAdd($dto);
    }

    protected function beforeAdd($dto, $model = null): array
    {
        if(is_null($model)) $model = $this->Model;

        if(!$model->isAutoIncrement && count($model->primaryKeyList) > 0) {
            $this->idData = array_reduce(
                array_merge($model->primaryKeyList, $model->uniqueKeyList)
                , function ($carry, $item) use ($dto) {
                    if(isset($dto[$item])) $carry[$item] = $dto[$item];
                    return $carry;
                }
                , []
            );
            if(count($this->idData)) {
                // soft delete(del_yn='Y')·미사용(use_yn='N') 행도 같은 PK를 차지하므로 기본 조건 없이 확인 (DB duplicate key 오류 대신 409)
                $useDelYnAsBase = $model->useDelYnAsBase;
                $useUseYnAsBase = $model->useUseYnAsBase;
                $model->useDelYnAsBase = false;
                $model->useUseYnAsBase = false;
                $exists = $this->checkDataExist(['where' => $this->idData], $model);
                $model->useDelYnAsBase = $useDelYnAsBase;
                $model->useUseYnAsBase = $useUseYnAsBase;

                if($exists) $this->response(['code' => DATA_ALREADY_EXIST]);
            }
        }

        return $dto;
    }

    protected function executeAdd($dto, $bool, $model = null): array
    {
        if(is_null($model)) {
            foreach ($this->getModelPipeline() as $modelName) {
                if (property_exists($this, $modelName)) {
                    $targetModel = $this->{$modelName};

                    $dto = $this->applyForeignKeyValues($dto, $targetModel);

                    $params = $this->setData($dto, $targetModel);
                    $params = $this->addData($params, $bool, $targetModel);

                    $dto = array_merge($dto, $params);
                }
            }
        }else{
            $dto = $this->applyForeignKeyValues($dto, $model);
            $dto = $this->setData($dto, $model);
            $dto = $this->addData($dto, $bool, $model);
        }

        return $dto;
    }

    protected function afterAdd($dto)
    {
        $data = $this->beforeResponse($dto);

        $this->response([
            'code' => DATA_CREATED,
            'data' => $this->idData,
        ], RestController::HTTP_CREATED);
    }

    protected function modify($key, $dto, $bool)
    {
        $dto = $this->beforeModify($key, $dto);

        // 없는 행·삭제된 행 수정은 404. 권한 표처럼 행을 먼저 만드는 곳이 있어 beforeModify 다음에 확인한다
        if(count($this->idData)) $this->checkDataExist(['where' => $this->idData], null, true);

        $this->checkDataScopeValues($dto, false);
        $this->checkUserGradeTarget();
        $this->checkUserGradeValue($dto);

        $dto = $this->executeModify($key, $dto, $bool);

        $this->afterModify($key, $dto);
    }

    protected function beforeModify($key, $dto): array
    {
        return $dto;
    }

    protected function executeModify($key, $dto, $bool, $model = null): array
    {
        if(is_null($model)) {
            // 부모·자식 행은 요청 본문이 아니라 메인 행이 가리키는 행만 수정한다 (본문 user_id 로 다른 회원을 고칠 수 없게)
            $baseRow = property_exists($this, 'Model_Parent') || property_exists($this, 'Model_Child') ? $this->getDeleteBaseRow() : null;

            foreach ($this->getModelPipeline() as $modelName) {
                if(property_exists($this, $modelName)) {
                    list($params, $idData) = $this->setModData($dto, $this->{$modelName});
                    // 메인 Model은 요청 본문이 아니라 checkIdentifierExist()가 URL·쿼리로 확인한 식별자로 수정 대상을 정함
                    if($modelName === 'Model' && count($this->idData)) $idData = $this->idData;
                    if($modelName !== 'Model' && $baseRow) $idData = $this->makeDeleteIdData($this->{$modelName}, $baseRow);
                    $this->checkModifyIdData($idData, $this->{$modelName});
                    // 식별자 컬럼 자체는 SET에서 빼서 수정 대상 행의 키가 바뀌지 않게 함. 앞 모델을 가리키는 키도 바꾸지 않음
                    $params = array_diff_key($params, $idData, array_flip($this->getGeneratedForeignKeyFields($this->{$modelName}, $modelName)));
                    // 이 표에서 바꿀 값이 없으면 건너뜀 (관리자 표처럼 키만 있는 메인 표)
                    if(count($params)) $this->modData($params, $idData, $bool, $this->{$modelName});
                }
            }
        }else{
            $dto = $this->setData($dto, $model);
            $this->checkModifyIdData($this->idData, $model);
            $this->modData($dto, $this->idData, $bool, $model);
        }

        return $dto;
    }

    protected function afterModify($key, $dto)
    {
        $data = $this->beforeResponse($dto);

        $this->response([
            'code' => DATA_EDITED,
            'data' => $this->idData,
        ]);
    }

    protected function remove($key, $data, $bool)
    {
        $this->checkUserGradeTarget();

        $data = $this->beforeRemove($key, $data);

        $data = $this->executeRemove($key, $data, $bool);

        $this->afterRemove($key, $data);
    }

    protected function beforeRemove($key, $data = []): array
    {
        return $data;
    }

    protected function executeRemove($key, $data, $bool): array
    {
        $baseRow = $this->getDeleteBaseRow();

        $pipeline = array_reverse($this->getModelPipeline());

        foreach ($pipeline as $modelName) {
            if (!property_exists($this, $modelName)) {
                continue;
            }

            $model = $this->{$modelName};

            $idData = $this->makeDeleteIdData($model, $baseRow);

            if (!count($idData)) {
                continue;
            }

            $model->delData($idData, $bool);
        }

        return $data;
    }

    protected function afterRemove($key, $data = [])
    {
        $data = $this->beforeResponse($data);

        $this->response([
            'code' => DATA_DELETED,
        ]);
    }

    protected function addData($dto, $bool, $model = null)
    {
        if(is_null($model)) $model = $this->Model;

        $result = $model->addData($dto, $bool);

        if ($result === false) $this->responseDbWriteError(__METHOD__, $model);

        if ($model->isAutoIncrement && $model->identifier) {
            $dto[$model->identifier] = $result;
            $this->idData[$model->identifier] = $result;
        }

        return $dto;
    }

    protected function modData($dto, $idData, $bool, $model = null)
    {
        if(is_null($model)) $model = $this->Model;

        if ($model->modData($dto, $idData, $bool) === false) $this->responseDbWriteError(__METHOD__, $model);

        return $dto;
    }

    /**
     * MY_Model::addDataPDO/modDataPDO 가 실패(rollback 후 false)했을 때 500으로 응답한다.
     */
    protected function responseDbWriteError(string $location, $model): void
    {
        // 입력 검사를 지나 DB 고유 제약에 걸림 (동시 요청 등)
        if($model->lastDbErrorCode === 1062) $this->response(['code' => DATA_ALREADY_EXIST], RestController::HTTP_CONFLICT);

        $this->responseError([
            'location' => $location,
            'param' => [
                'table' => $model->table,
            ],
            'type' => 'db error',
            'msg' => 'Database write failed',
        ], [
            'code' => INTERNAL_SERVER_ERROR,
        ]);
    }

    protected function getModelPipeline(): array
    {
        return ['Model_Parent', 'Model', 'Model_Child'];
    }

    protected function getGeneratedForeignKeyFields(object $model, string $currentModelName): array
    {
        if (!property_exists($model, 'foreignKeyList')) {
            return [];
        }

        $fields = [];

        foreach ($model->foreignKeyList as $field => $config) {
            if ($this->isForeignKeyGeneratedByPreviousModel($field, $model, $currentModelName)) {
                $fields[] = $field;
            }
        }

        return $fields;
    }

    protected function isForeignKeyGeneratedByPreviousModel(string $field, object $model, string $currentModelName): bool
    {
        if (
            !property_exists($model, 'foreignKeyList') ||
            !isset($model->foreignKeyList[$field])
        ) {
            return false;
        }

        $fk = $model->foreignKeyList[$field];

        $pipeline = $this->getModelPipeline();
        $currentIndex = array_search($currentModelName, $pipeline, true);

        if ($currentIndex === false || $currentIndex === 0) {
            return false;
        }

        $previousModelNames = array_slice($pipeline, 0, $currentIndex);

        foreach ($previousModelNames as $previousModelName) {
            if (!property_exists($this, $previousModelName)) {
                continue;
            }

            $previousModel = $this->{$previousModelName};

            $fkModelMatches = isset($fk['model']) && $fk['model'] === get_class($previousModel);
            $fkTableMatches = isset($fk['table']) && $fk['table'] === $previousModel->table;
            $fkColumnMatches = isset($fk['column']) && $fk['column'] === $previousModel->identifier;

            if (
                $previousModel->isAutoIncrement &&
                ($fkModelMatches || $fkTableMatches) &&
                $fkColumnMatches
            ) {
                return true;
            }
        }

        return false;
    }

    protected function applyForeignKeyValues(array $dto, object $model): array
    {
        if (!property_exists($model, 'foreignKeyList')) {
            return $dto;
        }

        foreach ($model->foreignKeyList as $field => $fk) {
            if (array_key_exists($field, $dto) && $dto[$field]) {
                continue;
            }

            $refColumn = $fk['column'] ?? null;

            if ($refColumn && array_key_exists($refColumn, $dto)) {
                $dto[$field] = $dto[$refColumn];
            }
        }

        return $dto;
    }

    protected function getDeleteBaseRow()
    {
        if (!count($this->idData)) {
            // 식별자 없이 DELETE 하면 HTML 500(show_error) 대신 JSON 400
            $this->keyNotExist();
        }

        $row = $this->Model->getDataWhere([], $this->idData);

        if (!$row) {
            $this->response([
                'code' => DATA_NOT_EXIST,
            ]);
        }

        return $row;
    }

    protected function makeDeleteIdData(object $model, $baseRow): array
    {
        $idData = [];

        if ($model->isAutoIncrement) {
            if (!$model->identifier) {
                return [];
            }

            if (property_exists($baseRow, $model->identifier)) {
                $idData[$model->identifier] = $baseRow->{$model->identifier};
            }

            return $idData;
        }

        foreach ($model->primaryKeyList as $field) {
            if (property_exists($baseRow, $field)) {
                $idData[$field] = $baseRow->{$field};
            }
        }

        return $idData;
    }
}
