-- template_ci3 기본 행. schema.sql 다음에 실행한다. 시스템 관리자(USR000 계정)는 설치 화면에서 만든다.
-- 출처: bizsoft-mig 개발 DB tbl_sys_code(2026-10-28)에서 템플릿 코드만(USR 사용자 구분, SCP 데이터 범위). BRD 게시판 구분은 2026-10-29 추가. bizsoft 전용 API·COM 은 뺐다.
-- 코드에서 고정으로 쓰는 값: USR000 시스템 관리자, USR001 관리자, USR002 파트너 (MY_Controller startUserSession)
-- tbl_sys_cfg 의 created_id 는 설치 전이라 사용자가 없어 0(설치) 으로 둔다.

INSERT INTO `tbl_sys_code` (`cmb_cd`, `big_cd`, `sml_cd`, `cd_name`, `cd_val`, `cd_desc`, `cd_nick`, `cd_srt`, `use_yn`, `created_dt`) VALUES
('BRD000', 'BRD', '000', '게시판 구분', NULL, NULL, NULL, 0, 'N', NOW()),
('BRD001', 'BRD', '001', '공지', NULL, NULL, NULL, 1, 'Y', NOW()),
('BRD002', 'BRD', '002', '문의', NULL, NULL, NULL, 2, 'Y', NOW()),
('COM000', 'COM', '000', '업체 구분', NULL, NULL, NULL, 0, 'N', NOW()),
('COM001', 'COM', '001', '본사', NULL, NULL, NULL, 1, 'Y', NOW()),
('COM002', 'COM', '002', '협력사', NULL, NULL, NULL, 2, 'Y', NOW()),
('SCP000', 'SCP', '000', '데이터 스코프 구분', NULL, NULL, NULL, 0, 'N', NOW()),
('SCP001', 'SCP', '001', '전체 데이터', NULL, NULL, NULL, 1, 'Y', NOW()),
('SCP002', 'SCP', '002', '소속 업체 데이터', NULL, NULL, NULL, 2, 'Y', NOW()),
('SCP003', 'SCP', '003', '소속 그룹 데이터', NULL, NULL, NULL, 3, 'Y', NOW()),
('SCP004', 'SCP', '004', '본인이 생성한 데이터', NULL, NULL, NULL, 4, 'Y', NOW()),
('SCP005', 'SCP', '005', '본인에게 배정된 데이터', NULL, NULL, NULL, 5, 'Y', NOW()),
('SCP006', 'SCP', '006', '본인 데이터만', NULL, NULL, NULL, 6, 'Y', NOW()),
('USR000', 'USR', '000', '사용자 구분', NULL, NULL, NULL, 0, 'N', NOW()),
('USR001', 'USR', '001', '관리자', NULL, NULL, NULL, 1, 'Y', NOW()),
('USR002', 'USR', '002', '파트너', NULL, NULL, NULL, 2, 'Y', NOW());

INSERT INTO `tbl_sys_cfg` (`cmb_cfg`, `big_cfg`, `sml_cfg`, `cfg_name`, `cfg_type`, `cfg_options`, `cfg_val`, `cfg_desc`, `created_dt`, `created_id`) VALUES
('SYS_USER_TOKEN_LIFETIME', 'SYS_USER', 'TOKEN_LIFETIME', '토큰 유효 시간(초)', 'text', NULL, '43200', '로그인 후 이 시간이 지나면 다시 로그인(자동 로그인 쿠키가 있으면 새 토큰). 초 단위, 43200 = 12시간, 0 = 만료 없음, 비우면 .env TOKEN_LIFETIME', NOW(), 0),
('SYS_USER_000', 'SYS_USER', '000', '회원·인증', 'text', NULL, NULL, NULL, NOW(), 0),
('SYS_USER_LOG_KEEP_DAYS', 'SYS_USER', 'LOG_KEEP_DAYS', '로그 보관 기간(일)', 'text', NULL, '', '사용자 로그와 요청 로그에 함께 적용합니다. 기간이 지난 로그는 요청을 처리할 때 1,000건씩 나누어 지웁니다. 비우거나 0이면 지우지 않습니다. 법적 보관 의무가 있으면 그 기간 이상으로 정하세요.', NOW(), 0),
('SYS_USER_PASSWORD_FIND_MODE', 'SYS_USER', 'PASSWORD_FIND_MODE', '비밀번호 찾기 방식', 'dropdown', '|정하지 않음\nshow|비밀번호 표시\nreset|재설정', '', '비밀번호 표시는 일부를 가려 보여 주며 복호화할 수 있는 저장 방식에서만 고를 수 있습니다. 재설정은 메일 설정이 있으면 링크를 메일로 보내고, 없으면 확인 후 재설정 화면을 보여 줍니다(메일 없이는 시스템 관리자 계정을 재설정할 수 없음). 정하지 않으면 비밀번호 표시 또는 문의 안내입니다.', NOW(), 0),
('SYS_USER_SIGNUP_EMAIL_YN', 'SYS_USER', 'SIGNUP_EMAIL_YN', '가입 필드: 이메일 사용', 'dropdown', 'Y|사용\nN|사용 안 함', 'Y', '사용 안 함이면 비밀번호 찾기를 쓸 수 없고, 아이디 찾기는 이름과 전화번호로 확인합니다.', NOW(), 0),
('SYS_USER_SIGNUP_TEL_YN', 'SYS_USER', 'SIGNUP_TEL_YN', '가입 필드: 전화번호 사용', 'dropdown', 'Y|사용\nN|사용 안 함', 'Y', '사용 안 함이면 아이디 찾기를 쓸 수 없습니다.', NOW(), 0);
