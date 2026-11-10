-- 회원 가입 필드와 비밀번호 찾기 방식을 드롭다운으로 바꾼다. 저장된 값은 유지한다.
-- sys-cfg-options, sys-user-category 다음에 실행한다. 다시 실행해도 된다. 전화·이메일 사용 값이 비어 있으면 지금 동작과 같은 Y 로 채운다.

-- 확인
SELECT cmb_cfg, cfg_type, cfg_val FROM tbl_sys_cfg WHERE cmb_cfg IN ('SYS_USER_SIGNUP_EMAIL_YN', 'SYS_USER_SIGNUP_TEL_YN', 'SYS_USER_PASSWORD_FIND_MODE');

UPDATE tbl_sys_cfg SET cfg_type = 'dropdown', cfg_options = 'Y|사용\nN|사용 안 함', cfg_val = IF(cfg_val IS NULL OR cfg_val = '', 'Y', cfg_val)
WHERE cmb_cfg IN ('SYS_USER_SIGNUP_EMAIL_YN', 'SYS_USER_SIGNUP_TEL_YN');

UPDATE tbl_sys_cfg SET cfg_type = 'dropdown', cfg_options = '|정하지 않음\nshow|비밀번호 표시\nreset|재설정', cfg_val = IFNULL(cfg_val, '')
WHERE cmb_cfg = 'SYS_USER_PASSWORD_FIND_MODE';

-- 설명도 선택 상자에 맞게 바꾼다
UPDATE tbl_sys_cfg SET cfg_desc = '사용 안 함이면 비밀번호 찾기를 쓸 수 없고, 아이디 찾기는 이름과 전화번호로 확인합니다.' WHERE cmb_cfg = 'SYS_USER_SIGNUP_EMAIL_YN';
UPDATE tbl_sys_cfg SET cfg_desc = '사용 안 함이면 아이디 찾기를 쓸 수 없습니다.' WHERE cmb_cfg = 'SYS_USER_SIGNUP_TEL_YN';
UPDATE tbl_sys_cfg SET cfg_desc = '비밀번호 표시는 일부를 가려 보여 주며 복호화할 수 있는 저장 방식에서만 고를 수 있습니다. 재설정은 메일 설정이 있으면 링크를 메일로 보내고, 없으면 확인 후 재설정 화면을 보여 줍니다(메일 없이는 시스템 관리자 계정을 재설정할 수 없음). 정하지 않으면 비밀번호 표시 또는 문의 안내입니다.' WHERE cmb_cfg = 'SYS_USER_PASSWORD_FIND_MODE';

-- 확인
SELECT cmb_cfg, cfg_type, cfg_val, cfg_options FROM tbl_sys_cfg WHERE cmb_cfg IN ('SYS_USER_SIGNUP_EMAIL_YN', 'SYS_USER_SIGNUP_TEL_YN', 'SYS_USER_PASSWORD_FIND_MODE');
