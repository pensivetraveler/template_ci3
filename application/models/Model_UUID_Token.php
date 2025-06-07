<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once __DIR__.'/Model_Common.php';

class Model_UUID_Token extends Model_Common
{
    public string  $table = 'uuid_token';
    public string  $identifier = 'id';
    public array   $primaryKeyList = ['id'];
    public array   $uniqueKeyList = [];
    public array   $notNullList = ['id','expires_dt'];
    public array   $nullList = [];
    public array   $strList = ['id','expires_dt'];
    public array   $intList = [];
    public array   $fileList = [];

    public bool    $isCreatedDt = true;
}