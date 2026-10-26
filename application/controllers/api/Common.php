<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Common extends MY_Builder_API
{
    private array $userLoginIds = [];

    function __construct()
    {
        parent::__construct();

        $this->apiRoute = 'api';
    }

    protected function auth()
    {

    }

    protected function beforeList($data): array
    {
        $data = parent::beforeList($data);

        $extraFields = [];

        if($this->input->get('format') === 'datatable' || $this->input->get('format') === 'log') {
            $extraFields['draw'] = (int)$this->input->get('draw');
            // 전체 레코드 수 (검색필터 제외)
            $extraFields['recordsTotal'] = $this->Model->getCnt(array_diff_key($data, ['filter' => true]));
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

        // date 기본값 초기화
        foreach ($data as $k=>$v) if($v === '0000-00-00') $data->{$k} = '';

        if(property_exists($data, 'created_dt') && !empty($data->created_dt)) {
            $data->recent_dt = $data->created_dt;
            if(property_exists($data, 'updated_dt') && !empty($data->updated_dt)) {
                $data->recent_dt = $data->updated_dt;
            }
        }

        // created_id·updated_id 는 user_id 대신 로그인 id 문자열로 바뀐다 (원래 user_id가 필요하면 parent 호출 전에 읽을 것)
        // 사용자가 soft delete 되어 조회되지 않으면 '' 로 둔다
        foreach (['created_id', 'updated_id'] as $field) {
            if(property_exists($data, $field) && !empty($data->{$field})) {
                $data->{$field} = $this->getUserLoginId($data->{$field});
            }
        }

        return $this->transformViewByMode($data);
    }

    /**
     * user_id → 로그인 id. 한 요청 안에서는 한 번만 조회 (목록 행마다 작성자·수정자를 조회하던 N+1 제거)
     */
    protected function getUserLoginId($userId): string
    {
        $key = (string)$userId;
        if(!array_key_exists($key, $this->userLoginIds)) {
            $user = $this->Model_User->getDataWhere(['id'], ['user_id' => $userId]);
            $this->userLoginIds[$key] = is_object($user) ? (string)$user->id : (is_string($user) ? $user : '');
        }
        return $this->userLoginIds[$key];
    }

    protected function transformViewByMode($data): object
    {
        // type/subtype 별 처리
        switch ($this->mode) {
            case 'form' :
                $selectMultiples = array_filter($this->formConfig, function ($item) {
                    return $item['type'] === 'select' && $item['subtype'] === 'select2' && isset($item['attributes']['multiple']);
                });
                foreach ($selectMultiples as $k=>$v) {
                    $field = $v['field'];
                    if(!empty($data->{$field})) $data->{$field} = explode(',', $data->{$field});
                }
                return $data;
            case 'list' :
            case 'view' :
            default :
                return $data;
        }
    }

    public function message_read_patch($key)
    {
        $this->load->model('Model_Message');
        $this->Model_Message->modData([
            'read_yn' => 'Y'
        ], [
            'message_id' => $key,
            'user_id' => $this->session->userdata('user_id'),
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
        // 발신자·숨은참조는 .env 로 정한다 (MAIL_FROM_ADDRESS, MAIL_FROM_NAME, MAIL_BCC)
        $this->email->from(getenv('MAIL_FROM_ADDRESS') ?: getenv('MAIL_USERNAME'), getenv('MAIL_FROM_NAME') ?: (getenv('APP_NAME_KR') ?: ''));
        $this->email->to($dto['to']);
        if(getenv('MAIL_BCC')) $this->email->bcc(getenv('MAIL_BCC'));

        $this->email->subject($dto['subject']);
        $this->email->message($this->load->view("email/{$dto['template']}", $dto, true));

        $this->load->model('Model_Email_Log');

        $debug_message = '';
        if (!$this->email->send()) $debug_message = $this->email->print_debugger();

        $this->Model_Email_Log->addData([
            'email_type' => $dto['template'],
            'doc_id' => $dto['doc_id'],
            'email_address' => $dto['to'],
            'success_yn' => strlen($debug_message)>0?'N':'Y',
            'debug_message' => $debug_message,
        ]);
    }
}
