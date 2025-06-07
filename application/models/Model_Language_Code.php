<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once __DIR__.'/Model_Common.php';

class Model_Language_Code extends Model_Common
{
    public string  $table = 'language_code';
    public string  $identifier = 'code';
    public array   $primaryKeyList = ['code'];
    public array   $uniqueKeyList = [];
    public array   $notNullList = ['code','display','path','use_yn'];
    public array   $nullList = [];
    public array   $strList = ['code','display','path','use_yn'];
    public array   $intList = [];
    public array   $fileList = [];
}