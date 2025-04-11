<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once __DIR__.'/Model_Common.php';

class Model_Sys_Version_Current extends Model_Common
{
	public string  $table = 'sys_version_current';
	public string  $identifier = '';
	public array   $primaryKeyList = [];
	public array   $uniqueKeyList = [];
	public array   $notNullList = ['platform_cd','version_id','version_name',];
	public array   $nullList = ['app_cd',];
	public array   $strList = ['platform_cd','app_cd','version_name',];
	public array   $intList = ['version_id',];
	public array   $fileList = [];

	public bool    $isCreatedDt = true;
	public bool    $isCreatedId = false;
	public bool    $isUpdatedDt = true;

	function __construct()
	{
		parent::__construct();
	}
}
