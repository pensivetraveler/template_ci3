<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once __DIR__.'/Model_Common.php';

class Model_Article_Attachment extends Model_Common
{
	public string  $table = 'article_attachment';
	public string  $identifier = '';
	public array   $primaryKeyList = ['article_id','file_id',];
	public array   $uniqueKeyList = [];
	public array   $notNullList = [];
	public array   $nullList = ['attach_cd','sort_order'];
	public array   $strList = ['attach_cd'];
	public array   $intList = ['article_id','file_id','sort_order',];
	public array   $fileList = [];

	function __construct()
	{
		parent::__construct();
	}
}
