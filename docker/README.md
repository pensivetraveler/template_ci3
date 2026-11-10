# 로컬 검증 서버

템플릿 변경을 파생 프로젝트(bizsoft 등) 없이 확인하는 로컬 서버. PHP 7.4 + Apache, MariaDB 10.11(운영 RDS와 같은 계열).

| 주소 | 내용 |
|---|---|
| http://localhost:8080/admin | 관리자 화면 |
| 127.0.0.1:3307 | DB (`template` / `template`, DB `template`) |

## 처음 띄우기

```bash
docker/init-env.sh
docker compose -f docker/docker-compose.yml up -d --build
```

1. `init-env.sh`가 `.env.example`로 `docker/.env.docker`를 만든다(키는 새로 생성, 커밋하지 않음). 컨테이너 안에서는 이 파일이 `.env.development` 자리에 겹쳐진다. 저장소의 `.env.development`는 바뀌지 않는다.
2. DB는 빈 볼륨으로 처음 뜰 때 `docs/schema/schema.sql` → `seed.sql`을 실행한다.
3. http://localhost:8080/admin/auth 를 열면 설치 화면이 나온다. 시스템 관리자를 만든다. 테스트 계정은 `docker/.env.docker`의 `TEST_ADMIN_*`에 적어 둔다.

## 다시 처음부터

```bash
docker compose -f docker/docker-compose.yml down -v
```

`-v`로 DB 볼륨까지 지우면 다음에 뜰 때 schema·seed를 다시 넣는다. 스키마나 seed를 바꾼 뒤 확인할 때 쓴다. 이미 있는 DB에 바뀐 부분만 넣으려면 `docs/migrations/` SQL을 실행한다.

```bash
docker compose -f docker/docker-compose.yml exec -T db mariadb -utemplate -ptemplate template < docs/migrations/<파일>.sql
```

## 참고

- 저장소 전체를 `/var/www/html`에 마운트하므로 코드 수정은 바로 반영된다.
- PHP 7.4 이미지의 기반 Debian bullseye는 지원이 끝나 `archive.debian.org`를 쓴다(`php/Dockerfile`).
- 업로드 파일은 저장소의 `public/uploads/`(커밋하지 않음)에 쌓인다.

## 운영과 같은 API 검사로 시험

개발 환경은 API 토큰·AJAX 전용 검사가 꺼져 있다. `docker/.env.docker`에 `REST_STRICT=true`를 넣고 web 컨테이너를 재시작하면 운영처럼 검사한다(파일 하나를 마운트해 재시작해야 반영됨).
