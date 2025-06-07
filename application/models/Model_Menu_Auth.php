<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once __DIR__.'/Model_Common.php';

class Model_Menu_Auth extends Model_Common
{
    public string  $table = 'menu_auth';
    public string  $identifier = '';
    public array   $primaryKeyList = ['user_cd', 'menu_id'];
    public array   $uniqueKeyList = [];
    public array   $notNullList = ['user_cd','menu_id','menu_auth','scope_cd',];
    public array   $nullList = [];
    public array   $strList = ['user_cd','menu_auth','scope_cd',];
    public array   $intList = ['menu_id',];
    public array   $fileList = [];

    public bool $isCreatedDt = true;
}