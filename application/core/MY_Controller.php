<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class MY_Controller extends CI_Controller
{
    protected bool $devMode;
    public string $langCode = '';
    public string $siteLang = '';

    function __construct()
    {
        parent::__construct();

        $this->load->library('encryption', array(
            'driver' => 'openssl',
            'cipher' => 'aes-256',
            'mode' => 'ctr',
        ));

        $this->devMode = ENVIRONMENT !== 'production';

        $this->load->library('opaque_token');
        $this->load->library('file_system');
    }

    function _view($view, $array = [])
    {
        $this->load->view("includes/View_head");
        $this->load->view("includes/View_header");
        $this->load->view($view, $array);
        $this->load->view("includes/View_modal");
        $this->load->view("includes/View_footer");
    }

    function setResponseData($data = NULL, $http_code = NULL): object
    {
        header('Content-Type: application/json;charset=utf-8');

        if(is_empty($data, 'code') && $http_code === null)
            show_error('Insufficient response data provided');

        $http_big_code = (int)floor($http_code/100);

        $response = new StdClass();
        $response->status = $http_big_code === 2;
        $response->code = is_empty($data, 'code')?(int)$http_code*10:$data['code'];
        $response->msg = is_empty($data, 'msg')?$this->lang->status($response->code):$data['msg'];
        $response->data = [];
        if(!is_empty($data, 'data')) {
            if(is_array($data['data']) && is_list_type($data['data'])) {
                $response->data = $data['data'];
            }else{
                $response->data[] = $data['data'];
            }
        }
        $response->errors = [];
        if(in_array($http_big_code, [4,5])) {
            if(is_empty($data, 'errors')) {
                $response->errors = [[
                    'location' => 'body',
                    'param' => null,
                    'value' => null,
                    'type' => 'server error',
                    'msg' => 'error occurred',
                ]];
            }else{
                $response->errors = $data['errors'];
            }
        }

        if(!is_empty($data, 'extra')) foreach ($data['extra'] as $k=>$v) $response->{$k} = $v;

        return $response;
    }

    function response($data = NULL, $http_code = NULL)
    {
        if($http_code === null) $http_code = floor((int)$data['code']/10);
        $response = $this->setResponseData($data, $http_code);

        $this->output
            ->set_status_header($http_code)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
            ->_display();
        exit;
    }

    function web_sendmail($to, $subject, $message)
    {
        $this->email->initialize([
            'useragent' => 'CodeIgniter',
            'mailpath' => '/usr/sbin/sendmail',
            'protocol' => 'smtp',
            'smtp_host' => SMTP_HOST,
            'smtp_user' => SMTP_USER,
            'smtp_pass' => SMTP_PASS,
            'smtp_port' => SMTP_PORT,
            'mailtype' => 'html',
            'charset' => 'utf-8',
            'newline' => "\r\n",
            'wordwrap' => TRUE,
        ]);
        $this->email->from(FROM_EMAIL, FROM_NAME);
        $this->email->to($to);
        $this->email->subject($subject);
        $this->email->message($message);
        return $this->email->send();
    }

    protected function uploader($name, $_files = null)
    {
        $response = [
            'result' => true,
            'data' => [],
            'code' => DATA_UPLOADED,
            'message' => 'success',
        ];

        if (is_null($_files)) $_files = $_FILES;

        if (array_key_exists($name, $_files) && $_files[$name] && $_files[$name]['name']) {
            $uploadPath = 'public/uploads/' . $this->router->class . '/' . date('Y') . '/';
            if (!make_directory($uploadPath)) throw new Exception($this->upload->display_errors(), CREATE_FOLDER_FAIL);

            $this->upload->initialize(
                array_merge(
                    $this->config->item($name . '_upload_config') ?: $this->config->item('base_upload_config'),
                    [
                        'upload_path' => $uploadPath,
                    ]
                )
            );

            if (is_array($_files[$name]['name'])) {
                $file_names = $_files[$name]['name'];

                for ($i = 0; $i < count($file_names); $i++) {
                    $_FILES[$name] = [
                        'name' => $_files[$name]['name'][$i],
                        'type' => $_files[$name]['type'][$i],
                        'tmp_name' => $_files[$name]['tmp_name'][$i],
                        'error' => $_files[$name]['error'][$i],
                        'size' => $_files[$name]['size'][$i],
                    ];

                    try {
                        if (!$this->upload->do_upload($name)) throw new Exception($this->upload->display_errors(), UPLOAD_FILE_FAIL);

                        $data = $this->upload->data();
                        $key = $this->Model_File->addData(array_merge($data,
                            ['file_link' => get_file_key_from_path($data['full_path'])]
                        ), false);
                        if (!$key) throw new Exception('FILE DB Error', WRITE_FILEDB_FAIL);

                        $response['data'][] = [
                            'file_id' => $key,
                            'attach_cd' => $this->getAttachCd($this->upload->data()['file_ext']),
                        ];
                    } catch (Exception $e) {
                        return [
                            'result' => false,
                            'data' => [],
                            'code' => $e->getCode(),
                            'message' => $e->getMessage(),
                        ];
                    }
                }
            } else {
                $_FILES = $_files;

                try {
                    if (!$this->upload->do_upload($name)) throw new Exception($this->upload->display_errors(), UPLOAD_FILE_FAIL);

                    $key = $this->Model_File->addData($this->upload->data(), false);
                    if (!$key) throw new Exception('FILE DB Error', WRITE_FILEDB_FAIL);

                    $response['data'][] = [
                        'file_id' => $key,
                        'attach_cd' => $this->getAttachCd($this->upload->data()['file_ext']),
                    ];
                } catch (Exception $e) {
                    return [
                        'result' => false,
                        'data' => [],
                        'code' => $e->getCode(),
                        'message' => $e->getMessage(),
                    ];
                }
            }
        } else {
            $response['result'] = false;
            $response['code'] = UPLOAD_DATA_NOT_EXIST;
            $response['message'] = 'empty';
        }

        return $response;
    }

    public function downloader($key = 0)
    {
        $response = [
            'result' => false,
            'data' => [],
            'code' => ERROR_DOWNLOAD_NOTDATA,
            'message' => 'empty',
        ];

        if (!$key) return $response;

        $data = $this->Model_File->getDataWhere([], ['file_id' => $key]);
        if (!$data) return $response;

        $this->load->library('storage');
        $content = $this->storage->read($this->storage->keyFromUri($data->full_path));
        if ($content === null) {
            $response['code'] = ERROR_DOWNLOAD_NOTFILE;
            return $response;
        }

        try {
            $this->Model_File->modNumb('download_cnt', 1, ['file_id' => $key]);
            force_download($data->client_name, $content);
        } catch (Exception $e) {
            log_message('error', 'downloader : modNumb error ' . $this->db->last_query());
            return [
                'result' => false,
                'data' => [],
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
            ];
        }

        return true;
    }

    function getAttachCd($filename)
    {
        $attach_cd = '';
        if (is_video_file_ext($filename)) {
            $attach_cd = 'ATT002';
        }
        if (is_image_file_ext($filename)) {
            $attach_cd = 'ATT003';
        }
        if (!$attach_cd) $attach_cd = 'ATT001';

        return $attach_cd;
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
        $this->db->where_not_in('sml_cd', ['000']);
        $dto = array_merge($dto, ['use_yn' => 'Y']);
        return $this->Model_Sys_Code->getListWhere([], $dto);
    }

    protected function getArticleFileLink($dto)
    {
        $this->db->select('article_attachment.*');
        $this->db->join('article_attachment', 'article_attachment.file_id=file.file_id', 'left');
        if (array_key_exists('article_id', $dto)) {
            $this->db->where('article_attachment.article_id', $dto['article_id']);
            unset($dto['article_id']);
        }
        return $this->getFileLink($dto);
    }

    protected function getArticleFileData($dto)
    {
        $this->db->select('article_attachment.*');
        $this->db->join('article_attachment', 'article_attachment.file_id=file.file_id', 'left');
        if (array_key_exists('article_id', $dto)) {
            $this->db->where('article_attachment.article_id', $dto['article_id']);
            unset($dto['article_id']);
        }
        return $this->getFileData($dto);
    }

    protected function getArticleFileList($dto)
    {
        $this->db->select('article_attachment.*');
        $this->db->join('article_attachment', 'article_attachment.file_id=file.file_id', 'left');
        // 첨부 순서(reorder 로 저장한 sort_order), 순서가 없는 예전 첨부는 뒤에 올린 순서대로
        $this->db->order_by('COALESCE('.$this->db->dbprefix('article_attachment').'.sort_order, 65535)', 'ASC', false);
        $this->db->order_by('article_attachment.file_id', 'ASC');
        if (array_key_exists('article_id', $dto)) {
            $this->db->where('article_attachment.article_id', $dto['article_id']);
            unset($dto['article_id']);
        }
        return $this->getFileList($dto);
    }

    protected function getFileLink($dto)
    {
        $data = $this->getFileData($dto);
        if ($data) {
            return !empty($data->file_link)?$data->file_link:get_filepath_from_link($data->full_path);
        } else {
            return '';
        }
    }

    protected function getFileData($dto)
    {
        return $this->Model_File->getData([], $dto);
    }

    protected function getFileList($dto)
    {
        return $this->Model_File->getList($this->Model_File->notIncludeSelects(), $dto);
    }

    protected function delFileData($dto)
    {
        // delData와 같은 조건(['file_id' => ...])으로 조회 (getFileData는 ['where' => ...] 형식이라 조건이 무시됨)
        $fileData = $this->Model_File->getDataWhere([], $dto);
        if(!$fileData) $this->response(['code' => UPLOAD_DATA_NOT_EXIST]);

        $this->Model_File->delData($dto);
        $this->load->library('storage');
        if(!$this->storage->delete($this->storage->keyFromUri($fileData->full_path))) $this->response(['code' => INTERNAL_SERVER_ERROR]);
    }

    /**
     * 마지막 getOptions 가 만든 옵션별 추가 값 [옵션 값 => [컬럼 => 값]] (option_data.select.extra 또는 render.extra)
     * → <option data-{컬럼}> (setFormColumn 이 option_extras 로 담고 화면 JS 가 붙임)
     */
    public array $lastOptionExtras = [];

    protected function getOptions($field, $attr = []): array
    {
        if (empty($attr)) $attr['option_type'] = 'field';

        $this->lastOptionExtras = [];
        $type = $attr['option_type'] ?? '';
        $data = $attr['option_data'] ?? [];
        $render = $attr['render'] ?? [];
        // 예전 문서의 option_data.select.{id,text,extra} 도 받는다 (render 가 비었을 때)
        if (empty($render['id']) && is_array($data['select'] ?? null)) $render = array_merge($render, $data['select']);
        $extraColumns = is_array($render['extra'] ?? null) ? array_values($render['extra']) : [];

        $options = [];
        if($type === 'none' || empty($type)) {
            return form_options_by_field();
        }else if($type === 'blank') {
            return [];
        }else if($type === 'static') {
            return empty($data) ? form_options_by_field() : $data;
        }else if($type === 'field') {
            return form_options_by_field($field ?? 'default');
        }else if(in_array($type, ['api', 'script'], true)) {
            // 브라우저가 채운다 (data-option-type, app-page-form.js loadOptionSources). 서버 쪽 옵션은 비움
            return [];
        }else if(in_array($type, array_keys(config_item('options')))) {
            return form_options_by_field($type);
        }else{
            switch ($type) {
                case 'method' :
                    if(method_exists($this, $data['method'])) {
                        $options = $this->{$data['method']}($data['params']??null);
                    }
                    break;
                case 'code' :
                    $list = $this->getCodeList($data['params']);
                    if (empty($render['id'])) $render = array_merge($render, ['id' => 'cmb_cd', 'text' => 'cd_name']);
                    $options = $this->getOptionsFromDBList($list, $render);
                    $this->collectOptionExtras($list, $render, $extraColumns);
                    break;
                default :
                    if (empty($data)) $this->logging(__METHOD__." : {$field} have no option data.");
                    if (empty($render)) $this->logging(__METHOD__." : {$field} attributes has no render data.");

                    switch ($type) {
                        case 'db' :
                            $list = $this->db
                                ->where($data['params'])
                                ->get($data['table'])->result_array();
                            $options = $this->getOptionsFromDBList($list, $render);
                            $this->collectOptionExtras($list, $render, $extraColumns);
                            break;
                        case 'model' :
                            if (!property_exists($this, $data['model'])) {
                                try {
                                    $this->load->model($data['model']);
                                }catch (Exception $e) {
                                    show_error("(".__FUNCTION__.") ".$e->getMessage());
                                }
                            }

                            if (!method_exists($this->{$data['model']}, $data['method']))
                                show_error("getOptions : Method {$data['method']} not exist.", E_USER_ERROR);

                            // PHP 8에서 문자열 키는 named argument로 해석되므로 순서 배열로 전달
                            // select 컬럼: id·text·add 와 extra 컬럼 (callback 등 다른 키는 컬럼이 아님)
                            $selectColumns = array_values(array_filter(array_merge(
                                [$render['id'] ?? null, $render['text'] ?? null, $render['add'] ?? null],
                                $extraColumns
                            )));
                            $list = call_user_func_array([$this->{$data['model']}, $data['method']], [
                                $selectColumns,
                                $data['params'] ?? [],
                            ]);
                            $options = $this->getOptionsFromDBList($list, $render);
                            $this->collectOptionExtras($list, $render, $extraColumns);
                            break;
                        case 'custom' :
                            $options = call_user_func_array($data['method'], $data['params']);
                            break;
                    }
            }
            return $options;
        }
    }

    /**
     * 옵션 행에서 extra 컬럼 값을 모은다 → $this->lastOptionExtras
     */
    protected function collectOptionExtras($list, array $render, array $extraColumns): void
    {
        if (!count($extraColumns) || !is_array($list) || empty($render['id'])) return;
        foreach ($list as $row) {
            $row = (array)$row;
            if (!array_key_exists($render['id'], $row)) continue;
            $values = [];
            foreach ($extraColumns as $column) {
                $name = strpos($column, '.') !== false ? substr($column, strrpos($column, '.') + 1) : $column;
                if (array_key_exists($name, $row)) $values[$name] = $row[$name];
            }
            if (count($values)) $this->lastOptionExtras[(string)$row[$render['id']]] = $values;
        }
    }

    protected function getOptionsFromDBList($list, $render)
    {
        if(count($list) === 0) return [];
        if(is_object($list[0]) || is_array($list[0])) {
            return array_reduce($list, function ($carry, $item) use ($render) {
                $item = (array)$item;
                $id = $item[$render['id']] ?? '';

                if (strpos($render['text'], '.') !== false) {
                    $exploded = explode('.', $render['text']);
                    $render['text'] = $exploded[1];
                }

                $text = $item[$render['text']] ?? '';
                $carry[$id] = $text;
                if(!is_empty($render, 'add')) {
                    $add = $item[$render['add']] ?? '';
                    if($add) $carry[$id] .= " ($add)";
                }
                return $carry;
            }, []);
        }else{
            return array_combine(array_values($list),array_values($list));
        }
    }

    protected function setFrontendOptionList($list, $render)
    {
        if(count($list) === 0) return [];
        if(is_object($list[0]) || is_array($list[0])) {
            return array_map(function ($item) use ($render) {
                $item = (array)$item;
                $id = $item[$render['id']] ?? '';

                if (strpos($render['text'], '.') !== false) {
                    $exploded = explode('.', $render['text']);
                    $render['text'] = $exploded[1];
                }

                $text = $item[$render['text']] ?? '';
                if(!is_empty($render, 'add')) {
                    $add = $item[$render['add']] ?? '';
                    if($add) $text .= " ($add)";
                }

                return [
                    'id' => $id,
                    'text' => $text,
                ];
            }, $list);
        }else{
            return array_combine(array_values($list),array_values($list));
        }
    }

    public function logging($message, $errorLevel = E_USER_ERROR, $triggerError = true): void
    {
        switch ($errorLevel) {
            case E_USER_ERROR:
                $type = 'ERROR';
                break;
            case E_USER_WARNING:
                $type = 'DEBUG';
                break;
            case E_USER_NOTICE:
            case E_USER_DEPRECATED:
                $type = 'INFO';
        }
        log_message($type ?? 'ERROR', $message);

        if($triggerError && $errorLevel === E_USER_ERROR) {
            if($this->devMode) show_error($message);
        }
    }

    protected function setToken($data)
    {
        if (empty($data['user_id'])) {
            return false;
        }

        $userId = $data['user_id'];
        $now = date('Y-m-d H:i:s');

        $length = $this->config->item('rest_key_length') ?: 40;

        do {
            $token = $this->opaque_token->generate($length);

            $exists = $this->Model_User_Token->getDataWhere([], [
                'token' => $token,
            ]);
        } while ($exists);

        $row = $this->Model_User_Token->getDataWhere([], [
            'user_id' => $userId,
        ]);

        if ($row) {
            $this->Model_User_Token->modData([
                'token'        => $token,
                'created_dt'   => $now,
                'ip_addresses' => $this->input->ip_address(),
            ], [
                'user_id' => $userId,
            ], true);
        } else {
            $this->Model_User_Token->addData([
                'user_id'        => $userId,
                'token'          => $token,
                'level'          => 1,
                'ignore_limits'  => 1,
                'is_private_key' => 0,
                'ip_addresses'   => $this->input->ip_address(),
                'created_dt'     => $now,
            ], true);
        }

        return $token;
    }

    public function switchLang($language = "")
    {
        $this->load->model('Model_Language_Code');

        if($language) {
            $data = $this->Model_Language_Code->getDataWhere([], [
                'path' => $language,
                'use_yn' => 'Y',
            ]);
            $this->langCode = $data->code;
        }else{
            $code = $this->input->get('lang')??explode('.', $_SERVER['HTTP_HOST'])[0];
            $data = $this->Model_Language_Code->getDataWhere([], [
                'code' => strtoupper($code),
                'use_yn' => 'Y',
            ]);
            if($data) {
                $this->langCode = $code;
                $language = strtolower($data->path);
            }else{
                if($this->session->userdata('site_lang')) {
                    $language = $this->session->userdata('site_lang');
                    $this->langCode = $this->session->userdata('lang_code');
                }else{
                    $language = $this->config->item('language');
                    $data = $this->Model_Language_Code->getDataWhere([], [
                        'path' => $language,
                        'use_yn' => 'Y',
                    ]);
                    $this->langCode = $data->code;
                }
            }
        }

        $this->siteLang = $language;
        if($this->session->userdata('site_lang') !== $language) {
            $this->session->set_userdata('site_lang', $language);
            $this->session->set_userdata('lang_code', $this->langCode);
        }
    }

    /**
     * 로그인 세션을 채운다 (Auth::login_post 와 자동 로그인이 같은 값을 쓰도록 한 곳에 둔다)
     */
    protected function startUserSession(object $userData): void
    {
        $this->session->set_userdata([
            'name' => $userData->name,
            'id' => $userData->id,
            'user_id' => $userData->user_id,
            'user_cd' => $userData->user_cd,
            'is_sys_admin' => in_array($userData->user_cd, ['USR000']),
            'is_admin' => in_array($userData->user_cd, ['USR000', 'USR001', 'USR002']),
            'token' => $this->setToken([
                'user_id' => $userData->user_id,
            ]),
        ]);
    }

    /**
     * 세션의 토큰이 그 회원의 현재 토큰인지 (토큰은 회원당 하나 — 다른 곳에서 로그인하면 바뀐다)
     * 같은 계정은 한 곳에서만 로그인 (2026-10-26 결정)
     */
    protected function isSessionTokenCurrent(): bool
    {
        return $this->getSessionTokenState() === 'current';
    }

    /**
     * 세션 토큰 상태: none(세션에 없음) | replaced(다른 곳 로그인으로 바뀜) | expired(token_lifetime() 지남) | current
     */
    protected function getSessionTokenState(): string
    {
        $token = $this->session->userdata('token');
        $userId = $this->session->userdata('user_id');
        if(!$token || !$userId) return 'none';

        $row = $this->Model_User_Token->getDataWhere(['token', 'created_dt'], ['user_id' => $userId]);
        if(!is_object($row) || (string)$row->token !== (string)$token) return 'replaced';
        if(is_token_expired($row->created_dt ?? null)) return 'expired';

        return 'current';
    }

    /**
     * 만료된 세션의 로그인 값만 지운다 (자동 로그인 쿠키는 남겨 새 토큰을 받을 수 있게)
     */
    protected function clearLoginSession(): void
    {
        foreach (['name', 'id', 'user_id', 'user_cd', 'is_sys_admin', 'is_admin', 'token'] as $key) {
            $this->session->unset_userdata($key);
        }
    }

    /**
     * 자동 로그인 쿠키로 로그인 (config/extra/autologin_config.php).
     * 키·만료·IP·회원 상태(user_limit_conditions, 삭제 회원)가 맞으면 startUserSession, 아니면 키 행과 쿠키를 지운다.
     * 예전에는 hook(MY_Hooks::checkPermission)이 세션에 user_id 만 넣어, 화면이 token 이 없다고 보고
     * 세션과 쿠키를 지웠다 → 자동 로그인이 한 번도 되지 않았음
     */
    protected function loginWithAutologinCookie(): bool
    {
        if(!$this->config->item('autologin_config_loaded')) return false;
        if($this->session->userdata('token')) return false;

        $cookieName = $this->config->item('autologin_cookie_name');
        $key = get_cookie($cookieName);
        if(!$key) return false;

        $table = $this->config->item('autologin_table');
        $columns = $this->config->item('autologin_columns');
        $lifetime = $this->config->item('autologin_cookie_lifetime') ?: 86400 * 30;
        $limits = $this->config->item('user_limit_conditions') ?: [];

        $row = $this->db->order_by($columns['date'], 'desc')
            ->where([$columns['key'] => $key])->get($table)->row_array();
        if(!$row) {
            delete_cookie($cookieName);
            return false;
        }

        $userData = null;
        $valid = element(USER_ID_COLUMN_NAME, $row)
            && element($columns['date'], $row)
            && strtotime(element($columns['date'], $row)) >= ctimestamp() - $lifetime
            && $this->input->ip_address() === element($columns['ip'], $row);

        if($valid) {
            $userData = $this->Model_User->getDataWhere([], [USER_ID_COLUMN_NAME => element(USER_ID_COLUMN_NAME, $row)]);
            if(!is_object($userData)) $valid = false;
            foreach ($limits as $field => $value) {
                if($valid && property_exists($userData, $field) && $userData->{$field} === $value) $valid = false;
            }
        }

        if(!$valid) {
            $this->db->delete($table, [$columns['id'] => element($columns['id'], $row)]);
            delete_cookie($cookieName);
            return false;
        }

        $this->startUserSession($userData);
        return true;
    }

    protected function destroyUserData(): void
    {
        if(count($this->session->userdata())) {
            foreach ($this->session->userdata() as $key=>$val) {
                $this->session->unset_userdata($key);
            }
            $this->session->sess_destroy();
        }

        // 세션 쿠키 삭제
        if (isset($_COOKIE[$this->config->item('sess_cookie_name')])) {
            setcookie($this->config->item('sess_cookie_name'), '', time() - 3600, '/');
        }
    }
}

include_once __DIR__.'/MY_Controller_API.php';
include_once __DIR__.'/MY_Controller_WEB.php';
include_once __DIR__.'/MY_Controller_APP.php';
include_once __DIR__.'/MY_Builder_WEB.php';
include_once __DIR__.'/MY_Builder_API.php';
