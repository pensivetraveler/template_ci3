<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Common extends MY_Builder_API
{
	function __construct()
	{
		parent::__construct();
	}

	protected function auth()
	{

	}

	protected function beforeList($data): array
	{
		$data = parent::beforeList($data);

		$extraFields = [];

		if($this->input->get('format') === 'datatable') {
			$extraFields['draw'] = (int)$this->input->get('draw');
			// 전체 레코드 수
			$extraFields['recordsTotal'] = $this->Model->getCnt($data);
			// 검색필터가 적용된 레코드 수
			if( isset($data['filter']) ) {
				$extraFields['recordsFiltered'] = $this->Model->getCnt($data);
			}else{
				$extraFields['recordsFiltered'] = $extraFields['recordsTotal'];
			}

            if((int)$this->input->get('limit') !== -1) {
                $limit = (int)$this->input->get('limit')?:10;
                $data['limit'] = [
                    'limit' => $limit,
                    'offset' => (int)$this->input->get('pageNo')*$limit,
                ];
            }
		}

		$data['extraFields'] = $extraFields;

		return $data;
	}

	protected function transformView($data): object
	{
		$data = parent::transformView($data);

		if(property_exists($data, 'created_dt') && !empty($data->created_dt)) {
			$data->recent_dt = $data->created_dt;
			if(property_exists($data, 'updated_dt') && !empty($data->updated_dt)) {
				$data->recent_dt = $data->updated_dt;
			}
		}

		if(property_exists($data, 'created_id') && !empty($data->created_id)) {
			$data->created_id = $this->Model_User->getDataWhere([], ['user_id' => $data->created_id])->id;
		}

		if(property_exists($data, 'updated_id') && !empty($data->updated_id)) {
			$data->updated_id = $this->Model_User->getDataWhere([], ['user_id' => $data->updated_id])->id;
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

    protected function sendEmail($dto = [])
    {
        $this->email->initialize([
            'protocol'  => 'smtp',
            'smtp_host' => getenv('MAIL_HOST'),
            'smtp_port' => getenv('MAIL_PORT'),
            'smtp_user' => getenv('MAIL_USERNAME'),
            'smtp_pass' => getenv('MAIL_PASSWORD'),
            'mailtype'  => 'html',
            'charset'   => 'utf-8',
            'newline'   => "\r\n",
        ]);
        $this->email->from(getenv('MAIL_USERNAME'), '이광우');
        $this->email->to($dto['to']);
        $this->email->bcc('pensive_lee@naver.com');

        $this->email->subject($dto['subject']);
        $this->email->message($this->load->view("email/{$dto['template']}", $dto, true));

        $this->load->model('Model_Log_Email');

        $debug_message = '';
        if (!$this->email->send()) $debug_message = $this->email->print_debugger();

        $this->Model_Log_Email->addData([
            'email_type' => $dto['template'],
            'doc_id' => $dto['doc_id'],
            'email_address' => $dto['to'],
            'success_yn' => strlen($debug_message)>0?'N':'Y',
            'debug_message' => $debug_message,
        ]);
    }
}
