<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once __DIR__.'/Model_Common.php';

class Model_Partner_Auth extends Model_Common
{
    public string  $table = 'partner_auth';
    public string  $identifier = '';
    public array   $primaryKeyList = ['partner_id', 'menu_id'];
    public array   $uniqueKeyList = [];
    public array   $notNullList = ['partner_id','menu_id','is_show','menu_auth','scope_cd',];
    public array   $nullList = [];
    public array   $strList = ['is_show','menu_auth','scope_cd',];
    public array   $intList = ['menu_id','partner_id',];
    public array   $fileList = [];

    public bool $isCreatedDt = true;
}
