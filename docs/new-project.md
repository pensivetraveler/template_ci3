# 새 프로젝트 골격 (template_ci3 기반)

template_ci3 v3 로 새 프로젝트를 시작할 때의 골격 체크리스트다. ai-collab `ai-collab-structure.md` §8 "빌더 경로"에서 이 문서를 가리킨다.
"안녕 빌더 프로젝트" 세션이 이 체크리스트(1~8)까지 하고 프로젝트를 담당 세션에 넘긴다. 엔티티 설계·화면·운영 배포는 담당 세션이 한다.

실제 예: koreadesk(`~/Programming/Projects/koreadesk`, `docs/koreadesk-setup.md`), bizsoft-mig.

## 0. 받을 것

- 프로젝트 이름(저장소·DB·도커 이름에 씀), 원격 저장소 주소, 고객 프로젝트 여부
- ai-collab("Collab 기능 보강")에 `[요청] 새 빌더 프로젝트 등록`을 보낸다: 경로, 원격, 고객 프로젝트 여부, 기반 커밋(아래 1)

## 1. 저장소 만들기

템플릿을 복제해 원격만 바꾼다. 템플릿 이력을 그대로 가져가야 나중에 merge 로 템플릿 변경을 받을 수 있다.

감사를 통과한 가장 최근 태그(`v3.N`, [template-audit.md](template-audit.md))에서 시작하는 것이 기본이다. 태그 뒤 코드 변경이 필요하면 그 변경은 감사 전이라는 것을 `docs/<프로젝트>-setup.md` 에 적는다(설계 공격에서 그 부분을 줄이지 않는다). 아직 태그가 없으면 v3 최신에서 시작한다.

```bash
git clone -b v3 https://github.com/pensivetraveler/template_ci3.git <프로젝트>
cd <프로젝트>
git remote rename origin upstream
git remote set-url --push upstream DISABLED      # 템플릿으로 잘못 push 하지 않게
git remote add origin <새 원격 주소>
git push -u origin v3                             # 기본 브랜치 이름은 프로젝트가 정한다(main 등)
```

### 기반 커밋 적기

`docs/<프로젝트>-setup.md`(프로젝트 메모) 맨 위에 적는다. 템플릿 변경을 받을 때마다 고친다.

```markdown
- 템플릿: template_ci3 v3 `<커밋>` (감사 단계 `v3.N` 또는 "감사 전", YYYY-MM-DD 기준). 받는 법: `git fetch upstream && git merge upstream/v3`
```

커밋은 `git rev-parse --short upstream/v3` 로 확인한다.

## 2. CLAUDE.md

새 저장소 루트의 `CLAUDE.md` 에 다음 줄을 넣는다(없으면 만든다).

