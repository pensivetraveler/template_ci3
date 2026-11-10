<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once __DIR__.'/Model_Common.php';

class Model_User_Logs extends Model_Common
{
    public string  $table = 'user_logs';
    public string  $identifier = 'id';
    public array   $primaryKeyList = ['id'];
    public array   $uniqueKeyList = [];
    public array   $notNullList = ['id','log_id','user_id','class','method','type','title',];
    public array   $nullList = ['subtype','message','params','datetime'];
    public array   $strList = ['class','method','type','subtype','title','message','params','datetime'];
    public array   $intList = ['id','log_id','user_id',];
    public array   $fileList = [];

    public string  $filteringDateColumn = 'datetime';
    public bool    $isAutoIncrement = true;


    function getList($select = [], $dto = [], $filter = [])
    {
        $dto['join'][] = [
            'select' => 'user_cd',
            'table' => 'user',
            'matches' => [
                'user_id' => 'user_id'
            ],
            'direction' => 'left'
        ];
        $dto['join'][] = [
            'select' => 'partner_id',
            'table' => 'partner',
            'matches' => [
                'user_id' => 'user_id'
            ],
            'direction' => 'left'
        ];
       return parent::getList($select, $dto, $filter);
    }

    function getCnt($dto = [], $filter = [])
    {
        $dto['join'][] = [
            'select' => 'user_cd',
            'table' => 'user',
            'matches' => [
                'user_id' => 'user_id'
            ],
            'direction' => 'left'
        ];
        $dto['join'][] = [
            'select' => 'partner_id',
            'table' => 'partner',
            'matches' => [
                'user_id' => 'user_id'
            ],
            'direction' => 'left'
        ];
       return parent::getCnt($dto, $filter);
    }

//    public function setFilterWhereIn($table, $data)
//    {
//        if(array_key_exists('partner_id', $data)){
//            $this->whereIn('partner', [
//                'partner_id' => $data['partner_id']
//            ]);
//            unset($data['partner_id']);
//        }
//
//        $this->whereIn($table, $data);
//    }
}
