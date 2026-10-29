-- 게시판 표 5개(board·article·article_attachment·article_read·comment)와 게시판 구분 코드(BRD)를 추가한다.
-- 템플릿 docs/schema/schema.sql 과 같은 구조. 이미 있는 표는 건너뛴다(IF NOT EXISTS), 같은 코드가 있으면 건너뛴다(INSERT IGNORE).
-- 예전 프로젝트(playtalktalk 등)에서 같은 이름의 표를 쓰던 DB 는 구조가 다를 수 있으니, 먼저 확인 쿼리로 본다.

-- 확인
SHOW TABLES LIKE 'tbl_board';
SHOW TABLES LIKE 'tbl_article%';
SHOW TABLES LIKE 'tbl_comment';
SELECT * FROM tbl_sys_code WHERE big_cd = 'BRD';

CREATE TABLE IF NOT EXISTS `tbl_board` (
  `board_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'auto_increment',
  `board_name` varchar(100) NOT NULL COMMENT '게시판명',
  `board_cd` char(6) NOT NULL COMMENT '게시판 구분 (tbl_sys_code.big_cd = BRD)',
  `attach_max` smallint(5) unsigned DEFAULT NULL COMMENT '첨부 최대 개수 (비우면 제한 없음)',
  `created_dt` datetime NOT NULL DEFAULT current_timestamp() COMMENT '생성일시',
  `created_id` bigint(20) unsigned NOT NULL COMMENT '생성자 (tbl_user.user_id)',
  `updated_dt` datetime DEFAULT NULL COMMENT '수정일시',
  `updated_id` bigint(20) unsigned DEFAULT NULL COMMENT '수정자 (tbl_user.user_id)',
  PRIMARY KEY (`board_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `tbl_article` (
  `article_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'auto_increment',
  `board_id` bigint(20) unsigned NOT NULL COMMENT '게시판 (tbl_board.board_id)',
  `parent_id` bigint(20) unsigned NOT NULL DEFAULT 0 COMMENT '원글 (tbl_article.article_id, 0 이면 원글)',
  `subject` varchar(300) NOT NULL COMMENT '제목',
  `content` longtext DEFAULT NULL COMMENT '내용',
  `thumbnail` bigint(20) unsigned DEFAULT NULL COMMENT '썸네일 (tbl_file.file_id)',
  `link` varchar(255) DEFAULT NULL COMMENT '컨텐트 URL',
  `view_count` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '조회수',
  `open_yn` enum('Y','N') NOT NULL DEFAULT 'Y' COMMENT '공개 여부',
  `del_yn` enum('Y','N') NOT NULL DEFAULT 'N' COMMENT '삭제여부',
  `created_dt` datetime NOT NULL DEFAULT current_timestamp() COMMENT '생성일시',
  `created_id` bigint(20) unsigned NOT NULL COMMENT '생성자 (tbl_user.user_id)',
  `updated_dt` datetime DEFAULT NULL COMMENT '수정일시',
  `updated_id` bigint(20) unsigned DEFAULT NULL COMMENT '수정자 (tbl_user.user_id)',
  PRIMARY KEY (`article_id`),
  KEY `idx_article_board` (`board_id`,`del_yn`,`created_dt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `tbl_article_attachment` (
  `article_id` bigint(20) unsigned NOT NULL COMMENT '게시글 (tbl_article.article_id)',
  `file_id` bigint(20) unsigned NOT NULL COMMENT '파일 (tbl_file.file_id)',
  `attach_cd` char(6) DEFAULT NULL COMMENT '첨부 구분',
  `sort_order` smallint(6) DEFAULT NULL COMMENT '순서',
  PRIMARY KEY (`article_id`,`file_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `tbl_article_read` (
  `article_id` bigint(20) unsigned NOT NULL COMMENT '게시글 (tbl_article.article_id)',
  `user_id` bigint(20) unsigned NOT NULL COMMENT '읽은 사용자 (tbl_user.user_id)',
  `read_dt` datetime NOT NULL DEFAULT current_timestamp() COMMENT '마지막으로 읽은 시각',
  PRIMARY KEY (`article_id`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `tbl_comment` (
  `comment_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'auto_increment',
  `article_id` bigint(20) unsigned NOT NULL COMMENT '게시글 (tbl_article.article_id)',
  `parent_id` bigint(20) unsigned NOT NULL DEFAULT 0 COMMENT '부모 댓글 (tbl_comment.comment_id, 0 이면 최상위)',
  `depth` tinyint(3) unsigned NOT NULL DEFAULT 0 COMMENT '깊이 (0: 댓글, 1: 답글)',
  `content` text NOT NULL COMMENT '내용',
  `del_yn` enum('Y','N') NOT NULL DEFAULT 'N' COMMENT '삭제여부',
  `created_dt` datetime NOT NULL DEFAULT current_timestamp() COMMENT '생성일시',
  `created_id` bigint(20) unsigned NOT NULL COMMENT '생성자 (tbl_user.user_id)',
  `updated_dt` datetime DEFAULT NULL COMMENT '수정일시',
  `updated_id` bigint(20) unsigned DEFAULT NULL COMMENT '수정자 (tbl_user.user_id)',
  PRIMARY KEY (`comment_id`),
  KEY `idx_comment_article` (`article_id`,`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO `tbl_sys_code` (`cmb_cd`, `big_cd`, `sml_cd`, `cd_name`, `cd_srt`, `use_yn`, `created_dt`) VALUES
('BRD000', 'BRD', '000', '게시판 구분', 0, 'N', NOW()),
('BRD001', 'BRD', '001', '공지', 1, 'Y', NOW()),
('BRD002', 'BRD', '002', '문의', 2, 'Y', NOW());

-- 확인
SHOW TABLES LIKE 'tbl_article%';
SELECT cmb_cd, cd_name, use_yn FROM tbl_sys_code WHERE big_cd = 'BRD';
