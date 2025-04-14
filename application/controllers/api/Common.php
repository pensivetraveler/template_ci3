<?php defined('BASEPATH') or exit('No direct script access allowed');

class Common extends MY_Builder_API
{
	function __construct()
	{
		parent::__construct();
	}

	protected function auth()
	{

	}

	protected function listBefore($data)
	{
		$data = parent::listBefore($data);

		$extraFields = [];

		if($this->input->get('format') === 'datatable') {
			$extraFields['draw'] = (int)$this->input->get('draw');
			// 전체 레코드 수
			$extraFields['recordsTotal'] = $this->Model->getCnt(
				$data['where'] ?? [],
				$data['like'] ?? [],
			);
			// 검색필터가 적용된 레코드 수
			if( isset($data['filter']) ) {
				$extraFields['recordsFiltered'] = $this->Model->getCnt(
					$data['where'] ?? [],
					$data['like'] ?? [],
					$data['filter'] ?? [],
				);
			}else{
				$extraFields['recordsFiltered'] = $extraFields['recordsTotal'];
			}

			$limit = (int)$this->input->get('limit')?:10;
			$offset = (int)$this->input->get('pageNo')*$limit;
			$this->db->limit($limit, $offset);
		}

		$data['extraFields'] = $extraFields;

		return $data;
	}

	protected function viewAfter($data)
	{
		$data = parent::viewAfter($data);

		if(property_exists($data, 'created_dt') && !empty($data->created_dt)) {
			$data->recent_dt = $data->created_dt;
			if(property_exists($data, 'updated_dt') && !empty($data->updated_dt)) {
				$data->recent_dt = $data->updated_dt;
			}
		}

		if(property_exists($data, 'created_id') && !empty($data->created_id)) {
			$data->created_id = $this->Model_User->getData([], ['user_id' => $data->created_id])->id;
		}

		if(property_exists($data, 'updated_id') && !empty($data->updated_id)) {
			$data->updated_id = $this->Model_User->getData([], ['user_id' => $data->updated_id])->id;
		}

		return $data;
	}

	public function message_read_patch($key)
	{
		$tokenData = $this->validateToken();

		$this->load->model('Model_Message');
		$this->Model_Message->modData([
			'read_yn' => 'Y'
		], [
			'message_id' => $key,
			'user_id' => $tokenData->user_id,
		]);

		$this->response([
			'code' => DATA_PROCESSED,
		]);
	}
}
