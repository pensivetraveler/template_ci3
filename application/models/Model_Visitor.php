<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once __DIR__.'/Model_Common.php';

class Model_Visitor extends Model_Common
{
    public string  $table = 'visitor';
    public string  $identifier = 'vi_id';
    public array   $primaryKeyList = ['vi_id'];
    public array   $uniqueKeyList = [];
    public array   $notNullList = ['vi_hit_count','vi_ip','vi_date','vi_time',];
    public array   $nullList = ['vi_visit_key','vi_referrer','vi_agent','vi_agent','vi_browser','vi_os','vi_device','vi_location','vi_first_at','vi_last_at'];
    public array   $strList = ['vi_visit_key','vi_ip','vi_date','vi_time','vi_referrer','vi_agent','vi_agent','vi_browser','vi_os','vi_device','vi_location','vi_first_at','vi_last_at'];
    public array   $intList = ['vi_id','vi_hit_count'];
    public array   $fileList = [];

    public bool    $isAutoIncrement = true;
    public bool    $isCreatedDt = true;
}
