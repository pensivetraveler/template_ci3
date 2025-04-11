<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once __DIR__.'/Model_Common.php';

class Model_Reply extends Model_Common
{
	public string  $table = 'reply';
	public string  $identifier = 'reply_id';
	public array   $primaryKeyList = ['reply_id','article_id'];
	public array   $uniqueKeyList = [];
	public array   $notNullList = ['depth','content','admin_yn'];
	public array   $nullList = [];
	public array   $strList = ['content','admin_yn'];
	public array   $intList = ['reply_id','article_id','depth'];
	public array   $fileList = [];

	public bool    $isAutoincrement = true;
	public bool    $isCreatedDt = true;

	function __construct()
	{
		parent::__construct();
	}
}
