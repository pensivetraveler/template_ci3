-- 파트너 소속 업체 컬럼. Model_Partner 가 조회하는데 템플릿 표에 없어 파트너 목록·상세가 500 이었다.
-- 이미 컬럼이 있는 프로젝트(bizsoft)는 바뀌지 않는다. 값은 쉼표로 구분한 company_id 목록이다.
ALTER TABLE `tbl_partner` ADD COLUMN IF NOT EXISTS `company_ids` varchar(60) DEFAULT NULL COMMENT '소속 업체 (쉼표로 구분한 tbl_company.company_id)';
