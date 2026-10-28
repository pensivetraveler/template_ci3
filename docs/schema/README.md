# DB 구조

| 파일 | 내용 |
|---|---|
| `schema.sql` | 표 구조(데이터 없음). 빈 DB에 그대로 실행한다. 설치 화면(`api/Setup` `addTables`, 개발 환경만)에 붙여 넣어도 된다 |
| `seed.sql` | 기본 행. `schema.sql` 다음에 실행한다: 사용자 구분(USR)·데이터 범위(SCP) 코드, 토큰 유효 시간 환경값 |
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
  - 게시판: `article`, `article_attachment`, `article_read`, `board`, `comment`, `reply`
  - 그 밖: `message`, `language_code`, `log_email`, `sys_version`, `sys_version_current`, `uuid_token`
