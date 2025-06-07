<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once __DIR__.'/Model_Common.php';

class Model_Menu extends Model_Common
{
    public string  $table = 'menu';
    public string  $identifier = 'menu_id';
    public array   $primaryKeyList = ['menu_id'];
    public array   $uniqueKeyList = [];
    public array   $notNullList = ['menu_id','code','title','is_use','is_sub_menu','is_login','is_auth','is_super','depth','srt'];
    public array   $nullList = ['parent_id','icon','class','method','attr','params','auth_params'];
    public array   $strList = ['code','title','icon','class','method','attr','params','auth_params'];
    public array   $intList = ['menu_id','parent_id','is_use','is_sub_menu','is_login','is_auth','is_super','depth','srt'];
    public array   $fileList = [];

    public bool    $isAutoIncrement = true;

    public function getParentList()
    {
        $list = $this->getList(['menu_id', 'title'], [
            'where' => [
                'parent_id' => 0,
            ],
            'orderBy' => [
                'srt' => 'asc',
            ]
        ]);
        return array_map(function ($item) {
            $item->title = lang('nav.'.$item->title);
            return $item;
        }, $list);
    }
}