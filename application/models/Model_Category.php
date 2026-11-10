<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once __DIR__.'/Model_Common.php';

class Model_Category extends Model_Common
{
    public string  $table = 'category';
    public string  $identifier = 'category_id';
    public array   $primaryKeyList = ['category_id'];
    public array   $uniqueKeyList = [];
    public array   $notNullList = ['category_id','parent_id','depth','category_name'];
    public array   $nullList = ['category_icon','category_nick','category_alias','category_extra','category_srt'];
    public array   $strList = ['category_name','category_nick','category_alias','category_extra'];
    public array   $intList = ['category_id','parent_id','depth','category_icon','category_srt'];
    public array   $fileList = ['category_icon'];

    public bool    $isAutoIncrement = true;
    public bool    $isUseYn = true;

}
