<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Model_Common extends MY_Model
{
    function __construct()
    {
        parent::__construct();
    }

    function getList($select = [], $where = [], $like = [], $limit = [], $orderBy = [], $filter = [])
    {
        $this->setFilter($filter);
        $this->limit($limit);
        $this->orderBy($orderBy);
        $this->where($this->table, $where, $like);
        if(empty($select)) $this->db->select($this->getSelectList());
        if($this->isDelYn) $this->db->where($this->table.".".DEL_YN_COLUMN_NAME, 'N');
        if($this->isUseYn && !array_key_exists(USE_YN_COLUMN_NAME, $where)) $this->db->where($this->table.".".USE_YN_COLUMN_NAME, 'Y');

        return parent::getListPDO($this->table, $select);
    }

    function getData($select = [], $where = [])
    {
        $this->where($this->table, $where, []);
        if(empty($select)) $this->db->select($this->getSelectList());
        if($this->isDelYn) $this->db->where($this->table.".".DEL_YN_COLUMN_NAME, 'N');
        if($this->isUseYn && !array_key_exists(USE_YN_COLUMN_NAME, $where)) $this->db->where($this->table.".".USE_YN_COLUMN_NAME, 'Y');

        return parent::getDataPDO($this->table, $select);
    }

    function getCnt($where = [], $like = [], $filter = [])
    {
        $this->setFilter($filter);
        $this->where($this->table, $where, $like);
        if($this->isDelYn) $this->db->where($this->table.".".DEL_YN_COLUMN_NAME, 'N');
        if($this->isUseYn && !array_key_exists(USE_YN_COLUMN_NAME, $where)) $this->db->where($this->table.".".USE_YN_COLUMN_NAME, 'Y');

        return parent::getCntPDO($this->table);
    }

    function addList($set)
    {
        $set = $this->getValidSetList($set);

        return parent::addListPDO($this->table, $set);
    }

    function addData($set, $bool = false)
    {
        $this->setCreatedId($set);
        if(!$this->isAutoincrement) $bool = false;

        $set = $this->getValidSetData($set);

        return parent::addDataPDO($this->table, $set, $bool);
    }

    function modData($set, $where, $bool = false)
    {
        if($this->isUpdatedDt) {
            $this->db->set(UPDATED_DT_COLUMN_NAME, 'now()', false);
            $this->setUpdatedId($set);
        }
        if(!$this->isAutoincrement) $bool = false;

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

        return $this->modDataPDO($this->table, [], $where, $bool);
    }

    function delData($where, $bool = false, $isSoftDelete = true, $set = [])
    {
        if($this->isDelYn) {
            if($isSoftDelete) {
                $this->db->set(DEL_YN_COLUMN_NAME, 'Y')->set(UPDATED_DT_COLUMN_NAME, 'now()', false);
                $this->setUpdatedId($set);
                return parent::modDataPDO($this->table, [], $where, $bool);
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
        if(!$this->identifier && !count($this->primaryKeyList)) return false;

        if($sortItem) {
            foreach ($sortItem as $key=>$val) $this->db->where("$key <> $val");

            $list = $this->getList([], $where, [], [], [$sortField => 'ASC']);
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
            $list = $this->getList([], $where, [], [], [$sortField => 'ASC']);
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

    protected function getValidSetList($set): array
    {
        return array_map(function($item) {
            if(!is_array($item)) $item = (array)$item;
            return $this->getValidSetData($item);
        }, $set);
    }

    protected function getValidSetData($set): array
    {
        $columnList = $this->getColumnList();
        $set = array_filter($set, function($key) use ($columnList) {
            return in_array($key, $columnList);
        }, ARRAY_FILTER_USE_KEY);

        if($this->isCreatedId && is_empty($set, CREATED_ID_COLUMN_NAME)) {
            $set[CREATED_ID_COLUMN_NAME] = $this->session->userdata(USER_ID_COLUMN_NAME) ?? 1;
        }
        return $set;
    }

    public function getColumnList(): array
    {
        return array_values(array_unique(
            array_filter(
                array_merge(
                    [$this->identifier],
                    $this->primaryKeyList,
                    $this->notNullList,
                    $this->nullList
                )
            )
        ));
    }

    protected function setCreatedId($set)
    {
        $userId = $this->session->userdata(USER_ID_COLUMN_NAME) ?? 1;
        if($this->isCreatedId)
            $this->db->set(CREATED_ID_COLUMN_NAME, is_empty($set, CREATED_ID_COLUMN_NAME) ? $userId : $set[CREATED_ID_COLUMN_NAME]);
    }

    protected function setUpdatedId($set)
    {
        $userId = $this->session->userdata(USER_ID_COLUMN_NAME) ?? 1;
        if($this->isCreatedId)
            $this->db->set(UPDATED_ID_COLUMN_NAME, is_empty($set, UPDATED_ID_COLUMN_NAME) ? $userId : $set[UPDATED_ID_COLUMN_NAME]);
    }

    public function validateTableColumns(): bool
    {
        return count($this->getColumnList()) === count([...$this->strList, ...$this->intList, ...$this->fileList]);
    }

    public function setFilter($filter)
    {
        if(empty($filter)) return null;

        $where = $filter['where'] ?? [];
        foreach ($where as $key => $val) {
            $this->db->where("{$this->table}.{$key}", $val);
        }

        $like = $filter['like'] ?? [];
        if(!is_empty($like, 'value')) {
            if(!is_empty($like, 'field')) {
                $this->db->like("{$this->table}.{$like['field']}", $like['value'], 'both');
            }else{
                if(count($this->strList)) {
                    foreach ($this->strList as $i=>$field) {
                        if($i === 0){
                            $this->db->like("{$this->table}.$field", $like['value'], 'both');
                        }else{
                            $this->db->or_like("{$this->table}.$field", $like['value'], 'both');
                        }
                    }
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
        $tableName = $this->db->dbprefix.$tableName;
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
}