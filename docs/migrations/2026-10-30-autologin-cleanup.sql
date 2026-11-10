-- tbl_user_autologin 에 쌓인 행 정리 (한 번만 실행).
-- 2026-10-30 부터 로그인할 때(api/Auth::login_post) 이 브라우저의 이전 행·만료 행을 지우고 사용자당 최신 5개만 남긴다.
-- 이 SQL 은 그 전에 쌓인 행을 같은 기준으로 지운다. 실행하지 않아도 로그인이 일어날 때마다 차례로 정리된다.
--
-- 기준: autologin_cookie_lifetime(30일)보다 오래된 행, 사용자별 최신 5개(autologin_max_per_user) 밖의 행.
-- 설정을 바꾼 프로젝트는 아래 INTERVAL 30 DAY 와 rn > 5 를 맞춘다.
-- 지운 행의 브라우저는 자동 로그인이 풀린다(다음 접속 때 로그인 화면). 쓰는 중인 최신 기기는 남는다.
-- MariaDB 10.2+ (ROW_NUMBER)

-- 확인: 사용자별 행 수, 지워질 행 수
SELECT user_id, COUNT(*) AS cnt, MIN(aul_datetime) AS oldest, MAX(aul_datetime) AS newest
FROM tbl_user_autologin GROUP BY user_id ORDER BY cnt DESC;

SELECT COUNT(*) AS expired FROM tbl_user_autologin
WHERE aul_datetime IS NULL OR aul_datetime < NOW() - INTERVAL 30 DAY;

-- 만료 행
DELETE FROM tbl_user_autologin
WHERE aul_datetime IS NULL OR aul_datetime < NOW() - INTERVAL 30 DAY;

-- 사용자별 최신 5개 밖의 행
DELETE t FROM tbl_user_autologin t
JOIN (
  SELECT aul_id FROM (
    SELECT aul_id, ROW_NUMBER() OVER (PARTITION BY user_id ORDER BY aul_datetime DESC, aul_id DESC) AS rn
    FROM tbl_user_autologin
  ) ranked WHERE rn > 5
) old ON old.aul_id = t.aul_id;

-- 확인
SELECT user_id, COUNT(*) AS cnt FROM tbl_user_autologin GROUP BY user_id ORDER BY cnt DESC;
