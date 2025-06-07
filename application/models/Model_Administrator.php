<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once __DIR__.'/Model_Common.php';

class Model_Administrator extends Model_Common
{
    public string  $table = 'admin';
    public string  $identifier = 'admin_id';
    public array   $primaryKeyList = ['admin_id','user_id',];
    public array   $uniqueKeyList = [];
    public array   $notNullList = ['admin_id','user_id','admin_cd'];
    public array   $nullList = [];
    public array   $strList = ['admin_cd',];
    public array   $intList = ['admin_id','user_id',];
    public array   $fileList = [];

    public bool    $isAutoIncrement = true;

    function __construct()
    {
        parent::__construct();
    }

    function getList($select = [], $dto = [], $filter = [])
    {
        if(!array_key_exists('join', $dto)) $dto['join'] = [];
        $dto['join'][] = [
            'select' => '*',
            'table' => 'user',
            'matches' => [
                'user_id' => 'user_id'
            ],
            'direction' => 'left'
        ];
        return parent::getList($select, $dto, $filter);
    }

    function getData($select = [], $dto = [])
    {
        if(!array_key_exists('join', $dto)) $dto['join'] = [];
        $dto['join'][] = [
            'select' => '*',
            'table' => 'user',
            'matches' => [
                'user_id' => 'user_id'
            ],
            'direction' => 'left'
        ];
        return parent::getData($select, $dto);
    }

    function getCnt($dto = [], $filter = [])
    {
        if(!array_key_exists('join', $dto)) $dto['join'] = [];
        $dto['join'][] = [
            'select' => '*',
            'table' => 'user',
            'matches' => [
                'user_id' => 'user_id'
            ],
            'direction' => 'left'
        ];
        return parent::getCnt($dto, $filter);
    }

}