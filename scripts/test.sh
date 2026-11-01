#!/bin/bash
# 프로젝트 시험 실행. 사용: scripts/test.sh [development|production|all] [phpunit 옵션...]
# 시험 DB(template_test)를 새로 만들고 컨테이너 안 8081 포트에 시험 서버를 띄운다. 개발 DB·.env.docker 는 건드리지 않는다.
set -u
cd "$(dirname "$0")/.."
[ -x .dev-vendor/bin/phpunit ] || { echo '먼저 실행: COMPOSER_VENDOR_DIR=.dev-vendor composer install --no-scripts'; exit 2; }
WEB=template-ci3-web-1; DB=template-ci3-db-1; NAME=template_test
MODE=${1:-all}; [ $# -gt 0 ] && shift

dbroot() { docker exec -i "$DB" sh -c "mariadb -uroot -p\"\$MARIADB_ROOT_PASSWORD\" $1"; }

# 시험이 만들 수 있는 빈 화면 JS·캐시만 끝나고 지운다 (전부터 있던 파일은 두기)
before=$(git ls-files -o --exclude-standard public/assets/admin/js public/assets/web/js 2>/dev/null)
cleanup() {
    docker exec "$WEB" sh -c 'pkill -f "[t]ests/app/router.php" ; pkill -f "[t]ests/app/smtp_sink.php" ; rm -f application/cache/menu_done application/cache/user_logs_purged /tmp/template_test_mail.txt' >/dev/null 2>&1
    git ls-files -o --exclude-standard public/assets/admin/js public/assets/web/js 2>/dev/null | while read -r f; do
        if ! echo "$before" | grep -qxF "$f" && [ ! -s "$f" ]; then rm -f "$f"; fi
    done
}
trap cleanup EXIT

run() {
    local env=$1; shift
    echo "=== $env ==="
    USER_NAME=$(docker exec "$WEB" sh -c "sed -n 's/^DB_USERNAME=//p' .env.development" | tr -d '\r"')
    { echo "DROP DATABASE IF EXISTS $NAME; CREATE DATABASE $NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci; GRANT ALL ON $NAME.* TO '$USER_NAME'@'%';"; } | dbroot "" || exit 2
    dbroot "$NAME" < docs/schema/schema.sql || exit 2
    dbroot "$NAME" < docs/schema/seed.sql || exit 2

    cleanup; before=$(git ls-files -o --exclude-standard public/assets/admin/js public/assets/web/js 2>/dev/null)
    docker exec -d -e TEST_CI_ENV="$env" -w /var/www/html "$WEB" php -S 127.0.0.1:8081 tests/app/router.php >/dev/null
    docker exec -d "$WEB" php /var/www/html/tests/app/smtp_sink.php 2526 /tmp/template_test_mail.txt >/dev/null
    for _ in 1 2 3 4 5 6 7 8 9 10; do
        docker exec "$WEB" php -r 'exit(@file_get_contents("http://127.0.0.1:8081/admin/auth")===false && !isset($http_response_header) ? 1 : 0);' && break
    done

    docker exec -e TEST_CI_ENV="$env" -e TEST_DB_NAME="$NAME" -w /var/www/html "$WEB" php tests/app/seed_menu.php || exit 2
    docker exec -e TEST_CI_ENV="$env" -e TEST_DB_NAME="$NAME" -w /var/www/html "$WEB" .dev-vendor/bin/phpunit "$@"
    local code=$?
    cleanup
    return $code
}

status=0
case "$MODE" in
    development|production) run "$MODE" "$@"; status=$? ;;
    all) run development "$@"; status=$?; run production "$@" || status=$? ;;
    *) echo "사용: scripts/test.sh [development|production|all] [phpunit 옵션...]"; exit 2 ;;
esac
docker exec -i "$DB" sh -c "mariadb -uroot -p\"\$MARIADB_ROOT_PASSWORD\" -e 'DROP DATABASE IF EXISTS $NAME'"
exit $status
