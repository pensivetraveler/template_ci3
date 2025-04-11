<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once __DIR__.'/Model_Common.php';

class Model_User_Token extends Model_Common
{
	public string  $table = 'user_token';
	public string  $identifier = 'id';
	public array   $primaryKeyList = ['id','user_id'];
	public array   $uniqueKeyList = ['token'];
	public array   $notNullList = ['token',];
	public array   $nullList = [];
	public array   $strList = ['token'];
	public array   $intList = ['id','user_id'];
	public array   $fileList = [];

	public bool    $isAutoincrement = true;
	public bool    $isCreatedDt = true;

	function __construct()
	{
		parent::__construct();
	}
}
