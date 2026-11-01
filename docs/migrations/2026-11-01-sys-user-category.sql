-- 환경값 설정의 회원(USER)과 인증(SYS_AUTH) 카테고리를 SYS_USER 하나로 합친다.
-- USER_* → SYS_USER_*, SYS_AUTH_TOKEN_LIFETIME → SYS_USER_TOKEN_LIFETIME. 저장한 값은 그대로 옮긴다.
-- 코드를 반영한 뒤, 2026-11-01-user-cfg-to-sys-cfg.sql 다음에 실행한다. 다시 실행해도 된다.
-- 코드 반영과 이 실행 사이에는 값을 못 읽어 기본값(토큰은 .env TOKEN_LIFETIME)으로 동작한다.
-- created_id 는 시스템 관리자(USR000)의 user_id. 시스템 관리자가 없으면 카테고리 행이 들어가지 않는다(마지막 확인 쿼리로 본다).

-- 확인
SELECT cmb_cfg, big_cfg, sml_cfg, cfg_val FROM tbl_sys_cfg WHERE big_cfg IN ('USER', 'SYS_AUTH', 'SYS_USER') ORDER BY cmb_cfg;

INSERT IGNORE INTO tbl_sys_cfg (cmb_cfg, big_cfg, sml_cfg, cfg_name, cfg_type, created_dt, created_id)
SELECT 'SYS_USER_000', 'SYS_USER', '000', '회원·인증', 'text', NOW(), user_id
FROM tbl_user WHERE user_cd = 'USR000' ORDER BY user_id LIMIT 1;

-- 이미 옮긴 뒤 옛 마이그레이션을 다시 실행해 USER·SYS_AUTH 행이 되살아났으면 지운다 (옮겨 둔 값을 유지)
DELETE old FROM tbl_sys_cfg old
JOIN tbl_sys_cfg moved ON moved.cmb_cfg = CONCAT('SYS_USER_', old.sml_cfg)
WHERE old.big_cfg IN ('USER', 'SYS_AUTH') AND old.sml_cfg <> '000';

UPDATE tbl_sys_cfg SET cmb_cfg = CONCAT('SYS_USER_', sml_cfg), big_cfg = 'SYS_USER'
WHERE big_cfg IN ('USER', 'SYS_AUTH') AND sml_cfg <> '000';

DELETE FROM tbl_sys_cfg WHERE cmb_cfg IN ('USER_000', 'SYS_AUTH_000');

-- 확인
SELECT cmb_cfg, big_cfg, sml_cfg, cfg_name, cfg_val FROM tbl_sys_cfg WHERE big_cfg IN ('USER', 'SYS_AUTH', 'SYS_USER') ORDER BY cmb_cfg;
