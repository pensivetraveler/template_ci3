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
            if(!$value) continue;
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

        $return['select'] = $data['select']??[];

        return $return;
    }
}
