<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once __DIR__.'/Common.php';

class Auth extends Common
{
    // index 차단 여부는 MY_Builder_API::__construct() 안에서 검사하므로 생성자 이후 대입이 아니라 기본값으로 선언
    protected bool $noIndexMethod = true;

    function __construct()
    {
        parent::__construct();

        $this->load->model('Model_User', 'Model');

        $this->setProperties($this->Model, false);

        // 자동 로그인 쿠키 이름·기간·사용자당 개수 (WEB 의 loginWithAutologinCookie 와 같은 설정)
        $this->config->load('extra/autologin_config', false);

        // 엔드포인트별 인증·키·로그 설정은 config/extra/api/api_method_config.php 의 'auth' 항목
    }

    public function dupCheck_get()
    {
        $key = $this->input->get('key');
        $value = $this->input->get('value');
        if(!$key || !$value){
            $this->response([
                'code' => EMPTY_REQUIRED_DATA,
                'data' => $this->input->get(),
            ]);
        }else{
            $count = $this->Model->getCntWhere([$key => $value]);
            if($count) {
                $this->response([
                    'code' => DATA_ALREADY_EXIST,
                ]);
            }else{
                $this->response([
                    'code' => DATA_AVAILABLE,
                ]);
            }
        }
    }

    public function idCheck_get()
    {
        $id = $this->input->get('id');
        if(!$id){
            $this->response([
                'code' => EMPTY_REQUIRED_DATA,
            ]);
        }else{
            $count = $this->Model->getCntWhere(['id' => $id]);
            if($count) {
                $this->response([
                    'code' => ID_ALREADY_EXIST,
                ]);
            }else{
                $this->response([
                    'code' => ID_IS_AVAILABLE,
                ]);
            }
        }
    }

    public function emailCheck_get()
    {
        $email = $this->input->get('email');
        if(!$email){
            $this->response([
                'code' => EMPTY_REQUIRED_DATA,
            ]);
        }else{
            $count = $this->Model->getCntWhere(['email' => $email]);
            if($count) {
                $this->response([
                    'code' => EMAIL_ALREADY_EXIST,
                ]);
            }else{
                $this->response([
                    'code' => EMAIL_IS_AVAILABLE,
                ]);
            }
        }
    }

    public function login_post()
    {
        $this->validateFormRules('form_login_config');

        $params = [
            'id' => $this->input->post('id'),
        ];
        if($this->input->post('user_cd')) $params['user_cd'] = $this->input->post('user_cd');

        $password = $this->input->post('password');

        $count = $this->Model->getCntWhere($params);
        if(!$count) $this->response(['code' => USER_NOT_EXIST,]);

        $userData = $this->Model->getDataWhere([], $params);
        if($userData->withdraw_yn === 'Y') $this->response(['code' => WITHDRWAN_USER]);

        if(!custom_password_verify($password, $userData->password, PASSWORD_DECRYPTABLE))
            $this->response(['code' => PASSWORD_IS_NOT_MATCHED, 'data' => []]);

        // 해시 모드인데 암호화 값으로 남은 계정은 로그인할 때 해시로 바꿔 저장 (일괄 전환 없이 차례로 옮겨짐)
        if(password_needs_upgrade($userData->password)) {
            if($this->Model->modData(['password' => create_hash($password)], ['user_id' => $userData->user_id]) === false) {
                log_message('error', 'login password upgrade failed : user_id '.$userData->user_id);
            }
        }

        // 이 브라우저의 이전 자동 로그인 행·만료 행 정리 (다시 로그인할 때마다 새 행만 쌓이던 문제)
        $cookieName = $this->config->item('autologin_cookie_name') ?: 'autologin';
        $lifetime = (int)($this->config->item('autologin_cookie_lifetime') ?: 86400 * 30);
        $this->clearBrowserAutologin($cookieName, !$this->input->post('autologin'));
        $this->purgeExpiredAutologin($lifetime);

        if ($this->input->post('autologin')) {
            $vericode = array('$', '/', '.');

            $hash = str_replace(
                $vericode,
                '',
                password_hash(
                    random_string('alnum', 10)
                    . element('user_id', (array) $userData)
                    . ctimestamp()
                    . element('id', (array) $userData),
                    PASSWORD_BCRYPT
                )
            );

            $this->Model_User_Autologin->addData([
                'user_id'      => element('user_id', (array) $userData),
                'aul_key'      => $hash,
                'aul_ip'       => $this->input->ip_address(),
                'aul_datetime' => cdate('Y-m-d H:i:s'),
            ]);

            $cookie_value = $hash;

            $this->trimUserAutologin(element('user_id', (array) $userData));

            set_cookie($cookieName, $cookie_value, $lifetime);
        }

        $this->startUserSession($userData);

        $this->loggingUserlog();

        $this->response([
            'code' => DATA_AVAILABLE,
            'data' => [
                // 자동 로그인 키는 HttpOnly 쿠키로만 보낸다 (응답 본문에 담으면 JS·XSS 가 읽을 수 있음)
                'autologin' => isset($cookie_value),
            ],
        ]);
    }

