<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once __DIR__.'/Model_Common.php';

class Model_Company extends Model_Common
{
    public string  $table = 'company';
    public string  $identifier = 'company_id';
    public array   $primaryKeyList = ['company_id'];
    public array   $uniqueKeyList = [];
    public array   $notNullList = ['company_id','company_name','company_cd'];
    public array   $nullList = ['company_code','company_bizno','company_ceo','company_zipcode','company_addr1','company_addr2','company_type','company_item','memo'];
    public array   $strList = ['company_name','company_cd','company_code','company_bizno','company_ceo','company_zipcode','company_addr1','company_addr2','company_type','company_item','memo'];
    public array   $intList = ['company_id',];
    public array   $fileList = [];

    public bool    $isAutoIncrement = true;
    public bool    $isDelYn = true;
    public bool    $isCreatedDt = true;
    public bool    $isCreatedId = true;
    public bool    $isUpdatedDt = true;

}
