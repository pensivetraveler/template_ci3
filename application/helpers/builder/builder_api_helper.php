<?php
if ( ! function_exists('reformat_get_data'))
{
    function reformat_get_data($data, $exceptValidateKeys): array
    {
        $return = [];

        if(array_key_exists('render', $data)) {
            $return['render'] = $data['render'];
            unset($data['render']);
        }

        foreach ($data as $field=>$value) {
            if(in_array($field, $exceptValidateKeys)) continue;
            // '0'은 유효한 조건값이므로 빈 값만 건너뜀 (예: ?depth=0)
            if($value === null || $value === '' || $value === []) continue;
            $return['where'][$field] = $value;
        }

        if(array_key_exists('filters', $data)) {
            $filters = $data['filters'];

            if(!empty($filters)) {
                foreach ($filters as $type => $filter) {
                    switch ($type) {
                        case 'where' :
                            foreach ($filter as $field=>$value) {
                                if(is_null($value) || $value === '' || $value === '*') continue;
                                $return['filter']['where'][$field] = $value;
                            }
                            break;
                        case 'whereIn' :
                            foreach ($filter as $field=>$value) {
                                if(empty($value)) continue;
                                $return['filter']['whereIn'][$field] = $value;
                            }
                            break;
                        case 'like' :
                            foreach ($filter as $item) {
                                if(!is_empty($item, 'value')) {
                                    $return['filter']['like'][] = [
                                        'field' => $item['field']??'',
                                        'value' => $item['value'],
                                    ];
                                }
                            }
                            break;
                        case 'date' :
                            if(isset($filter['date_range'])) {
                                if(!is_empty($filter, 'start_date') && !is_empty($filter, 'end_date') && !is_empty($filter, 'on_date')) {
                                    switch ($filter['date_range']) {
                                        case 'today' :
                                            $filter['on_date'] = date('Y-m-d');
                                            break;
                                        case 'yesterday' :
                                            $filter['on_date'] = date('Y-m-d', strtotime('-1 day'));
                                            break;
                                        case 'last7days' :
                                            $filter['start_date'] = date('Y-m-d', strtotime('-6 days'));
                                            $filter['end_date'] = date('Y-m-d');
                                            break;
                                        case 'last30days' :
                                            $filter['start_date'] = date('Y-m-d', strtotime('-29 days'));
                                            $filter['end_date'] = date('Y-m-d');
                                            break;
                                        case 'currentMonth' :
                                            $filter['start_date'] = date('Y-m-').'01';
                                            $filter['end_date'] = date('Y-m-').date('t');
                                            break;
                                        case 'lastMonth' :
                                            $filter['start_date'] = date('Y-m-', strtotime('-1 Month')).'01';
                                            $filter['end_date'] = date('Y-m-', strtotime('-1 Month')).date('t', strtotime('-1 Month'));
                                            break;
                                        case 'thisYear' :
                                            $filter['start_date'] = date('Y').'-01-01';
                                            $filter['end_date'] = date('Y').'-12-31';
                                            break;
                                        case 'lastYear' :
                                            $filter['start_date'] = date('Y', strtotime('-1 Year')).'-01-01';
                                            $filter['end_date'] = date('Y', strtotime('-1 Year')).'-12-31';
                                            break;
                                    }
                                }
                                unset($filter['date_range']);
                            }

                            foreach ($filter as $field=>$value) {
                                if(!$value) continue;
                                $return['filter']['date'][$field] = $value;
                            }
                            break;
                    }
                }
            }
        }else{
            $return['filter'] = [];
        }

        if(array_key_exists('format', $data)) {
            if($data['format'] === 'datatable') {
                if($data['searchWord'] && $data['searchCategory']) {
                    $return['filter']['like'][$data['searchCategory']] = $data['searchWord'];
                }
            }
        }

        $return['select'] = reformat_request_select($data['select'] ?? []);

        return $return;
    }
}

if ( ! function_exists('reformat_request_select'))
{
    /**
     * 요청 파라미터로 받은 select 를 단순 컬럼명만 남기도록 정리한다.
     * MY_Model::select() 는 "식 as 별칭" 형태를 이스케이프 없이 쿼리에 넣으므로,
     * 클라이언트 값에서 함수·서브쿼리·별칭·테이블 접두어를 허용하면 SQL 인젝션이 된다.
     * 식이 필요한 select 는 컨트롤러/모델 코드에서 직접 지정한다.
     */
    function reformat_request_select($select): array
    {
        if(is_string($select)) $select = explode(',', $select);
        if(!is_array($select)) return [];

        $columns = [];
        foreach ($select as $column) {
            if(!is_string($column)) continue;
            $column = trim($column);
            if(preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $column)) $columns[] = $column;
        }

        return array_values(array_unique($columns));
    }
}
