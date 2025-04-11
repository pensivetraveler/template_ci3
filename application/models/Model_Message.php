<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once __DIR__.'/Model_Common.php';

class Model_Message extends Model_Common
{
	public string  $table = 'message';
	public string  $identifier = 'message_id';
	public array   $primaryKeyList = ['message_id','user_id','article_id'];
	public array   $uniqueKeyList = [];
	public array   $notNullList = ['content','read_yn'];
	public array   $nullList = ['comment_id'];
	public array   $strList = ['content','read_yn'];
	public array   $intList = ['message_id','user_id','article_id','comment_id'];
	public array   $fileList = [];

	public bool    $isAutoincrement = true;
	public bool    $isCreatedDt = true;

	function __construct()
	{
		parent::__construct();
	}
}
