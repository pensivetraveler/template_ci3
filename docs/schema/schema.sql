-- template_ci3 DB 구조 (데이터 없음). 빈 DB에 그대로 실행하면 된다. dbprefix tbl_, utf8mb4.
-- 출처: bizsoft-mig 개발 DB(MariaDB 10.4.34) 2026-10-28 구조 덤프에서 템플릿 표만 골라 AUTO_INCREMENT 값·bizsoft 전용 컬럼(tbl_partner.company_ids)을 뺐다.
-- tbl_user_session 은 production 의 DB 세션(sess_driver=database), tbl_user_refresh_token 은 app/Common.php(앱 로그인, 아직 쓰는 화면 없음)용. bizsoft 에는 접두어 없는 user_refresh_token 으로 있지만 코드는 dbprefix 가 붙은 이름으로 넣는다.
-- 기본 행은 seed.sql. 시스템 관리자(USR000)는 설치 화면(Setup addSystemUser)에서 만든다.
-- 규칙: DB 구조를 바꾸면 이 파일과 docs/migrations/<날짜>-<주제>.sql 을 같이 고친다 (docs/schema/README.md).
-- 아직 없는 표(모델은 있음, 기준 DB에 없음): article, article_attachment, article_read, board, comment, reply, message, language_code, log_email, sys_version, sys_version_current, uuid_token
-- 문장 안에 세미콜론을 쓰지 않는다 (설치 화면 addTables 가 ; 로 나눠 실행)

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE `tbl_user` (
  `user_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'auto_increment',
  `user_cd` char(6) NOT NULL COMMENT '사용자구분 코드 (tbl_sys_code.big_cd = USR)',
  `user_api_key` char(32) DEFAULT NULL COMMENT 'API용 사용자 식별키',
  `id` varchar(20) NOT NULL COMMENT '아이디',
  `password` varchar(1000) NOT NULL COMMENT '비밀번호 (복호화 가능해야함)',
  `name` varchar(30) NOT NULL COMMENT '이름',
  `birth` date DEFAULT NULL COMMENT '생년월일',
  `gender` char(1) DEFAULT NULL COMMENT '성별',
  `email` varchar(100) DEFAULT NULL COMMENT '이메일',
  `tel` varchar(20) DEFAULT NULL COMMENT '휴대전화',
  `zipcode` char(5) DEFAULT NULL COMMENT '우편번호',
  `addr1` varchar(300) DEFAULT NULL COMMENT '주소1',
  `addr2` varchar(300) DEFAULT NULL COMMENT '주소2',
  `addr3` varchar(300) DEFAULT NULL COMMENT '주소3',
  `withdraw_yn` enum('Y','N') DEFAULT 'N' COMMENT '탈퇴여부',
  `withdraw_dt` date DEFAULT NULL COMMENT '탈퇴일시 (Y-m-d)',
  `memo` text DEFAULT NULL COMMENT '비고',
  `del_yn` enum('Y','N') NOT NULL DEFAULT 'N' COMMENT '삭제여부',
  `created_dt` datetime NOT NULL DEFAULT current_timestamp() COMMENT '생성일시',
  `created_id` bigint(20) unsigned NOT NULL COMMENT '생성자 (tbl_user.user_id)',
  `updated_dt` datetime DEFAULT NULL COMMENT '수정일시',
  `updated_id` bigint(20) unsigned DEFAULT NULL COMMENT '수정자 (tbl_user.user_id)',
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_administrator` (
  `administrator_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'auto_increment',
  `user_id` bigint(20) unsigned NOT NULL COMMENT 'User Key (tbl_user.user_id)',
  PRIMARY KEY (`administrator_id`,`user_id`),
  KEY `FK_tbl_user_TO_tbl_administrator_1` (`user_id`),
  CONSTRAINT `FK_tbl_user_TO_tbl_administrator_1` FOREIGN KEY (`user_id`) REFERENCES `tbl_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_partner` (
  `partner_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'auto_increment',
  `user_id` bigint(20) unsigned NOT NULL COMMENT 'User Key (tbl_user.user_id)',
  PRIMARY KEY (`partner_id`,`user_id`),
  KEY `FK_tbl_user_TO_tbl_partner_1` (`user_id`),
  CONSTRAINT `FK_tbl_user_TO_tbl_partner_1` FOREIGN KEY (`user_id`) REFERENCES `tbl_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_company` (
  `company_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'auto_increment',
  `company_name` varchar(255) NOT NULL COMMENT '회사명',
  `company_cd` char(6) NOT NULL COMMENT '회사 구분',
  `company_code` char(6) DEFAULT NULL COMMENT '회사고유코드',
  `company_bizno` varchar(10) DEFAULT NULL COMMENT '사업자등록번호',
  `company_ceo` varchar(30) DEFAULT NULL COMMENT '대표자명',
  `company_zipcode` char(5) DEFAULT NULL COMMENT '우편번호',
  `company_addr1` varchar(300) DEFAULT NULL COMMENT '주소1',
  `company_addr2` varchar(300) DEFAULT NULL COMMENT '주소2',
  `company_type` varchar(255) DEFAULT NULL COMMENT '업태',
  `company_item` varchar(255) DEFAULT NULL COMMENT '업종',
  `memo` text DEFAULT NULL COMMENT '비고',
  `del_yn` enum('Y','N') NOT NULL DEFAULT 'N' COMMENT '삭제여부',
  `created_dt` datetime NOT NULL DEFAULT current_timestamp() COMMENT '생성일시',
  `created_id` bigint(20) unsigned NOT NULL COMMENT '생성자 (tbl_user.user_id)',
  `updated_dt` datetime DEFAULT NULL COMMENT '수정일시',
  `updated_id` bigint(20) unsigned DEFAULT NULL COMMENT '수정자 (tbl_user.user_id)',
  PRIMARY KEY (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_category` (
  `category_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'auto_increment',
  `parent_id` bigint(20) NOT NULL DEFAULT 0,
  `depth` smallint(6) DEFAULT 1,
  `category_name` varchar(255) NOT NULL COMMENT '카테고리명',
  `category_nick` varchar(255) DEFAULT NULL COMMENT '카테고리 닉네임',
  `category_alias` varchar(255) DEFAULT NULL,
  `category_extra` varchar(255) DEFAULT NULL COMMENT '카테고리 비고',
  `category_icon` bigint(20) unsigned DEFAULT NULL COMMENT '카테고리 아이콘 (tbl_file.file_id)',
  `category_srt` tinyint(4) DEFAULT 0 COMMENT '카테고리 나열순서',
  `use_yn` enum('Y','N') DEFAULT NULL COMMENT '사용 여부',
  PRIMARY KEY (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_file` (
  `file_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'File Key',
  `file_name` varchar(255) NOT NULL COMMENT 'Name of the file that was uploaded, including the filename extension',
  `file_type` varchar(20) NOT NULL COMMENT 'File MIME type identifier',
  `file_link` varchar(3000) DEFAULT NULL COMMENT 'File link',
  `file_path` varchar(255) NOT NULL COMMENT 'Absolute server path to the file',
  `full_path` varchar(255) NOT NULL COMMENT 'Absolute server path, including the file name',
  `raw_name` longtext NOT NULL COMMENT 'File name, without the extension',
  `orig_name` longtext NOT NULL COMMENT 'Original file name. This is only useful if you use the encrypted name option.',
  `client_name` longtext NOT NULL COMMENT 'File name supplied by the client user agent, but possibly sanitized',
  `file_ext` varchar(20) NOT NULL COMMENT 'Filename extension, period included',
  `file_size` float NOT NULL COMMENT 'File size in kilobytes',
  `is_image` tinyint(1) DEFAULT NULL COMMENT 'Whether the file is an image or not. 1 = image. 0 = not.',
  `image_width` smallint(6) DEFAULT 0 COMMENT 'Image width',
  `image_height` smallint(6) DEFAULT 0 COMMENT 'Image height',
  `image_type` varchar(20) DEFAULT NULL COMMENT 'Image type (usually the file name extension without the period)',
  `image_size_str` varchar(255) DEFAULT NULL COMMENT 'A string containing the width and height (useful to put into an image tag)',
  `download_cnt` smallint(6) DEFAULT 0 COMMENT '다운로드 횟수',
  `created_dt` datetime NOT NULL DEFAULT current_timestamp() COMMENT '생성일시',
  `created_id` bigint(20) unsigned NOT NULL COMMENT '생성자 (tbl_user.user_id)',
  `updated_dt` datetime DEFAULT NULL COMMENT '수정일시',
  `updated_id` bigint(20) unsigned DEFAULT NULL COMMENT '수정자 (tbl_user.user_id)',
  PRIMARY KEY (`file_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_menu` (
  `menu_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'auto_increment',
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `code` char(20) NOT NULL,
  `title` varchar(255) NOT NULL,
  `icon` varchar(60) DEFAULT NULL,
  `class` varchar(60) NOT NULL,
  `method` varchar(60) NOT NULL,
  `attr` varchar(3000) DEFAULT NULL,
  `depth` tinyint(4) DEFAULT 0,
  `srt` smallint(6) DEFAULT 0,
  `params` varchar(3000) DEFAULT NULL,
  `auth_params` varchar(255) DEFAULT NULL,
  `is_sub_menu` tinyint(4) DEFAULT 0,
  `is_login` tinyint(4) DEFAULT 0,
  `is_auth` tinyint(4) NOT NULL DEFAULT 0,
  `is_super` tinyint(4) DEFAULT 0,
  `is_use` tinyint(4) DEFAULT 0,
  `base_auth` char(6) DEFAULT NULL COMMENT '메뉴 기본 권한 (CRUD,Export,Import)',
  PRIMARY KEY (`menu_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_menu_auth` (
  `menu_id` bigint(20) unsigned NOT NULL,
  `grade_cd` char(6) NOT NULL COMMENT '권한 등급',
  `is_show` tinyint(4) DEFAULT 0 COMMENT '메뉴 노출 여부 (1:노출,0:미노출)',
  `menu_auth` char(6) DEFAULT NULL COMMENT 'CRUD 적용 권한 (CRUD,Export,Import)',
  `scope_cd` char(6) DEFAULT NULL COMMENT '데이터 범위 권한',
  `created_dt` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`menu_id`,`grade_cd`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_partner_auth` (
  `menu_id` bigint(20) unsigned NOT NULL,
  `partner_id` bigint(20) unsigned NOT NULL COMMENT '파트너 ID (tbl_partner.partner_id)',
  `is_show` tinyint(4) DEFAULT 0 COMMENT '메뉴 노출 여부 (1:노출,0:미노출)',
  `menu_auth` char(6) DEFAULT NULL COMMENT 'CRUD 적용 권한 (CRUD,Export,Import)',
  `scope_cd` char(6) DEFAULT NULL COMMENT '데이터 범위 권한',
  `created_dt` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`menu_id`,`partner_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_sys_cfg` (
  `cmb_cfg` varchar(255) NOT NULL COMMENT 'Sys Cfg Key',
  `big_cfg` varchar(255) NOT NULL COMMENT '환경설정 대구분',
  `sml_cfg` varchar(255) NOT NULL COMMENT '환경설정 소구분',
  `cfg_name` varchar(300) NOT NULL COMMENT '환경설정 명',
  `cfg_val` longtext DEFAULT NULL COMMENT '환경설정 값',
  `cfg_desc` longtext DEFAULT NULL COMMENT '환경설정 설명',
  `cfg_type` varchar(20) NOT NULL DEFAULT 'text' COMMENT '환경설정 타입',
  `created_dt` datetime NOT NULL DEFAULT current_timestamp() COMMENT '생성일시',
  `created_id` bigint(20) unsigned NOT NULL COMMENT '생성자 (tbl_user.user_id)',
  `updated_dt` datetime DEFAULT NULL COMMENT '수정일시',
  `updated_id` bigint(20) unsigned DEFAULT NULL COMMENT '수정자 (tbl_user.user_id)',
  PRIMARY KEY (`cmb_cfg`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_sys_code` (
  `cmb_cd` char(6) NOT NULL COMMENT 'Code Key',
  `big_cd` char(3) NOT NULL COMMENT '코드 대구분',
  `sml_cd` char(3) NOT NULL COMMENT '코드 소구분',
  `cd_name` varchar(30) NOT NULL COMMENT '코드 명',
  `cd_val` varchar(255) DEFAULT NULL COMMENT '코드 값',
  `cd_desc` varchar(255) DEFAULT NULL COMMENT '코드 설명',
  `cd_nick` varchar(255) DEFAULT NULL COMMENT '코드 별명',
  `cd_srt` smallint(5) unsigned DEFAULT 1 COMMENT '코드 순서',
  `use_yn` enum('Y','N') DEFAULT 'Y' COMMENT '사용여부',
  `created_dt` datetime NOT NULL DEFAULT current_timestamp() COMMENT '생성일시',
  `created_id` bigint(20) unsigned DEFAULT NULL COMMENT '생성자 (tbl_user.user_id)',
  `updated_dt` datetime DEFAULT NULL COMMENT '수정일시',
  `updated_id` bigint(20) unsigned DEFAULT NULL COMMENT '수정자 (tbl_user.user_id)',
  PRIMARY KEY (`cmb_cd`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_user_autologin` (
  `aul_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'auto_increment',
  `user_id` bigint(20) unsigned NOT NULL COMMENT 'User Key',
  `aul_key` varchar(255) NOT NULL COMMENT '자동로그인 토큰. 쿠키로 저장됨.',
  `aul_ip` char(45) NOT NULL DEFAULT '' COMMENT '자동로그인 설정한 회원의 IP',
  `aul_datetime` datetime DEFAULT NULL COMMENT '자동로그인 시각',
  PRIMARY KEY (`aul_id`,`user_id`),
  KEY `FK_tbl_user_TO_tbl_user_autologin_1` (`user_id`),
  CONSTRAINT `FK_tbl_user_TO_tbl_user_autologin_1` FOREIGN KEY (`user_id`) REFERENCES `tbl_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_user_token` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'auto_increment',
  `user_id` bigint(20) unsigned NOT NULL COMMENT 'User Key',
  `token` varchar(40) NOT NULL DEFAULT '' COMMENT 'Token',
  `level` tinyint(4) NOT NULL DEFAULT 0 COMMENT 'Level',
  `ignore_limits` tinyint(4) NOT NULL DEFAULT 0 COMMENT 'Ignore Limits',
  `is_private_key` tinyint(4) NOT NULL DEFAULT 0 COMMENT 'IsPrivateKey',
  `ip_addresses` char(45) DEFAULT '' COMMENT 'IpAddresses',
  `created_dt` datetime DEFAULT current_timestamp() COMMENT '생성일시',
  PRIMARY KEY (`id`,`user_id`),
  KEY `FK_tbl_user_TO_tbl_user_token_1` (`user_id`),
  CONSTRAINT `FK_tbl_user_TO_tbl_user_token_1` FOREIGN KEY (`user_id`) REFERENCES `tbl_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_user_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `log_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `class` varchar(60) NOT NULL,
  `method` varchar(60) NOT NULL,
  `type` varchar(20) NOT NULL,
  `subtype` varchar(20) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `message` varchar(255) DEFAULT NULL,
  `params` longtext DEFAULT NULL,
  `datetime` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_user_limits` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'auto_increment',
  `uri` varchar(255) NOT NULL DEFAULT '',
  `count` int(11) NOT NULL DEFAULT 0,
  `hour_started` int(10) unsigned NOT NULL DEFAULT 0,
  `api_key` varchar(64) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `idx_user_limits_uri_key` (`uri`,`api_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_user_access` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'auto_increment',
  `key` varchar(3000) NOT NULL DEFAULT '',
  `all_access` tinyint(4) NOT NULL DEFAULT 0 COMMENT 'all_access',
  `controller` varchar(50) NOT NULL DEFAULT '' COMMENT 'controller',
  `date_created` datetime DEFAULT NULL COMMENT '생성일시',
  `date_modified` timestamp NOT NULL DEFAULT current_timestamp() COMMENT '수정일시',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_rest_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'auto_increment',
  `uri` longtext NOT NULL DEFAULT '' COMMENT '요청 URI',
  `method` varchar(6) NOT NULL COMMENT 'GET,POST,PUT,PATCH,OPTION,DELETE',
  `params` longtext DEFAULT NULL COMMENT 'params (json format)',
  `api_key` varchar(40) NOT NULL,
  `ip_address` char(45) NOT NULL DEFAULT '' COMMENT 'ip_address',
  `time` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'time',
  `rtime` float DEFAULT NULL COMMENT 'rtime',
  `authorized` varchar(1) NOT NULL DEFAULT '' COMMENT 'authorized',
  `response_code` smallint(3) DEFAULT 0 COMMENT 'response_code',
  `kind` enum('api','page','system') NOT NULL DEFAULT 'api',
  `created_dt` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_visitor` (
  `vi_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'auto_increment',
  `vi_visit_key` char(64) DEFAULT NULL COMMENT '방문 중복 방지 키',
  `vi_hit_count` int(10) unsigned NOT NULL DEFAULT 1 COMMENT '동일 방문 반복 호출 수',
  `vi_first_at` datetime DEFAULT NULL COMMENT '최초 방문 시간',
  `vi_last_at` datetime DEFAULT NULL COMMENT '마지막 호출 시간',
  `vi_ip` char(45) NOT NULL DEFAULT '' COMMENT '접속자 IP',
  `vi_date` date NOT NULL COMMENT '접속 날짜',
  `vi_time` time NOT NULL COMMENT '접속 시각',
  `vi_referrer` longtext DEFAULT '' COMMENT '접속 경로',
  `vi_agent` varchar(3000) DEFAULT NULL COMMENT '에이전트',
  `vi_browser` varchar(255) DEFAULT NULL COMMENT '브라우저',
  `vi_os` varchar(255) DEFAULT NULL COMMENT 'OS',
  `vi_device` varchar(255) DEFAULT NULL COMMENT '디바이스',
  `vi_location` varchar(255) DEFAULT NULL COMMENT '접속위치',
  `created_dt` datetime DEFAULT NULL,
  PRIMARY KEY (`vi_id`),
  UNIQUE KEY `uk_tbl_visitor_visit_key` (`vi_visit_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_geoip_cache` (
  `geoip_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ip` varchar(45) NOT NULL COMMENT 'IPv4/IPv6',
  `provider` varchar(30) NOT NULL COMMENT 'ipwhois, freeipapi, ipapi, ip_api',
  `location_json` longtext DEFAULT NULL COMMENT '정규화된 위치 정보 JSON',
  `raw_json` longtext DEFAULT NULL COMMENT 'provider 원본 응답 JSON',
  `country_code` varchar(10) DEFAULT NULL,
  `country_name` varchar(100) DEFAULT NULL,
  `region_name` varchar(100) DEFAULT NULL,
  `city_name` varchar(100) DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `timezone` varchar(100) DEFAULT NULL,
  `hit_count` int(10) unsigned NOT NULL DEFAULT 0,
  `last_hit_at` datetime DEFAULT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`geoip_id`),
  UNIQUE KEY `uk_geoip_cache_ip_provider` (`ip`,`provider`),
  KEY `idx_geoip_cache_ip` (`ip`),
  KEY `idx_geoip_cache_expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_user_session` (
  `id` varchar(128) NOT NULL COMMENT '세션 ID',
  `ip_address` char(45) DEFAULT '',
  `timestamp` int(10) unsigned DEFAULT 0,
  `data` blob DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tbl_user_refresh_token` (
  `refresh_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'refresh token id',
  `user_id` bigint(20) unsigned NOT NULL COMMENT 'user id',
  `token_hash` char(64) NOT NULL COMMENT 'sha256 hashed refresh token',
  `token_family` char(36) DEFAULT NULL COMMENT 'refresh token rotation family uuid',
  `device_id` varchar(100) DEFAULT NULL COMMENT 'client device id',
  `device_name` varchar(100) DEFAULT NULL COMMENT 'device name',
  `platform` varchar(30) DEFAULT NULL COMMENT 'web, ios, android, desktop',
  `user_agent` text DEFAULT NULL COMMENT 'user agent',
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'issued ip address',
  `expires_at` datetime NOT NULL COMMENT 'token expiry datetime',
  `last_used_at` datetime DEFAULT NULL COMMENT 'last used datetime',
  `revoked_at` datetime DEFAULT NULL COMMENT 'revoked datetime',
  `replaced_by` bigint(20) unsigned DEFAULT NULL COMMENT 'new refresh_id after rotation',
  `created_dt` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'created datetime',
  `updated_dt` datetime DEFAULT NULL ON UPDATE current_timestamp() COMMENT 'updated datetime',
  PRIMARY KEY (`refresh_id`),
  UNIQUE KEY `uq_user_refresh_token_hash` (`token_hash`),
  KEY `idx_user_refresh_token_user_id` (`user_id`),
  KEY `idx_user_refresh_token_expires_at` (`expires_at`),
  KEY `idx_user_refresh_token_revoked_at` (`revoked_at`),
  KEY `idx_user_refresh_token_family` (`token_family`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='user refresh token';

SET FOREIGN_KEY_CHECKS = 1;
