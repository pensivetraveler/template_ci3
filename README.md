# template_ci3

CodeIgniter 3 기반 관리자 템플릿이다. 설정 파일(config)만으로 목록·등록·수정·상세 화면과 REST API를 만들어 주는 **빌더**를 포함한다.

- 대상 브랜치: `v3`
- 빌더 설정 상세 문서: [`docs/builder/`](docs/builder/)
- 남은 작업·알려진 문제: [`TODO.md`](TODO.md)

## 목차

1. [요구 사항과 설치](#요구-사항과-설치)
2. [빌더 구조](#빌더-구조)
3. [설정 파일 구성](#설정-파일-구성)
4. [설정 로딩과 병합 규칙](#설정-로딩과-병합-규칙)
5. [엔티티 추가하기](#엔티티-추가하기)
6. [상세 문서](#상세-문서)

---

## 요구 사항과 설치

- PHP 7.4 이상 (`composer.json`의 `config.platform.php`는 `7.4.33`으로 고정)
- PHP 확장: `json`, `openssl` (`get_starred_*` 헬퍼는 `mbstring`도 사용)
- Composer 의존성 (`composer.json` 기준)
  - `symfony/dotenv` ^5.4: `.env` 로딩
  - `aws/aws-sdk-php` ^3.337: S3 파일 저장소 (`libraries/Storage.php`). PHP 7.4를 지원하는 마지막 3.337.x로 고정
- `symfony/http-client`는 PHP 8.1 이상이 필요해서 쓰지 않는다.

### 환경 설정

1. `.env.example`을 복사해서 `.env.development` / `.env.production`을 만든다. `.env.*`는 커밋하지 않는다.
2. 필수 키
   - `ENCRYPTION_KEY`: `hex:` 접두사 + 64자리 hex. 비어 있으면 모든 요청이 `show_error`로 중단된다.
     ```bash
     php -r "echo 'hex:' . bin2hex(random_bytes(32)), PHP_EOL;"
     ```
   - `JWT_KEY`: JWT 서명 키
   - `DB_*`: 데이터베이스 접속 정보
3. 환경은 `CI_ENV`가 정한다. 저장소의 `.htaccess`는 `development`이므로 운영 서버에서는 서버 설정으로 `production`을 주입한다.
4. 자주 쓰는 플래그
   - `CACHING_MENU`: `true`이면 nav 설정으로 메뉴를 만들어 DB에 저장하고 메뉴 권한을 적용한다. 자세한 동작은 [route-method-nav.md](docs/builder/route-method-nav.md#5-nav-설정) 참고
   - `PASSWORD_DECRYPTABLE`: `TRUE`면 비밀번호를 복호화 가능한 암호화로, `FALSE`면 해시로 저장한다.
   - `SYSTEM_INSPECTION`, `ALLOWED_IPS`: 점검 모드와 점검 중 허용 IP

---

## 빌더 구조

빌더는 화면을 만드는 **WEB**과 데이터를 처리하는 **API** 두 부분으로 나뉜다.

| 구분 | 기반 클래스 | 하는 일 |
|---|---|---|
| WEB | `core/MY_Builder_WEB.php` ← `controllers/admin/Common.php` | route·method 설정을 해석해서 화면(view)을 고르고, 목록 컬럼·폼 필드·필터를 `common.*` JS 변수로 내려 준다. 데이터는 직접 조회하지 않는다. |
| API | `core/MY_Builder_API.php` ← `controllers/api/Common.php` | 목록·상세·등록·수정·삭제, 검증, 파일 업로드, 엑셀 내보내기·가져오기를 처리한다. 스키마는 Model과 `form_*`/`list_*`/`view_*` 설정에서 가져온다. |
| JS | `public/assets/builder/js/app-page-*.js` | WEB이 내려 준 변수로 DataTable과 폼을 그리고 API를 호출한다. |

WEB과 API가 함께 쓰는 로직은 `traits/BuilderInitTrait.php`, `BuilderCommonTrait.php`, `BuilderColumnsTrait.php`와 `helpers/builder/*.php`에 있다.

### 요청 흐름 (관리자 목록 화면)

```
GET /admin/administrators
  → MY_Builder_WEB::__construct   설정 로딩, route/method 설정 결정
  → _remap                        로그인 확인 → 메뉴·권한 확인 → setJSVariables()
  → list()                        builder/layout/list.php 렌더링 + app-page-list.js 로드

app-page-list.js (DataTable)
  → GET /admin/api/administrators?_mode=list&format=datatable&pageNo=…&limit=…&filters…
  → MY_Builder_API::beforeGet → list() → Model 조회 → JSON 응답
```

- 라우팅: `config/extra/custom_routes.php`가 `admin/api/(:any)`를 `api/$1`로 보낸다. `core/MY_Router.php`는 없는 컨트롤러를 `controllers/web/`에서 다시 찾는다.
- `controllers/web/` 컨트롤러는 `MY_Controller_WEB`를 상속하므로 빌더를 거치지 않는다. 빌더 화면은 현재 `admin` 플랫폼만 쓴다.

---

## 설정 파일 구성

빌더 설정은 `application/config/extra/` 아래에 있다.

| 위치 | 역할 |
|---|---|
| `builder/builder_*.php` | 기본값. 모든 플랫폼에 공통으로 깔리는 base 설정과 route·method 변형 정의 |
| `admin/admin_*.php` | admin 플랫폼 설정. 엔티티별 항목을 여기에 추가한다. |
| `web/web_*.php` | `/api/*` 호출(web 플랫폼)에서 쓰는 form·list 설정 |

| 설정 종류 | 기본값 파일 | 엔티티 설정 키 | 문서 |
|---|---|---|---|
| base | `builder_base_config.php` | `platform_config` | [route-method-nav.md](docs/builder/route-method-nav.md) |
| route | `builder_route_config.php` | `route_config['{class}']` | [route-method-nav.md](docs/builder/route-method-nav.md) |
| method | `builder_method_config.php` | `route_config['{class}']['methods']['{method}']` | [route-method-nav.md](docs/builder/route-method-nav.md) |
| nav | `builder_nav_config.php` | `admin_nav_menu` | [route-method-nav.md](docs/builder/route-method-nav.md) |
| form | `builder_form_config.php` | `form_{class}_config` | [form.md](docs/builder/form.md) |
| list | `builder_list_config.php` | `list_{class}_config` | [list-filter.md](docs/builder/list-filter.md) |
| filter | `builder_filter_config.php` | `filter_{class}_config` | [list-filter.md](docs/builder/list-filter.md) |
| view | `builder_view_config.php` | `view_{class}_config` | [view-excel.md](docs/builder/view-excel.md) |
| excel (가져오기) | — | `excel_{class}_config` | [view-excel.md](docs/builder/view-excel.md) |

`builder_page_config.php`와 각 플랫폼의 `*_page_config.php`는 어느 쪽에서도 읽지 않는다.

---

## 설정 로딩과 병합 규칙

### 로딩 순서

1. `builder/builder_*.php` 중 지정된 파일 (`BuilderCommonTrait::loadConfigs()`)
   - WEB: base, form, nav, route, method, list, filter, view (8개)
   - API: base, route, form, list, view (5개). **API는 nav·method·filter 설정을 읽지 않는다.**
2. `builder_base_constants.php`
3. `{flag}/*_config.php` 전부 (알파벳 순)
4. `{flag}/*_constants.php` 전부
5. `language/{lang}/{flag}/*_lang.php` 전부

`flag`는 WEB에서는 컨트롤러가 정하고(`admin/Common.php` → `admin`), API에서는 URI 첫 세그먼트로 정한다(`/admin/api/…` → `admin`, `/api/…` → `web`).

### 병합 규칙

세 가지 방식이 섞여 있으므로 어느 단계인지 구분해야 한다.

1. **파일 간: 최상위 키 교체.** CI3 `config->load()`는 최상위에서 `array_merge`만 한다. `admin_*.php`에 `builder_*.php`와 같은 최상위 키를 정의하면 배열 전체가 교체된다.
2. **route·method·nav 항목: `fill_config_properties($config, $base)`** (`helpers/builder/builder_route_helper.php`)
   - 구체적인 쪽(`$config`)에 있는 키가 이긴다. 기본값(`$base`)은 비어 있는 키만 채운다.
   - 기본값이 연관 배열(`properties`, `buttons`, `actions` 등)이면 재귀적으로 채운다. 그래서 구체적인 쪽에 `'actions' => []`를 적어도 기본값이 다시 채워진다. 끄려면 각 키를 `false`로 지정해야 한다.
   - 기본값이 리스트 배열이거나 빈 배열(`[]`)이면 구체적인 쪽에 키가 없을 때만 통째로 넣는다.
3. **`builder_method_config.php` 내부: `array_replace_recursive`.** 변형을 만들 때 쓰며, 이때는 빈 배열로 덮어도 키가 지워지지 않는다.

> `normalize_route_config()`도 `builder_route_helper.php`에 있지만 호출하는 곳이 없다.

### 레이어 순서

- route: `route_base_config` ← `route_{type}_base_config` ← `route_{type}_{subtype}_config` ← 엔티티 route 항목
- method: `method_base_config` ← `method_{category}_base_config` ← `method_{category}_{type}_config` ← `method_{category}_{type}_{subtype}_config` ← 엔티티 method 항목

### 설정 이름 찾기

WEB은 form·list·filter 설정을 다음 순서로 찾고 처음 존재하는 키를 쓴다 (`BuilderColumnsTrait::setConfigList()`).

1. `{kind}_{resourceConfig}_config`: method `properties`의 `resourceConfig`로 다른 엔티티 설정을 빌려 쓸 때
2. `{kind}_{class}_{method}_config`
3. `{kind}_{class}_config`
4. `{kind}_{method}_config`

> **주의** — WEB은 클래스명을 `snakeize()`로, API는 `strtolower()`로 바꿔서 찾는다. `partnerAuth`처럼 대문자가 섞인 클래스는 WEB은 `partner_auth`, API는 `partnerauth`를 찾으므로 키가 어긋난다. 클래스명은 소문자 한 단어로 짓거나 양쪽 키를 모두 맞춰야 한다.

---

## 엔티티 추가하기

`administrators`가 설정이 맞게 짜인 예시다. 새 엔티티 `{class}`(예: `projects`)를 추가할 때 손댈 곳은 다음과 같다.

| # | 파일 | 내용 |
|---|---|---|
| 1 | `models/Model_{Name}.php` | `Model_Common` 상속. `table`, `identifier`, `primaryKeyList`, `uniqueKeyList`, `notNullList`, `nullList`, `strList`, `intList`, `fileList` 등 선언 |
| 2 | `controllers/admin/{Class}.php` | `Common` 상속. `titleList[]` 추가, `addJsVars(['API_URI' => $this->apiUri.'{class}', 'API_PARAMS' => []])` |
| 3 | `controllers/api/{Class}.php` | `Common` 상속. `routeTitle`, `validateModel('Model_{Name}', 'Model')`, `setProperties($this->Model)` |
| 4 | `admin/admin_route_config.php` | `route_config['{class}']`에 route와 methods 정의. [route-method-nav.md](docs/builder/route-method-nav.md) |
| 5 | `admin/admin_form_config.php` | `form_{class}_config`. [form.md](docs/builder/form.md) |
| 6 | `admin/admin_list_config.php` | `list_{class}_config`. WEB은 이 설정이 없으면 에러가 난다. [list-filter.md](docs/builder/list-filter.md) |
| 7 | `admin/admin_filter_config.php` | `filter_{class}_config` (선택) |
| 8 | `admin/admin_nav_config.php` | `admin_nav_menu`에 `class`, `title`, `icon` 추가 |
| 9 | `language/{korean,english}/admin/*_lang.php` | 필드 라벨(`{class}.{field}`), 메뉴 제목(`nav.*`) |
| 10 | 메뉴 DB | `CACHING_MENU=true`일 때만 필요. 첫 요청 때 nav 설정으로 생성되며, 시스템 관리자가 아닌 등급에는 `menu_auth` 행이 있어야 한다. |

- 설정 키의 `{class}`는 컨트롤러 클래스명(소문자)과 정확히 같아야 한다. 단수·복수가 다르면(`company` vs `companies`) 찾지 못한다.
- form 설정의 `field`는 Model 컬럼명과 같아야 한다.
- 필요하면 `{platform}/js/{class}_preset.js`, `{class}_onload.js`로 화면별 스크립트를 붙인다. 처음 접근할 때 빈 파일이 자동으로 만들어진다.

---

## 상세 문서

| 문서 | 다루는 내용 |
|---|---|
| [route-method-nav.md](docs/builder/route-method-nav.md) | base·route·method·nav 설정, method 변형 목록, 화면별 JS 변수와 뷰, mode와 메뉴 권한 |
| [form.md](docs/builder/form.md) | form 필드 키, type/subtype별 렌더링, `form_attributes`·`option_attributes`·`group_attributes`, 검증, 파일 필드, JS 폼 라이프사이클 |
| [list-filter.md](docs/builder/list-filter.md) | list 컬럼과 셀 렌더링, onclick/onchange/render, DataTable 요청·응답, 버튼·행 액션·내보내기, filter 설정 |
| [view-excel.md](docs/builder/view-excel.md) | 상세 보기(view) 설정과 화면, 엑셀 가져오기 설정 |

문서는 코드 동작 기준으로 썼다. 동작하지 않거나 구현되지 않은 항목은 문서에 `**미구현**`, `**주의**`로 짧게 표시했고, 전체 목록은 [`TODO.md` 6장 "빌더 설정 조사"](TODO.md#6-빌더-설정-조사-2026-10-23)에 있다.
