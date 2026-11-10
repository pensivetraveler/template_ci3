<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once __DIR__.'/Model_Common.php';

class Model_Rest_Logs extends Model_Common
{
    public string  $table = 'rest_logs';
    public string  $identifier = 'id';
    public array   $primaryKeyList = ['id'];
    public array   $uniqueKeyList = [];
    public array   $notNullList = ['id','uri','method','api_key','ip_address','time','authorized','kind'];
    public array   $nullList = ['params','rtime','response_code'];
    public array   $strList = ['uri','method','params','api_key','ip_address','authorized','kind'];
    public array   $intList = ['id','time','rtime','response_code'];
    public array   $fileList = [];

    public bool    $isAutoIncrement = true;
    public bool    $isCreatedDt = true;
}
