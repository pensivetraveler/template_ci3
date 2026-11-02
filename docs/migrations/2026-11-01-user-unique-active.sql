-- tbl_user 의 로그인 아이디(id)·이메일(email)을 DB 에서도 중복되지 않게 한다.
-- 코드 검사(is_unique)만으로는 동시 요청이나 코드를 거치지 않는 저장을 막지 못한다.
--
-- 삭제 표시(del_yn='Y')된 행은 코드와 같이 중복으로 보지 않으므로 삭제한 회원의 아이디를 다시 쓸 수 있다.
-- 일반 UNIQUE 대신, 삭제되지 않은 행에서만 값을 갖는 생성 컬럼에 UNIQUE 를 건다 (NULL 은 중복으로 보지 않음).
-- 빈 문자열 이메일은 값이 없는 것으로 본다 (setData 가 NULL 을 '' 로 저장).
-- MariaDB 10.2.3+ / MySQL 5.7+
--
-- 실행 전: 아래 확인 쿼리 두 개가 빈 결과여야 한다. 행이 나오면 ALTER 가 실패하므로 그 계정을 먼저 정리한다.
-- 운영: 앱 계정에 ALTER 권한이 없으면 마스터 계정으로 실행한다.
--
-- 실행 순서: 코드를 먼저 올린다. 5abce8b 이전 코드는 응답에서 active_id·active_email 을 숨기지 못한다.
-- 탈퇴(withdraw_yn='Y')는 삭제가 아니므로 아이디·이메일을 계속 차지한다 (is_unique 와 같은 기준).
-- 삭제 표시를 되돌릴 때 같은 값을 쓰는 행이 있으면 UPDATE 가 1062 로 실패한다.
--
-- 프로젝트 확인 (실행 전):
--  - 이메일 중복을 허용하는 프로젝트(form 설정에서 email 의 is_unique 를 뺀 경우)는 active_email·uk_user_active_email 두 줄을 빼고 실행한다.
--    그대로 실행하면 그 화면의 중복 이메일 저장이 409 오류가 된다.
--  - id·email 길이와 정렬 규칙이 아래 VARCHAR·표 기본값과 같은지 SHOW FULL COLUMNS FROM tbl_user 로 본다.
--    utf8mb4_general_ci 는 대소문자와 뒤 공백을 구분하지 않으므로 'A@x.com' 과 'a@x.com' 은 같은 값이다 (아래 확인 쿼리도 같은 기준).
--
-- 되돌리기: ALTER TABLE tbl_user DROP KEY uk_user_active_id, DROP KEY uk_user_active_email, DROP COLUMN active_id, DROP COLUMN active_email;

-- 확인: 삭제되지 않은 행의 중복
SELECT id, COUNT(*) AS cnt, GROUP_CONCAT(user_id) AS user_ids
FROM tbl_user WHERE del_yn = 'N' GROUP BY id HAVING cnt > 1;

SELECT email, COUNT(*) AS cnt, GROUP_CONCAT(user_id) AS user_ids
FROM tbl_user WHERE del_yn = 'N' AND email IS NOT NULL AND email <> '' GROUP BY email HAVING cnt > 1;

ALTER TABLE tbl_user
    ADD COLUMN active_id VARCHAR(20) GENERATED ALWAYS AS (IF(del_yn = 'N', id, NULL)) VIRTUAL,
    ADD COLUMN active_email VARCHAR(100) GENERATED ALWAYS AS (IF(del_yn = 'N' AND email <> '', email, NULL)) VIRTUAL,
    ADD UNIQUE KEY uk_user_active_id (active_id),
    ADD UNIQUE KEY uk_user_active_email (active_email);
