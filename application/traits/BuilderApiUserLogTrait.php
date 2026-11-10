<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * 사용자 로그 남기기와 보관 기간 지난 로그 삭제 (MY_Builder_API)
 */
trait BuilderApiUserLogTrait
{
    protected function loggingUserlog()
    {
        // 로그인 전 요청(회원가입)은 남길 사용자가 없다
        if(!$this->session->userdata('user_id')) return;

        $class = $this->router->class;
        $method = $this->router->method;
        $restMethod = $this->input->method();
        $idExist = !empty($this->idData);
        $userKind = '시스템';
        if($this->session->userdata('user_cd') !== 'USR000') {
            $userKind = $this->getCodeName([
                'cmb_cd' => $this->session->userdata('user_cd')
            ]);
        }

        // 로그는 남기지 않아도 보관 기간 지난 로그는 지운다 (시스템 관리자만 쓰는 운영에서도 돌도록)
        if(!$this->devMode && $this->session->userdata('is_sys_admin')) {
            $this->purgeLogs();
            return;
        }

        $name = $this->session->userdata('name');
        $pp = $this->josa->__replace($name, '이');
        $subject = "{$name}($userKind){$pp}";
        $object = '';
        $verb = '';
        $message = '';

        switch ($class) {
            case 'auth' :
                $type = 'auth';
                if($method === 'login') {
                    $title = '로그인';
                    $verb = "로그인했습니다";
                }else if($method === 'logout') {
                    $title = '로그아웃';
                    $verb = "로그아웃했습니다";
                }
                break;
            default :
                switch ($restMethod) {
                    case 'get' :
                        $type = 'read';
                        $title = '조회';
                        $verb = "조회했습니다";
                        if($idExist) {
                            $object = "데이터를";
                        }else{
                            $object = "목록을";
                        }
                        break;
                    case 'post' :
                    case 'put' :
                    case 'patch' :
                        $object = "데이터를";
                        if($idExist) {
                            $title = '수정';
                            $type = 'update';
                            $verb = "수정했습니다";
                        }else{
                            $title = '등록';
                            $type = 'create';
                            $verb = "등록했습니다";
                        }
                        break;
                    case 'delete' :
                        $title = '삭제';
                        $type = 'delete';
                        $object = "데이터를";
                        $verb = "삭제했습니다";
                        break;
                }
                break;
        }

        if($object) {
            if($this->routeTitle) {
                $object = $this->routeTitle . ' ' . $object;
            }
        }

        foreach ([$subject, $object, $verb] as $i=>$item) {
            if($item !== '') $message .= $item;
            if($i === 2) {
                $message .= '.';
            }else{
                if($item !== '') $message .= ' ';
            }
        }

        $this->Model_User_Logs->addData([
            'user_id' => $this->session->userdata("user_id"),
            'class' => $this->router->class,
            'method' => $this->router->method,
            'title' => $title ?? '',
            'message' => $message ?? '',
            'log_id' => $this->_insert_id,
            'type' => $type ?? '',
            // 대상 행의 식별자만 남긴다 (요청 본문에는 비밀번호가 섞일 수 있다)
            'params' => $idExist ? json_encode($this->idData, JSON_UNESCAPED_UNICODE) : null,
        ]);

        $this->purgeLogs();
    }

    /**
     * 보관 기간(환경값 SYS_USER_LOG_KEEP_DAYS)이 지난 사용자 로그·요청 로그를 지운다. 비었거나 0 이면 지우지 않는다.
     * 예약 실행 환경이 없어 로그를 남길 때 지운다
     */
    protected function purgeLogs(): void
    {
        // 지우면 되살릴 수 없으므로 정수가 아닌 값(저장 검사를 거치지 않은 값)이면 지우지 않는다
        $value = user_cfg('LOG_KEEP_DAYS');
        if(!ctype_digit($value) || (int)$value <= 0) return;
        $days = (int)$value;

        // 사용자 로그 datetime 은 DB 시각(current_timestamp)으로 쌓이므로 DB 시각으로, 요청 로그 time 은 PHP time() 으로 쌓이므로 그대로 비교한다
        $this->purgeLogRows('user_logs_purged', $this->db, $this->Model_User_Logs->table, "`datetime` < NOW() - INTERVAL {$days} DAY");
        if($this->config->item('rest_enable_logging')) {
            $this->purgeLogRows('rest_logs_purged', $this->rest->db, $this->config->item('rest_logs_table'), '`time` < '.(time() - $days * 86400));
        }
    }

    /**
     * 요청이 길어지지 않게 한 번에 1000행까지만 지운다. 더 남았으면 다음 요청에서 잇고, 다 지운 날은 다시 보지 않는다
     */
    protected function purgeLogRows(string $cacheKey, $db, string $table, string $condition): void
    {
        $today = date('Y-m-d');
        if($this->cache->file->get($cacheKey) === $today) return;

        $db->where($condition, null, false)->limit(1000)->delete($table);
        if($db->affected_rows() < 1000) $this->cache->file->save($cacheKey, $today, 172800);
    }
}
