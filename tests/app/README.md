# 프로젝트 시험

CodeIgniter 본체 시험(`tests/phpunit.xml`)과 별개로, 이 프로젝트 코드를 검사한다.

## 처음 한 번

```bash
COMPOSER_VENDOR_DIR=.dev-vendor composer install --no-scripts
```

시험 도구는 `.dev-vendor`에 따로 둔다. `vendor`는 일부 파일이 git 에 추적되어 있어 그냥 `composer install` 을 하면 추적 파일이 바뀐다.

## 실행

로컬 Docker(`docker/`)가 떠 있어야 한다.

```bash
scripts/test.sh                                   # 개발 모드와 운영 모드 둘 다
scripts/test.sh development                       # 개발 모드만
scripts/test.sh production --testsuite integration
scripts/test.sh development --filter testCompanyCrud
```

## 하는 일

- `template_test` DB 를 `docs/schema/schema.sql`, `seed.sql` 로 새로 만들고 끝나면 지운다. 개발 DB 와 `.env.docker` 는 건드리지 않는다.
- 컨테이너 안 `127.0.0.1:8081` 에 시험 서버(`tests/app/router.php`)를 띄운다. 메일 시험용 수신기는 2526 포트다.
- 운영 모드는 `CI_ENV=production` 으로 돈다 (`SetEnv` 대신 router 가 정하므로 HTTPS 도 켜진 것으로 처리).

## 시험 추가

- `tests/app/unit/`: CI 없이 도는 함수.
- `tests/app/integration/`: `ApiTestCase` 를 상속해 HTTP 로 확인한다. `makeUser()`, `login()`, `systemAdmin()`, `grant()` 를 쓴다.
- 요청별 환경 변수는 `$client->request(..., $env)` 로 넘긴다 (`['SIGNUP_USER_CD' => 'USR003']`).
- 브라우저 동작(JS)은 검사하지 못한다. 화면 버그는 따로 확인한다.
- 마이그레이션으로 DB 를 바꾸면 `schema.sql` 도 같이 고쳐야 시험에 반영된다.