```markdown
## 템플릿에서 온 코드
- 이 저장소는 template_ci3 v3 기반이다(기반 커밋: docs/<프로젝트>-setup.md).
- 템플릿에서 온 코드(application/core·libraries·helpers·traits·hooks, views/builder, public/assets/builder, config/extra/builder 등)에서
  템플릿에도 고쳐야 할 문제를 찾으면, 여기서 우회하더라도 "안녕 빌더 프로젝트" 세션에 `[요청 #<세션 약칭>-<번호>]` 로 알린다.
  템플릿에 반영되면 merge 로 받고 우회를 지운다.
```

## 3. 의존성

```bash
composer install --no-dev --no-scripts
npm ci            # 화면 스크립트 lint 용 (npm run lint)
```

## 4. 로컬 서버 (docker)

템플릿의 `docker/docker-compose.yml` 은 이름 `template-ci3`, 포트 8080·3307 을 쓴다. **그대로 띄우면 템플릿 검증 서버와 컨테이너가 겹친다**(같은 이름이면 다른 저장소의 컨테이너를 바꿔 버린다). 프로젝트마다 다음을 바꾼다.

| 파일 | 바꿀 것 |
|---|---|
| `docker/docker-compose.yml` | `name:` 을 프로젝트 이름으로, web 포트(8080→ 다른 번호), db 포트(3307→ 다른 번호), DB 이름·계정 |
| `docker/init-env.sh` | `APP_NAME`, `APP_URL`(바꾼 포트), `DB_DATABASE`·`DB_USERNAME`·`DB_PASSWORD` |
| `scripts/test.sh` | `WEB=`·`DB=` 컨테이너 이름(`<name>-web-1`, `<name>-db-1`) |
| `docker/php/Dockerfile` | 운영 PHP 와 다르면 버전 (템플릿은 PHP 7.4) |
| `docker/docker-compose.yml` db 이미지 | 운영 DB 와 같은 계열·버전 (템플릿은 MariaDB 10.11) |

koreadesk 는 compose 파일을 따로(`docker/koreadesk-compose.yml`, 8081·3308) 두었다. 어느 쪽이든 템플릿 서버와 이름·포트가 겹치지 않으면 된다.

```bash
docker/init-env.sh                                # docker/.env.docker 생성 (키 새로 만듦, 커밋하지 않음)
docker compose -f docker/docker-compose.yml up -d --build
```

## 5. 환경 설정 (.env)

- `.env.example` 을 복사해 `.env.development` / `.env.production` 을 만든다. `.env.*` 는 커밋하지 않는다. 로컬 docker 는 `docker/.env.docker` 가 `.env.development` 자리에 겹쳐진다.
- 필수: `ENCRYPTION_KEY`(`hex:` + 64자리), `JWT_KEY`, `DB_*`, `APP_URL`, `IS_ADMIN=1`(관리자 화면을 쓰면. 없으면 /admin 이 404)
- 거의 항상 켜는 것: `CACHING_MENU=true` — 시스템 관리자가 아닌 계정(USR001·USR002)을 쓰려면 필요하다. false 면 메뉴 권한이 없어 무한 redirect 가 난다
- 프로젝트가 정할 것: `PASSWORD_DECRYPTABLE`, `MAIL_*`, `AWS_*`(S3), `WEB_AUTH_API`·`SIGNUP_USER_CD`(웹 로그인·회원가입이 있을 때만), `CSP_REPORT_ONLY`/`CSP_ENFORCE`
- 각 키 설명은 `.env.example` 주석과 README "환경 설정"

## 6. DB와 설치 화면

1. docker 는 빈 볼륨으로 처음 뜰 때 `docs/schema/schema.sql` → `seed.sql` 을 실행한다. 다른 서버는 직접 같은 순서로 실행한다.
2. `http://localhost:<포트>/admin/auth` 를 열면 설치 화면이 나온다. 시스템 관리자(USR000)를 만든다.
3. 테스트 계정은 `docker/.env.docker` 의 `TEST_ADMIN_ID`·`TEST_ADMIN_PASSWORD` 에 적어 둔다(커밋하지 않음).
4. 프로젝트 전용 표·컬럼은 `docs/migrations/<YYYY-MM-DD>-<프로젝트>-<주제>.sql` 로 만든다. 템플릿의 `schema.sql` 은 고치지 않는다(템플릿 merge 때 충돌). 규칙은 `docs/schema/README.md`

## 7. 첫 엔티티

README "엔티티 추가하기"의 표(모델·관리자 컨트롤러·API 컨트롤러·route·form·list·nav·언어)를 따른다. 설정 상세는 `docs/builder/`.

- 엔티티 설정은 나눠 쓰기(`config/extra/admin/admin_*_config.php`)와 엔티티 한 파일(`config/extra/admin/resources/<entity>.php`) 둘 다 된다. 새 프로젝트 엔티티는 한 파일이 merge 충돌이 적다. `docs/builder/resources.md`
- 쓰지 않는 템플릿 메뉴(게시판·업체·Projects 등)는 `admin_nav_config.php` 에서 빼고, 메뉴 관리에서 초기화한다(초기화하면 메뉴 권한 행이 지워지므로 권한을 다시 준다)
- 로그인 뒤 첫 화면은 `admin_base_config` 의 `loggedInRedirect`

## 8. 확인하고 넘기기

```bash
npm run lint
scripts/test.sh all          # 시험 DB 를 새로 만들어 단위·통합 시험 (개발·운영 모드)
```

- 설치 화면 → 로그인 → 첫 엔티티 목록·등록·수정 화면이 브라우저에서 열리는지 본다
- 첫 커밋(4~7 의 변경)을 push 하고, 기반 커밋과 담당 세션 이름을 ai-collab 에 알린다. 여기까지가 골격이다

## 템플릿 변경 받기 (core 동기화)

```bash
git fetch upstream
git merge upstream/v3        # 충돌은 프로젝트 쪽 변경을 살려 푼다
scripts/test.sh all
```

- merge 뒤 `docs/<프로젝트>-setup.md` 의 기반 커밋을 고친다. merge 커밋 메시지에 템플릿 커밋 범위와 요약을 적는다(예: `Merge upstream/v3: 템플릿 5169d41 (WEB 업로드 오류 500 수정)`)
- 템플릿 이력이 없는 저장소(복제하지 않고 파일만 가져온 경우)는 `git diff <이전 기반>..<새 기반>` 을 템플릿에서 만들어 적용하고, 커밋 메시지를 `core: <이전>..<새>(요약)` 로 적는다(bizsoft 방식)
- 서버별로 갈라진 파일(서버에만 있는 코드가 든 `MY_Hooks.php` 등)은 merge 로 덮지 말고 차이만 손으로 옮긴다

## 배포할 때 알아 둘 것

- `docs/`·`docker/`·`tests/` 는 서버에 올리지 않는다(`docs/` 의 SQL 이 웹으로 보일 수 있다)
- 쓰기 권한: `application/cache/`, `application/logs/`, `application/uploads/`, `public/temps/`
- nginx 면 `application/` 차단을 서버 설정으로 한다(Apache 는 `.htaccess` 가 막음)
- 운영은 `CI_ENV=production` 을 서버 설정으로 준다(저장소의 `.htaccess` 는 development)

## 파생 프로젝트 (core 동기화 기준점)

템플릿 변경을 보낼 때, 감사 계기(파생 프로젝트 운영 배포 전, [template-audit.md](template-audit.md) 2)를 판단할 때 본다. 프로젝트가 템플릿 변경을 받으면 담당 세션이 알려 주고 이 표를 고친다. 전체 장부는 ai-collab `docs/projects.md`.

| 프로젝트 | 저장소 | 담당 세션 | 받는 방식 | 기반 커밋 (받은 날) |
|---|---|---|---|---|
| koreadesk (외주개발) | `~/Programming/Projects/koreadesk` | "koreadesk 외주 개발" | upstream merge | `05c7a0d` (2026-11-10, koreadesk `0dd248d`) |
| bizsoft | — | "프레임 관리 다운로드 버튼" 등 | diff 적용(`core: <이전>..<새>`) | 확인 필요 |
