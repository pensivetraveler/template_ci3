-- (이 메뉴는 2026-11-01-user-cfg-to-sys-cfg.sql 에서 없앴다. 새로 실행하지 않는다.)
-- 시스템 > 회원 설정(system/userCfg) 메뉴를 넣는다. 이미 저장된 메뉴(tbl_menu)가 있는 프로젝트만 실행한다.
-- 환경값 설정(sysCfg) 메뉴를 그대로 본떠 시스템 관리자 전용으로 만들고, 다시 실행해도 중복되지 않는다.
-- 메뉴 캐시를 쓰면 실행 뒤 application/cache/menu_done 을 지운다.
-- 설정값(tbl_sys_cfg big_cfg = USER)은 화면에서 저장할 때 생긴다. 값이 없으면 지금 동작 그대로다.

INSERT INTO tbl_menu (parent_id, code, title, icon, class, method, attr, depth, srt, params, auth_params, is_sub_menu, is_login, is_auth, is_super, is_use, base_auth)
SELECT parent_id, code, 'UserCfg Management', icon, class, 'userCfg', attr, depth, srt, params, auth_params, is_sub_menu, is_login, is_auth, is_super, is_use, base_auth
FROM tbl_menu
WHERE class = 'system' AND method = 'sysCfg'
  AND NOT EXISTS (SELECT 1 FROM (SELECT menu_id FROM tbl_menu WHERE class = 'system' AND method = 'userCfg') t);