    /**
     * 요청에 자동 로그인 쿠키가 있으면 그 행을 지운다. $deleteCookie 면 쿠키도 (자동 로그인을 끄고 로그인한 경우)
     */
    protected function clearBrowserAutologin(string $cookieName, bool $deleteCookie): void
    {
        $key = get_cookie($cookieName);
        if(!$key) return;

        $this->Model_User_Autologin->delData(['aul_key' => $key]);
        if($deleteCookie) delete_cookie($cookieName);
    }

    /**
     * 기간(autologin_cookie_lifetime)이 지난 행을 모든 사용자에서 지운다. 쿠키로 다시 오지 않는 브라우저의 행이 남지 않도록
     */
    protected function purgeExpiredAutologin(int $lifetime): void
    {
        $this->db->group_start()
            ->where('aul_datetime <', date('Y-m-d H:i:s', ctimestamp() - $lifetime))
            ->or_where('aul_datetime IS NULL', null, false)
            ->group_end()
            ->delete($this->Model_User_Autologin->table);
    }

    /**
     * 사용자당 최신 autologin_max_per_user 개(기본 5)만 남긴다. 넘으면 가장 오래된 기기의 자동 로그인이 풀린다
     */
    protected function trimUserAutologin($userId): void
    {
        $max = (int)($this->config->item('autologin_max_per_user') ?: 5);
        $ids = array_column($this->db->select('aul_id')
            ->where('user_id', $userId)
            ->order_by('aul_datetime', 'DESC')
            ->order_by('aul_id', 'DESC')
            ->get($this->Model_User_Autologin->table)->result_array(), 'aul_id');

        $old = array_slice($ids, $max);
        if(count($old)) $this->db->where('user_id', $userId)->where_in('aul_id', $old)->delete($this->Model_User_Autologin->table);
    }

    public function signup_post()
    {
        // 설정한 프로젝트의 공개 경로에서만 받는다. 관리자 경로로는 가입하지 않는다
        $userCd = $this->config->item('signup_user_cd');
        if(!$userCd || in_array($this->flag, $this->permissionFlags, true)) show_404();

        $this->validateAuthForm('form_signup_config', 'signup');

        // user_id 는 AUTO_INCREMENT 라 기본값을 넣지 않는다
        // 등급도 모델 필수값이라 검증 전에 넣는다
        $this->defaultList = [
            USER_CD_COLUMN_NAME => $userCd,
            'approve_yn' => 'N',
            'withdraw_yn' => 'N',
            'del_yn' => 'N',
        ];

        // beforePost() 는 [$key, $data] 를 돌려준다. 저장은 파이프라인(executeAdd)을 거쳐 비밀번호 해시·FK 채우기를 적용
        // (예전 코드는 정의되지 않은 Model_Child 를 사용해 회원가입이 동작하지 않았다)
        list(, $dto) = $this->beforePost(0);
        $dto[USER_CD_COLUMN_NAME] = $userCd; // 등급은 요청 값을 쓰지 않는다
        $this->executeAdd($dto, false);

        $this->loggingUserlog();

        $this->response([
            'code' => DATA_CREATED,
            'data' => $this->idData,
        ], RestController::HTTP_CREATED);
    }

