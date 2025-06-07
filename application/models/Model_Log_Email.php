<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once __DIR__.'/Model_Common.php';

class Model_Log_Email extends Model_Common
{
    public string  $table = 'log_email';
    public string  $identifier = 'log_id';
    public array   $primaryKeyList = ['log_id'];
    public array   $uniqueKeyList = [];
    public array   $notNullList = ['log_id','email_type','doc_id','email_address','success_yn',];
    public array   $nullList = ['debug_message'];
    public array   $strList = ['email_type','email_address','success_yn','debug_message'];
    public array   $intList = ['log_id','doc_id'];
    public array   $fileList = [];

    public bool    $isAutoIncrement = true;
    public bool    $isCreatedDt = true;
}