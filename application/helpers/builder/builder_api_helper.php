<?php
if ( ! function_exists('reformat_get_data'))
{
    function reformat_get_data($data, $exceptValidateKeys): array
    {
        $return = [];
        foreach ($data as $field=>$value) {
            if(in_array($field, $exceptValidateKeys)) continue;
            if(!$value) continue;
            $return['where'][$field] = $value;
        }

        if(array_key_exists('filters', $data)) {
            $filters = $data['filters'];
            foreach ($filters as $type => $filter) {
                switch ($type) {
                    case 'where' :
                        foreach ($filter as $field=>$value) {
                            if(!$value) continue;
                            $return['filter']['where'][$field] = $value;
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
                        foreach ($filter as $field=>$value) {
                            if(!$value) continue;
                            $return['filter']['date'][$field] = $value;
                        }
                        break;
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
