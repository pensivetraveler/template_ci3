<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once __DIR__.'/Model_Common.php';

class Model_User_Token extends Model_Common
{
	public string  $table = 'user_token';
	public string  $identifier = 'id';
	public array   $primaryKeyList = ['id'];
	public array   $uniqueKeyList = ['user_id','token'];
	public array   $notNullList = ['user_id','token','level','ignore_limits','is_private_key'];
	// created_dt = 토큰을 (다시) 발급한 시각. 저장 가능 컬럼에 없으면 수정(setToken 의 토큰 교체) 때 걸러져 첫 발급 값이 남았다
	public array   $nullList = ['ip_addresses', 'created_dt'];
	public array   $strList = ['token','ip_addresses'];
	public array   $intList = ['id','user_id','level','ignore_limits','is_private_key'];
	public array   $fileList = [];

	public bool    $isAutoIncrement = true;
	public bool    $isCreatedDt = true;
}
