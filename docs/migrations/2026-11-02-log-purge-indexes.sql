-- 보관 기간이 지난 로그를 지울 때(MY_Builder_API::purgeLogRows) 표 전체를 훑지 않게 인덱스를 건다.
-- 인덱스가 없으면 지울 행이 1000 건 미만인 날 DELETE 가 표를 끝까지 훑으며 행을 잠가, 그동안 로그 INSERT(화면 요청)가 기다린다.
-- SYS_USER_LOG_KEEP_DAYS 에 값을 넣기 전에 실행한다. 다시 실행해도 된다 (MariaDB).
--
-- 되돌리기: ALTER TABLE tbl_user_logs DROP INDEX idx_user_logs_datetime; ALTER TABLE tbl_rest_logs DROP INDEX idx_rest_logs_time;

ALTER TABLE tbl_user_logs ADD INDEX IF NOT EXISTS idx_user_logs_datetime (`datetime`);
ALTER TABLE tbl_rest_logs ADD INDEX IF NOT EXISTS idx_rest_logs_time (`time`);

UPDATE tbl_sys_cfg
SET cfg_desc = '사용자 로그와 요청 로그에 함께 적용합니다. 기간이 지난 로그는 요청을 처리할 때 1,000건씩 나누어 지웁니다. 지운 로그는 되살릴 수 없습니다. 비우거나 0이면 지우지 않습니다. 법적 보관 의무가 있으면 그 기간 이상으로 정하세요.'
WHERE cmb_cfg = 'SYS_USER_LOG_KEEP_DAYS';
