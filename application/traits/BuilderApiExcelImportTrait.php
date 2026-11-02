<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * 엑셀 일괄 등록: 검증과 업로드 (MY_Builder_API)
 */
trait BuilderApiExcelImportTrait
{
    /**
     * 엑셀 가져오기 1단계: 행을 검증만 한다. 오류가 있으면 422 + 행·필드별 오류 목록.
     * 요청 바디(JSON): [{"_row": 엑셀 행 번호, "{필드}": 값, ...}, ...]
     */
    public function validateExcel_post(): void
    {
        $this->beforeExcelUpload();

        $this->response([
            'code' => DATA_AVAILABLE,
        ]);
    }

    /**
     * 엑셀 가져오기 2단계: 다시 검증한 뒤 한 번에(트랜잭션) INSERT 한다.
     */
    public function uploadExcel_post(): void
    {
        $data = $this->beforeExcelUpload();

        $this->afterExcelUpload($data);
    }

    /** 한 번에 받는 최대 행 수 (화면은 50행씩 나눠 보냄) */
    protected int $excelChunkLimit = 500;

    /**
     * 엑셀로 받을 필드. excel_{class}_config 가 있으면 그 field, 없으면 form 설정에서
     * 화면(MY_Builder_WEB::getExcelHeaders)과 같은 규칙으로 고른다: form 노출, hidden·common·file·password·공통 컬럼·확인 필드(matches) 제외
     */
    protected function getExcelImportFields(): array
    {
        $excel = $this->resourceLoader->entityConfig($this->resourceLoader->entityConfigName('excel', $this->router->class), [], false);
        if($excel) return array_values(array_filter(array_column($excel, 'field')));

        $except = [CREATED_ID_COLUMN_NAME, CREATED_DT_COLUMN_NAME, UPDATED_ID_COLUMN_NAME, UPDATED_DT_COLUMN_NAME, DEL_YN_COLUMN_NAME, USE_YN_COLUMN_NAME, RECENT_DT_COLUMN_NAME];
        $fields = [];
        foreach ($this->formConfig as $item) {
            if(empty($item['field']) || (array_key_exists('form', $item) && !$item['form'])) continue;
            if(in_array($item['type'] ?? '', ['hidden', 'common', 'file', 'password'], true)) continue;
            if(in_array($item['field'], $except, true)) continue;
            if(preg_match('/matches\[/', $item['rules'] ?? '')) continue;
            $fields[] = $item['field'];
        }
        return $fields;
    }

