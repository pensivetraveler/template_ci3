<?php
function reorder($list, $addData = [], $sortKey = 'sort_order')
{
    $lastKey = count($list);
    if(empty($addData)) {
        foreach ($list as $i=>$data) {
            if(is_array($data)){
                $list[$i][$sortKey] = $i+1;
            }else{
                $list[$i]->{$sortKey} = $i+1;
            }
        }
    }else{
        $addKey = (int)is_array($addData)?$addData[$sortKey]:$addData->{$sortKey};
        if($addKey > $lastKey) {
            $list[] = $addData;
        }else{
            for($i = count($list); $i > -1; $i--){
                if($i > $addKey-1) {
                    if(is_array($list[$i-1])){
                        $list[$i-1][$sortKey]++;
                    }else{
                        $list[$i-1]->{$sortKey}++;
                    }
                    $list[$i] = $list[$i-1];
                }
                if($i === $addKey-1) $list[$i] = $addData;
            }
        }
    }
    return $list;
}

function getColumnList($queryParentTogether, $befList, $newList)
{
    if($queryParentTogether){
        return array_unique(array_merge($befList, $newList));
    }else{
        return $newList;
    }
}

function getJoinOption($isChild, $obj)
{
    return $isChild?"{$obj->db->dbprefix}{$obj->table}.{$obj->parentIdentifier}={$obj->db->dbprefix}{$obj->parentTable}.{$obj->parentIdentifier}":"";
}

function getWhereList($where, $tablename, $columnList)
{
    $keys_with_prefix = array_map(function ($key) use ($tablename, $columnList) {
        return
			!str_contains($key, '.') && !empty($columnList) && in_array($key, $columnList)
				? $tablename.".".$key
				: $key;
    }, array_keys($where));
    return array_combine($keys_with_prefix, $where);
}

function getSelectList($tableName, $columnList)
{
    foreach ($columnList as $idx=>$column) {
        $columnList[$idx] = "{$tableName}.{$column}";
    }
    return $columnList;
}

function getValidSetList($setList, $columnList)
{
    return array_map(function($item) use ($columnList) {
        if(!is_array($item)) $item = (array)$item;
        return array_filter($item, function($key) use ($columnList) {
            return in_array($key, $columnList);
        }, ARRAY_FILTER_USE_KEY);
    }, $setList);
}

function getValidSetData($setList, $columnList)
{
    return array_filter($setList, function($key) use ($columnList) {
        return in_array($key, $columnList);
    }, ARRAY_FILTER_USE_KEY);
}
