<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once __DIR__.'/Model_Common.php';

class Model_File extends Model_Common
{
	public string  $table = 'file';
	public string  $identifier = 'file_id';
	public array   $primaryKeyList = ['file_id'];
	public array   $uniqueKeyList = [];
	public array   $notNullList = ['file_id','file_name','file_type','file_link','file_path','full_path','raw_name','orig_name','client_name','file_ext','file_size','is_image'];
	public array   $nullList = ['image_width','image_height','image_type','image_size_str','download_cnt'];
	public array   $strList = ['file_name','file_link','file_type','file_path','full_path','raw_name','orig_name','client_name','file_ext','image_type','image_size_str',];
	public array   $intList = ['file_id','file_size','is_image','image_width','image_height','download_cnt'];
	public array   $fileList = [];
	// 저장 경로는 응답에 싣지 않는다 (다운로드·삭제는 기본 조회로 읽음)
	public array   $notIncludeColumns = ['file_path', 'full_path'];

	public bool    $isAutoIncrement = true;
	public bool    $isCreatedDt = true;
	public bool    $isCreatedId = true;
	public bool    $isUpdatedDt = false;

	/**
	 * file_path·full_path 는 서버 절대 경로 대신 저장소 키로 저장한다 (설치 경로가 바뀌어도 다운로드·삭제가 되도록). s3:// 는 그대로
	 */
	function addData($set, $bool = false)
	{
		foreach (['file_path', 'full_path'] as $column) {
			if (isset($set[$column]) && strpos((string)$set[$column], 's3://') !== 0) $set[$column] = get_file_key_from_path($set[$column]);
		}
		return parent::addData($set, $bool);
	}

	/**
	 * file_link 는 저장소 키(예: public/uploads/…)로 저장하고, 읽을 때 지금 서버 기준 주소로 바꿔 돌려준다 (get_file_url).
	 * 예전에 저장한 전체 주소는 그대로 (docs/migrations/2026-10-30-file-link-relative.sql 로 키로 바꿀 수 있음)
	 */
	function getData($select = [], $dto = [])
	{
		$result = parent::getData($select, $dto);
		if (is_array($select) && count($select) === 1) {
			return $this->isFileLinkSelect($select[0]) && $result !== null ? get_file_url($result) : $result;
		}
		return $this->withFileUrl($result);
	}

	function getList($select = [], $dto = [], $filter = [])
	{
		$result = parent::getList($select, $dto, $filter);
		if (is_array($select) && count($select) === 1) {
			return $this->isFileLinkSelect($select[0]) ? array_map('get_file_url', $result) : $result;
		}
		return array_map([$this, 'withFileUrl'], $result);
	}

	protected function withFileUrl($row)
	{
		if (is_object($row) && property_exists($row, 'file_link')) $row->file_link = get_file_url($row->file_link);
		return $row;
	}

	protected function isFileLinkSelect($column): bool
	{
		return is_string($column) && preg_match('/(^|\.)file_link$/', trim($column)) === 1;
	}
}