    /**
     * 엑셀 행을 검증해 저장할 값만 남긴다. 오류가 하나라도 있으면 422 로 응답하고 멈춘다.
     *  - 받는 필드 외 키는 버림, defaultList 로 빈 값 채움
     *  - form rules (등록 기준: required_mod[add] 는 required 로), 모델 notNullList 필수
     *  - 고유키: DB 중복과 이번 요청 안의 중복
     * 오류 항목: param = 필드, value = 엑셀 행 번호(_row), type = 규칙, msg = 메시지
     */
    protected function validateExcelData($data): array
    {
        if(!is_array($data) || !count($data) || array_keys($data) !== range(0, count($data) - 1)) {
            $this->response(['code' => EMPTY_REQUIRED_DATA], RestController::HTTP_BAD_REQUEST);
        }
        if(count($data) > $this->excelChunkLimit) {
            $this->response(['code' => BAD_REQUEST, 'msg' => "한 번에 {$this->excelChunkLimit}행까지 보낼 수 있습니다."], RestController::HTTP_BAD_REQUEST);
        }

        // 부모·자식 모델이 있는 엔티티(예: 관리자 = 관리자 + 사용자)는 한 행이 여러 테이블에 나뉘어 저장돼 일괄 등록을 지원하지 않는다
        if(property_exists($this, 'Model_Parent') || property_exists($this, 'Model_Child')) {
            $this->response(['code' => BAD_REQUEST, 'msg' => '이 화면은 엑셀 일괄 등록을 지원하지 않습니다.'], RestController::HTTP_BAD_REQUEST);
        }

        $model = $this->Model;
        $fields = $this->getExcelImportFields();
        $fieldConfig = array_values(array_filter($this->formConfig, function ($item) use ($fields) {
            return in_array($item['field'] ?? '', $fields, true);
        }));
        $rules = array_map(function ($item) {
            $item['rules'] = preg_replace('/required_mod\[[^\]]*add[^\]]*\]/', 'required', $item['rules'] ?: 'do_nothing');
            return $item;
        }, $fieldConfig);
        $labels = array_column($fieldConfig, 'label', 'field');

        // 선택형 필드는 선택지 값만 받는다 (일반 등록·수정과 같은 기준)
        $optionValues = $this->getValidatableOptionValues($fieldConfig);

        $errors = [];
        $rows = [];
        $seenUnique = [];
        // 같은 행·필드 오류는 한 번만 (form rule 과 notNull·고유키 검사가 겹칠 수 있음)
        $addError = function ($rowNo, $field, $type, $msg) use (&$errors) {
            foreach ($errors as $error) if($error['value'] === $rowNo && $error['param'] === $field) return;
            $errors[] = ['location' => __METHOD__, 'param' => $field, 'value' => $rowNo, 'type' => $type, 'msg' => $msg];
        };
        foreach ($data as $idx => $row) {
            $rowNo = is_array($row) && isset($row['_row']) && is_numeric($row['_row']) ? (int)$row['_row'] : $idx + 1;
            if(!is_array($row)) {
                $addError($rowNo, '', 'row', '행 형식이 올바르지 않습니다.');
                continue;
            }

            $clean = [];
            foreach ($fields as $field) {
                if(array_key_exists($field, $row) && $row[$field] !== null && !is_array($row[$field])) $clean[$field] = trim((string)$row[$field]);
            }
            foreach ($this->defaultList as $field => $default) {
                if(!isset($clean[$field]) || $clean[$field] === '') $clean[$field] = $default;
            }

            // form rules
            if(count($rules)) {
                $this->form_validation->reset_validation();
                $this->form_validation->set_data(count($clean) ? $clean : ['_' => '']);
                $this->form_validation->set_rules($rules);
                if($this->form_validation->run() === false) {
                    foreach (validation_errors_array() as $error) {
                        $addError($rowNo, $error['field'], $error['rule'] ?? 'rule', $error['message']);
                    }
                }
            }

            // 선택지 값 검사
            foreach ($optionValues as $field => [$values, $seperator]) {
                if(!isset($clean[$field]) || $clean[$field] === '') continue;
                foreach (explode($seperator, $clean[$field]) as $value) {
                    if(!in_array(trim($value), $values, true)) {
                        $label = lang(str_replace('lang:', '', $labels[$field] ?? $field));
                        $addError($rowNo, $field, 'in_list', $this->josa->__conv("{$label}{은} 선택할 수 있는 값이 아닙니다: ").trim($value));
                        break;
                    }
                }
            }

            // 모델 필수 컬럼
            foreach ($model->notNullList as $key) {
                if(($model->isAutoIncrement && $key === $model->identifier) || in_array($key, $model->fileList, true)) continue;
                if(!in_array($key, $fields, true) && !array_key_exists($key, $this->defaultList)) continue;
                if(!isset($clean[$key]) || $clean[$key] === '') {
                    $label = lang(str_replace('lang:', '', $labels[$key] ?? $key));
                    $addError($rowNo, $key, 'required', $this->josa->__conv("{$label}{은} 필수 입력값 입니다."));
                }
            }

            // 고유키 (DB, 이번 요청 안)
            foreach ($model->uniqueKeyList as $key) {
                if(!isset($clean[$key]) || $clean[$key] === '') continue;
                $label = lang(str_replace('lang:', '', $labels[$key] ?? $key));
                if(isset($seenUnique[$key][$clean[$key]])) {
                    $addError($rowNo, $key, 'duplicate', $this->josa->__conv("{$label}{이} {$seenUnique[$key][$clean[$key]]}행과 같습니다."));
                }elseif($this->checkDuplicate([$key => $clean[$key]], $model)) {
                    $addError($rowNo, $key, 'duplicate', $this->josa->__conv("동일 {$label}{이} 이미 존재합니다."));
                }
                $seenUnique[$key][$clean[$key]] = $rowNo;
            }

            $rows[] = $clean;
        }

        if(count($errors)) {
            $this->responseError($errors, [], RestController::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $rows;
    }

    /**
     * 엑셀 가져오기 전 권한 확인.
     * route 설정에 excel 메서드가 있는 클래스만 허용한다 (화면을 켜지 않은 엔티티에 API 로 대량 INSERT 하지 못하게).
     * 메뉴 권한(import) 검사는 API 권한 검사(TODO.md 2장)와 함께 넣는다. 필요하면 오버라이드.
     */
    protected function checkExcelImportPermission(): void
    {
        $route = $this->resourceLoader->routeEntry($this->router->class);
        if(!isset($route['methods']['excel'])) {
            $this->response(['code' => FORBIDDEN_ACCESS, 'msg' => '엑셀 일괄 등록이 허용되지 않은 화면입니다.'], RestController::HTTP_FORBIDDEN);
        }
    }

    protected function beforeExcelUpload(): array
    {
        $this->checkExcelImportPermission();

        if(!property_exists($this, 'Model')) {
            $this->response(['code' => MODEL_IS_NOT_DEFINED], RestController::HTTP_INTERNAL_SERVER_ERROR);
        }

        $data = json_decode($this->input->raw_input_stream, true);

        return $this->validateExcelData($data);
    }

    protected function afterExcelUpload($data): void
    {
        // 저장 전 값 정리(형 변환·비밀번호 해시 등)는 일반 등록과 같은 setData()
        $rows = array_map(function ($row) {
            return $this->setData($row);
        }, $data);

        // 데이터 범위 밖 값이 있으면 저장하지 않음
        foreach ($rows as $row) {
            $this->checkDataScopeValues($row, true);
            $this->checkUserGradeValue($row);
        }

        if($this->Model->addList($rows) === false) {
            $this->response([
                'code' => WRITE_FILEDB_FAIL,
            ], RestController::HTTP_INTERNAL_SERVER_ERROR);
        }

        $this->response([
            'code' => DATA_CREATED,
            'data' => ['count' => count($rows)],
        ], RestController::HTTP_CREATED);
    }

    public function deleteExcelFile_patch()
    {
        $class = $this->input->patch('class') ?? null;
        if(!$class) $this->response(['code' => EMPTY_REQUIRED_DATA]);
        // 경로 조작 방지: 클래스 이름 형식만 허용
        if(!is_string($class) || !preg_match('/^[A-Za-z][A-Za-z0-9_]*$/', $class)) $this->response(['code' => BAD_REQUEST]);

        $filename = $class.'_upload_sample.xlsx';
        $filepath = 'public'.DIRECTORY_SEPARATOR.'sample'.DIRECTORY_SEPARATOR;
        if(!unlink(FCPATH.$filepath.$filename)) $this->response(['code' => INTERNAL_SERVER_ERROR]);

        $this->response([
            'code' => DATA_DELETED,
        ]);
    }
}
