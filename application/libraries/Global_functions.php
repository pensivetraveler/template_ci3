<?php
if (!defined("BASEPATH")) exit("No direct script access allowed");

class Global_functions
{
    protected $CI;
    protected $fileSystemLib;
    protected bool $userModelLoaded = false;

    function __construct()
    {
        $this->CI =& get_instance();
        $this->fileSystemLib = $this->CI->file_system;

        $this->userModelLoaded = $this->CI->load->is_loaded('Model_User');
    }

    protected function getCodeName($dto)
    {
        $data = $this->getCodeData($dto);
        return $data ? $data->cd_name : '';
    }

    protected function getCodeData($dto)
    {
        $list = $this->getCodeList($dto);
        return $list ? $list[0] : null;
    }

    protected function getCodeList($dto)
    {
        $this->CI->db->where_not_in('sml_cd', ['000']);
        $dto = array_merge($dto, ['use_yn' => 'Y']);
        return $this->CI->Model_Sys_Code->getListWhere([], $dto);
    }

    protected function getArticleFileData($dto)
    {
        $this->CI->db->select('article_attachment.*');
        $this->CI->db->join('article_attachment', 'article_attachment.file_id=file.file_id', 'left');
        if (array_key_exists('article_id', $dto)) {
            $this->CI->db->where('article_attachment.article_id', $dto['article_id']);
            unset($dto['article_id']);
        }
        return $this->fileSystemLib->getFileData($dto);
    }

    protected function getArticleFileList($dto)
    {
        $this->CI->db->select('article_attachment.*');
        $this->CI->db->join('article_attachment', 'article_attachment.file_id=file.file_id', 'left');
        if (array_key_exists('article_id', $dto)) {
            $this->CI->db->where('article_attachment.article_id', $dto['article_id']);
            unset($dto['article_id']);
        }
        return $this->fileSystemLib->getFileList($dto);
    }
}
