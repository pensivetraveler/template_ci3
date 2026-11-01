<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once __DIR__.'/Model_Common.php';

class Model_Password_Reset extends Model_Common
{
    public string  $table = 'password_reset';
    public string  $identifier = 'reset_id';
    public array   $primaryKeyList = ['reset_id'];
    public array   $uniqueKeyList = ['token_hash'];
    public array   $notNullList = ['reset_id','user_id','token_hash','expires_dt'];
    public array   $nullList = ['request_ip','created_dt'];
    public array   $strList = ['token_hash','expires_dt','request_ip','created_dt'];
    public array   $intList = ['reset_id','user_id'];
    public array   $fileList = [];

    public bool    $isAutoIncrement = true;
    public bool    $isCreatedDt = true;

    /**
     * 만료되지 않은 토큰의 행. 없으면 null
     */
    public function findValid(string $token)
    {
        if($token === '') return null;
        return $this->getDataWhere([], [
            'token_hash' => hash('sha256', $token),
            'expires_dt >' => date('Y-m-d H:i:s'),
        ]) ?: null;
    }
}
