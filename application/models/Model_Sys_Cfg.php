<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once __DIR__.'/Model_Common.php';

class Model_Sys_Cfg extends Model_Common
{
	public string  $table = 'sys_cfg';
	public string  $identifier = 'cmb_cfg';
	public array   $primaryKeyList = ['cmb_cfg'];
	public array   $uniqueKeyList = [];
	public array   $notNullList = ['cmb_cfg','big_cfg','sml_cfg','cfg_name','cfg_type'];
	public array   $nullList = ['cfg_val','cfg_desc'];
	public array   $strList = ['cmb_cfg','big_cfg','sml_cfg','cfg_name','cfg_type','cfg_val','cfg_desc',];
	public array   $intList = [];
	public array   $fileList = [];

	public bool    $isCreatedDt = true;
	public bool    $isCreatedId = true;
	public bool    $isUpdatedDt = true;

    public function getListByBig($select = [], $where = [], $like = [], $limit = [], $orderBy = [], $filter = [])
    {
        return array_reduce($this->getList($select, $where, $like, $limit, $orderBy, $filter), function ($carry, $item) {
            $carry[$item->big_cfg][] = (array)$item;
            return $carry;
        });
    }
}
