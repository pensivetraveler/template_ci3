<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once __DIR__.'/Model_Common.php';

class Model_Board extends Model_Common
{
	public string  $table = 'board';
	public string  $identifier = 'board_id';
	public array   $primaryKeyList = ['board_id'];
	public array   $uniqueKeyList = [];
	public array   $notNullList = ['board_id','board_name','board_cd'];
	public array   $nullList = ['attach_max'];
	public array   $strList = ['board_name','board_cd',];
	public array   $intList = ['board_id','attach_max'];
	public array   $fileList = [];

	public bool    $isAutoincrement = true;
	public bool    $isCreatedId = true;
	public bool    $isCreatedDt = true;
	public bool    $isUpdatedDt = true;

	function __construct()
	{
		parent::__construct();
	}
}
