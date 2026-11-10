<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once __DIR__.'/Model_Common.php';

class Model_Menu_Auth extends Model_Common
{
    public string  $table = 'menu_auth';
    public string  $identifier = '';
    public array   $primaryKeyList = ['grade_cd', 'menu_id'];
    public array   $uniqueKeyList = [];
    public array   $notNullList = ['grade_cd','menu_id','is_show','menu_auth','scope_cd',];
    public array   $nullList = [];
    public array   $strList = ['grade_cd','is_show','menu_auth','scope_cd',];
    public array   $intList = ['menu_id',];
    public array   $fileList = [];

    public bool $isCreatedDt = true;
}
