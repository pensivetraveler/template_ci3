<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * 요청 값 검증: form 규칙, 옵션 값, 식별자·행 존재, 고유 값 (MY_Builder_API)
 */
trait BuilderApiValidationTrait
{
    /**
     * PATCH는 일부 필드만 보내므로(목록 인라인 수정 등) 본문에 있는 필드의 form rules만 검증한다.
     */
    protected function validatePatchFormRules(array $data): void
    {
        if(is_empty($this->formConfig)) return;

        $formConfig = $this->formConfig;
        $this->formConfig = array_values(array_filter($formConfig, function ($item) use ($data) {
            $group = $item['group'] ?? '';
            return array_key_exists($item['field'] ?? '', $data) || ($group && $group !== 'base' && array_key_exists($group, $data));
        }));

        if(count($this->formConfig)) $this->validateFormRules('', $data);

        $this->formConfig = $formConfig;
    }

    /**
     * 선택형 필드(select·radio·checkbox) 값이 선택지에 있는지 검사한다 (등록·수정·부분 수정, form 규칙 검사 뒤).
     * - 요청에 값이 있는 기본 그룹 필드만. 빈 값은 required 규칙이 맡는다
     * - $allowStored: 수정이면 지금 저장된 값은 통과 (선택지가 사용 중인 것만이라 그 사이 빠진 값으로 저장이 막히지 않도록)
     * 검사 대상·제외 기준은 getValidatableOptionValues()
     */
    protected function validateOptionValues($data, bool $allowStored = false): void
    {
        if(!is_array($data) || !count($data) || is_empty($this->formConfig)) return;

        $fieldConfig = array_values(array_filter($this->formConfig, function ($item) use ($data) {
            $group = $item['group'] ?? '';
            return isset($item['field']) && array_key_exists($item['field'], $data) && (!$group || $group === 'base');
        }));

        $stored = null;
        $errors = [];
        foreach ($this->getValidatableOptionValues($fieldConfig) as $field => [$values, $seperator, $label]) {
            $value = $data[$field];
            if($value === null) continue;
            $list = is_array($value) ? $value : explode($seperator, (string)$value);

            foreach ($list as $one) {
                $one = is_scalar($one) ? trim((string)$one) : null;
                if($one === '' || ($one !== null && in_array($one, $values, true))) continue;

                if($one !== null && $allowStored && count($this->idData) && property_exists($this, 'Model') && $this->Model) {
                    if($stored === null) $stored = (array)($this->Model->getDataWhere([], $this->idData) ?: []);
                    $storedValues = array_map('trim', explode($seperator, (string)($stored[$field] ?? '')));
                    if(in_array($one, $storedValues, true)) continue;
                }

                $errors[] = [
                    'location' => __METHOD__,
                    'param' => $field,
                    'value' => $one,
                    'type' => 'in_list',
                    'msg' => $this->josa->__conv(lang(str_replace('lang:', '', $label))."{은} 선택할 수 있는 값이 아닙니다."),
                ];
                break;
            }
        }

        if(count($errors)) {
            $this->responseError($errors, [
                'data' => $data,
            ], RestController::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * 선택지를 검사할 필드와 선택지 값: [필드 => [값 목록(문자열), 구분자, label]]
     * 서버가 선택지를 알 수 없거나 믿을 수 없는 필드는 뺀다:
     *   option_type 이 없음·none·blank·api·script(브라우저가 채움), 옵션 설정이 없어 기본 더미(Option 1/2)가 되는 field·static,
     *   선택지가 빈 필드, option_attributes.validate === false (직접 입력도 받는 select 등)
     * 일반 등록·수정(validateOptionValues)과 엑셀 가져오기(validateExcelData)가 같이 쓴다
     */
    protected function getValidatableOptionValues(array $fieldConfig): array
    {
        $result = [];
        foreach ($fieldConfig as $item) {
            if(!isset($item['field']) || !in_array($item['type'] ?? '', ['select', 'radio', 'checkbox'], true)) continue;

            $attr = $item['option_attributes'] ?? [];
            if(!is_array($attr) || !count($attr) || ($attr['validate'] ?? true) === false) continue;

            $type = $attr['option_type'] ?? '';
            if($type === '' || in_array($type, ['none', 'blank', 'api', 'script'], true)) continue;
            if($type === 'field' && is_empty(config_item('options')[$item['field']] ?? [])) continue;
            if($type === 'static' && is_empty($attr['option_data'] ?? [])) continue;

            $options = $this->getOptions($item['field'], $attr);
            if(!is_array($options) || !count($options)) continue;

            $result[$item['field']] = [array_map('strval', array_keys($options)), $attr['seperator'] ?? ',', $item['label'] ?? $item['field']];
        }
        return $result;
    }

    protected function validate($data = [], $model = null, $validate = true, $configName = '', $exceptRequiredKeys = [])
    {
        if($validate) $this->validateFormRules($configName, $data);

        $this->validateManually(
            $data,
            $model ?? $this->Model,
            $this->validateMessages,
            $this->validateCallback,
            $exceptRequiredKeys,
        );
    }

    protected function validateFormRules($configName = '', $data = []): array
    {
        $method = __METHOD__;
        $errors = [];
        $config = $this->config->get($configName, $this->formConfig, false);
        if(empty($data)) $data = $this->input->post_put();

        // base rule validation
        $config = array_map(function ($item) {
            if(!array_key_exists('rules', $item) || !$item['rules']) $item['rules'] = 'do_nothing';
            if(is_empty($item, 'group')) $item['group'] = 'base';
            return $item;
        }, array_filter($config, function ($item) {
            return ($item['type'] ?? '') !== 'common';
        }));
        // MY_Builder_WEB::setFormData 와 같이 그룹 항목의 group_attributes 를 순서대로 누적 병합
        $groups = [];
        foreach ($config as $item) {
            $group = $item['group'];
            if(!array_key_exists($group, $groups)) $groups[$group] = $group === 'base' ? [] : $this->config->get('builder_form_base_group_attributes');
            if($group !== 'base') $groups[$group] = array_merge($groups[$group], $item['group_attributes'] ?? []);
        }
        foreach ($groups as $group => $attr) {
            $groupConfig = array_filter($config, function($item) use ($group) {
                return $item['group'] === $group;
            });
            // CI3 set_rules()는 POST가 아니고 검증 데이터가 비어 있으면 규칙을 등록하지 않는다.
            // PUT·PATCH에서도 규칙이 등록되도록 요청 데이터를 먼저 넣고, 아래 분기에서 그룹별 데이터로 바꾼다.
            // 그룹마다 새로 검증한다. 앞 그룹 규칙이 남아 있으면 다음 그룹 run() 에서 다시 검사돼 같은 오류가 두 번 들어간다
            $this->form_validation->reset_validation();
            $this->form_validation->set_data(count($data) ? $data : ['_' => '']);
            $this->form_validation->set_rules($group === 'base' ? set_array_checkbox_rule_fields($groupConfig, $data) : $groupConfig);

            if($group === 'base') {
                $targetData = [];
                foreach ($data as $field => $value) {
                    if(in_array($field, array_column($groupConfig, 'field'))){
                        $targetData[$field] = $value;
                    }
                }
                $this->form_validation->set_data($targetData);
                if($this->form_validation->run() === false) {
                    $errors = array_merge(
                        $errors,
                        $this->setValidateFormErrors(validation_errors_array(), $method),
                    );
                }
            }else{
                $enveloped = $attr['envelope_name'];
                $targetData = [];
                if($enveloped) {
                    if(!array_key_exists($group, $data)) continue;
                    $targetData = $data[$group];
                }else{
                    foreach ($data as $field => $value) {
                        if(in_array($field, array_column($groupConfig, 'field'))){
                            $targetData[$field] = $value;
                        }
                    }
                }

                if($attr['group_repeater']) {
                    if($enveloped) {
                        foreach ($targetData as $i => $item) {
                            foreach ($item as $field => $value) {
                                if(empty($value)) {
                                    unset($targetData[$i]);
                                    break;
                                }
                            }
                        }
                        $targetData = array_values($targetData);
                        for($i = 0; $i < count($targetData); $i++) {
                            $this->form_validation->set_data($targetData[$i]);
                            if($this->form_validation->run() === false) {
                                $errors = array_merge(
                                    $errors,
                                    $this->setValidateFormErrors(validation_errors_array(), $method, $group, $attr, $i),
                                );
                            }
                        }
                    }else{
                        $cnt = 0;
                        foreach ($targetData as $field => $value) {
//							$targetData[$field] = array_values($value);
                            if($cnt === 0) $cnt = count($value);
                            $cnt = min($cnt, count($value));
                        }

                        for($i = 0; $i < $cnt; $i++) {
                            $item = [];
                            foreach ($targetData as $k => $v) $item[$k] = $v[$i];
                            $this->form_validation->set_data($item);
                            if($this->form_validation->run() === false) {
                                $errors = array_merge(
                                    $errors,
                                    $this->setValidateFormErrors(validation_errors_array(), $method, $group, $attr, $i),
                                );
                            }
                        }
                    }
                }else{
                    $this->form_validation->set_data($targetData);
                    if($this->form_validation->run() === false) {
                        $errors = array_merge(
                            $errors,
                            $this->setValidateFormErrors(validation_errors_array(), $method, $group, $attr),
                        );
                    }
                }
            }
        }

        // file rule validation
        foreach ($config as $item) {
            // Check if 'rules' exists in the array item
            if (isset($item['rules'])) {
                // Use regex to check
                foreach ($this->config->item('file_rules') as $rule=>$ruleData) {
                    if (preg_match("/{$ruleData['exp']}/{$ruleData['flags']}", $item['rules'], $matches)) {
                        $param = $matches[2]??null;
                        if($this->form_validation->{$rule}($item['field'], $matches[2]) === false){
                            $errors[] = [
                                'location' => $method,
                                'param' => $item['field'],
                                'value' => $param,
                                'type' => $rule,
                                'msg' => $this->form_validation->get_error_msg($rule, $item['label'], $param),
                            ];
                        }
                    }
                }
            }
        }

        if(count($errors)) {
            $this->responseError($errors, [
                'data' => $this->input->post(),
            ], RestController::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $data;
    }

    protected function setValidateFormErrors($errors, $method, $group = 'base', $attr = [], $i = 0)
    {
        return array_reduce($errors, function ($carry, $item) use ($method, $group, $attr, $i) {
            $param = preg_replace('/\[\]$/', '', $item['field']);
            if(!empty($attr)) {
                if($attr['envelope_name']) {
                    $param = $group.($attr['group_repeater']?"[$i]":'')."[$param]";
                }else{
                    $param = $param.($attr['group_repeater']?"[$i]":'');
                }
            }

            $carry[] = [
                'location' => $method,
                'param' => $param,
                'value' => $item['value'],
                'type' => $item['rule'],
                'msg' => $item['message'],
            ];
            return $carry;
        }, []);
    }

    protected function validateJson($required = [], $optional = [], $strList = [], $intList = [], $msgList = [], $callbacks = [])
    {
        $json_data = $this->input->raw_input_stream;
        $parsed_data = (array)json_decode($json_data);
        if(empty($required)) $required = array_keys($parsed_data);

        $dto = new class {};
        $dto->table = '';
        $dto->identifier = '';
        $dto->primaryKeyList = [];
        $dto->notNullList = empty($required)?array_keys($parsed_data):$required;
        $dto->nullList = $optional;
        $dto->strList = $strList;
        $dto->intList = $intList;
        $dto->fileList = [];

        $this->validateManually($parsed_data, $dto, $msgList, $callbacks);

        return $parsed_data;
    }

    protected function validateManually($data = [], $model = null, $msgList = [], $callbacks = [], $exceptRequiredKeys = [])
    {
        if($model === null || !isset($model->notNullList))
            $this->responseError([
                'location' => __METHOD__,
                'type' => 'required',
            ], [
                'code' => EMPTY_REQUIRED_DATA,
                'data' => $data,
            ]);

        if($this->input->method() === 'post') {
            foreach ($model->notNullList as $key) {
                if ($model->identifier && $key === $model->identifier) {
                    continue;
                }

                if (in_array($key, $model->primaryKeyList, true)) {
                    continue;
                }

                if (in_array($key, $exceptRequiredKeys, true)) {
                    continue;
                }

                if (array_key_exists($key, $callbacks)) {
                    $this->{$callbacks[$key]}();
                    continue;
                }

                $errorMsg = '';
                $value = null;
                $msg = '';

                if(array_key_exists($key, $msgList)) {
                    $msg = $msgList[$key];
                }else{
                    $lang = $model->table ? lang($model->table.'.'.$key) : $key;

                    // 파일 컬럼은 업로드 여부로 검사 (수정은 기존 파일이 유지되므로, 값이 이미 있으면 통과)
                    if(count($model->fileList) > 0 && in_array($key, $model->fileList)){
                        if(!is_file_posted($key) && is_empty($data, $key) && !count($this->idData)) {
                            $errorMsg = "File Data {$key} Is Missing.";
                            $data = $_FILES;
                            $msg = $this->josa->__conv("$lang{을} 업로드하세요.");
                        }
                    }else{
                        if(!array_key_exists($key, $data)) {
                            $errorMsg = 'Required';
                        }else if(is_empty($data, $key)) {
                            $value = $data[$key];
                            $errorMsg = 'empty';
                        }

                        if($errorMsg)
                            $msg = $this->josa->__conv("$lang{은} 필수 입력값 입니다.");
                    }
                }

                if($errorMsg) {
                    $this->responseError([
                        'location' => __METHOD__,
                        'param' => $key,
                        'value' => $value,
                        'type' => 'required',
                        'msg' => $errorMsg,
                    ], [
                        'code' => EMPTY_REQUIRED_DATA,
                        'msg' => array_key_exists($key, $msgList) ? $msgList[$key] : $msg,
                        'data' => $data,
                    ]);
                }
            }
        }

        return $data;
    }

    protected function checkIdentifierExist($key = 0, $strict = false, $model = null): array
    {
        if (!$model && property_exists($this, 'Model')) {
            $model = $this->Model;
        }

        if (is_null($model)) {
            if ($strict) {
                $this->responseError([
                    'location' => __METHOD__,
                    'param' => [
                        'class' => $this->router->class,
                    ],
                    'msg' => "Model is not defined",
                ]);
            }else{
                return $this->idData;
            }
        }

        $this->idData = [];

        if ($model->isAutoIncrement) {
            if (!$model->identifier) {
                if ($strict) {
                    $this->responseError([
                        'location' => __METHOD__,
                        'param' => [
                            'class' => $this->router->class,
                        ],
                        'msg' => "Identifier is not defined",
                    ]);
                }
            }

            $value = $key ?: $this->input->get($model->identifier);

            if ($value !== null && $value !== '') {
                $this->idData[$model->identifier] = $value;
            }

            if ($strict && !count($this->idData)) {
                $this->responseError([
                    'location' => __METHOD__,
                    'param' => [
                        'class' => $this->router->class,
                    ],
                    'msg' => "Identifier value is missing",
                ]);
            }

            return $this->idData;
        }

        if (count($model->primaryKeyList) > 0) {
            $found = [];

            // 기본키가 하나뿐이면 URL 키(/sysCode/ABC001)를 그 값으로 쓴다. 없으면 쿼리 파라미터
            // (전에는 URL 키를 무시해 SysCode::beforeRemove 처럼 idData 와 $key 가 다른 출처에서 올 수 있었음)
            if (count($model->primaryKeyList) === 1 && $key !== null && $key !== '' && $key !== 0 && $key !== '0') {
                $this->idData = [$model->primaryKeyList[0] => $key];
                return $this->idData;
            }

            foreach ($model->primaryKeyList as $field) {
                $value = $this->input->get($field);

                if ($value !== null && $value !== '') {
                    $found[$field] = $value;
                }
            }

            if (count($found) === count($model->primaryKeyList)) {
                $this->idData = $found;
                return $this->idData;
            }

            if (count($found) > 0 && $strict) {
                $this->responseError([
                    'location' => __METHOD__,
                    'param' => [
                        'table' => $model->table,
                    ],
                    'msg' => "ID Field and Values is not equivalent counts",
                ]);
            }

            return [];
        }

        if ($strict) {
            $this->responseError([
                'location' => __METHOD__,
                'param' => [
                    'table' => $model->table,
                ],
                'msg' => "There\'s No Columns for Identifying",
            ]);
        }

        return [];
    }

    protected function checkDataExist($data, $model = null, $exit = false): bool
    {
        if(is_null($model)) $model = $this->Model;
        $result = $this->checkCnt($data, $model);

        if($exit && !$result) {
            $this->response([
                'code' => DATA_NOT_EXIST,
            ], RestController::HTTP_NOT_FOUND);
        }

        return $result;
    }

    protected function checkUniqueExist($dto, $model = null)
    {
        // 등록 여부: _mode=add 이거나, 수정 대상 식별자가 없을 때(afterPost/afterPut 이 add 로 처리)
        $modeAdd = $this->mode === 'add' || !count($this->idData);

        if(is_null($model)) $model = $this->Model;
        if(count($model->uniqueKeyList) > 0){
            foreach ($model->uniqueKeyList as $key) {
                if(array_search($key, array_column($this->formConfig, 'field')) === false) continue;
                if(!array_key_exists($key, $dto)) continue;

                $idx = array_search($key, array_column($this->formConfig, 'field'));
                $config = $this->formConfig[$idx];
                if(!$modeAdd && (!array_key_exists('editable', $config['form_attributes']) || !$config['form_attributes']['editable'])) continue;

                $isIncludeDeleted = false;
                if(array_key_exists('form_attributes', $this->formConfig[$idx]) && !is_empty($this->formConfig[$idx]['form_attributes'], 'check_delete')) {
                    $isIncludeDeleted = $config['form_attributes']['check_delete'];
                }

                if($this->checkDuplicate([$key => $dto[$key]], $model, !$modeAdd?$dto:[], $isIncludeDeleted)){
                    $lang = $model?lang($model->table.'.'.$key):$key;
                    $this->response([
                        'code' => DATA_ALREADY_EXIST,
                        'msg' => $this->josa->__conv("동일 $lang{이} 이미 존재합니다."),
                    ], RestController::HTTP_CONFLICT);
                    break;
                }
            }
        }
    }

    protected function checkDuplicate($unique, $model = null, $dto = [], $isIncludeDeleted = false)
    {
        foreach ($unique as $key=>$val) {
            if(is_null($model)) {
                if(property_exists($this, 'Model_Parent') && in_array($key, $this->Model_Parent->uniqueKeyList)) {
                    $model = $this->Model_Parent;
                }else if(property_exists($this, 'Model_Child') && in_array($key, $this->Model_Child->uniqueKeyList)) {
                    $model = $this->Model_Child;
                }else if(property_exists($this, 'Model')){
                    $model = $this->Model;
                }
            }

            // 수정 중인 자기 행은 제외. 메인 Model은 URL·쿼리로 확인한 idData, 그 외에는 dto의 식별자 값
            $whereNot = [];
            if(!is_empty($dto) && $model->identifier) {
                $isMainModel = property_exists($this, 'Model') && $model === $this->Model;
                $selfId = ($isMainModel ? ($this->idData[$model->identifier] ?? null) : null) ?? ($dto[$model->identifier] ?? null);
                if($selfId !== null && $selfId !== '') $whereNot = [$model->identifier => $selfId];
            }
            return $model->checkDuplicate($unique, $whereNot, $isIncludeDeleted);
        }
    }

    protected function checkCnt($dto, $model = null)
    {
        if(is_null($model)) $model = $this->Model;
        return $model->getCnt($dto) > 0;
    }
}
