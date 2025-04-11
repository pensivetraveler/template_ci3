<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once __DIR__.'/Model_Common.php';

class Model_Comment extends Model_Common
{
	public string  $table = 'comment';
	public string  $identifier = 'comment_id';
	public array   $primaryKeyList = ['comment_id'];
	public array   $uniqueKeyList = [];
	public array   $notNullList = ['comment_id','article_id','parent_id','content'];
	public array   $nullList = ['del_yn','depth'];
	public array   $strList = ['content','del_yn'];
	public array   $intList = ['comment_id','article_id','parent_id','depth'];
	public array   $fileList = [];

	public bool    $isAutoincrement = true;
	public bool    $isDelYn = true;
	public bool    $isCreatedDt = true;
	public bool    $isCreatedId = true;
	public bool    $isUpdatedDt = true;

	function __construct()
	{
		parent::__construct();
	}
}