    /**
     * 가입 필드 설정(회원 설정 화면)에 맞춘 폼 항목으로 검증한다
     */
    private function validateAuthForm(string $configName, string $form): void
    {
        $this->config->set_item($configName, filter_auth_form($this->config->item($configName) ?: [], $form));
        $this->validateFormRules($configName);
    }

    public function findId_post()
    {
        if(!find_id_enabled()) show_404();

        $this->validateAuthForm('form_find_id_config', 'find_id');

        $params = [
            'tel' => $this->input->post('tel'),
        ];
        if(signup_field_enabled('email')) {
            $params['email'] = $this->input->post('email');
        }else{
            $params['name'] = $this->input->post('name');
        }
        if($this->input->post('user_cd')) $params['user_cd'] = $this->input->post('user_cd');

        $count = $this->Model->getCntWhere($params);

        if(!$count) {
            $this->response(['code' => USER_NOT_EXIST,]);
        }else{
            $id = $this->Model->getDataWhere(['id'], $params);

            $this->response([
                'code' => DATA_RETRIEVED,
                'data' => [
                    'result' => [
                        'id' => get_starred_id($id),
                    ],
                    'redirect_to' => '',
                ],
            ]);
        }
    }

    public function findPassword_post()
    {
        if(!find_password_enabled()) show_404();

        $this->validateAuthForm('form_find_password_config', 'find_password');

        $params = [
            'id' => $this->input->post('id'),
            'email' => $this->input->post('email'),
        ];
        if(signup_field_enabled('tel')) $params['tel'] = $this->input->post('tel');
        if($this->input->post('user_cd')) $params['user_cd'] = $this->input->post('user_cd');

        // 찾기 방식은 회원 설정 화면에서 정한다. 정하지 않았으면 비밀번호 표시 또는 문의
        if(user_cfg('PASSWORD_FIND_MODE') === 'reset') $this->requestPasswordReset($params);

        if(!$this->Model->getCntWhere($params)) $this->response(['code' => USER_NOT_EXIST]);

        $stored = PASSWORD_DECRYPTABLE ? $this->Model->getDataWhere(['password'], $params) : '';
        $password = $stored && !is_password_hash($stored) ? $this->encryption->decrypt($stored) : false;
        if($password === false) $this->askAdministrator();

        $this->response([
            'code' => DATA_RETRIEVED,
            'data' => [
                'result' => [
                    'password' => get_starred_password($password),
                ],
                'redirect_to' => '',
            ],
        ]);
    }

    private function askAdministrator(): void
    {
        $this->response([
            'code' => BAD_REQUEST,
            'msg' => '관리자에게 문의해주세요.',
        ]);
    }

    /**
     * 메일 설정이 있으면 링크를 메일로 보내고(계정 유무와 관계없이 같은 응답), 없으면 재설정 화면으로 보낸다.
     * 메일 없이는 시스템 관리자 계정을 재설정하지 않는다
     */
    private function requestPasswordReset(array $params): void
    {
        if(!$this->db->table_exists('password_reset')) $this->askAdministrator();

        $this->load->model('Model_Password_Reset');
        $user = $this->Model->getDataWhere([], array_merge($params, ['withdraw_yn' => 'N']));
        $path = $this->config->item('password_reset_path');

        if(password_reset_mail_ready()) {
            if($user && !$this->isPasswordResetCoolingDown($user->user_id)) {
                $token = $this->createPasswordResetToken($user->user_id, 1800);
                $this->sendEmail([
                    'to' => $user->email,
                    'subject' => '비밀번호 재설정 안내',
                    'template' => 'password_reset',
                    'doc_id' => $user->user_id,
                    'no_bcc' => true,
                    'link' => rtrim(getenv('APP_URL'), '/').'/'.$path.'?token='.$token,
                    'minutes' => 30,
                ]);
            }
            $this->response([
                'code' => DATA_PROCESSED,
                'msg' => '입력하신 정보가 맞으면 재설정 링크를 메일로 보냈습니다.',
            ]);
        }

        if(!$user) $this->response(['code' => USER_NOT_EXIST]);
        if($user->user_cd === 'USR000') $this->askAdministrator();

        $token = $this->createPasswordResetToken($user->user_id, 600);
        $this->response([
            'code' => DATA_PROCESSED,
            'data' => [
                'redirect_to' => base_url($path).'?token='.$token,
            ],
        ]);
    }

