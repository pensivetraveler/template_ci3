<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once __DIR__.'/Model_Common.php';

class Model_Sys_Code extends Model_Common
{
	public string  $table = 'sys_code';
	public string  $identifier = 'cmb_cd';
	public array   $primaryKeyList = ['cmb_cd'];
	public array   $uniqueKeyList = [];
	public array   $notNullList = ['cmb_cd','big_cd','sml_cd','cd_name','cd_srt',];
	public array   $nullList = ['cd_val','cd_desc','cd_nick','use_yn',];
	public array   $strList = ['cmb_cd','big_cd','sml_cd','cd_name','cd_val','cd_desc','cd_nick','use_yn',];
	public array   $intList = ['cd_srt',];
	public array   $fileList = [];

	public bool    $isCreatedDt = true;
	public bool    $isCreatedId = true;
	public bool    $isUpdatedDt = true;

	function __construct()
	{
		parent::__construct();
	}

	function getList($select = [], $where = [], $like = [], $limit = [], $orderBy = [], $filter = [])
	{
		if(count($orderBy) === 0) {
			$orderBy = ['cd_srt' => 'ASC'];
		}

		return parent::getList($select, $where, $like, $limit, $orderBy, $filter);
	}

    public function getListByBig($select = [], $where = [], $like = [], $limit = [], $orderBy = [], $filter = [])
    {
        return array_reduce($this->getList($select, $where, $like, $limit, $orderBy, $filter), function ($carry, $item) {
            $carry[$item->big_cd][] = (array)$item;
            return $carry;
        });
    }

    public function getBigCodeList()
    {
        $this->db->group_by('big_cd');
        return $this->getList(['big_cd', 'cd_name'], [], [], [], ['big_cd' => 'desc']);
    }
}
