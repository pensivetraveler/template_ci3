#!/bin/bash
# 하위 세션 작업 트리 준비. 사용: scripts/worktree-setup.sh [이름]
#  - 원래 체크아웃에서 이름을 주면: v3 최신에서 issue/<이름> 브랜치로 .claude/worktrees/<이름> 를 만든다
#  - 작업 트리 안에서 인자 없이: 커밋하지 않는 의존 파일(vendor·.dev-vendor·node_modules·.env.development)을 원래 체크아웃에 잇는다
# 상대 경로 링크라 호스트와 컨테이너(/var/www/html) 양쪽에서 같은 곳을 가리킨다. 자세한 절차: docs/session-workflow.md
set -eu
cd "$(dirname "$0")/.."
MAIN=$(git worktree list --porcelain | sed -n '1s/^worktree //p')

if [ $# -gt 0 ]; then
    [ "$PWD" = "$MAIN" ] || { echo '이름을 주는 형식은 원래 체크아웃에서 실행합니다'; exit 2; }
    NAME=$1; DIR=.claude/worktrees/$NAME
    git fetch -q origin v3
    git worktree add "$DIR" -b "issue/$NAME" origin/v3
    cd "$DIR"
fi

case "${PWD#"$MAIN"}" in
    /.claude/worktrees/*) ;;
    *) echo "원래 체크아웃의 .claude/worktrees/ 아래 작업 트리에서 실행합니다: $PWD"; exit 2 ;;
esac

for f in vendor .dev-vendor node_modules .env.development; do
    [ -e "$MAIN/$f" ] || { echo "건너뜀(원래 체크아웃에 없음): $f"; continue; }
    [ -e "$f" ] || [ -L "$f" ] || ln -s "../../../$f" "$f"
done
# 링크는 디렉터리가 아니라 .gitignore 의 /vendor/ 같은 줄에 걸리지 않는다 → 이 저장소 로컬 exclude 에만 넣는다 (커밋되지 않음)
EXCLUDE="$(git rev-parse --git-common-dir)/info/exclude"
for f in /vendor /.dev-vendor /node_modules; do grep -qxF "$f" "$EXCLUDE" 2>/dev/null || echo "$f" >> "$EXCLUDE"; done
echo "준비됨: $PWD ($(git rev-parse --abbrev-ref HEAD))"
