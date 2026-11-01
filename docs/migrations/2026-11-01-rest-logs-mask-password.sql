-- 요청 로그(tbl_rest_logs)에 평문으로 저장된 비밀번호를 지운다. 코드를 반영한 뒤 한 번 실행한다.
-- 2026-11-01 전까지 비밀번호를 받는 API(관리자·파트너 등록 등)의 요청 값이 그대로 저장됐다. 로그인 API 는 log=false 라 남지 않는다.
-- 대상 행의 params 만 비우고 행은 남긴다. 백업에 있는 사본은 지워지지 않으니 건수와 백업 접근 범위를 보고 비밀번호 재설정을 판단한다.
-- 대상은 비밀번호 키가 있는 행이다 (password_find_mode 같은 다른 키는 걸리지 않음). 프로젝트에 다른 비밀번호 키가 있으면 조건에 더한다.
-- 값은 조회하지 않는다. 다시 실행해도 가려진 행(***)은 건드리지 않는다.

-- 확인: 대상 건수 (값을 보지 않도록 건수만)
SELECT uri, method, COUNT(*) AS cnt, MIN(created_dt) AS oldest, MAX(created_dt) AS newest
FROM tbl_rest_logs
WHERE (params LIKE '%"password"%' OR params LIKE '%"password_confirm"%') AND params NOT LIKE '%\\*\\*\\*%'
GROUP BY uri, method;

UPDATE tbl_rest_logs SET params = NULL
WHERE (params LIKE '%"password"%' OR params LIKE '%"password_confirm"%') AND params NOT LIKE '%\\*\\*\\*%';
