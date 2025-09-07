<?php
function reorder($list, $addData = [], $sortKey = 'sort_order', $notPermitSpace = false, $start = 1)
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

            if($notPermitSpace) {
                $j = $start;
                for($i = 0; $i < count($list); $i++) {
                    if(is_array($list[$i])) {
                        $list[$i][$sortKey] = $j;
                    }else{
                        $list[$i]->{$sortKey} = $j;
                    }
                    $j++;
                }
            }
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

/** 내부 공백 정규화 */
if (!function_exists('qb_norm_ws')) {
    function qb_norm_ws(string $s): string {
        return preg_replace('/\s+/', ' ', trim($s));
    }
}

/** 이미 같은 JOIN이 있는지 검사 (table + ON + TYPE) */
if (!function_exists('qb_join_exists')) {
    function qb_join_exists(CI_DB_query_builder $db, string $table, string $on = null, string $type = null): bool
    {
        $ref  = new ReflectionClass($db);
        $prop = $ref->getProperty('qb_join');
        $prop->setAccessible(true);
        $joins = (array) $prop->getValue($db);

        // dbprefix 고려
        $full = preg_quote($db->dbprefix . $table, '/');
        $basePattern = "/\\bJOIN\\s+`?{$full}`?\\b/i";

        foreach ($joins as $sql) {
            if (!preg_match($basePattern, $sql)) continue;

            if ($on !== null) {
                if (stripos(qb_norm_ws($sql), qb_norm_ws($on)) === false) continue;
            }
            if ($type !== null) {
                if (stripos($sql, strtoupper($type) . ' JOIN') === false) continue;
            }
            return true;
        }
        return false;
    }
}

/** 중복 JOIN 방지용 래퍼 */
if (!function_exists('join_once')) {
    function join_once(CI_DB_query_builder $db, string $table, string $on = '', string $type = '', bool $escape = null): CI_DB_query_builder
    {
        if (!qb_join_exists($db, $table, $on ?: null, $type ?: null)) {
            $db->join($table, $on, $type, $escape);
        }
        return $db;
    }
}

/** JOIN 중복 제거 (디버깅/응급처치용) */
if (!function_exists('qb_dedupe_joins')) {
    function qb_dedupe_joins(CI_DB_query_builder $db): void
    {
        $ref  = new ReflectionClass($db);
        $prop = $ref->getProperty('qb_join');
        $prop->setAccessible(true);
        $joins = (array) $prop->getValue($db);

        // 공백 정규화 후 유니크
        $norm = [];
        $uniq = [];
        foreach ($joins as $s) {
            $k = qb_norm_ws($s);
            if (!isset($norm[$k])) {
                $norm[$k] = true;
                $uniq[] = $s; // 원문 유지
            }
        }
        $prop->setValue($db, array_values($uniq));
    }
}

/** SELECT 한 번만 추가 */
if (!function_exists('select_once')) {
    function select_once(CI_DB_query_builder $db, string $expr, bool $protect = TRUE): CI_DB_query_builder
    {
        $ref  = new ReflectionClass($db);
        $prop = $ref->getProperty('qb_select');
        $prop->setAccessible(true);
        $sels = (array) $prop->getValue($db);

        $key  = strtolower(qb_norm_ws($expr));
        foreach ($sels as $s) {
            if (strtolower(qb_norm_ws($s)) === $key) {
                return $db; // 이미 있음
            }
        }
        return $db->select($expr, $protect);
    }
}

/** SELECT 중복 제거 */
if (!function_exists('qb_dedupe_selects')) {
    function qb_dedupe_selects(CI_DB_query_builder $db): void
    {
        $ref  = new ReflectionClass($db);
        $prop = $ref->getProperty('qb_select');
        $prop->setAccessible(true);
        $sels = (array) $prop->getValue($db);

        $seen = [];
        $keep = [];
        foreach ($sels as $s) {
            $k = strtolower(qb_norm_ws($s));
            if (!isset($seen[$k])) {
                $seen[$k] = true;
                $keep[] = $s;
            }
        }
        $prop->setValue($db, $keep);
    }
}

/** 현재 빌더 SQL 확인 (상태는 유지) */
if (!function_exists('qb_dump')) {
    function qb_dump(CI_DB_query_builder $db): string
    {
        return $db->get_compiled_select('', FALSE);
    }
}

/** 빌더 상태 초기화 습관화 */
if (!function_exists('qb_reset')) {
    function qb_reset(CI_DB_query_builder $db): void
    {
        $db->reset_query();
    }
}

/** (선택) 별칭 포함 JOIN 존재 확인 */
if (!function_exists('qb_has_join_alias')) {
    function qb_has_join_alias(CI_DB_query_builder $db, string $table, ?string $alias): bool
    {
        $ref  = new ReflectionClass($db);
        $prop = $ref->getProperty('qb_join');
        $prop->setAccessible(true);
        $joins = (array) $prop->getValue($db);

        $full = preg_quote($db->dbprefix . $table, '/');
        if ($alias !== null) {
            $a = preg_quote($alias, '/');
            $pattern = "/\\bJOIN\\s+`?{$full}`?\\s+(?:AS\\s+)?`?{$a}`?\\b/i";
        } else {
            $pattern = "/\\bJOIN\\s+`?{$full}`?\\b/i";
        }
        foreach ($joins as $sql) {
            if (preg_match($pattern, $sql)) return true;
        }
        return false;
    }
}
