<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once __DIR__.'/Model_Common.php';

class Model_User_Autologin extends Model_Common
{
	public string  $table = 'user_autologin';
	public string  $identifier = 'aul_id';
	public array   $primaryKeyList = ['aul_id','user_id'];
	public array   $uniqueKeyList = [];
	public array   $notNullList = ['aul_id','user_id','aul_key','aul_ip','aul_datetime'];
	public array   $nullList = [];
	public array   $strList = ['aul_key','aul_ip','aul_datetime'];
	public array   $intList = ['aul_id','user_id'];
	public array   $fileList = [];

	public bool    $isAutoincrement = true;

	function __construct()
	{
		parent::__construct();
	}
}
