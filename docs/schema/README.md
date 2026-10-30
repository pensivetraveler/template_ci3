# DB 구조

| 파일 | 내용 |
|---|---|
| `schema.sql` | 표 구조(데이터 없음). 빈 DB에 그대로 실행한다. 설치 화면(`api/Setup` `addTables`, 개발 환경만)에 붙여 넣어도 된다 |
| `seed.sql` | 기본 행. `schema.sql` 다음에 실행한다: 사용자 구분(USR)·데이터 범위(SCP)·게시판 구분(BRD) 코드, 토큰 유효 시간 환경값 |
| `../migrations/<YYYY-MM-DD>-<주제>.sql` | 이미 운영 중인 DB를 바꾸는 SQL. 실행 전 확인 쿼리 → 변경 → 확인 쿼리 순서 |

## 규칙

- DB 구조를 바꾸면 **`schema.sql`과 마이그레이션 파일을 같은 커밋에서** 고친다. 새 프로젝트는 `schema.sql`, 운영 중인 프로젝트는 마이그레이션으로 같은 구조가 되어야 한다.
- 템플릿 코드가 고정으로 쓰는 기본 행(코드·환경값)을 추가하면 `seed.sql`과 마이그레이션 파일을 같이 고친다.
- 파생 프로젝트(bizsoft 등)가 더한 표·컬럼은 넣지 않는다.
- 설치 화면 파서(`libraries/Sql_parser.php`) 때문에 `CREATE TABLE \`이름\` (` 형식을 쓰고(`IF NOT EXISTS` 없이), 문장 안에 세미콜론을 쓰지 않는다. 고친 뒤에는 빈 DB에서 직접 실행과 파서 두 가지로 확인한다.
- 모델(`$table`·`primaryKeyList`·`notNullList`)과 맞는지 함께 본다. 모델이 DB보다 엄격한 것(모델 필수, DB NULL 허용)은 괜찮고, 반대(DB NOT NULL 기본값 없음, 모델 목록에 없음)는 INSERT 오류가 나므로 맞춘다.

## 출처와 빠진 표 (2026-10-28)

- 기준: bizsoft-mig 개발 DB(MariaDB 10.4.34, 운영 RDS 10.11)의 구조 덤프에서 템플릿 표만 골랐다. `AUTO_INCREMENT` 값과 bizsoft 전용 컬럼(`tbl_partner.company_ids`)은 뺐다.
- `tbl_user_refresh_token`: bizsoft에는 접두어 없는 `user_refresh_token`으로 있지만, `controllers/app/Common.php`는 `$this->db->insert('user_refresh_token')`이라 dbprefix가 붙은 `tbl_user_refresh_token`에 넣는다. 그래서 템플릿은 접두어 있는 이름으로 둔다.
- 아직 없는 표: 모델은 있지만 기준 DB에 없어 구조를 모른다. 기능을 만들 때 여기에 추가한다.
  - 게시판: `reply`(템플릿 코드가 쓰지 않음, 답변 여부는 관리자 댓글로 계산), `article_prefer`(좋아요·싫어요, 모델 없음 — cwspecial 에 있음)
  - 그 밖: `message`, `language_code`, `log_email`, `sys_version`, `sys_version_current`, `uuid_token`
- 게시판 표(2026-10-29): playtalktalk 의 article·article_attachment·article_read 구조에서 프로젝트 전용 컬럼(program_id·stage_id)을 빼고, 템플릿 코드가 쓰는 `open_yn`(공개 여부)·`article_read.read_dt`를 더했다. board·comment 는 SQL 이 없어 모델과 API(`Comments` depth 0 댓글·1 답글)에 맞춰 설계

## 값 형식

- `tbl_file.file_link`(2026-10-30~): 전체 주소가 아니라 저장소 키(예: `public/uploads/articles/2026/a.png`)를 저장한다. API·화면에는 `Model_File`이 읽을 때 지금 서버 기준 주소(로컬 `base_url`, S3 CDN)로 바꿔 준다(`get_file_url`). 예전에 저장한 전체 주소(`http(s)://…`)는 그대로 쓰이고, `../migrations/2026-10-30-file-link-relative.sql`로 로컬 파일 행을 키로 바꿀 수 있다.
- `tbl_file.full_path`: 아직 서버 절대 경로(로컬 `/var/www/html/public/…`) 또는 `s3://버킷/키`. 배포 경로가 바뀌면 로컬 파일 다운로드·삭제가 실패할 수 있다(TODO).
