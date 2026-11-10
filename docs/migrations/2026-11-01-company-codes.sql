-- 업체 구분 코드(COM). 업체 화면의 company_cd 선택지다. 이미 COM 코드를 쓰는 프로젝트는 건드리지 않는다.
INSERT IGNORE INTO `tbl_sys_code` (`cmb_cd`, `big_cd`, `sml_cd`, `cd_name`, `cd_val`, `cd_desc`, `cd_nick`, `cd_srt`, `use_yn`, `created_dt`) VALUES
('COM000', 'COM', '000', '업체 구분', NULL, NULL, NULL, 0, 'N', NOW()),
('COM001', 'COM', '001', '본사', NULL, NULL, NULL, 1, 'Y', NOW()),
('COM002', 'COM', '002', '협력사', NULL, NULL, NULL, 2, 'Y', NOW());
