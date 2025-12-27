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
        $set = $this->getValidSetList($set);

        return parent::addListPDO($this->table, $set);
    }

    function addData($set, $bool = false)
    {
        $this->setCreatedId($set);
        if(!$this->isAutoIncrement) $bool = false;

        $set = $this->getValidSetData($set);

        return parent::addDataPDO($this->table, $set, $bool);
    }

    function modData($set, $where, $bool = false)
    {
        if($this->isUpdatedDt) {
            $this->db->set(UPDATED_DT_COLUMN_NAME, 'now()', false);
            $this->setUpdatedId($set);
        }
        if(!$this->isAutoIncrement) $bool = false;

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

    public function determineDiffColumns(): array
    {
        $arr1 = $this->getColumnList();
        $arr2 = array_unique([...$this->strList, ...$this->intList, ...$this->fileList]);
        return array_values(array_diff(array_merge($arr1, $arr2), array_intersect($arr1, $arr2)));
    }

    public function validateTableColumns(): bool
    {
        return count($this->getColumnList()) === count(array_unique([...$this->strList, ...$this->intList, ...$this->fileList]));
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

        $this->setFilterLike($table, $filter['like'] ?? []);

        $this->setFilterDate($table, $filter['date'] ?? []);
    }

    public function setFilterWhere($table, $data)
    {
        $this->where($table, $data);
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
        if($this->isCreatedDt) {
            $columnName = 'DATE_FORMAT('.CREATED_DT_COLUMN_NAME.',"%Y-%m-%d")';
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

    public function truncate()
    {
        $this->db->truncate($this->table);
    }
}
