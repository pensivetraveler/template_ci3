-- 환경값 유형에 드롭다운(cfg_type = dropdown)을 추가하면서 선택지 열을 넣는다. 코드를 반영하기 전에 실행한다.
-- 기존 행은 바뀌지 않는다. 다시 실행해도 된다.

-- 확인
SHOW COLUMNS FROM tbl_sys_cfg LIKE 'cfg_options';

ALTER TABLE tbl_sys_cfg ADD COLUMN IF NOT EXISTS `cfg_options` text DEFAULT NULL COMMENT '드롭다운 선택지 (한 줄에 하나, 값|표시명)' AFTER `cfg_type`;

-- 확인
SHOW COLUMNS FROM tbl_sys_cfg LIKE 'cfg_options';
