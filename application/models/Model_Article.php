<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once __DIR__.'/Model_Common.php';

class Model_Article extends Model_Common
{
	public string  $table = 'article';
	public string  $identifier = 'article_id';
	public array   $primaryKeyList = ['article_id'];
	public array   $uniqueKeyList = [];
	public array   $notNullList = ['article_id','board_id','subject','del_yn','open_yn'];
	public array   $nullList = ['parent_id','content','thumbnail','link','view_count'];
	public array   $strList = ['subject','content','link','del_yn','open_yn'];
	public array   $intList = ['article_id','board_id','parent_id','view_count'];
	public array   $fileList = ['thumbnail'];

	public bool    $isAutoIncrement = true;
	public bool    $isDelYn = true;
	public bool    $isCreatedDt = true;
	public bool    $isCreatedId = true;
	public bool    $isUpdatedDt = true;
}
