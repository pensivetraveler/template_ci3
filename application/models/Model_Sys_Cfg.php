<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once __DIR__.'/Model_Common.php';

class Model_Sys_Cfg extends Model_Common
{
    const CATEGORY_SML = '000';

	public string  $table = 'sys_cfg';
	public string  $identifier = 'cmb_cfg';
	public array   $primaryKeyList = ['cmb_cfg'];
	public array   $uniqueKeyList = [];
	public array   $notNullList = ['cmb_cfg','big_cfg','sml_cfg','cfg_name','cfg_type'];
	public array   $nullList = ['cfg_val','cfg_desc'];
	public array   $strList = ['cmb_cfg','big_cfg','sml_cfg','cfg_name','cfg_type','cfg_val','cfg_desc',];
	public array   $intList = [];
	public array   $fileList = [];
    public array   $defaultOrderBy = [
        ['big_cfg' => 'ASC'],
        ['sml_cfg' => 'ASC'],
    ];

	public bool    $isCreatedDt = true;
	public bool    $isCreatedId = true;
	public bool    $isUpdatedDt = true;

    public function getListByBig($select = [], $dto = [])
    {
        return array_reduce($this->getList($select, $dto), function ($carry, $item) {
            if($item->sml_cfg === self::CATEGORY_SML) return $carry;
            $carry[$item->big_cfg][] = (array)$item;
            return $carry;
        });
    }

    /**
     * 탭으로 보여 줄 카테고리. 소구분 000 행이 카테고리다. 카테고리 행 없이 값만 있는 big_cfg 도 값이 가려지지 않게 넣는다
     */
    public function getBigCfgList()
    {
        $list = [];
        foreach ($this->getList(['big_cfg', 'sml_cfg', 'cfg_name']) as $item) {
            $isCategory = $item->sml_cfg === self::CATEGORY_SML;
            if($isCategory || !isset($list[$item->big_cfg])) {
                $list[$item->big_cfg] = (object)[
                    'big_cfg' => $item->big_cfg,
                    'cfg_name' => $isCategory ? $item->cfg_name : $item->big_cfg,
                    'registered' => $isCategory,
                ];
            }
        }
        return array_values($list);
    }
}
