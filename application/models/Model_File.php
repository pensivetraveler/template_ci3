<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once __DIR__.'/Model_Common.php';

class Model_File extends Model_Common
{
	public string  $table = 'file';
	public string  $identifier = 'file_id';
	public array   $primaryKeyList = ['file_id'];
	public array   $uniqueKeyList = [];
	public array   $notNullList = ['file_id','file_name','file_type','file_link','file_path','full_path','raw_name','orig_name','client_name','file_ext','file_size','is_image'];
	public array   $nullList = ['image_width','image_height','image_type','image_size_str','download_cnt'];
	public array   $strList = ['file_name','file_link','file_type','file_path','full_path','raw_name','orig_name','client_name','file_ext','image_type','image_size_str',];
	public array   $intList = ['file_id','file_size','is_image','image_width','image_height','download_cnt'];
	public array   $fileList = [];

	public bool    $isAutoincrement = true;
	public bool    $isCreatedDt = true;
	public bool    $isCreatedId = true;
	public bool    $isUpdatedDt = false;

	function __construct()
	{
		parent::__construct();
	}
}
