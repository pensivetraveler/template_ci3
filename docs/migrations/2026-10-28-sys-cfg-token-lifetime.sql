-- 인증 토큰 유효 시간을 관리자 > 시스템 > 환경값 설정에서 바꿀 수 있게 tbl_sys_cfg 에 행을 넣는다.
-- common_helper token_lifetime() 이 읽는 순서: 이 행(cmb_cfg = SYS_AUTH_TOKEN_LIFETIME) → .env TOKEN_LIFETIME → 43200(12시간).
-- 값은 초 단위 0 이상 정수, 0 이면 만료 없음, 비우면 .env 값을 쓴다. 바꾼 값은 다음 요청부터 모든 토큰(이미 발급된 것 포함)에 적용된다.
--
-- 실행 전: 아래 확인 쿼리로 같은 행이 없는지 본다. 행이 이미 있으면 INSERT IGNORE 는 아무것도 바꾸지 않는다.
-- created_id 는 시스템 관리자(USR000)의 user_id. 시스템 관리자가 없으면 행이 들어가지 않는다(마지막 확인 쿼리로 본다).

-- 확인
SHOW CREATE TABLE tbl_sys_cfg;
SELECT * FROM tbl_sys_cfg WHERE cmb_cfg = 'SYS_AUTH_TOKEN_LIFETIME';

INSERT IGNORE INTO tbl_sys_cfg (cmb_cfg, big_cfg, sml_cfg, cfg_name, cfg_type, cfg_val, cfg_desc, created_dt, created_id)
SELECT 'SYS_AUTH_TOKEN_LIFETIME', 'SYS_AUTH', 'TOKEN_LIFETIME', '토큰 유효 시간(초)', 'text', '43200',
    '로그인 후 이 시간이 지나면 다시 로그인(자동 로그인 쿠키가 있으면 새 토큰). 초 단위, 43200 = 12시간, 0 = 만료 없음, 비우면 .env TOKEN_LIFETIME',
    NOW(), user_id
FROM tbl_user WHERE user_cd = 'USR000' ORDER BY user_id LIMIT 1;

-- 확인
SELECT cmb_cfg, cfg_name, cfg_val FROM tbl_sys_cfg WHERE cmb_cfg = 'SYS_AUTH_TOKEN_LIFETIME';
