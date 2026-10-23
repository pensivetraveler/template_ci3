# Route · Method · Nav · Base 설정

builder 기반 플랫폼(현재 `admin`)에서 페이지 하나는 **route 항목**(클래스 단위)과 그 안의 **method 항목**(액션 단위)으로 정의되고, 사이드 메뉴는 **nav 설정**, 플랫폼 공통 동작은 **base 설정**(`platform_config`)이 정한다.
이 문서는 각 설정이 어떤 순서로 로드·병합되고, 어떤 키가 실제로 어디서 읽히는지를 `v3` 브랜치 코드 기준으로 정리한 레퍼런스다.
컬럼 정의(form/list/filter/view/excel)는 별도 문서를 참고한다: [form.md](form.md), [list-filter.md](list-filter.md), [view-excel.md](view-excel.md).

**목차**

1. [개요: 로딩 · 병합 규칙](#1-개요-로딩--병합-규칙)
2. [Base 설정 (`platform_config`)](#2-base-설정-platform_config)
3. [Route 설정](#3-route-설정)
4. [Method 설정](#4-method-설정)
5. [Nav 설정](#5-nav-설정)

---

## 1. 개요: 로딩 · 병합 규칙

### 로딩 순서

`BuilderCommonTrait::loadConfigs()`가 아래 순서로 로드한다.

| 단계 | 대상 파일 | 비고 |
|---|---|---|
| 1 | `application/config/extra/builder/builder_*.php` (컨트롤러가 넘긴 목록만) | `config->load(..., false)` |
| 2 | `builder_base_constants.php` | `require_once`. `BUILDER_FLAGNAME = 'builder'` 등 |
| 3 | `config/extra/{flag}/*_config.php` **전부** (glob, 알파벳 순) | admin: base → filter → form → list → nav → page → route → view |
| 4 | `config/extra/{flag}/*_constants.php` 전부 | `PLATFORM_NAME`, `PLATFORM_ASSET_*` 정의 |
| 5 | `language/{lang}/{flag}/*_lang.php` 전부 | `nav_lang.php` → `nav.*` 번역 |

### WEB / API 차이

| 컨트롤러 | 1단계에서 로드하는 builder 파일 | 위치 |
|---|---|---|
| `MY_Builder_WEB` (admin 등) | base, form, nav, route, method, list, filter, view (8개) | `MY_Builder_WEB::__construct()` |
| `MY_Builder_API` | base, route, form, list, view (5개). **nav / method / filter는 로드하지 않음** | `MY_Builder_API::__construct()` |

- `builder_page_config.php`는 WEB/API 어느 쪽도 로드하지 않는다(레거시).
- `flag`: WEB은 컨트롤러가 직접 지정한다(`admin/Common::__construct()` → `'admin'`). API는 URI 첫 세그먼트를 쓰고, 그 값이 `api`이면 `'web'`이 된다(`MY_Builder_API::__construct()`).
- `web` 플랫폼 컨트롤러(`controllers/web/Common.php`)는 `MY_Controller_WEB`을 상속하므로 builder 파이프라인을 **타지 않는다**. `web_route_config.php`, `web_page_config.php`, `web_base_config.php`는 `/api/...` 호출 때 API가 glob으로 로드만 하고 route/method 처리에는 쓰이지 않는다.

### 병합 규칙

세 가지 방식이 섞여 있다.

1. **파일 간: 최상위 키 교체.** `config->load()`는 CI3 `CI_Config::load()`의 `array_merge($this->config, $config)`를 따른다. 같은 최상위 키(예: `platform_config`)를 `admin_base_config.php`에 다시 정의하면 builder 쪽 배열이 **통째로 교체**된다. 깊은 병합은 없다.
2. **route / method / nav 항목: `builder_route_helper.php` `fill_config_properties($config, $base)`**
   - `$config`(구체적인 쪽)에 이미 있는 키가 이긴다. `$base`는 빠진 키만 채운다.
   - `$base` 값이 **연관배열**이면 재귀적으로 채운다(`properties`, `includes`, `buttons`, `actions`, `exports` 등). 이때 `$config` 쪽 값이 `[]`여도 `$base`의 키가 **그대로 채워진다**. 즉 `'actions' => []`로는 상위 기본값을 지울 수 없고, 끄려면 키마다 `false`를 줘야 한다.
   - `$base` 값이 **리스트(숫자 키) 배열이거나 빈 배열**이면 잎(leaf)으로 취급해 `$config`에 키가 없을 때만 통째로 넣는다(`format_helper.php` `is_list_type()`은 빈 배열에도 `true`를 돌려준다).
   - `$base`는 연관배열인데 `$config`의 같은 키가 스칼라면(예: `'includes' => false`) 배열 타입힌트 때문에 TypeError가 난다.
3. **builder 파일 내부의 사전 병합: `array_replace_recursive`** (`builder_method_config.php`의 `method_form_base_config`, `method_form_base_grid_config`, `method_form_auth_*_config`). 여기서도 빈 배열로 덮어써도 키가 지워지지 않는다.

> **주의**
> - 빈 배열(`[]`)은 어떤 병합 방식에서도 "비우기"로 동작하지 않는다 — 끌 키는 `false`로 명시.

### Config 조회 헬퍼 (`MY_Config`)

- `$this->config->item('a.b.c')`: 점(.) 경로로 중첩 키를 읽는다(`MY_Config::item()`).
- `$this->config->get($item, $default, $triggerError = true)`: 값이 없으면 `$default`를 돌려주고, `$triggerError`가 true면 에러 로그를 남긴다(`MY_Config::get()`). `$item`에 리스트를 주면 순서대로 찾아 처음 존재하는 키의 값을 쓴다.

---

## 2. Base 설정 (`platform_config`)

### 정의 / 오버라이드

| 파일 | 내용 |
|---|---|
| `config/extra/builder/builder_base_config.php` | 기본값 |
| `config/extra/admin/admin_base_config.php` | builder와 **동일 내용**으로 재정의. 최상위 교체이므로 builder 값은 사실상 쓰이지 않는다 |
| `config/extra/web/web_base_config.php` | `loggedInRedirect`, `noLoginRedirect`만 있음 |

`MY_Builder_WEB::__construct()`에도 "base config 병합 필요" TODO가 남아 있다. 키 하나만 바꾸려 해도 플랫폼 파일에 전체를 다시 적어야 한다.

### 키

| 키 | 타입 | 값/기본값 | 설명 |
|---|---|---|---|
| `loggedInRedirect` | string | `'dashboard'` | 로그인 상태 기본 경로. 앞에 `{baseUri}/`가 붙는다(`MY_Builder_WEB::__construct()`). 로그인 상태에서는 `MY_Builder_WEB::setupPlatformRedirect()`가 **권한 필터 후 첫 번째 메뉴**로 덮어쓰므로, 실제로는 `admin/Common::__construct()`의 `defaultController`, `Auth::login()`의 `REDIRECT_URI`, 헤더 로고 링크 등에서 의미가 있다. JS `LOGGED_IN_REDIRECT` |
| `noLoginRedirect` | string | `'auth'` | 비로그인·잘못된 접근 시 이동 경로(`{baseUri}/auth`). `MY_Builder_WEB::checkLoggedIn()`, `index()`, `Auth::findId()`/`findPassword()`에서 사용. JS `NO_LOGIN_REDIRECT`(`app-page-common.js`) |
| `navDefaultParams` | assoc | `['layout' => 'side-menu']` | 모든 메뉴 항목의 `params`에 `array_merge`한다(`BuilderCommonTrait::fillMenuConf()`). **플랫폼 값이 항목 값을 덮어쓴다.** href 쿼리스트링과 활성 메뉴 판정에 쓰인다 |
| `notAllowAccess` | list | `['common']` | 이 목록의 클래스로 직접 접근하면 로그인 여부에 따라 `loggedInRedirect`/`noLoginRedirect`로 보낸다(`MY_Builder_WEB::setupPlatformRedirect()`). **주의** — `common`은 그 전에 `setRouteConfig()`가 return하고 `checkLoggedIn()`이 "Incorrect Access"로 보내므로 실효 없음 |
| `*_config_loaded` 플래그 | bool | `true` | **미구현** — 읽는 코드 없음 |

`baseUri`는 `flag === routes['default_platform']`이면 `''`, 아니면 `flag`이다(`MY_Builder_WEB::__construct()`).

### Base 관련 constants

| 파일 | 영향 |
|---|---|
| `builder/builder_base_constants.php` | `BUILDER_FLAGNAME = 'builder'`. 전체 레이아웃 `builder/layout/index`와 viewApp 뷰 탐색 fallback 경로에 쓰인다 |
| `admin/admin_base_constants.php`, `web/web_base_constants.php` | `PLATFORM_NAME = basename(__DIR__)` → 뷰의 `$platformName`이 되어 `{platformName}/includes/*`, `{platformName}/layout/*` 경로를 정한다(`layout/index.php`). 파일이 없으면 `builder_base_helper.php` `builder_view()`가 첫 세그먼트를 `builder/`로 바꿔 로드한다 |
| 〃 `PLATFORM_ASSET_JS_PATH` / `CSS_PATH` | `MY_Builder_WEB::afterViewApp()`이 `style.css`, `common.js`, `{class}[_{method}]_preset.js` / `_onload.js`를 로드한다. **파일이 없으면 빈 파일을 자동 생성**한다(method가 index/list/add/edit/excel이면 `_{method}` 생략) |

---

## 3. Route 설정

### 정의 위치

| 키 | 파일 | 역할 |
|---|---|---|
| `route_base_config` | `builder_route_config.php` | 모든 route의 최하위 기본값 |
| `route_{type}_base_config` | 〃 (`route_page_base_config`, `route_modal_base_config`) | type별 기본값 |
| `route_{type}_{subtype}_config` | 〃 (`route_page_auth_config`) | type+subtype별 기본값 |
| `base_includes_config` | 〃 | `includes` 기본값. 뷰에 `$includes`가 없을 때 `layout/index.php`의 fallback |
| `route_config` | `admin_route_config.php`, `web_route_config.php` | **클래스명 → route 항목** 맵. 실제 페이지 정의 |

- WEB: `MY_Builder_WEB::setRouteConfig()`가 생성자에서 `route_config[router->class]`를 찾고, 소문자 키가 있으면 그것을 쓴다. 찾은 항목에 `fillRouteConfigProperties()`를 적용해 `$this->routeConfig`에 넣는다. `common` 클래스는 건너뛴다.
- API: route 레이어링을 **하지 않는다**. 원본 `route_config`를 읽는 곳은 `api/MenuList::options_get()`과 `BuilderCommonTrait::getMethodList()`(`MY_Builder_API::getMethodList_get()`) 두 곳뿐이다.

### 레이어링 체인 (`BuilderCommonTrait::fillRouteConfigProperties()`)

```
route_config[class]                    (가장 우선)
  ← route_{type}_{subtype}_config
  ← route_{type}_base_config
  ← route_base_config                  (최하위)
```

- `type`, `subtype`은 항목에 없으면 `'base'`로 본다. **`category`는 레이어링에 쓰이지 않는다.**
- `type=page, subtype=base`이면 `route_page_base_config`가 두 단계에서 중복 매칭된다(결과 영향 없음).
- 병합 후 처리:
  - `methods`가 비어 있으면 `E_USER_ERROR`.
  - `properties.baseMethod`가 비어 있으면 **첫 번째 method 키**를 넣는다. 지정했는데 `methods`에 없으면 `E_USER_ERROR`.
  - `properties.allows = array_keys(methods)`로 **무조건 덮어쓴다**. `$this->allowedMethods`에도 같은 값을 넣는다.

### 정의된 type/subtype 조합

| config 키 | type | subtype | 용도 / 차이점 | 현재 사용 |
|---|---|---|---|---|
| `route_base_config` | (없음) | base | 공통 기본값 | 모든 route |
| `route_page_base_config` | page | – | 일반 페이지. 실질적 추가값 없음 | admin route 전체(`type => 'page'`) |
| `route_page_auth_config` | page | auth | 로그인 계열. `baseMethod='login'`, `allowNoLogin=true`, header/footer/modalAppend 숨김 | **사용 안 함** — admin `auth` route는 `subtype => 'base'`에 같은 값을 직접 적음 |
| `route_modal_base_config` | modal | base | 모든 includes를 끈 조각 출력 | **사용 안 함** |

### Route 항목 키

| 키 | 타입 | 값/기본값 | 설명 |
|---|---|---|---|
| `category` | string | `'page'` | **미구현** — 읽는 코드 없음 (page/api 구분 의도) |
| `type` | string | 없으면 `'base'` | `route_{type}_*` 레이어 선택에만 쓴다 |
| `subtype` | string | `'base'` | `route_{type}_{subtype}_config` 선택에 쓴다 |
| `methods` | assoc (method명 → method 항목) | `[]` (비면 E_USER_ERROR) | 허용 method 목록이자 method 설정. 키가 곧 URL 세그먼트. → [4. Method 설정](#4-method-설정) |
| `properties.baseMethod` | string | `''` → 첫 method | `/{flag}/{class}`(index)로 들어오면 실행할 method. `setMethodConfig()`, `index()`, `viewApp()` 뷰 탐색, `setupPlatformPage()`, `BuilderColumnsTrait::setFormColumns()`, `getMenuBaseAuth()`에서 사용 |
| `properties.allows` | list | `[]` | **주의** — 지정해도 `array_keys(methods)`로 덮어씀. list/form/view `actions` 필터링과 `PAGE_*_URI` 생성 여부를 정한다 |
| `properties.noIndex` | bool | `false` | `true`면 `index()`가 `{href}/{baseMethod}`로 redirect. 메뉴 관리 API의 method 선택 목록에서 `index`가 빠진다 |
| `properties.allowNoLogin` | bool | `false` | `false`이고 비로그인이면 세션 정리 후 `noLoginRedirect`(`checkLoggedIn()`, `index()`) |
| `properties.includes.{head,header,modalPrepend,modalAppend,footer,tail}` | bool | 모두 `true` | 레이아웃 조각 출력 여부. `beforeViewApp()` → `$includes` → `layout/index.php` |
| `properties.identifier` | list | `[]` | 식별자 필드. method의 `identifier`보다 **우선**. 둘 다 비면 컬럼 중 `subtype === 'identifier'`인 필드를 자동 사용 → JS `IDENTIFIER` (`setIdentifier()`) |
| `properties.noIdentifier` | bool | `false` | `true`면 view/edit에서 식별자 검사(`KEY` 세팅, "Incorrect Access")를 건너뛴다. method 쪽과 OR (`checkIdentifierExist()`) |
| `properties.formExist` | bool | `false` | **미구현** — route 레벨에서는 읽지 않음. method `properties.formExist`만 유효 |

### 처리 흐름 (WEB)

1. 생성자: `setRouteConfig()` → `setMethodConfig()`.
2. `_remap()`: `checkLoggedIn()`(allowNoLogin) → `isBuilderAvailable()` → `setupPlatform()`(메뉴 → redirect → 권한 → JS 변수) → 컨트롤러에 해당 PHP 메서드가 없으면 `properties.perform` / `redirectUri` → 메서드 실행.
3. `index()`: `noIndex`면 redirect. 아니면 로그인 확인 후 `$this->{baseMethod}()` 호출.

### 예시 (`admin_route_config.php` `administrators`)

```php
'administrators' => [
    'category' => 'page',
    'type' => 'page',          // route_page_base_config
    'subtype' => 'base',
    'properties' => [
        'baseMethod' => 'list', // /admin/administrators → list()
    ],
    'methods' => [
        'list' => [ /* method 항목 (4장 참고) */ ],
    ],
],
// 병합 후 properties: allows=['list'], noIndex=false, allowNoLogin=false,
// includes=모두 true, identifier=[], noIdentifier=false
```

> **주의**
> - `route_config`에 없는 클래스로 접근하면 `trigger_error` 후 빈 키로 계속 진행해 에러가 연쇄된다(예: 컨트롤러만 있는 `EmailLogs`, `Company`). 반대로 `projects`는 route·메뉴만 있고 컨트롤러가 없다.
> - `methods`가 없는 route(예: `web_route_config.php`)에 레이어링을 적용하면 E_USER_ERROR.
> - `baseMethod`가 없는 route(`visitors`, `logs`)는 API `getMethodList()` / `MenuList::options_get()`에서 undefined index.
> - `route_page_auth_config`, `route_modal_base_config`, `builder_route_helper.php`의 `normalize_route_config()` / `get_route_method_base_config()`는 사용되지 않는다.
> - `admin_page_config.php`, `web_page_config.php`(`page_config`), `formProperties` 키는 읽는 코드가 없는 레거시다.

---

## 4. Method 설정

### 정의 / 로딩

- 기본값: `builder_method_config.php`. **WEB에서만 로드**한다.
- 실제 정의: `route_config[class]['methods'][methodName]`.
- `MY_Builder_WEB::setMethodConfig()`:
  - 대상 method는 요청 method. `index`면 `baseMethod`를 쓴다.
  - `routeConfig['methods']`에서 찾고, 없으면 소문자로 다시 찾는다.
  - 목록에 없는 method면 `trigger_error` 후 생성자 안에서 `$this->index()`를 호출한다(이 시점엔 로그인 확인 전이라 `allowNoLogin=false` route는 `noLoginRedirect`로 간다).
  - `category`가 없으면 항목 내용을 **무시하고** `method_base_config`를 그대로 쓴다.
  - `category === 'action'`이면 레이어링 없이 원본을 그대로 쓴다.
  - 그 외에는 `BuilderCommonTrait::fillMethodConfigProperties()`를 거친다.

### 레이어링 체인 (`BuilderCommonTrait::fillMethodConfigProperties()`)

```
entry                                          (가장 우선)
  ← method_{category}_{type}_{subtype}_config
  ← method_{category}_{type}_config
  ← method_{category}_base_config
  ← method_base_config                         (최하위)
```

category/type/subtype이 없으면 각각 `'base'`로 본다. 각 단계는 해당 키가 정의돼 있을 때만 적용된다.

### 정의된 method 변형 (`builder_method_config.php`)

| config 키 | 매칭 단계 | category/type/subtype/mode | 주요 기본값 |
|---|---|---|---|
| `method_base_config` | 최하위 | base/base/base/read | `resourceConfig=''`, `layout{default,allowed}`, `actions=[]`, `buttons=[]`, `properties{noIdentifier:false, identifier:[]}`, `widgets=[]` |
| `method_list_base_config` | category | list/table/base/read | `buttons{add:true, excel:true}`, `actions{duplicate:false, edit:true, view:false, delete:true}`, `exports{print,csv,excel,pdf,copy: 모두 false}`, `properties{plugin:'', isCheckbox:false, formExist:false, formConfig:'', formType:'side', formSubType:'base', formStyle:'base', filterConfig:'', isRowNumber:true, …}` |
| `method_list_table_datatable_config` | cat_type_sub | list/table/**datatable** | `properties.plugin='datatable'` |
| `method_list_log_config` | cat_type | list/**log**/base/read | `isRowNumber:false`, `isCheckbox:false`, `formExist:false`. `actions=[]`, `buttons=[]`도 적혀 있지만 **주의** — 빈 배열이라 list_base의 add/excel/edit/delete가 그대로 채워짐 |
| `method_form_base_config` | category (+ type=base면 cat_type에서도) | form/base/base/**''** | `actions{list:true, delete:true}`, `buttons=[]` |
| `method_form_base_grid_config` | cat_type_sub | form/base/**grid** | form_base와 같은 값 |
| `method_form_article_config` | cat_type | form/**article**/base | `properties{editor{type:'quill',imageUpload:true}, thumbnail, attachments, seo, preview}` — **미구현**(읽는 코드 없음) |
| `method_form_auth_config` | cat_type | form/**auth**/–/authenticate | `actions=[]`로 적었지만 `array_replace_recursive`라 `{list, delete}`가 남는다 |
| `method_form_auth_login_config` | cat_type_sub | form/auth/**login** | `resourceConfig='login'` |
| `method_form_auth_find_id_config` | cat_type_sub | form/auth/**find_id** | `resourceConfig='find_id'` |
| `method_form_auth_find_password_config` | cat_type_sub | form/auth/**find_password** | `resourceConfig='find_password'` |
| `method_view_base_config` | category | view/base/base/read | `actions{list,edit,delete: true}`, `properties{formConfig:'', formType:'', isComments:false}` |
| `method_excel_base_config` | category | excel/base/base/**import** | `actions{list:true}`, `properties{templateDownload, preview, validateBeforeImport}` — **미구현**(읽는 코드 없음) |

admin에서 실제로 쓰는 조합: `list/table/datatable`(administrators 등), `list/log/access|error`(logs, cat_type 단계까지만 매칭), `form/auth/login|find_id|find_password`(auth), `form/base/base`(myinfo), `form/base/grid`(projects add/edit), `form/base/side`(system.sysCfg, 전용 config 없음 → form_base), `form/menu/menu_list`(system.menuList, 전용 config 없음 → form_base), `view/dashboard/base`(dashboard → view_base).

### Method 항목 키

| 키 | 타입 | 값/기본값 | 설명 |
|---|---|---|---|
| `category` | string | `list` / `form` / `view` / `excel` / `action` / (`base`) | 레이어링 1단계, `setJSVariables()` 분기. `setupPlatformPage()`는 **baseMethod 원본 항목의 category**가 `list`인지 본다. `make_base_auth_from_method()`도 list만 계산 |
| `type` | string | `'base'` | 레이어링 2단계. list는 `'table'`일 때만 `LIST_*` JS 변수를 만든다. form(add/edit)에서는 `$formType`이 되어 `layout/form_{type}.php`를 include(`prepareFormData()`, `layout/form.php`) |
| `subtype` | string | `'base'` | 레이어링 3단계. list: `addListScripts(subtype)`(datatable일 때만 asset 로드). form: JS `FORM_TYPE`, `$formSubType` → `form_base.php`가 `base`/`grid`로 분기. view: **`'view'`일 때만** view 데이터·`VIEW_COLUMNS` 준비 |
| `mode` | string | `read`/`create`/`update`/`delete`/`export`/`import`(권한 비트), `authenticate`/`process`/`''`(항상 허용) | 아래 [mode와 메뉴 권한](#mode와-메뉴-권한) 참고 |
| `resourceConfig` | string | `''` | 컬럼 config 이름의 접두 없는 부분. form: `form_{rc}_config`, list: `list_{rc}_config`, view: `view_{rc}_config`, excel: `excel_{rc}_config`. 비면 `{kind}_{class}_{method}_config` → `{kind}_{class}_config` → `{kind}_{method}_config` 순으로 찾는다(`BuilderColumnsTrait::setConfigList()`). 상세: [form.md](form.md), [list-filter.md](list-filter.md), [view-excel.md](view-excel.md) |
| `buttons` | assoc | list: `{add:true, excel:true}`, 그 외 `[]` | list: 권한 필터(`add`→create, `excel`→import, `setListButtons()`) 후 JS `LIST_BUTTONS`. `add`/`excel` 외 키는 **커스텀 버튼** `{text, classes, action, params}`이며 `action`은 `window[action](this, params)`로 호출(`app-page-list.js`). form/view: `$buttons`(`{key:{text}}`)로 렌더링 |
| `actions` | assoc(bool) | list: `{duplicate:false, edit:true, view:false, delete:true}`, form: `{list, delete}`, view: `{list, edit, delete}` | true인 키만 남긴다(`reformat_bool_type_list`). list: 권한 필터(view→read, edit→update, delete→delete, `setListActions()`) 후 JS `LIST_ACTIONS` → 행별 아이콘(edit/view/delete/duplicate만 지원). list에서 `formExist=false`이거나 form/view 페이지면 `delete` 외 키는 `allows`에 있어야 남는다. **주의** — `duplicate`는 권한 필터 없음 |
| `exports` | assoc(bool) | `{print,csv,excel,pdf,copy: false}` | export 권한이 없으면 전부 false(`setListExports()`) → JS `LIST_EXPORTS` |
| `properties.identifier` | list | `[]` | route `identifier`가 비었을 때만 사용 |
| `properties.noIdentifier` | bool | `false` | route 쪽과 OR |
| `properties.plugin` | string | `''` / `'datatable'` | JS `LIST_PLUGIN`. **미구현** — JS에서 읽지 않음 |
| `properties.isCheckbox` | bool | `false` | JS `LIST_CHEKBOX`(오타 그대로), 뷰 `$isCheckbox`(체크박스 열) |
| `properties.isRowNumber` | bool | list `true` / log `false` | 행 번호 열(`row_num`)을 앞에 추가(`setListColumns()`) |
| `properties.formExist` | bool | `false` | list 안 사이드(offcanvas) 폼. true면 `formExist`/`listForm`을 켜고 JS `FORM_EXIST`, `FORM_DATA`, `FORM_REGEXP`, `FORM_TYPE`, `FORM_STYLE`를 넘기고 form asset을 로드. 이때 같은 클래스의 `add()`/`edit()`는 `show_404()` |
| `properties.formConfig` | string | `''` | 사이드 폼 컬럼 config(`form_{formConfig}_config`, 비면 `setConfigList()` fallback). 값이 있으면 `PAGE_ADD_URI`/`PAGE_EDIT_URI`를 비운다 |
| `properties.formType` | string | `'side'` | JS `FORM_TYPE`, `$formType`. **주의** — `list.php`가 `'side'`를 하드코딩하고 JS도 안 읽어 효과 없음 |
| `properties.formSubType` | string | `'base'` | 사이드 폼 class `form-subtype-{x}`(`form_side.php`) |
| `properties.formStyle` | string | `'base'` | JS `FORM_STYLE`, offcanvas `data-form-style`. **미구현** — JS에서 읽지 않음 |
| `properties.filterConfig` | string | `''` | `filter_{x}_config`. 비면 `filter_{class}_{method}_config` 등 → JS `FILTER_COLUMNS`. 상세: [list-filter.md](list-filter.md) |
| `properties.viewConfig` | string | (정의 없음) | list에서 값이 있으면 `PAGE_VIEW_URI`를 비운다(모달 조회 유도). 기본값 정의는 없다 |
| `properties.isComments` | bool | `false` | view 페이지에서 `app-page-comment.js`와 댓글 뷰 로드(`prepareViewData()`) |
| `properties.perform` | string | – | 컨트롤러에 해당 PHP 메서드가 **없을 때** 대신 실행할 메서드명(`_remap()`) |
| `properties.redirectUri` | string | – | 컨트롤러에 해당 메서드가 없을 때 `perform` 뒤 `base_url(redirectUri)`로 이동(`_remap()`) |
| `layout` | assoc | `{default:'base', allowed:[…]}` | **미구현** — 읽는 코드 없음 |
| `widgets` | list | `[]` | **미구현** — 읽는 코드 없음 |

### category별 처리 (JS 변수 / 뷰 / asset)

`setupPlatform()` → `MY_Builder_WEB::setJSVariables()`. JS 변수는 `MY_Hooks::setPHPVars()`가 `common.*` 네임스페이스로 내보낸다(`custom_config.php` `phptojs.namespace`).

| category | 조건 | JS 변수 | 뷰 / asset |
|---|---|---|---|
| list | `type === 'table'` | `LIST_BUTTONS`, `LIST_ACTIONS`, `LIST_EXPORTS`, `LIST_COLUMNS`, `LIST_PLUGIN`, `LIST_OPTIONS`(=properties 전체), `LIST_CHEKBOX`, `LIST_PAGING=true` — 상세 [list-filter.md](list-filter.md) | `list()` → `prepareListData()` → 기본 `subPage='builder/layout/list'` |
| list | 항상 | `FILTER_COLUMNS` | `layout/filter` |
| list | `properties.formExist` | `FORM_EXIST`, `FORM_DATA`, `FORM_REGEXP`, `FORM_TYPE`(=properties.formType), `FORM_STYLE` — 상세 [form.md](form.md) | `addFormScripts()`: tagify, form-validation, maxlength, autosize, repeater, quill |
| list | `subtype === 'datatable'` | – | `addListScripts()`: datatables CSS/JS + `app-page-list.js`. 다른 subtype은 아무것도 로드하지 않음(log는 `Logs::userAccess()` 등이 `CommonLogList.js`를 직접 추가) |
| form | 항상 | `FORM_DATA`(`resourceConfig` 기준), `FORM_REGEXP`, `FORM_TYPE`(=**subtype**) | `add()`/`edit()` → `prepareFormData()` → `layout/form` → `layout/form_{type}.php`. `app-page-add.js` / `app-page-edit.js` |
| view | `subtype === 'view'`일 때만 | `VIEW_COLUMNS`, `VIEW_TYPE` — 상세 [view-excel.md](view-excel.md) | `view()` → `prepareViewData()` → `layout/view` → `layout/view_{subtype}.php`, `app-page-view.js` |
| excel | (분기 없음) | – | `excel()`은 method 설정과 무관하게 `app-page-excel.js`, tabledit, xlsx 로드. 헤더 구성은 `getExcelHeaders()` — [view-excel.md](view-excel.md) |
| action | (분기 없음) | – | `perform` / `redirectUri`만 처리 |
| 공통 | – | `IDENTIFIER`(`setIdentifier()`). `PAGE_{LIST,ADD,EDIT,VIEW,EXCEL}_URI`: `actions + buttons`에서 해당 키가 false가 아니고, list의 add/edit는 `formConfig`·view는 `viewConfig`가 비어 있으며, **`allows`(=methods 키)에 있을 때만** `{href}/{action}`, 아니면 `''` | – |
| 공통 | – | `LOGGED_IN_REDIRECT`, `NO_LOGIN_REDIRECT`(`setupPlatformRedirect()`) | – |

### viewApp 뷰 탐색 순서

`subPage`가 지정되지 않았을 때 `MY_Builder_WEB::viewApp()`이 method(index면 baseMethod)를 `snakeize`해 아래 순서로 찾는다(첫 경로는 디렉터리가 있을 때만 시도).

1. `{get_path()}/{class}/{method}.php`
2. `{get_path()}/layout/{method}.php`
3. `builder/{class}/{method}.php`
4. `builder/layout/{method}.php`

찾은 뷰는 전체 레이아웃 `builder/layout/index`의 `$subPage`로 include된다. 템플릿에는 `$pageConfig`(= methodConfig)도 전달되지만 읽는 뷰는 없다.

> **주의** — 못 찾으면 `$errors`에 기록하지만 `blank` 지정이 `$data`가 아닌 `$view`에 들어가 `subPage`가 비어 버린다.

### mode와 메뉴 권한

- 6자리 auth 문자열 자리 순서: **create, read, update, delete, export, import** (`builder_auth_helper.php` `auth_char_map()`). `INIT_AUTH_CHAR='111111'`, `BASE_AUTH_CHAR='000000'`(`custom_constants.php`).
- `get_auth_value($mode, $auth)` / `is_auth_avaliable()`: mode가 위 6개가 아니면(`authenticate`, `process`, `''` 등) **기본값 true**. form 기본 mode가 `''`이므로 add/edit에는 `mode`를 명시해야 권한 검사가 걸린다.
- `MY_Builder_WEB::setupPlatformPage()`는 다음을 **모두** 만족할 때 `methodConfig.mode` 비트가 0이면 `alert('Not Authorized')` 후 baseMethod로 보낸다.
  1. 로그인 상태이고 시스템 관리자(`is_sys_admin`)가 아님
  2. `currentMenu`가 존재함(없으면 `loggedInRedirect`로 redirect)
  3. `pageAuth`(= 메뉴의 `menu_auth`)가 `'000000'`이 아님(**000000이면 검사 없이 통과**)
  4. route의 **baseMethod 항목이 list category**이고 현재 method가 baseMethod가 아님
- list 화면 버튼/액션 권한 필터(`pageAuth` 기준): `buttons.add`→create, `buttons.excel`→import, `actions.view`→read, `actions.edit`→update, `actions.delete`→delete, `exports.*`→export. 시스템 관리자는 `pageAuth=INIT_AUTH_CHAR`(전체 허용).
- 메뉴 기본 권한(`base_auth`)은 `builder_menu_helper.php` `make_base_auth_from_method()`가 **list category method에 대해서만** 계산한다: C=`buttons.add`, R=`actions.view`, U=`actions.edit`, D=`actions.delete`, E=`exports` 중 하나라도 true, I=`buttons.excel`. list가 아니면 `'000000'`. 예: administrators.list → `101100`.

### 예시 (`admin_route_config.php` `administrators.list`)

```php
'list' => [
    'category' => 'list',      // method_list_base_config
    'type' => 'table',         // (method_list_table_config 없음)
    'subtype' => 'datatable',  // method_list_table_datatable_config → plugin=datatable, datatables asset 로드
    'buttons' => ['excel' => false],                  // 업로드 버튼 숨김 (add는 기본 true → create 권한 필요)
    'actions' => ['view' => false, 'excel' => false], // actions.excel은 JS 아이콘이 없어 무시됨
    'properties' => ['formExist' => true],            // 목록 + offcanvas 사이드 폼, add()/edit()는 404
],
// 결과: LIST_BUTTONS={add:<create권한>, excel:false}, LIST_ACTIONS=['edit','delete'](권한 필터 후),
// PAGE_ADD_URI=''(allows=['list']에 add 없음), FORM_TYPE='side'
```

> **주의**
> - view 파이프라인은 `subtype === 'view'`에서만 동작하는데 그런 config가 없다 — 현재 `VIEW_COLUMNS`/`prepareViewData()`는 어디서도 실행되지 않음. dashboard는 `builder/layout/view.php`가 잡혀 `$viewType` 미정의.
> - form 페이지의 `$formType`은 `type`, JS `FORM_TYPE`은 `subtype` — 이름은 같고 의미가 다르다. `layout/form_{type}.php`는 base/side/modal/tabs만 있어 auth/menu/article type은 컨트롤러가 `subPage`를 직접 지정해야 한다.
> - `category: 'action'` 항목은 레이어링되지 않지만 `setJSVariables()`가 `actions`, `buttons`, `properties.identifier`를 읽으므로 직접 넣어야 한다.
> - baseMethod 항목에 `category`가 없으면 `setupPlatformPage()`에서 notice, list가 아닌 baseMethod route는 mode 검사를 전혀 하지 않는다.
> - `method_list_base_config`의 `buttons.excel=true` 기본값 때문에 excel method가 없어도 base_auth의 I 비트가 켜진다.
> - excel 페이지는 `getExcelHeaders()` 버그로 현재 항상 에러 — [view-excel.md](view-excel.md) 참고.

---

## 5. Nav 설정

### 정의 / 로딩

| 키 | 파일 | 사용 |
|---|---|---|
| `builder_nav_menu_base` | `builder_nav_config.php` | 메뉴 항목 기본값(fill) |
| `builder_nav_menu_sample` | 〃 | `{flag}_nav_menu`가 없을 때 쓰는 fallback |
| `builder_nav_top_base`, `builder_nav_top_sample` | 〃 | **미구현** — 읽는 코드 없음 |
| `admin_nav_menu` | `admin_nav_config.php` | admin 사이드 메뉴 정의 |
| `admin_nav_top` | 〃 | **미구현** — 읽는 코드 없음 |

- nav 설정은 **WEB만** 로드한다. API의 메뉴 기능(`MenuList`/`MenuAuth`)은 DB(`menu`, `menu_auth` 테이블) 기준이다.
- `BuilderCommonTrait::getMenuList()`가 `config->get("{flag}_nav_menu", builder_nav_menu_sample)`을 `fillMenuConf()`에 넘긴다. 최상위가 리스트 배열이 아니면 `show_error`.

### 메뉴 항목 키 (`builder_nav_menu_base`)

| 키 | 타입 | 기본값 | 설명 |
|---|---|---|---|
| `code` | string | `''` | `<li data-menu-code>`. DB 저장 뒤 `MenuList::generateMenuCode_get()`이 `M{srt}0000` 형식으로 갱신 |
| `icon` | string | `''` | `<i class="menu-icon tf-icons {icon}">`(remixicon 클래스) |
| `title` | string | `''` | 표시명. `__('nav.'.title)`로 번역, 번역이 없으면 title 그대로(`MY_language_helper.php` `__()`). 번역 파일: `language/{lang}/admin/nav_lang.php` |
| `class` | string | `''` | 컨트롤러 클래스(= `route_config` 키). href 생성, 현재 메뉴 판정, base_auth 계산에 쓴다 |
| `method` | string | `''` → `class`가 있으면 `'index'` | `index`면 href에서 생략 |
| `attr.className` | list | `[]` | `<a class>`. 하위 메뉴가 있으면 `menu-toggle waves-effect` 추가(`setMenuAttributes()`) |
| `attr.target` | string | `'_self'` | 하위 메뉴가 없는 1depth 링크의 `target`(`header_nav_left.php`) |
| `attr.href` | string | `''` | `class`와 `method`가 있으면 `/{flag}/{class}[/{method}]`로 **덮어쓴다**. 하위 메뉴가 있으면 `''`. class가 비면 설정값 그대로(외부 URL 가능). `builder_base_helper.php` `get_menu_href()`가 `params`를 쿼리로 붙인다 |
| `params` | assoc | `['layout'=>'side-menu']` | href 쿼리스트링. **주의** — `navDefaultParams`가 덮어씀. 활성 판정(`MY_html_helper.php` `is_current_uri()`)은 현재 쿼리와 **모두 일치**해야 active |
| `authParams` | list | `[]` | DB `auth_params`에 저장만 한다. **미구현** — 읽는 코드 없음 |
| `subMenu` | list | `[]` | 하위 메뉴 목록. 같은 기본값으로 재귀 fill(depth=2). 렌더링·DB 저장 모두 **2단계까지만** 지원 |
| `isSubMenu` | bool | `false` | **주의** — 설정해도 무시하고 `count(subMenu) > 0`으로 재계산 |
| `isLogin` | bool | `true` | DB `is_login`에 저장만 한다. **미구현** — 읽는 코드 없음 |
| `isAuth` | bool | `true` | `false`면 권한 레코드 없이도 항상 노출. `true`면 `menu_auth`에 해당 grade 행이 있어야 노출(`MY_Builder_WEB::setupPlatformMenuAuth()`) |
| `isSuper` | bool | `false` | `true`면 `MenuAuth::beforeList()`가 grade별 권한 행을 만들 때 **제외**(`is_super=0` 조건) → 일반 관리자에게 안 보임(CACHING_MENU=true일 때). WEB에서 직접 검사하지는 않는다 |
| `isUse` | bool | `true` | `false`면 메뉴에서 제외(`setMenuAttributes()`) |
| (자동) `depth` | int | 1 / 2 | `fillMenuConf()` |
| (자동) `listClassName` | string | `'active'`, `'open active'` | 현재 URI와 일치하면 붙는다(`builder_web_helper.php` `is_active_page()`) |
| (DB 저장 시) `baseAuth`, `srt`, `parentId`, `menuId` | – | – | 아래 흐름 참고(`setMenuData()`) |

### 메뉴 생성 / 캐시 / DB 저장 흐름

`setupPlatform()` → `MY_Builder_WEB::setupPlatformMenu()` → `BuilderCommonTrait::setMenuList()`

1. 파일 캐시 `menu_done`이 있으면 **`CACHING_MENU` 값과 상관없이** 캐시를 쓴다.
2. 캐시가 없으면 config로 `getMenuList()`를 만든다. `env('CACHING_MENU')`가 true면 `saveMenuList()`를 호출한다.
   - 모든 `route_config`에 route/method 레이어링을 적용한다.
   - 항목마다 `baseAuth = make_base_auth_from_method(해당 class의 method 설정)`(`getMenuBaseAuth()`). `index`면 baseMethod 기준, 하위 메뉴가 있거나 route가 없으면 `000000`.
   - `srt`, `parentId`를 넣고 키를 snake_case로, 배열은 serialize해서 `Model_Menu::addData()`로 저장(`transformMenuData()`).
   - 결과를 `menu_done`(TTL 0)으로 저장하고 다시 읽는다.
3. `setMenuAttributes()`로 href, class, active를 계산한다.
4. 로그인 상태 + `CACHING_MENU` + 시스템 관리자 아님이면, `menu_auth`에서 `grade_cd = session.user_cd`, `is_show = 1`인 행을 읽어 `setupPlatformMenuAuth()` 적용.
   - `isAuth=true` 항목은 `menuId`가 권한 목록에 있어야 남고, 남으면 `menuAuth`(6자리)·`scopeCd`를 붙이고 하위 메뉴도 재귀로 거른다. `isAuth=false`는 무조건 남는다.
   - 남은 항목마다 `setCurrentMenu()`: `router->class === class`이고, method가 같거나 / 현재 method가 baseMethod거나 / 현재 method가 route `methods`에 있으면 `currentMenu`가 된다.
5. `currentMenu.menuAuth` → `pageAuth`, `scopeCd` → `scope`(`setupPlatformPage()`). 렌더링: `$data['menus']` → `includes/header.php` → `includes/header_nav_left.php`.
6. 로그인 후 첫 화면(`loggedInRedirect`)은 필터링된 `menuList[0]`의 class/method로 정해진다(`setupPlatformRedirect()`).

메뉴 관리 화면은 `admin/System::menuList()`로, config 기반 목록(`getMenuList()`)과 캐시 목록을 함께 보여 준다. API `MenuList`는 응답마다(`beforeResponse()` → `cachingMenu()`) DB 기준으로 `menu_done`을 다시 만들고, `reset_get()`은 `menu`, `menu_auth`, `partner_auth`를 truncate하고 캐시를 지운다.

### 예시 (`admin_nav_config.php`)

```php
$config['admin_nav_menu'] = [
    [
        'icon' => 'ri-team-line',
        'title' => 'Administrators Management', // nav_lang → '관리자 관리'
        'class' => 'administrators',            // href=/admin/administrators?layout=side-menu
    ],                                          // method='' → 'index' → base_auth: list 기준 101100
    [
        'icon' => 'ri-folder-history-line',
        'title' => 'Logs',                      // class 없음 → 하위 메뉴 토글
        'subMenu' => [
            ['title' => 'Visitors',   'class' => 'visitors', 'isSuper' => true],
            ['title' => 'UserAccess', 'class' => 'logs', 'method' => 'userAccess'], // /admin/logs/userAccess
        ],
    ],
];
```

> **주의**
> - `CACHING_MENU=false`(`.env.example` 기본값)에서 비시스템 관리자가 로그인하면 `currentMenu`가 설정되지 않아 `loggedInRedirect`로 무한 redirect된다.
> - `pageAuth === '000000'`이면 mode 검사를 건너뛰면서 list 버튼/액션은 모두 false가 된다.
> - `menu_done` 캐시가 사라진 상태에서 `CACHING_MENU=true`면 `saveMenuList()`가 기존 행을 지우지 않고 다시 insert해 메뉴가 중복될 수 있다.
> - 메뉴 `method`가 route `methods`에 없으면 `getMenuBaseAuth()`에서 undefined index. `class`는 대소문자를 구분해 매칭한다(`myInfo` ≠ `myinfo` → `000000`).
> - 권한 있는 메뉴가 0개면 `setupPlatformMenuAuth()`가 `null`을 돌려 오류가 나고, 첫 항목이 `isUse=false`면 `menuList[0]`이 없을 수 있다.
