-- 환경값 설정 화면을 카테고리(big_cfg) 탭으로 바꾸면서, 지금 있는 big_cfg 를 카테고리 행(소구분 000)으로 등록한다.
-- 시스템 코드의 소구분 000 과 같은 방식이다. 행이 없어도 화면은 big_cfg 값을 이름으로 쓴 탭으로 보여 준다.
-- USER 는 2026-11-01-user-cfg-to-sys-cfg.sql 에서 넣는다. 다시 실행해도 INSERT IGNORE 라 이미 있는 행은 바뀌지 않는다.
-- 코드가 먼저 올라간 뒤 실행한다. 옛 코드의 화면에서는 이 행이 값 입력칸으로 보인다.
-- created_id 는 시스템 관리자(USR000)의 user_id. 시스템 관리자가 없으면 행이 들어가지 않는다(마지막 확인 쿼리로 본다).

-- 확인
SELECT big_cfg, COUNT(*) FROM tbl_sys_cfg GROUP BY big_cfg;

INSERT IGNORE INTO tbl_sys_cfg (cmb_cfg, big_cfg, sml_cfg, cfg_name, cfg_type, created_dt, created_id)
SELECT CONCAT(c.big_cfg, '_000'), c.big_cfg, '000',
    CASE c.big_cfg WHEN 'SYS_AUTH' THEN '인증' WHEN 'SYS_CODE' THEN '시스템 코드' WHEN 'SYS_P2P' THEN 'P2P' ELSE c.big_cfg END,
    'text', NOW(), u.user_id
FROM (SELECT DISTINCT big_cfg FROM tbl_sys_cfg WHERE big_cfg <> 'USER') c
JOIN (SELECT user_id FROM tbl_user WHERE user_cd = 'USR000' ORDER BY user_id LIMIT 1) u;

-- 확인
SELECT cmb_cfg, big_cfg, sml_cfg, cfg_name FROM tbl_sys_cfg WHERE sml_cfg = '000';
