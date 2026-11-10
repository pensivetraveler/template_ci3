-- 파트너 소속 업체 컬럼을 255자로 늘린다. 60자에서는 넘는 값이 조용히 잘려 업체 범위가 틀어질 수 있었다.
-- 길이만 늘리므로 기존 값은 바뀌지 않는다. 이미 잘린 값은 복구되지 않으니 먼저 아래 건수를 확인한다.
-- 2026-11-01-partner-company-ids.sql 다음에 실행한다.

-- 확인: 60자에 닿은 행 (이미 잘렸을 수 있음)
SELECT COUNT(*) AS at_limit FROM tbl_partner WHERE CHAR_LENGTH(company_ids) >= 60;

ALTER TABLE `tbl_partner` MODIFY `company_ids` varchar(255) DEFAULT NULL COMMENT '소속 업체 (쉼표로 구분한 tbl_company.company_id)';
