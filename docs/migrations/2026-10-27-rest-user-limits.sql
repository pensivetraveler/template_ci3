-- REST 요청 횟수 제한 표(rest_limits_table = 'user_limits', dbprefix tbl_)를 RestController::_check_limit 이 쓰는 구조로 맞춘다.
-- RestController 는 uri·api_key·count·hour_started 컬럼을 쓴다 (application/config/extra/rest_config.php 주석의 표준 구조).
-- 예전 구조(id·url·count·hour_started)에서는 limit 이 있는 엔드포인트(로그인 등, 9165d57)가 'Unknown column uri' 로 500 이 된다.
--
-- 실행 전: 1) 아래 확인 쿼리로 지금 구조를 본다  2) 표를 백업한다(행은 카운터라 비워도 됨)
-- 운영: 앱 계정은 ALTER 권한이 없으면 마스터 계정으로, 새 이미지 배포 "전에" 실행한다.

-- 확인
SHOW CREATE TABLE tbl_user_limits;

-- A. 예전 구조(url 컬럼, uri·api_key 없음)일 때
ALTER TABLE tbl_user_limits
    CHANGE COLUMN url uri VARCHAR(255) NOT NULL DEFAULT '',
    MODIFY COLUMN `count` INT NOT NULL DEFAULT 0,
    MODIFY COLUMN hour_started INT UNSIGNED NOT NULL DEFAULT 0,
    ADD COLUMN api_key VARCHAR(64) NOT NULL DEFAULT '' AFTER hour_started,
    ADD INDEX idx_user_limits_uri_key (uri, api_key);

-- B. 표가 없을 때
-- CREATE TABLE tbl_user_limits (
--     id INT UNSIGNED NOT NULL AUTO_INCREMENT,
--     uri VARCHAR(255) NOT NULL DEFAULT '',
--     `count` INT NOT NULL DEFAULT 0,
--     hour_started INT UNSIGNED NOT NULL DEFAULT 0,
--     api_key VARCHAR(64) NOT NULL DEFAULT '',
--     PRIMARY KEY (id),
--     KEY idx_user_limits_uri_key (uri, api_key)
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 확인
SHOW CREATE TABLE tbl_user_limits;
