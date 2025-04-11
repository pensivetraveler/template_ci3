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

	public bool    $isAutoincrement = false;
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
	public function where($table, $where, $like = [])
    {
		if(count($where) > 0) {
			foreach ($where as $key=>$val) {
				if(is_numeric($key)) {
					$this->db->where($table.'.'.$val);
				}else{
					if(is_array($val)) {
						$this->db->where_in($table.'.'.$key, $val);
					}else{
						$this->db->where($table.'.'.$key, $val);
					}
				}
			}
		}

        if(count($like) > 0) {
            $this->db->group_start();
            foreach ($like as $key=>$val) {
                if($key === 0) $this->db->like($key, $val, 'both');
                $this->db->or_like($key, $val, 'both');
            }
            $this->db->group_end();
        }
    }

    public function limit($data)
    {
        if(count($data) > 0){
            if(array_key_exists('limit', $data)){
                $offset = (array_key_exists('offset', $data))?$data['offset']:0;
                $this->db->limit($data['limit'], $offset);
			}else{
				$this->db->limit($data[0], $data[1]);
			}
        }
    }

    public function orderBy($data)
    {
        if(count($data) > 0) {
            if(is_list_type($data)) {
                foreach ($data as $k=>$v) {
                    foreach ($v as $key => $value) $this->db->order_by($key, $value);
                }
            }else{
                foreach ($data as $key => $value) $this->db->order_by($key, $value);
            }
        }else{
			if($this->identifier && $this->isAutoincrement === true) {
				$this->orderBy(["$this->table.$this->identifier" => 'DESC']);
			}else if($this->isCreatedDt) {
				$this->orderBy(["$this->table.".CREATED_DT_COLUMN_NAME => 'DESC']);
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
