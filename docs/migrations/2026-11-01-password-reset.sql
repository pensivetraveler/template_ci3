-- 비밀번호 재설정 토큰 표와 메일 발송 기록 표를 만든다. 비밀번호 찾기 방식을 재설정으로 쓸 때 필요하다.
-- tbl_password_reset 이 없으면 재설정은 '관리자에게 문의'로 응답한다. tbl_log_email 이 없으면 발송 기록만 남기지 않는다.
-- 토큰 원문은 저장하지 않는다 (SHA-256). 다시 실행해도 문제없다.

CREATE TABLE IF NOT EXISTS `tbl_password_reset` (
  `reset_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'auto_increment',
  `user_id` bigint(20) unsigned NOT NULL COMMENT 'User Key',
  `token_hash` char(64) NOT NULL COMMENT '재설정 토큰의 SHA-256',
  `expires_dt` datetime NOT NULL COMMENT '만료 일시',
  `request_ip` char(45) NOT NULL DEFAULT '' COMMENT '요청 IP',
  `created_dt` datetime NOT NULL DEFAULT current_timestamp() COMMENT '생성일시',
  PRIMARY KEY (`reset_id`),
  UNIQUE KEY `uk_password_reset_token` (`token_hash`),
  KEY `idx_password_reset_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `tbl_log_email` (
  `log_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'auto_increment',
  `email_type` varchar(100) NOT NULL COMMENT '메일 종류 (뷰 이름)',
  `doc_id` bigint(20) unsigned NOT NULL DEFAULT 0 COMMENT '관련 행 Key',
  `email_address` varchar(100) NOT NULL COMMENT '받는 주소',
  `success_yn` enum('Y','N') NOT NULL COMMENT '성공 여부',
  `debug_message` longtext DEFAULT NULL COMMENT '실패 내용',
  `created_dt` datetime NOT NULL DEFAULT current_timestamp() COMMENT '생성일시',
  PRIMARY KEY (`log_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
