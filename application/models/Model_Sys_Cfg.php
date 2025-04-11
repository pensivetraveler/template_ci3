<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once __DIR__.'/Model_Common.php';

class Model_Sys_Cfg extends Model_Common
{
	public string  $table = 'sys_cfg';
	public string  $identifier = 'cmb_cfg';
	public array   $primaryKeyList = [];
	public array   $uniqueKeyList = ['cmb_cfg'];
	public array   $notNullList = ['cmb_cfg','big_cfg','sml_cfg','cfg_name',];
	public array   $nullList = ['cfg_val','cfg_desc','cfg_srt','use_yn',];
	public array   $strList = ['cmb_cfg','big_cfg','sml_cfg','cfg_name','cfg_val','cfg_desc','use_yn',];
	public array   $intList = ['cfg_srt',];
	public array   $fileList = [];

	public bool    $isUseYn = true;
	public bool    $isCreatedDt = true;
	public bool    $isCreatedId = true;
	public bool    $isUpdatedDt = true;

	function __construct()
	{
		parent::__construct();
	}
}