    private function isPasswordResetCoolingDown($userId): bool
    {
        return $this->Model_Password_Reset->getCntWhere([
            'user_id' => $userId,
            'created_dt >' => date('Y-m-d H:i:s', time() - 60),
        ]) > 0;
    }

    /**
     * 새 토큰을 만들고 이전 토큰은 지운다. 원문은 돌려주기만 하고 저장하지 않는다
     */
    private function createPasswordResetToken($userId, int $lifetime): string
    {
        $token = bin2hex(random_bytes(32));
        $this->db->where('user_id', $userId)->delete($this->Model_Password_Reset->table);
        $this->Model_Password_Reset->addData([
            'user_id' => $userId,
            'token_hash' => hash('sha256', $token),
            'expires_dt' => date('Y-m-d H:i:s', time() + $lifetime),
            'request_ip' => $this->input->ip_address(),
            'created_dt' => date('Y-m-d H:i:s'),
        ]);
        return $token;
    }

    public function resetPassword_post()
    {
        if(!find_password_enabled() || user_cfg('PASSWORD_FIND_MODE') !== 'reset' || !$this->db->table_exists('password_reset')) show_404();

        $this->load->model('Model_Password_Reset');
        $this->load->model('Model_User_Token');

        $invalid = ['code' => BAD_REQUEST, 'msg' => '링크가 만료되었거나 이미 사용되었습니다. 비밀번호 찾기를 다시 해 주세요.'];
        $row = $this->Model_Password_Reset->findValid((string)$this->input->post('token'));
        if(!$row) $this->response($invalid);

        $this->validateFormRules('form_reset_password_config');

        // 같은 토큰으로 동시에 들어와도 한 번만 쓰이도록 먼저 지운다
        $this->db->where('reset_id', $row->reset_id)->delete($this->Model_Password_Reset->table);
        if($this->db->affected_rows() < 1 || !$this->Model->getCntWhere(['user_id' => $row->user_id, 'withdraw_yn' => 'N'])) $this->response($invalid);

        $this->Model->modData([
            'password' => create_hash($this->input->post('password')),
        ], [
            'user_id' => $row->user_id,
        ]);

        // 이전 비밀번호로 만든 자동 로그인·API 토큰은 쓰지 못하게 한다
        $this->db->where('user_id', $row->user_id)->delete($this->Model_User_Autologin->table);
        $this->db->where('user_id', $row->user_id)->delete($this->Model_User_Token->table);

        $this->response([
            'code' => DATA_EDITED,
            'data' => [
                'notice' => '비밀번호를 변경했습니다. 새 비밀번호로 로그인해 주세요.',
                'redirect_to' => base_url($this->flag.'/auth/login'),
            ],
        ]);
    }

    public function withdraw_post()
    {
        $this->Model->modData([
            'withdraw_yn' => 'Y',
            'withdraw_dt' => date('Y-m-d'),
        ], [
            'user_id' => $this->session->userdata('user_id'),
        ]);

        $this->Model_User_Autologin->delData([
            'user_id' => $this->session->userdata('user_id'),
        ]);

        $this->destroyUserData();

        $this->response([
            'code' => DATA_PROCESSED,
        ]);
    }

    public function passwordCheck_post()
    {
        $params = [
            'password' => $this->input->post('password'),
        ];

        $userData = $this->Model->getData([], [
            'where' => ['user_id' => $this->session->userdata('user_id'),]
        ]);

        if(!$userData || !custom_password_verify($params['password'], $userData->password, PASSWORD_DECRYPTABLE)){
            $this->response([
                'code' => PASSWORD_IS_NOT_MATCHED,
            ]);
        }

        $this->response([
            'code' => DATA_PROCESSED,
        ]);
    }

    public function logout_post()
    {
        $this->loggingUserlog();

        $this->destroyUserData();

        $this->response([
            'code' => DATA_PROCESSED,
        ]);
    }
}
