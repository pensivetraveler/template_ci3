#!/bin/sh
# 검증 서버용 docker/.env.docker 를 .env.example 에서 만든다 (이미 있으면 그대로 둔다, 커밋하지 않음)
set -e
cd "$(dirname "$0")"
[ -f .env.docker ] && { echo ".env.docker 이미 있음"; exit 0; }

ENC="hex:$(openssl rand -hex 32)"
JWT="$(openssl rand -base64 48 | tr -d '\n')"
sed -e "s|^ENCRYPTION_KEY=.*|ENCRYPTION_KEY=${ENC}|" \
    -e "s|^JWT_KEY=.*|JWT_KEY=${JWT}|" \
    -e "s|^APP_NAME=.*|APP_NAME=template_ci3|" \
    -e "s|^APP_NAME_KR=.*|APP_NAME_KR=\"템플릿 검증\"|" \
    -e "s|^APP_URL=.*|APP_URL=http://localhost:8080|" \
    -e "s|^DB_CONNECTION=.*|DB_CONNECTION=mysql|" \
    -e "s|^DB_HOST=.*|DB_HOST=db|" \
    -e "s|^DB_PORT=.*|DB_PORT=3306|" \
    -e "s|^DB_DATABASE=.*|DB_DATABASE=template|" \
    -e "s|^DB_USERNAME=.*|DB_USERNAME=template|" \
    -e "s|^DB_PASSWORD=.*|DB_PASSWORD=template|" \
    -e "s|^DB_PREFIX=.*|DB_PREFIX=tbl_|" \
    -e "s|^DB_CHARSET=.*|DB_CHARSET=utf8mb4|" \
    -e "s|^DB_COLLATION=.*|DB_COLLATION=utf8mb4_general_ci|" \
    ../.env.example > .env.docker
echo ".env.docker 만듦"
