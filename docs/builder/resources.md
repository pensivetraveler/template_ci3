# 리소스 설정 형식 (분할형 · 통합형)

엔티티(리소스) 하나의 빌더 설정은 두 가지 형식 중 하나로 쓴다. 사람이 여러 엔티티를 비교하며 편집할 때는 **분할형**, 에이전트가 엔티티 하나를 한 번에 읽고 쓸 때는 **통합형**이 편하다. 두 형식 모두 `libraries/Resource_loader.php`가 같은 설정 키로 바꿔 주므로 WEB·API 코드는 형식을 구분하지 않는다.

**목차**

1. [두 형식](#1-두-형식)
2. [통합형 파일](#2-통합형-파일)
3. [통합형 → 설정 키 대응](#3-통합형--설정-키-대응)
4. [메뉴(nav)](#4-메뉴nav)
5. [규칙](#5-규칙)
6. [확인 명령](#6-확인-명령)

---

## 1. 두 형식

| | 분할형 | 통합형 |
|---|---|---|
| 위치 | `config/extra/{flag}/{flag}_{종류}_config.php` (종류별 파일에 여러 엔티티) | `config/extra/{flag}/resources/{클래스}.php` (엔티티당 파일 하나) |
| 쓰는 사람 | 사람 (엔티티 간 비교·일괄 수정) | 에이전트 (엔티티 단위 생성·수정) |
| 읽는 곳 | CI `config->load` | `Resource_loader::loadMergedResources()`가 분할형과 같은 키로 넣음 |

`{flag}`는 플랫폼(`admin` 등), `{클래스}`는 컨트롤러 클래스의 URL 이름(`projects`, `partnerAuth`)이다.

## 2. 통합형 파일

파일은 `return [...]`로 배열을 돌려준다. 최상위 키는 아래만 쓸 수 있고, 모두 생략할 수 있다.

```php
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// config/extra/admin/resources/projects.php
return [
    'model' => 'Model_Project',          // 참고용 (로더는 읽지 않음, 생성·검사 도구용)

    'route' => [                         // = route_config['projects']
        'category' => 'page', 'type' => 'page', 'subtype' => 'base',
        'properties' => ['baseMethod' => 'list'],
        'methods' => [
            'list' => [
                'category' => 'list', 'type' => 'table', 'subtype' => 'datatable',
                'properties' => ['formExist' => true],
            ],
        ],
    ],

    'nav' => [                           // 메뉴 항목 (4장)
        'icon' => 'ri-code-box-line',
        'title' => 'Projects Management',
    ],

    'form'   => [ /* = form_projects_config   */ ],
    'list'   => [ /* = list_projects_config   */ ],
    'filter' => [ /* = filter_projects_config */ ],
    'view'   => [ /* = view_projects_config   */ ],
    'excel'  => [ /* = excel_projects_config  */ ],

    'methods' => [                       // 메서드별 설정
        'add' => ['form' => [ /* = form_projects_add_config */ ]],
    ],
];
```

각 섹션의 내용은 분할형과 똑같다. 키 의미는 [route-method-nav.md](route-method-nav.md), [form.md](form.md), [list-filter.md](list-filter.md), [view-excel.md](view-excel.md)를 따른다.

## 3. 통합형 → 설정 키 대응

| 통합형 | 들어가는 설정 키 |
|---|---|
| `route` | `route_config[{클래스}]` |
| `form` / `list` / `filter` / `view` / `excel` | `{종류}_{builder_config_name(클래스)}_config` |
| `methods[{메서드}][{종류}]` | `{종류}_{클래스}_{builder_config_name(메서드)}_config` |
| `nav` | `{flag}_nav_menu` 안의 해당 클래스 항목 |

로더가 넣은 뒤에는 분할형과 구분되지 않으므로, 설정 이름 찾는 순서(README "설정 이름 찾기")도 똑같이 적용된다.

## 4. 메뉴(nav)

메뉴 **트리와 순서는 `{flag}_nav_config.php`의 `{flag}_nav_menu`가 정한다.** 통합형의 `nav`는 그 트리에 들어갈 항목 값만 준다.

- 트리에 `['class' => 'projects']`처럼 같은 class 항목이 있으면, 트리에 적힌 값은 그대로 두고 빈 키(`icon`, `title` 등)만 `nav` 값으로 채운다.
- 트리에 없으면 `nav.parent` 아래, `nav.order` 위치에 넣는다.
  - `parent`: 상위 메뉴의 `class` 또는 `title`. 없거나 못 찾으면 최상위.
  - `order`: 같은 단계에서 0부터 센 위치. 없으면 맨 끝.
- `parent`, `order`는 메뉴 항목 키가 아니라서 넣기 전에 빠진다. 그 밖의 키는 `builder_nav_menu_base`와 같다.
- `CACHING_MENU=true`면 메뉴가 파일 캐시(`menu_done`)·DB에 저장되므로, 메뉴를 바꾼 뒤에는 캐시를 지워야 반영된다.

## 5. 규칙

- **엔티티 단위로 한 형식만 쓴다.** 통합형 파일이 넣으려는 키(route, 엔티티 설정)가 분할형에 이미 있으면, 개발 환경(`ENVIRONMENT=development`)에서는 에러로 멈추고 그 밖에서는 error 로그를 남기고 통합형 파일을 건너뛴다. 섹션 일부만 나눠 쓰는 것은 허용하지 않는다.
- 메뉴 트리에서 클래스를 참조하는 것(`['class' => 'projects']`)은 중복이 아니다.
- 최상위 키는 `route`, `nav`, `form`, `list`, `filter`, `view`, `excel`, `methods`, `model`만 쓴다. 다른 키가 있으면 에러.
- 파일 이름이 클래스 이름이다(`projects.php` → `projects`).

## 6. 확인 명령

DB 연결이 되는 환경에서 실행한다.

```bash
php index.php module/cli resource_dump admin projects
```

리소스가 어느 형식에 있는지(`split`/`merged`), route 메서드별로 form·list·filter·view·excel을 어떤 키로 읽는지, API가 읽는 키, 메뉴 항목을 출력한다.

```bash
php index.php module/cli resource_check admin
```

통합형 파일의 키·중복, 메뉴 class가 route에 없는 항목, admin 컨트롤러 파일이 없는 route를 검사한다. 문제가 있으면 목록을 출력하고 종료 코드 1로 끝난다.

> **예정** — 분할형 ↔ 통합형 변환 명령(양방향)은 다음 단계에서 추가한다.
