<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * 행 복제 (MY_Builder_API)
 */
trait BuilderApiDuplicateTrait
{
    // 새 행을 만드는 동작이라 POST로만 받음
    public function duplicate_post($key = 0)
    {
        // URL·쿼리로 받은 식별자로 원본 행을 찾음 (조건 없이 조회해 첫 행이 복제되던 문제 수정)
        $idData = $this->checkIdentifierExist($key, true);
        $data = $this->Model->getData([], [
            'where' => $idData,
        ]);

        if(!$data) {
            $this->response([
                'code' => DATA_NOT_EXIST,
            ]);
        }

        $dto = (array)$data;
        // 사용 여부(use_yn)는 원본 값을 그대로 둔다 (빼면 DB 기본값 NULL 로 들어감 — bizsoft #빌더-34)
        foreach ([$this->identifier, CREATED_ID_COLUMN_NAME, CREATED_DT_COLUMN_NAME, UPDATED_ID_COLUMN_NAME, UPDATED_DT_COLUMN_NAME, DEL_YN_COLUMN_NAME] as $field) {
            unset($dto[$field]);
        }
        if($this->Model->isDelYn) $dto[DEL_YN_COLUMN_NAME] = 'N';

        // 일반 등록과 같은 경로 (엔티티 beforeAdd·데이터 범위 값 검사), 메인 Model 만 저장
        $dto = $this->beforeDuplicate($dto, $data);
        $dto = $this->beforeAdd($dto);
        $this->checkDataScopeValues($dto, true);
        $this->checkUserGradeValue($dto);
        $this->executeAdd($dto, false, $this->Model);

        $this->afterDuplicate($this->idData, $data);

        $this->response([
            'code' => DATA_PROCESSED,
            'msg' => lang('Data replication has been completed'),
            'data' => $this->idData,
        ]);
    }

    /**
     * 복제할 값 손보기. 기본:
     *  - 민감 컬럼($deniedRequestFields·$hiddenResponseFields: 비밀번호·API 키 등)은 복사하지 않음
     *  - 파일 컬럼은 비움(0) — 같은 file_id 를 나눠 가지면 한쪽 파일 삭제가 다른 쪽 파일도 지움
     *  - 고유 컬럼(uniqueKeyList): 문자열이면 '_copy', '_copy2' … 중 비어 있는 값, 아니면 비움
     * 엔티티에서 오버라이드해 규칙을 바꾼다 (예: 별칭 규칙)
     */
    protected function beforeDuplicate(array $dto, $source): array
    {
        $model = $this->Model;

        foreach (array_unique(array_merge($this->deniedRequestFields, $this->hiddenResponseFields)) as $field) {
            unset($dto[$field]);
        }

        foreach ($model->fileList as $field) {
            if(array_key_exists($field, $dto)) $dto[$field] = 0;
        }

        foreach ($model->uniqueKeyList as $field) {
            if(!array_key_exists($field, $dto) || $dto[$field] === null || $dto[$field] === '') continue;
            if(is_string($dto[$field]) && !is_numeric($dto[$field])) {
                $dto[$field] = $this->getDuplicateUniqueValue($field, $dto[$field], $model);
            }else{
                unset($dto[$field]);
            }
        }

        return $dto;
    }

    /**
     * 복제 뒤 처리 (딸린 표 복사 등). $newIdData: 새 행 식별자, $source: 원본 행. 기본은 없음
     */
    protected function afterDuplicate(array $newIdData, $source): void
    {
    }

    /**
     * 고유 컬럼 값 뒤에 '_copy', '_copy2' … 를 붙여 아직 없는 값 (삭제·미사용 행도 같은 값을 차지하므로 기본 조건 없이 확인)
     */
    protected function getDuplicateUniqueValue(string $field, string $value, $model): string
    {
        $useDelYnAsBase = $model->useDelYnAsBase;
        $useUseYnAsBase = $model->useUseYnAsBase;
        $model->useDelYnAsBase = false;
        $model->useUseYnAsBase = false;

        $candidate = $value.'_copy';
        for ($i = 2; $i <= 100 && $model->getCntWhere([$field => $candidate]) > 0; $i++) {
            $candidate = $value.'_copy'.$i;
        }

        $model->useDelYnAsBase = $useDelYnAsBase;
        $model->useUseYnAsBase = $useUseYnAsBase;

        return $candidate;
    }
}
