<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class MY_Model extends CI_Model
{
    public string $table = '';
    public string $identifier = '';
    public array $primaryKeyList = [];
    public array $uniqueKeyList = [];
    public array $notNullList = [];
    public array $nullList = [];
    public array $strList = [];
    public array $intList = [];
    public array $fileList = [];
    public array $defaultOrderBy = [];

    public bool    $isAutoIncrement = false;
    public bool    $isDelYn = false;
    public bool    $isUseYn = false;
    public bool    $isCreatedDt = false;
    public bool    $isCreatedId = false;
    public bool    $isUpdatedDt = false;

    function __construct()
    {
        log_message('info', 'Model Class Initialized');
        $this->load->database();
    }

    /*
    |--------------------------------------------------------------------------
    | Query 직접 작성
    |--------------------------------------------------------------------------
    */
    public function getDataQuery($sql,$array)
    {
        return $this->db->query($sql,$array)->row();
    }

    public function getListQuery($sql,$array)
    {
        return $this->db->query($sql,$array)->result();
    }

    public function getCntQuery($sql,$array)
    {
        return $this->db->query($sql,$array)->row()->cnt;
    }

    public function addSqlSet($dto)
    {
        $sql = " SET ";
        foreach ($dto as $key=>$value){
            $sql .= " {$key} = '{$value}',";
        }
        return substr($sql, 0, -1);
    }

    public function addSqlWhere($dto)
    {
        $sql = " WHERE 1=1 ";
        foreach ($dto as $key=>$value){
            $sql .= " AND {$key} = '{$value}' ";
        }
        return $sql;
    }

    public function querySql($sql, $params, $returnBool = false)
    {
        $insert = (strpos($sql, "INSERT INTO") !== -1);

        $this->db->trans_begin();

        $this->db->query($sql, $params);

        $result = $this->db->trans_status();

        if ($result === false){
            $query_log = $this->db->last_query();
            log_message('error'," query :  '$query_log \r\n' ");
            $this->db->trans_rollback();
        }else{
            if ($returnBool === false){
                $result = ($insert)?$this->db->insert_id():$this->db->affected_rows();
            }
            $this->db->trans_commit();
        }

        return $result;
    }

    /*
    |--------------------------------------------------------------------------
    | Query 빌더 (PDO)
    |--------------------------------------------------------------------------
    */
    public function getDataPDO($table, $select = [], $where = [])
    {
        $this->db->where($where);
        if(count($select) > 0) $this->db->select($select);

        $result = $this->db->get($table)->row();

        if(count($select) === 1) {
            if($result && property_exists($result, $select[0])){
                return $result->{$select[0]};
            }else{
                return null;
            }
        }else{
            return $result;
        }
    }

    public function getListPDO($table, $select = [], $where = [])
    {
        $this->db->where($where);
        if(count($select) > 0) $this->db->select($select);

        $result = $this->db->get($table)->result();

        if(count($select) === 1) {
            return array_map(function ($curr) use ($select) {
                return $curr->{$select[0]};
            }, $result);
        }else{
            return $result;
        }
    }

    public function getCntPDO($table, $where = [])
    {
        $this->db->where($where);
        return $this->db->count_all_results($table);
    }

    public function addListPDO($table, $set)
    {
        $this->db->trans_begin();

        if($this->db->insert_batch($table, $set)){
            return $this->afterTrans(true, true);
        }else{
            return $this->db->error();
        }
    }

    public function addDataPDO($table, $set, $returnBool = false)
    {
        $this->db->trans_begin();

        $this->db
            ->set($set);

        if($this->db->insert($table)){
            return $this->afterTrans(true, $returnBool);
        }else{
            return $this->db->error();
        }
    }

    public function modDataPDO($table, $set, $where, $returnBool = false)
    {
        $this->db->trans_begin();

        $this->db
            ->set($set)
            ->where($where);

        if($this->db->update($table)){
            return $this->afterTrans(false, $returnBool);
        }else{
            return $this->db->error();
        }
    }

    public function delDataPDO($table, $where, $returnBool = false)
    {
        $this->db->trans_begin();

        $this->db->delete($table, $where);

        return $this->afterTrans(false, $returnBool);
    }

    public function afterTrans($insert = true, $returnBool = false)
    {
        $result = $this->db->trans_status();

        if ($this->db->trans_status() === false)
        {
            $query_log = $this->db->last_query();
            log_message('error'," query :  '$query_log \r\n' ");
            $this->db->trans_rollback();
        }
        else
        {
            if ($returnBool === false)
            {
                $result = ($insert)?$this->db->insert_id():$this->db->affected_rows();
            }
            $this->db->trans_commit();
        }

        return $result;
    }

    /*
    |--------------------------------------------------------------------------
    | 사용 공통 함수
    |--------------------------------------------------------------------------
    */
    public function where($table, $data)
    {
        if(!empty($data)) {
            if(is_list_type($data)) {
                foreach ($data as $item) {
                    $this->where($table, $item);
                }
            }else{
                foreach ($data as $key=>$val) {
                    $column = str_contains($key, '.') ? $key : $table.'.'.$key;
                    if(is_array($val)) {
                        $this->db->where_in($column, $val);
                    }else{
                        $this->db->where($column, $val);
                    }
                }
            }
        }
        if($this->isDelYn) $this->db->where($table.".".DEL_YN_COLUMN_NAME, 'N');
        if($this->isUseYn && !array_key_exists(USE_YN_COLUMN_NAME, $data)) $this->db->where($table.".".USE_YN_COLUMN_NAME, 'Y');
    }

    public function like($table, $data = [])
    {
        if(!empty($data)) {
            if(is_list_type($data)) {
                foreach ($data as $item) {
                    $this->like($table, $item);
                }
            }else{
                foreach ($data as $key=>$val) {
                    $column = str_contains($key, '.') ? $key : $table.'.'.$key;
                    if(is_array($val)) {
                        $this->db->group_start();
                        foreach ($val as $i=>$subVal) {
                            if($i === 0) {
                                $this->db->like($column, $subVal, 'both');
                            }else{
                                $this->db->or_like($column, $subVal, 'both');
                            }
                        }
                        $this->db->group_end();
                    }else{
                        $this->db->like($column, $val, 'both');
                    }
                }
            }
        }
    }

    public function orLike($table, $keys, $vals)
    {
        if(count($keys) > 0) {
            $this->db->group_start();
            if(is_array($keys) && is_array($vals)) {
                show_error('Only One among Keys and Vals can be array type.');
            }elseif (is_array($keys) && !is_array($vals)) {
                foreach ($keys as $i=>$key) {
                    $column = str_contains($key, '.') ? $key : $table.'.'.$key;
                    if($i === 0) {
                        $this->db->like($column, $vals, 'both');
                    }else{
                        $this->db->or_like($column, $vals, 'both');
                    }
                }
            }elseif (!is_array($keys) && is_array($vals)) {
                $column = str_contains($keys, '.') ? $keys : $table.'.'.$keys;
                foreach ($vals as $i=>$val) {
                    if($i === 0) {
                        $this->db->like($column, $val, 'both');
                    }else{
                        $this->db->or_like($column, $val, 'both');
                    }
                }
            }else{
                $this->like($table, [$keys => $vals]);
            }
            $this->db->group_end();
        }
    }

    public function whereIn($table, $data = [])
    {
        if(!empty($data)) {
            if(is_list_type($data)) {
                foreach ($data as $item) {
                    $this->whereIn($table, $item);
                }
            }else{
                foreach ($data as $key=>$val) {
                    $column = str_contains($key, '.') ? $key : $table.'.'.$key;
                    if(is_array($val)) {
                        $this->db->where_in($column, $val);
                    }else{
                        $this->db->where_in($column, [$val]);
                    }
                }
            }
        }
    }

    public function whereNot($table, $data = [])
    {
        if(is_list_type($data)) {
            foreach ($data as $item) {
                $this->whereNot($table, $item);
            }
        }else{
            foreach ($data as $key=>$val) {
                $column = str_contains($key, '.') ? $key : $table.'.'.$key;
                if(is_array($val)) {
                    $this->db->where_not_in($column, $val);
                }else{
                    $this->db->where_not_in($column, [$val]);
                }
            }
        }
    }

    public function limit($data)
    {
        if(!empty($data)) {
            if(array_key_exists('limit', $data)){
                $offset = (array_key_exists('offset', $data))?$data['offset']:0;
                $this->db->limit($data['limit'], $offset);
            }else{
                $this->db->limit($data[0], $data[1]);
            }
        }
    }

    public function join($table, $data = [], $alias = null)
    {
        if(!empty($data)) {
            if(is_list_type($data)){
                foreach ($data as $item) $this->join($table, $item);
            }else{
                if(!qb_join_exists($this->db, $data['table'])) {
                    if(is_empty($data, 'select')) {
                        foreach ($data['matches'] as $key=>$val) {
                            $this->db->select($data['table'].'.'.$key);
                        }
                    }else if(is_string($data['select'])){
                        $this->db->select($data['table'].'.'.$data['select']);
                    }else{
                        foreach ($data['select'] as $select) {
                            $this->db->select($data['table'].'.'.$select);
                        }
                    }

                    $matchQuery = '';
                    $i = 0;
                    foreach ($data['matches'] as $key=>$val) {
                        if($i > 0) $matchQuery .= " AND ";
                        $matchQuery .= " {$data['table']}.{$key} = {$table}.{$val} ";
                        $i++;
                    }

                    if(!qb_join_exists($this->db, $data['table'], $matchQuery, $data['direction']??'left')) {
                        $this->db->join(
                            $data['table'],
                            $matchQuery,
                            $data['direction']??'left'
                        );
                    }
                }
            }
        }
    }

    public function orderBy($table, $data = [])
    {
        if(!empty($data)) {
            if(is_list_type($data)) {
                foreach ($data as $item) {
                    foreach ($item as $key => $val) {
                        $column = str_contains($key, '.') ? $key : $table.'.'.$key;
                        $this->db->order_by($column, $val);
                    }
                }
            }else{
                foreach ($data as $key => $val) {
                    $column = str_contains($key, '.') ? $key : $table.'.'.$key;
                    $this->db->order_by($column, $val);
                }
            }
        }else{
            if(!empty($defaultOrderBy)) {
                $this->orderBy($defaultOrderBy);
            }else if($this->identifier && $this->isAutoIncrement === true) {
                $this->db->order_by("$table.$this->identifier", 'DESC');
            }else if($this->isCreatedDt) {
                $this->db->order_by("$table.".CREATED_DT_COLUMN_NAME, 'DESC');
            }
        }
    }

    public function groupBy($table, $data = [])
    {
        if(!empty($data)) {
            if(is_array($data)) {
                foreach ($data as $item) {
                    $this->groupBy($table, $item);
                }
            }else{
                $column = str_contains($data, '.') ? $data : $table.'.'.$data;
                $this->db->group_by($column);
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | 기타 테이블 및 컬럼 정보
    |--------------------------------------------------------------------------
    */
    public function getTableInfo($table = '')
    {
        if(!$table) $table = $this->table;
        return $this->getListQuery("
			SELECT * 
			FROM
			    INFORMATION_SCHEMA.columns
			WHERE
			    1=1
				AND table_schema = ?
				AND table_name = ?
		", [
            $this->db->database,
            $this->db->dbprefix.$table
        ]);
    }

    public function getTableColumns($table = ''): array
    {
        if(!$table) $table = $this->table;
        $result = array_map(function($item) {
            return (array)$item;
        }, $this->getTableInfo($table));
        return array_column($result, 'COLUMN_NAME');
    }

    public function getTableFields($table = '')
    {
        if(!$table) $table = $this->table;
        return $this->db->list_fields($table);
    }
}
