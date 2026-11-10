-- (키 이름은 다음 마이그레이션 2026-11-01-sys-user-category.sql 에서 SYS_USER_* 로 바뀐다. 이어서 실행한다. 다시 실행했으면 그 마이그레이션도 다시 실행한다.)
-- 회원 설정 값을 환경값 설정의 USER 카테고리로 옮기고 회원 설정(system/userCfg) 메뉴를 없앤다.
-- 코드를 반영한 뒤, 2026-11-01-sys-cfg-category.sql 다음에 실행한다. 저장 위치(big_cfg = USER)는 그대로라 이미 저장한 값은 바뀌지 않는다.
-- 다시 실행해도 된다. 메뉴 캐시를 쓰면 실행 뒤 application/cache/menu_done 을 지운다.
-- created_id 는 시스템 관리자(USR000)의 user_id. 시스템 관리자가 없으면 행이 들어가지 않는다(마지막 확인 쿼리로 본다).

-- 확인
SELECT cmb_cfg, cfg_val FROM tbl_sys_cfg WHERE big_cfg = 'USER';
SELECT menu_id, title FROM tbl_menu WHERE class = 'system' AND method = 'userCfg';

INSERT IGNORE INTO tbl_sys_cfg (cmb_cfg, big_cfg, sml_cfg, cfg_name, cfg_type, cfg_val, cfg_desc, created_dt, created_id)
SELECT v.cmb_cfg, v.big_cfg, v.sml_cfg, v.cfg_name, 'text', v.cfg_val, v.cfg_desc, NOW(), u.user_id
FROM (
    SELECT 'USER_000' cmb_cfg, 'USER' big_cfg, '000' sml_cfg, '회원' cfg_name, NULL cfg_val, NULL cfg_desc UNION ALL
    SELECT 'USER_LOG_KEEP_DAYS' cmb_cfg, 'USER' big_cfg, 'LOG_KEEP_DAYS' sml_cfg, '로그 보관 기간(일)' cfg_name, '' cfg_val, '사용자 로그와 요청 로그에 함께 적용합니다. 기간이 지난 로그는 요청을 처리할 때 1,000건씩 나누어 지웁니다. 비우거나 0이면 지우지 않습니다. 법적 보관 의무가 있으면 그 기간 이상으로 정하세요.' cfg_desc UNION ALL
    SELECT 'USER_PASSWORD_FIND_MODE' cmb_cfg, 'USER' big_cfg, 'PASSWORD_FIND_MODE' sml_cfg, '비밀번호 찾기 방식' cfg_name, '' cfg_val, 'show(비밀번호 일부 가려 표시, 복호화할 수 있는 저장 방식에서만) 또는 reset(메일 설정이 있으면 재설정 링크를 메일로 보내고, 없으면 확인 후 재설정 화면을 보여 줌. 메일 없이는 시스템 관리자 계정을 재설정할 수 없음). 비우면 비밀번호 표시 또는 문의 안내입니다.' cfg_desc UNION ALL
    SELECT 'USER_SIGNUP_EMAIL_YN' cmb_cfg, 'USER' big_cfg, 'SIGNUP_EMAIL_YN' sml_cfg, '가입 필드: 이메일 사용' cfg_name, 'Y' cfg_val, 'Y 또는 N. N 이면 비밀번호 찾기를 쓸 수 없고, 아이디 찾기는 이름과 전화번호로 확인합니다.' cfg_desc UNION ALL
    SELECT 'USER_SIGNUP_TEL_YN' cmb_cfg, 'USER' big_cfg, 'SIGNUP_TEL_YN' sml_cfg, '가입 필드: 전화번호 사용' cfg_name, 'Y' cfg_val, 'Y 또는 N. N 이면 아이디 찾기를 쓸 수 없습니다.' cfg_desc
) v
JOIN (SELECT user_id FROM tbl_user WHERE user_cd = 'USR000' ORDER BY user_id LIMIT 1) u;

-- 전에 회원 설정 화면에서 저장한 행은 이름만 있고 설명이 없다
UPDATE tbl_sys_cfg SET cfg_name = '로그 보관 기간(일)', cfg_desc = '사용자 로그와 요청 로그에 함께 적용합니다. 기간이 지난 로그는 요청을 처리할 때 1,000건씩 나누어 지웁니다. 비우거나 0이면 지우지 않습니다. 법적 보관 의무가 있으면 그 기간 이상으로 정하세요.' WHERE cmb_cfg = 'USER_LOG_KEEP_DAYS';
UPDATE tbl_sys_cfg SET cfg_name = '비밀번호 찾기 방식', cfg_desc = 'show(비밀번호 일부 가려 표시, 복호화할 수 있는 저장 방식에서만) 또는 reset(메일 설정이 있으면 재설정 링크를 메일로 보내고, 없으면 확인 후 재설정 화면을 보여 줌. 메일 없이는 시스템 관리자 계정을 재설정할 수 없음). 비우면 비밀번호 표시 또는 문의 안내입니다.' WHERE cmb_cfg = 'USER_PASSWORD_FIND_MODE';
UPDATE tbl_sys_cfg SET cfg_name = '가입 필드: 이메일 사용', cfg_desc = 'Y 또는 N. N 이면 비밀번호 찾기를 쓸 수 없고, 아이디 찾기는 이름과 전화번호로 확인합니다.' WHERE cmb_cfg = 'USER_SIGNUP_EMAIL_YN';
UPDATE tbl_sys_cfg SET cfg_name = '가입 필드: 전화번호 사용', cfg_desc = 'Y 또는 N. N 이면 아이디 찾기를 쓸 수 없습니다.' WHERE cmb_cfg = 'USER_SIGNUP_TEL_YN';

DELETE FROM tbl_menu_auth WHERE menu_id IN (SELECT menu_id FROM tbl_menu WHERE class = 'system' AND method = 'userCfg');
DELETE FROM tbl_partner_auth WHERE menu_id IN (SELECT menu_id FROM tbl_menu WHERE class = 'system' AND method = 'userCfg');
DELETE FROM tbl_menu WHERE class = 'system' AND method = 'userCfg';

-- 확인
SELECT cmb_cfg, sml_cfg, cfg_name, cfg_val FROM tbl_sys_cfg WHERE big_cfg = 'USER';
SELECT COUNT(*) FROM tbl_menu WHERE class = 'system' AND method = 'userCfg';
