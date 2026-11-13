#!/bin/bash
# 프로젝트 시험 실행. 사용: scripts/test.sh [development|production|all] [phpunit 옵션...]
# 시험 DB(template_test)를 새로 만들고 컨테이너 안 8081 포트에 시험 서버를 띄운다. 개발 DB·.env.docker 는 건드리지 않는다.
set -u
cd "$(dirname "$0")/.."
[ -x .dev-vendor/bin/phpunit ] || { echo '먼저 실행: COMPOSER_VENDOR_DIR=.dev-vendor composer install --no-scripts'; exit 2; }
WEB=template-ci3-web-1; DB=template-ci3-db-1; NAME=template_test
MODE=${1:-all}; [ $# -gt 0 ] && shift

# git worktree(.claude/worktrees/<이름>)에서 돌려도 그 작업 트리의 코드로 시험한다. 컨테이너는 원래 체크아웃을 /var/www/html 로
# 마운트하므로 작업 트리는 그 아래 같은 상대 경로에 보인다. 작업 트리는 먼저 scripts/worktree-setup.sh 로 vendor 등을 잇는다.
MAIN=$(git worktree list --porcelain | sed -n '1s/^worktree //p')
REL=${PWD#"$MAIN"}
case "$REL" in
    '') ;;
    /.claude/worktrees/*) [ -e vendor ] || { echo '먼저 실행: scripts/worktree-setup.sh'; exit 2; } ;;
    *) echo "컨테이너에 마운트되지 않은 작업 트리입니다: $PWD (원래 체크아웃의 .claude/worktrees/ 아래에 만드세요)"; exit 2 ;;
esac
CWD=/var/www/html$REL

# 시험 DB·포트(8081)를 한 벌만 쓰므로 여러 작업 트리가 동시에 돌리지 않게 잠근다 (끝나면 풂, 죽은 잠금은 pid 로 확인)
LOCK=/tmp/template_ci3_test.lock
while ! mkdir "$LOCK" 2>/dev/null; do
    pid=$(cat "$LOCK/pid" 2>/dev/null)
    if [ -n "$pid" ] && ! kill -0 "$pid" 2>/dev/null; then rm -rf "$LOCK"; continue; fi
    echo "다른 시험이 도는 중 ($(cat "$LOCK/who" 2>/dev/null)) — 기다림"; sleep 10
done
echo $$ > "$LOCK/pid"; echo "$PWD" > "$LOCK/who"

dbroot() { docker exec -i "$DB" sh -c "mariadb -uroot -p\"\$MARIADB_ROOT_PASSWORD\" $1"; }

# 시험이 만들 수 있는 빈 화면 JS·캐시만 끝나고 지운다 (전부터 있던 파일은 두기)
before=$(git ls-files -o --exclude-standard public/assets/admin/js public/assets/web/js 2>/dev/null)
cleanup() {
    docker exec "$WEB" sh -c 'pkill -f "[t]ests/app/router.php" ; pkill -f "[t]ests/app/smtp_sink.php" ; cd '"$CWD"' && rm -f application/cache/menu_done application/cache/user_logs_purged /tmp/template_test_mail.txt' >/dev/null 2>&1
    git ls-files -o --exclude-standard public/assets/admin/js public/assets/web/js 2>/dev/null | while read -r f; do
        if ! echo "$before" | grep -qxF "$f" && [ ! -s "$f" ]; then rm -f "$f"; fi
    done
}
trap 'cleanup; rm -rf "$LOCK"' EXIT

run() {
    local env=$1; shift
    echo "=== $env ==="
    USER_NAME=$(docker exec "$WEB" sh -c "sed -n 's/^DB_USERNAME=//p' .env.development" | tr -d '\r"')
    { echo "DROP DATABASE IF EXISTS $NAME; CREATE DATABASE $NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci; GRANT ALL ON $NAME.* TO '$USER_NAME'@'%';"; } | dbroot "" || exit 2
    dbroot "$NAME" < docs/schema/schema.sql || exit 2
    dbroot "$NAME" < docs/schema/seed.sql || exit 2

    cleanup; before=$(git ls-files -o --exclude-standard public/assets/admin/js public/assets/web/js 2>/dev/null)
    docker exec -d -e TEST_CI_ENV="$env" -w "$CWD" "$WEB" php -S 127.0.0.1:8081 tests/app/router.php >/dev/null
    docker exec -d "$WEB" php "$CWD/tests/app/smtp_sink.php" 2526 /tmp/template_test_mail.txt >/dev/null
    for _ in 1 2 3 4 5 6 7 8 9 10; do
        docker exec "$WEB" php -r 'exit(@file_get_contents("http://127.0.0.1:8081/admin/auth")===false && !isset($http_response_header) ? 1 : 0);' && break
    done

    docker exec -e TEST_CI_ENV="$env" -e TEST_DB_NAME="$NAME" -w "$CWD" "$WEB" php tests/app/seed_menu.php || exit 2
    docker exec -e TEST_CI_ENV="$env" -e TEST_DB_NAME="$NAME" -w "$CWD" "$WEB" .dev-vendor/bin/phpunit "$@"
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
