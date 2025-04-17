<?php
defined('BASEPATH') or exit('No direct script access allowed');

class MY_Controller_WEB extends MY_Controller
{
    protected string $defaultController = '';
    protected string $table;
    protected string $identifier;
    protected array $primaryKeyList;
    protected array $uniqueKeyList;
    protected array $notNullList;
    protected array $nullList;
    protected array $strList;
    protected array $intList;
    protected array $fileList;
    protected array $validateMessages;
    protected array $validateCallback;

    public string $baseViewPath = '';
    public string $isLoginRedirect;
    public string $noLoginRedirect;
    public array $data;
    public array $titleList;
    public array $addCSS;
    public array $addJS;
    public array $jsVars;
    public int $perPage;

    public function __construct()
    {
        parent::__construct();

        $this->load->helper('html');
        $this->load->library('pagination');

        $this->form_validation->set_error_delimiters('', '');

        $this->identifier = '';
        $this->validateMessages = [];
        $this->validateCallback = [];

        $this->noLoginRedirect = '';
        $this->data = [];
        $this->titleList = [];
        $this->addCSS = [];
        $this->addJS = ['head' => [], 'tail' => []];
        $this->jsVars = [];
        $this->perPage = 10;

		$this->lang->load('form_validation', $this->siteLang);
    }

    public function index()
    {
        if($this->router->class === 'common') {
            if($this->defaultController) {
                $url = '/';
                if(get_path() && get_path() !== $this->router->routes['default_platform']) $url .= get_path().'/';
                $url .= $this->defaultController;
                if($_SERVER['QUERY_STRING']) $url .= '?'.$_SERVER['QUERY_STRING'];
                redirect($url);
            }else{
                $this->load->view('welcome_message');
            }
        }
    }

    protected function checkLogin(): bool
    {
        return true;
    }

    protected function validateToken()
    {
        $token = $this->input->post('token')?:$this->session->userdata('token');
        if(is_empty($token)){
			alert('토큰 값이 없습니다.', base_url($this->noLoginRedirect));
        }else{
            $decodedToken = $this->authorization_token->tokenParamCheck($token);
            if($decodedToken['status'] === FALSE){
				$this->session->unset_userdata('token');
                switch ($decodedToken['message']) {
                    case 'Token Time Expire.':
						alert(lang('Token Expired'), base_url($this->noLoginRedirect));
                    default:
						alert(lang('Invalid Token'), base_url($this->noLoginRedirect));
                }
            }else{
                $this->session->set_userdata('token', $token);
                return $decodedToken['data'];
            }
        }
    }

    protected function setTitleList($data = [])
    {
        $this->titleList = $data;
    }

    /**
     * 페이징 함수를 함수를 이용해서 가공해준다.
     * @param $url       string 유지할 URI
     * @param $totalRow  int 총 Row 수
     * @param $perPage   int 한번에 보여줄 페이징 수
     * @param $config    array 페이징 환경설정
     * @return string     가공된 Paging 정보
     */
    protected function getPaginationLinks($url, $totalRow, $perPage, $config = []): string
	{
        $page = $this->input->get('page');
        $page = $page ? intVal($page) : 1;

        $numLinks = ($page <= 4) ? (9 - $page) : 4;

        // 페이징 환경설정
        $pagingConfig = [
            'base_url' => base_url($url),    // 각페이지 지정변수 : 현재 페이지 URL
            'total_rows' => $totalRow,       // 각페이지 지정변수 : 전체 목록갯수
            'per_page' => $perPage,          // 각페이지 지정변수 : 한번에 보여줄 갯수
            'num_links' => $numLinks,        // 페이징 좌우 노출 갯수
            'use_page_numbers' => true,     // 페이징번호로 파라메터 넘김
            'page_query_string' => true,    // 페이징 넘버 쿼리형태로
            'reuse_query_string' => true,   // 기존 파라메터 유지
            'first_link' => '&lt;&lt;',
            'last_link' => "&gt;&gt;",
            'query_string_segment' => 'page',
        ];

        $config = array_merge($pagingConfig, $config);

        $this->pagination->initialize($config);

        return $this->pagination->create_links();
    }

    protected function viewApp($data)
    {
		// css, script 중복 호출 방지
		$this->addCSS = unravel_list($this->addCSS);
		$this->addJS['head'] = unravel_list($this->addJS['head']);
		$this->addJS['tail'] = unravel_list($this->addJS['tail']);

        $data['title'] = get_site_title(APP_NAME, $this->titleList);
        $data['addCSS'] = $this->addCSS;
        $data['addJS'] = $this->addJS;
        $data['dialog'] = $this->session->flashdata('dialog');
        $data['class'] = $this->router->class;
        $data['method'] = $this->router->method;
        $data['titleList'] = $this->titleList;

        $data = array_merge($this->data, $data);
        $this->output->set_header('Last-Modified: ' . gmdate("D, d M Y H:i:s") . ' GMT');
        $this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0',false);
        $this->output->set_header('Pragma: no-cache');
        $this->output->set_header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

		if(!$this->baseViewPath) show_error('Base View Path is not set.');
        if(!$this->config->item('error_occurs')) $this->load->view($this->baseViewPath, $data);
    }

	protected function addJsVars($data)
	{
		foreach ($data as $key => $val) {
			$this->jsVars[$key] = $val;
		}
		if($this->config->item('life_cycle') === 'post_controller') {
			$this->phptojs->append($data);
		}
	}
}
