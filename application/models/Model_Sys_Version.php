<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once __DIR__.'/Model_Common.php';

class Model_Sys_Version extends Model_Common
{
	public string  $table = 'sys_version';
	public string  $identifier = 'version_id';
	public array   $primaryKeyList = ['version_id'];
	public array   $uniqueKeyList = [];
	public array   $notNullList = ['version_id','platform_cd','version_name',];
	public array   $nullList = ['app_cd','app_file','upload_dt','activate_yn',];
	public array   $strList = ['platform_cd','app_cd','version_name','upload_dt','activate_yn',];
	public array   $intList = ['version_id',];
	public array   $fileList = ['app_file'];

	public bool    $isCreatedDt = true;
	public bool    $isCreatedId = true;
	public bool    $isUpdatedDt = true;

	function __construct()
	{
		parent::__construct();
	}
}
