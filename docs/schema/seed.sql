-- template_ci3 기본 행. schema.sql 다음에 실행한다. 시스템 관리자(USR000 계정)는 설치 화면에서 만든다.
-- 출처: bizsoft-mig 개발 DB tbl_sys_code(2026-10-28)에서 템플릿 코드만(USR 사용자 구분, SCP 데이터 범위). bizsoft 전용 API·COM 은 뺐다.
-- 코드에서 고정으로 쓰는 값: USR000 시스템 관리자, USR001 관리자, USR002 파트너 (MY_Controller startUserSession)
-- tbl_sys_cfg 의 created_id 는 설치 전이라 사용자가 없어 0(설치) 으로 둔다.

INSERT INTO `tbl_sys_code` (`cmb_cd`, `big_cd`, `sml_cd`, `cd_name`, `cd_val`, `cd_desc`, `cd_nick`, `cd_srt`, `use_yn`, `created_dt`) VALUES
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

INSERT INTO `tbl_sys_cfg` (`cmb_cfg`, `big_cfg`, `sml_cfg`, `cfg_name`, `cfg_type`, `cfg_val`, `cfg_desc`, `created_dt`, `created_id`) VALUES
('SYS_AUTH_TOKEN_LIFETIME', 'SYS_AUTH', 'TOKEN_LIFETIME', '토큰 유효 시간(초)', 'text', '43200', '로그인 후 이 시간이 지나면 다시 로그인(자동 로그인 쿠키가 있으면 새 토큰). 초 단위, 43200 = 12시간, 0 = 만료 없음, 비우면 .env TOKEN_LIFETIME', NOW(), 0);
