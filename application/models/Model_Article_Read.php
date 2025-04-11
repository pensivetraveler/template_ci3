<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once __DIR__.'/Model_Common.php';

class Model_Article_Read extends Model_Common
{
	public string  $table = 'article_read';
	public string  $identifier = '';
	public array   $primaryKeyList = ['article_id','user_id'];
	public array   $uniqueKeyList = [];
	public array   $notNullList = ['article_id','user_id'];
	public array   $nullList = ['read_dt'];
	public array   $strList = ['read_dt'];
	public array   $intList = ['article_id','user_id'];
	public array   $fileList = [];

	function __construct()
	{
		parent::__construct();
	}
}
