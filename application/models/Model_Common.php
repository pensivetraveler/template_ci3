<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Model_Common extends MY_Model
{
    function __construct()
    {
        parent::__construct();
    }

    function getList($select = [], $dto = [], $filter = [])
    {
        if(has_inner_array($select)) show_error('Select Parameter is Unacceptable');
        if(empty($select)) $this->db->select($this->getSelectList());
        if(count($filter) > 0) $this->setFilter($this->table, $filter);
        $this->setCondition($this->table, $dto);
        return parent::getListPDO($this->table, $select);
    }

    function getListWhere($select = [], $where = [])
    {
        return $this->getList($select, ['where' => $where]);
    }

    function getData($select = [], $dto = [])
    {
        if(has_inner_array($select)) show_error('Select Parameter is Unacceptable');
        if(empty($select)) $this->db->select($this->getSelectList());
        $this->setCondition($this->table, $dto, false);
        return parent::getDataPDO($this->table, $select);
    }

    function getDataWhere($select = [], $where = [])
    {
        return $this->getData($select, ['where' => $where]);
    }

    function getCnt($dto = [], $filter = [])
    {
        if(count($filter) > 0) $this->setFilter($this->table, $filter);
        $this->setCondition($this->table, $dto, false);
        return parent::getCntPDO($this->table);
    }

    function getCntWhere($where = [])
    {
        return $this->getCnt(['where' => $where]);
    }

    function addList($set)
    {
        $set = $this->getValidSetList($set, false);

        return parent::addListPDO($this->table, $set);
    }

    function addData($set, $bool = false)
    {
        if(!$this->isAutoIncrement) $bool = false;

        $set = $this->getValidSetData($set, false);

        return parent::addDataPDO($this->table, $set, $bool);
    }

    function modData($set, $where, $bool = false)
    {
        if(!$this->isAutoIncrement) $bool = false;

        $this->validateWhereCondition($where);

        $set = $this->getValidSetData($set);

        return parent::modDataPDO($this->table, $set, $where, $bool);
    }

    function modNumb($field, $count, $where, $bool = false)
    {
        if ($count > 0) {
            $this->db->set($field, $field . '+' . $count, false);
        } else {
            $this->db->set($field, $field . $count, false);
        }

        $this->validateWhereCondition($where);

        $set = $this->getValidSetData([]);

        return $this->modDataPDO($this->table, $set, $where, $bool);
    }

    function delData($where, $bool = false, $isSoftDelete = true, $set = [])
    {
        $this->validateWhereCondition($where);

        if($this->isDelYn) {
            if($isSoftDelete) {
                $set = $this->getValidSetData(array_merge($set, [
                    DEL_YN_COLUMN_NAME => 'Y'
                ]));
                return parent::modDataPDO($this->table, $set, $where, $bool);
            }else{
                return parent::delDataPDO($this->table, $where, $bool);
            }
        }else{
            return parent::delDataPDO($this->table, $where, $bool);
        }
    }

    function checkDuplicate($where, $whereNot = [], $isIncludeDeleted = true)
    {
        if(empty($where)) throw new Exception("checkDuplicate : where parameter empty");

        if(count($this->uniqueKeyList) > 0) {
            $this->where($this->table, $where);
            if($this->isDelYn && $isIncludeDeleted === false) $this->db->where($this->table.".".DEL_YN_COLUMN_NAME, 'N');
            if($this->isUseYn && $isIncludeDeleted === false) $this->db->where($this->table.".".USE_YN_COLUMN_NAME, 'N');
            foreach ($whereNot as $key=>$val) $this->db->where_not_in($key, [$val]);
            return parent::getCntPDO($this->table);
        }else{
            return false;
        }
    }

    function reorder($where, $sortField, $sortItem = null, $newIndex = 0)
    {
        $columnList = $this->getColumnList();
        if(!in_array($sortField, $columnList)) return false;
        if(!count($this->primaryKeyList)) return false;

        if($sortItem) {
            foreach ($sortItem as $key=>$val) $this->db->where("$key <> $val");

            $list = $this->getList([], [
                'where' => $where,
                'orderBy' => [$sortField => 'ASC'],
            ]);
            $idx = 1;
            $matched = false;
            foreach ($list as $item) {
                if((int)$item->{$sortField} >= (int)$newIndex && !$matched) {
                    $matched = true;
                    $idx++;
                }

                $itemWhere = [];
                if($this->identifier) {
                    $itemWhere = [$this->identifier => $item->{$this->identifier}];
                }else{
                    foreach ($this->primaryKeyList as $key) $itemWhere[$key] = $item->{$key};
                }

                $this->modData([$sortField => $idx], $itemWhere);
                $idx++;
            }

            $this->modData([$sortField => $newIndex], $sortItem);
        }else{
            $list = $this->getList([], [
                'where' => $where,
                'orderBy' => [$sortField => 'ASC'],
            ]);
            foreach ($list as $i=>$item) {
                $itemWhere = [];
                if($this->identifier) {
                    $itemWhere = [$this->identifier => $item->{$this->identifier}];
                }else{
                    foreach ($this->primaryKeyList as $key) $itemWhere[$key] = $item->{$key};
                }

                $this->modData([$sortField => $i+1], $itemWhere);
            }
        }
    }

    protected function getSelectList(): array
    {
        $columnList = $this->getColumnList();
        if($this->isDelYn) $columnList[] = DEL_YN_COLUMN_NAME;
        if($this->isUseYn) $columnList[] = USE_YN_COLUMN_NAME;
        if($this->isCreatedDt) {
            $columnList[] = CREATED_DT_COLUMN_NAME;
            if($this->isCreatedId) $columnList[] = CREATED_ID_COLUMN_NAME;
        }
        if($this->isUpdatedDt) {
            $columnList[] = UPDATED_DT_COLUMN_NAME;
            if($this->isCreatedId) $columnList[] = UPDATED_ID_COLUMN_NAME;
        }
        foreach ($columnList as $idx=>$column) $columnList[$idx] = "$this->table.$column";
        return $columnList;
    }

    protected function getValidSetList($set, $isUpdate = true): array
    {
        return array_map(function($item) {
            if(!is_array($item)) $item = (array)$item;
            return $this->getValidSetData($item, false, true);
        }, $set);
    }

    protected function getValidSetData($set, $isUpdate = true, $isListData = false): array
    {
        $columnList = $this->getColumnList();

        $set = array_filter($set, function($key) use ($columnList) {
            return in_array($key, $columnList);
        }, ARRAY_FILTER_USE_KEY);

        if(!$isUpdate) {
            $set = $this->setCreatedDt($set, $isListData);
            $set = $this->setCreatedId($set, $isListData);
        }else{
            $set = $this->setUpdatedDt($set, $isListData);
            $set = $this->setUpdatedId($set, $isListData);
        }

        return $set;
    }

    public function getColumnList(): array
    {
        $list = array_values(array_unique(
            array_filter(
                array_merge(
                    $this->notNullList,
                    $this->nullList
                )
            )
        ));

        if($this->isDelYn && !in_array(DEL_YN_COLUMN_NAME, $list)) $list[] = DEL_YN_COLUMN_NAME;
        if($this->isUseYn && !in_array(USE_YN_COLUMN_NAME, $list)) $list[] = USE_YN_COLUMN_NAME;

        return $list;
    }

    public function getTypedColumnList(): array
    {
        $list = array_values(array_unique(array_filter([
            ...$this->strList,
            ...$this->intList,
            ...$this->fileList,
        ])));

        if ($this->isDelYn && !in_array(DEL_YN_COLUMN_NAME, $list, true)) {
            $list[] = DEL_YN_COLUMN_NAME;
        }

        if ($this->isUseYn && !in_array(USE_YN_COLUMN_NAME, $list, true)) {
            $list[] = USE_YN_COLUMN_NAME;
        }

        return $list;
    }

    protected function setCreatedDt($set = array(), $isListData = false)
    {
        if($this->isCreatedDt) {
            $value = is_empty($set, CREATED_DT_COLUMN_NAME) ? date('Y-m-d H:i:s') : $set[CREATED_DT_COLUMN_NAME];
            if($isListData) {
                $set[CREATED_DT_COLUMN_NAME] = $value;
            }else{
                $this->db->set(CREATED_DT_COLUMN_NAME, $value);
            }
        }
        return $set;
    }

    protected function setCreatedId($set = array(), $isListData = false)
    {
        if($this->isCreatedId) {
            $userId = $this->session->userdata(USER_ID_COLUMN_NAME) ?? 1;
            $value = is_empty($set, CREATED_ID_COLUMN_NAME) ? $userId : $set[CREATED_ID_COLUMN_NAME];
            if($isListData) {
                $set[CREATED_ID_COLUMN_NAME] = $value;
            }else{
                $this->db->set(CREATED_ID_COLUMN_NAME, $value);
            }
        }
        return $set;
    }

    protected function setUpdatedDt($set = array(), $isListData = false)
    {
        if($this->isUpdatedDt) {
            $value = is_empty($set, UPDATED_DT_COLUMN_NAME) ? date('Y-m-d H:i:s') : $set[UPDATED_DT_COLUMN_NAME];
            if($isListData) {
                $set[UPDATED_DT_COLUMN_NAME] = $value;
            }else{
                $this->db->set(UPDATED_DT_COLUMN_NAME, $value);
            }
        }
        return $set;
    }

    protected function setUpdatedId($set = array(), $isListData = false)
    {
        if($this->isCreatedId) {
            $userId = $this->session->userdata(USER_ID_COLUMN_NAME) ?? 1;
            $value = is_empty($set, UPDATED_ID_COLUMN_NAME) ? $userId : $set[UPDATED_ID_COLUMN_NAME];
            if($isListData) {
                $set[UPDATED_ID_COLUMN_NAME] = $value;
            }else{
                $this->db->set(UPDATED_ID_COLUMN_NAME, $value);
            }
        }
        return $set;
    }

    public function determineDiffColumns(): array
    {
        $columnList = $this->getColumnList();
        $typedColumnList = $this->getTypedColumnList();

        return [
            'missingTypeDefinition' => array_values(array_diff($columnList, $typedColumnList)),
            'missingColumnDefinition' => array_values(array_diff($typedColumnList, $columnList)),
        ];
    }

    protected function validateModelColumns(): bool
    {
        $diff = $this->determineDiffColumns();

        return empty($diff['missingTypeDefinition'])
            && empty($diff['missingColumnDefinition']);
    }

    protected function validatePrimaryKeyDefinition(): bool
    {
        if ($this->isAutoIncrement) {
            if (count($this->primaryKeyList) !== 1) {
                return false;
            }

            if (!$this->identifier) {
                return false;
            }

            if (!in_array($this->identifier, $this->primaryKeyList)) {
                return false;
            }
        }

        return true;
    }

    public function validateNullDefinition(): bool
    {
        $duplicated = array_intersect($this->notNullList, $this->nullList);

        return count($duplicated) === 0;
    }

    public function validateColumnRoleDefinition(): bool
    {
        $columnList = $this->getColumnList();

        if ($this->identifier && !in_array($this->identifier, $columnList, true)) {
            return false;
        }

        foreach ($this->primaryKeyList as $column) {
            if (!in_array($column, $columnList, true)) {
                return false;
            }
        }

        foreach ($this->uniqueKeyList as $column) {
            if (!in_array($column, $columnList, true)) {
                return false;
            }
        }

        if (property_exists($this, 'foreignKeyList')) {
            foreach ($this->foreignKeyList as $column => $config) {
                if (!in_array($column, $columnList, true)) {
                    return false;
                }
            }
        }

        return true;
    }

    public function validateModelDefinition()
    {
        return $this->validateNullDefinition()
            && $this->validateModelColumns()
            && $this->validateColumnRoleDefinition()
            && $this->validatePrimaryKeyDefinition();
    }

    public function setSelect($table, $data)
    {

    }

    public function setCondition($table, $data, $list = true)
    {
        foreach ($data as $key => $val) {
            if($key === 'select') continue;
            if(!in_array($key, ['where','whereIn','whereNot','like','orLike','limit','orderBy','groupBy','filter','join'])) continue;
            if(!$list && in_array($key, ['limit','orderBy','groupBy'])) continue;

            if(in_array($key, ['limit'])) {
                $this->{$key}($val);
            }else if($key === 'filter') {
                $this->setFilter($table, $val);
            }else{
                $this->{$key}($table, $val);
            }
        }
        if(!array_key_exists('where', $data)) $this->where($table, []);
        if($list && !array_key_exists('orderBy', $data)) $this->orderBy($table, []);
    }

    public function setFilter($table, $filter)
    {
        if(empty($filter)) return;

        $this->setFilterWhere($table, $filter['where'] ?? []);

        $this->setFilterWhereIn($table, $filter['whereIn'] ?? []);

        $this->setFilterWhereNot($table, $filter['whereNot'] ?? []);

        $this->setFilterLike($table, $filter['like'] ?? []);

        $this->setFilterDate($table, $filter['date'] ?? []);
    }

    public function setFilterWhere($table, $data)
    {
        $this->where($table, $data);
    }

    public function setFilterWhereIn($table, $data)
    {
        $this->whereIn($table, $data);
    }

    public function setFilterWhereNot($table, $data)
    {
        $this->whereNot($table, $data);
    }

    public function setFilterLike($table, $data)
    {
        foreach ($data as $item) {
            if(!is_empty($item, 'value')) {
                if(!is_empty($item, 'field')) {
                    $this->like($table, [$item['field'] => $item['value']]);
                }else{
                    $this->orLike($table, $this->strList, $item['value']);
                }
            }
        }
    }

    public function setFilterDate($table, $data)
    {
        if($this->filteringDateColumn) {
            $columnName = 'DATE_FORMAT('.$this->filteringDateColumn.',"%Y-%m-%d")';
            if(array_key_exists('on_date', $data) && !empty($data['on_date'])) {
                $this->db->where($columnName, $data['on_date']);
            }else{
                if(array_key_exists('start_date', $data) && !empty($data['start_date'])) {
                    $this->db->where($columnName.' >=', $data['start_date']);
                }
                if(array_key_exists('end_date', $data) && !empty($data['end_date'])) {
                    $this->db->where($columnName.' <=', $data['end_date']);
                }
            }
        }
    }

    public function getTableList()
    {
        $query = $this->db->query("
            SELECT *
            FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
        ");

        return $query->result_array();
    }

    public function getTableCount()
    {
        $query = $this->db->query("
            SELECT COUNT(*) AS table_count
            FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
        ");

        return $query->row()->table_count;
    }

    public function getNotNullColumns($tableName)
    {
        $tableName = $this->dbprefix.$tableName;
        $query = $this->db->query("
            SELECT COLUMN_NAME
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = ?
            AND IS_NULLABLE = 'NO'
            AND COLUMN_DEFAULT IS NULL
        ", [$tableName]);

        return array_column($query->result_array(), 'COLUMN_NAME');
    }

    public function deleteAllTables()
    {
        $this->db->query("
SET FOREIGN_KEY_CHECKS = 0;

SET @sql = (
    SELECT GROUP_CONCAT('DROP TABLE IF EXISTS `', table_name, '`')
    FROM INFORMATION_SCHEMA.TABLES
    WHERE table_schema = DATABASE()
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET FOREIGN_KEY_CHECKS = 1;
		");
    }

    public function checkSystemUserExist(): bool
    {
        return $this->db
                ->where(['user_cd' => 'USR000'])
                ->from(USER_TABLE_NAME)
                ->count_all_results() > 0;
    }

    public function truncate()
    {
        $this->db->truncate($this->table);
    }

    protected function validateWhereCondition($where): void
    {
        if(has_inner_array($where)) show_error(__FUNCTION__ . ': Please check the where condition');
        return;
    }
}
