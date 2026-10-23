# View · Excel 가져오기 설정 (view_config, excel_config)

엔티티의 상세보기(view) 화면과 엑셀 가져오기(import) 화면을 설정하는 방법을 정리한 참고 문서이다.
설정 이름을 찾는 순서, 항목 병합 규칙, 메소드 설정, 요청 흐름을 코드 기준으로 설명한다. 지금 동작하지 않는 부분도 함께 적었다.
경로는 저장소 루트 기준이다. 버그와 미구현 항목의 전체 목록은 [TODO.md](../../TODO.md#6-빌더-설정-조사-2026-10-23)에 있다.

**목차**

- [Part 1. View 설정과 상세보기 화면](#part-1-view-설정과-상세보기-화면)
  - [1. 관련 파일](#1-관련-파일) · [2. 설정 이름 해석](#2-설정-이름-해석) · [3. 항목 병합 규칙](#3-항목-병합-규칙) · [4. builder_view_base 키](#4-builder_view_base-키) · [5. 메소드 설정](#5-메소드-설정-method_view_base_config) · [6. 요청 흐름](#6-요청-흐름) · [7. 설정 예시](#7-설정-예시-가상-예시)
- [Part 2. Excel 가져오기 설정](#part-2-excel-가져오기-설정)
  - [1. 메소드 설정](#1-메소드-설정-method_excel_base_config) · [2. 헤더 설정](#2-헤더-설정-excel_name_config) · [3. 샘플 파일 생성](#3-샘플-파일-생성) · [4. 화면과 업로드 흐름](#4-화면과-업로드-흐름) · [5. form 규칙과의 관계](#5-form-규칙과의-관계)

관련 문서: 메소드 변형(category/type/subtype)과 route 설정은 [route-method-nav.md](route-method-nav.md), form 항목과 rules는 [form.md](form.md), 목록·필터와 Excel/CSV 내보내기(export)는 [list-filter.md](list-filter.md)를 참고한다.

---

## Part 1. View 설정과 상세보기 화면

> **현재 상태** — WEB의 view 페이지는 데이터 준비 조건(`subtype === 'view'`)과 기본 subtype(`'base'`)이 맞지 않고, `view_view.php`도 없어서 그대로는 동작하지 않는다. 자세한 항목은 [TODO.md](../../TODO.md#6-빌더-설정-조사-2026-10-23)를 참고한다.

### 1. 관련 파일

| 구분 | 파일 | 역할 |
|---|---|---|
| 기본값 | `application/config/extra/builder/builder_view_config.php` | `builder_view_base`, `builder_view_hidden_config`, 설명용 템플릿 `builder_view_config` |
| 플랫폼 설정 | `application/config/extra/admin/admin_view_config.php`, `application/config/extra/web/web_view_config.php` | `view_{name}_config`를 정의하는 곳. 현재 두 파일 모두 비어 있음(`*_view_config_loaded` 플래그만 있음) |
| 메소드 기본값 | `application/config/extra/builder/builder_method_config.php` | `method_view_base_config` |
| WEB 컨트롤러 | `application/core/MY_Builder_WEB.php` | `view()`, `prepareViewData()`, `setJSVariables()`, `setViewColumns()` |
| API 컨트롤러 | `application/core/MY_Builder_API.php` | `__construct()`(설정 이름), `setProperties()`(viewConfig 로드), `view()`, `transformView()` |
| API 공통 | `application/controllers/api/Common.php` | `transformView()` 오버라이드, `transformViewByMode()` |
| 뷰 | `application/views/builder/layout/view.php`, `view_base.php`, `view_grid.php`, `view_modal.php`, `view_article.php`, `view_comments.php` | 레이아웃 껍데기와 subtype별 본문 |
| JS | `public/assets/builder/js/app-page-view.js` | 데이터 조회와 채우기, 목록/수정/삭제 버튼 |
| JS 공통 | `public/assets/builder/js/app-page-common.js` | `getData()`, `deleteData()`, `isMyData()`, `getUrlWithIdentifiers()` |

### 2. 설정 이름 해석

설정 이름은 **WEB과 API가 서로 다른 규칙**으로 찾는다. 둘 다 list, filter, form, excel 설정이 쓰는 `BuilderColumnsTrait::setConfigList()`를 쓰지 않는다.

| 측 | 위치 | 규칙 |
|---|---|---|
| WEB | `MY_Builder_WEB::setViewColumns()` | ① 인자 `$name`이 있으면 그 이름 그대로 → ② 메소드 설정의 `resourceConfig`가 있으면 `view_{resourceConfig}_config` → ③ 둘 다 없으면 `jsVars['FORM_DATA']`(form 설정)를 복사하고, hidden이 아닌 항목은 `type='view'`, `subtype='base'`로 바꿈 |
| API | `MY_Builder_API::__construct()`, `MY_Builder_API::setProperties()` | `'view_' . strtolower(클래스명) . '_config'` 하나만 사용. 하위 클래스에서 `$viewConfigName` 속성으로 덮어쓸 수 있음. snakeize를 하지 않으므로 `MenuList`는 `view_menulist_config`가 됨 |

> **주의** — WEB은 `resourceConfig`, API는 소문자 클래스명을 쓰므로 두 쪽 이름이 같아지도록 맞춰야 한다.

참고: `setConfigList()`는 `{flag}_{resourceConfig}_config` → `{flag}_{class}_{method}_config` → `{flag}_{class}_config` → `{flag}_{method}_config` 순서로 후보를 만든다. view에는 이 순서가 적용되지 않는다.

설정 파일 로드: `BuilderCommonTrait::loadConfigs()`가 `config/extra/{flag}/*_config.php`를 모두 읽는다. 그래서 `view_*_config`를 `admin_view_config.php`가 아닌 다른 `*_config.php`에 두어도 읽힌다.

### 3. 항목 병합 규칙

**WEB (`MY_Builder_WEB::setViewColumns()`)** — 항목마다 아래 순서로 처리한다.

1. `subtype === 'identifier'`이면 `type`을 `'hidden'`으로 바꾼다.
2. 비어 있는 키에 기본값을 넣는다: `category='base'`, `type='view'`, `subtype='base'`. 이 값을 base 병합보다 먼저 넣으므로 **실제 기본 type은 `builder_view_base`의 `'text'`가 아니라 `'view'`** 이다.
3. `type === 'common'`이면 여기서 그대로 반환한다.
4. `id = field`로 설정한다. 이 id가 JS 바인딩 키가 된다.
5. `type === 'hidden'`이면 `array_merge(builder_view_hidden_config, item)`.
6. 그 밖에 `type !== 'view'`(form 입력형)이면 `setFormColumn()`(form 기본값 병합, rules 해석, 옵션 처리)과 `get_admin_form_attributes($item, 'page')`를 거친다. 마지막으로 `array_merge(builder_view_base, item)`.

**API (`MY_Builder_API::setProperties()`)** — 단순히 `array_merge(builder_view_base, item)`만 한다. GET/POST 요청에서만 로드된다.

### 4. builder_view_base 키

`builder_view_config.php`의 `$config['builder_view_base']`.

| 키 | 타입 | 기본값 | 설명 | 사용 위치 |
|---|---|---|---|---|
| `field` | string | `''` | DB 컬럼명. 응답 JSON 키이자 DOM id(`id=field`) | `setViewColumns()`, `view_base.php`, `app-page-view.js` `applyViewData()`, API `transformView()` |
| `label` | string | `''` | 라벨. `lang()`으로 번역(`lang:` 접두사 허용) | `view_base.php`, `view_grid.php`, `view_modal.php` |
| `category` | string | `'base'` | 입력형일 때 `get_admin_form_attributes()`가 CSS 클래스 `form-input_{category}`에 사용 | `setViewColumns()`, `builder_form_helper.php` `get_admin_form_attributes()` |
| `type` | string | base는 `'text'`, **실제 기본값은 `'view'`** | `view`: 읽기 전용 `<span id>` / `hidden`: hidden input / `common`: grid의 빈 칸 / 그 밖(text, select 등): form 입력 위젯 | `view_base.php`, `view_grid.php` |
| `subtype` | string | `'base'` | `'identifier'`이면 hidden으로 바뀌고 IDENTIFIER로 쓰임. 입력형이면 form subtype | `setViewColumns()`, `setIdentifier()` |
| `attributes` | array | `[]` | hidden input 속성. 입력형이면 `get_admin_form_attributes()` 결과로 덮어씀 | `view_base.php`, `setViewColumns()` |
| `help_block` | array | `[]` | `{tag, text, attr}`. 비어 있지 않으면 필드 아래에 출력 | `view_base.php`, `MY_html_helper.php` `get_help_block_html()` |
| `colspan` | int | `6` | grid에서 `col-md-{colspan}`. base, modal에서는 쓰이지 않음 | `view_grid.php`, `builder_form_helper.php` `set_form_rows()` |

base에는 없지만 코드가 읽는 키:

| 키 | 타입 | 설명 | 사용 위치 |
|---|---|---|---|
| `option_attributes` | array | `_mode=view`이고 그 필드가 `transTargetKeys['view']`에 있으면 `getOptions()`로 코드값을 라벨로 바꿈. **주의** — `transTargetKeys`를 채우는 컨트롤러가 없어 사실상 동작 안 함 | `MY_Builder_API::transformView()` |
| `id` | string | 자동으로 `field`가 들어감(사용자 값은 덮어씀) | `setViewColumns()` |
| `default` | mixed | `view_article.php`의 identifier hidden 값 | `view_article.php` |
| `group` | string | `trans_formdata_dit_type()`가 읽음 | `builder_form_helper.php` `trans_formdata_dit_type()` |
| form 계열 키(`rules`, `form_attributes`, `option_attributes`, `icon` 등) | – | 입력형 항목은 `setFormColumn()`을 거치므로 form 설정과 같은 의미. [form.md](form.md) 참고 | `BuilderColumnsTrait::setFormColumn()` |

- `builder_view_hidden_config`는 `builder_view_base`에서 `type='hidden'`만 바꾼 값이다.
- `builder_view_config`(`hiddens`/`fields`/`actions`/`buttons` 구조)는 **어디서도 읽지 않는다**. 설정 예시로만 남아 있다.

### 5. 메소드 설정 (`method_view_base_config`)

`builder_method_config.php`에 정의된다. route의 `methods.{name}`에 `category => 'view'`를 주면 `BuilderCommonTrait::fillMethodConfigProperties()`가 `method_base_config` → `method_view_base_config` → `method_view_{type}_config` → `method_view_{type}_{subtype}_config` 순서로 병합한다. 현재 `method_view_*` 변형은 base 하나뿐이다. 변형 규칙은 [route-method-nav.md](route-method-nav.md)를 참고한다.

| 키 | 기본값 | 설명 | 사용 위치 |
|---|---|---|---|
| `category` | `'view'` | `setJSVariables()`의 view 분기 | `MY_Builder_WEB::setJSVariables()` |
| `type` | `'base'` | WEB에서 읽지 않음(admin `dashboard`는 `type=>'dashboard'`) | – |
| `subtype` | `'base'` | `$viewType`이 되어 `view_{subtype}.php`를 고름. **주의** — 데이터 준비는 `subtype === 'view'`일 때만 함 | `view()`, `prepareViewData()`, `setJSVariables()`, `view.php` |
| `mode` | `'read'` | baseMethod가 list인 클래스에서 메뉴 권한(CRUDEI) 검사 키 | `MY_Builder_WEB::setupPlatformPage()` |
| `resourceConfig` | `''` | `view_{resourceConfig}_config` 이름 | `setViewColumns()` |
| `actions.list/edit/delete` | `true` | 하단 버튼과 드롭다운 표시. `delete` 외의 action은 route `allows`(=methods 키)에 있어야 표시. 각 action마다 `PAGE_{ACTION}_URI` 생성 | `prepareViewData()`, `setJSVariables()` |
| `buttons` | `[]`(method_base) | `{key => ['text'=>...]}`. `btn-view-{key}` 버튼을 출력(클릭 핸들러는 직접 구현) | `view_base.php` |
| `properties.isComments` | `false` | `app-page-comment.js` 로드, `view_article.php`에서 댓글 영역 출력 | `prepareViewData()`, `view_article.php` |
| `properties.formConfig`, `properties.formType` | `''` | **미구현** — view에서 읽는 코드 없음 | – |
| `properties.noIdentifier`, `properties.identifier` | `false`, `[]` | 키 존재 검사와 IDENTIFIER 구성 | `checkIdentifierExist()`, `setIdentifier()` |

subtype별 본문 파일 — `view.php`가 `builder_view("{$platformName}/layout/view_{$viewType}")`를 호출한다. 플랫폼 경로에 파일이 없으면 `builder/`로 대체한다(`builder_base_helper.php` `builder_view()`).

| subtype | 파일 | 형태 | 기대하는 `$viewData` 구조 |
|---|---|---|---|
| `base` | `view_base.php` | 라벨 2칸 + 값 10칸 가로형 행, 우상단 드롭다운, 하단 버튼 | `{hiddens:[], fields:[item, …]}` |
| `grid` | `view_grid.php` | `colspan` 기반 12칸 그리드, `common`은 빈 칸 | `{hiddens:[], fields:[[item, …], …]}`(행 배열) |
| `modal` | `view_modal.php` | base에서 헤더/드롭다운을 뺀 형태 | base와 같음 |
| `article` | `view_article.php` | 게시글(subject, created_id, created_dt, view_count, thumbnail, content, uploads) + 선택적 댓글 | `trans_formdata_dit_type()`용 평평한 리스트, `$identifier` |
| `view` | `view_view.php` | **미구현** — 파일 없음 | – |

> **주의**
> - `subtype`을 `'base'`로 두면 VIEW_COLUMNS·`prepareViewData()`가 실행되지 않고, `'view'`로 두면 `view_view.php`가 없음.
> - `prepareViewData()`가 viewType을 `reformat_form_data_by_type()`의 2번째 인자(form_type)로 넘겨 grid 행 배열이 만들어지지 않음.
> - `view_article.php`, `view_comments.php`는 `prepareViewData()`가 넘기는 데이터 구조와 맞지 않음.
> - `VIEW_COLUMNS`, `VIEW_TYPE`은 JS에서 쓰지 않음(응답 키와 DOM id로만 바인딩).

### 6. 요청 흐름

**WEB** (`GET /{flag}/{class}/view/{key}`)

```
MY_Builder_WEB::_remap() → setupPlatform() → setJSVariables()
  └ category=='view' && subtype=='view' 일 때만 VIEW_COLUMNS = setViewColumns(), VIEW_TYPE
  └ IDENTIFIER = setIdentifier(), PAGE_LIST/ADD/EDIT/VIEW/EXCEL_URI
MY_Builder_WEB::view($key)
  └ checkIdentifierExist() → jsVars.KEY
  └ subtype=='view' 일 때만 prepareViewData() + app-page-view.js 추가
      viewType   = subtype
      viewData   = reformat_form_data_by_type(VIEW_COLUMNS, viewType)
      identifier / actions / buttons / isComments
      입력형 컬럼(type!=='view')이 하나라도 있으면 addFormScripts()
      subPage    = builder/layout/view
  └ viewApp() → afterViewApp(): {class}_view_preset.js / _onload.js 자동 생성·로드
```

**브라우저** (`app-page-view.js`)

1. `applyViewData(common.KEY)` → `getData(KEY, {_mode:'view'})`. 동기 AJAX로 `GET {API_URI}/{key}?_mode=view`를 보낸다. 키가 숫자 하나면 경로에, 복합키면 쿼리스트링에 붙는다(`getUrlWithIdentifiers()`).
   - `API_URI`는 각 WEB 컨트롤러가 `addJsVars(['API_URI' => …])`로 직접 지정해야 한다(예: `controllers/admin/Partners.php`의 `__construct()`). 기본값은 `''`이다.
2. 응답 객체의 키마다 `#${key}` 요소를 찾아 채운다.
   - `appPlugins.view[key]`가 있으면 `console.log`만 하고 끝(**미구현** 훅, `builder-common.js`의 `appPlugins`)
   - `thumbnail`: 배경 이미지 / `uploads`: `{CURRENT_URI}/downloader/{file_id}` 다운로드 링크 목록
   - `SPAN`: `innerHTML = 값`(없으면 `-`) / 그 밖(input 등): `.value = 값`. **주의** — innerHTML이라 저장형 XSS 위험
3. `.view-form`이 있으면 `preparePlugins` → `fetchViewData` 커스텀 이벤트 → `refreshPlugins`를 실행하고 `body[data-onload]=true`로 설정한다.
4. `setButtonsDisplay()`: `isMyData()`로 `GET {API_URI}/isMyData/{key}`. 관리자가 아니고 `created_id`가 본인이 아니면 `.btn-view-edit`, `.btn-view-delete`를 제거한다(`MY_Builder_API::isMyData_get()`).
5. 버튼 핸들러(문서 ready 블록)
   - `.btn-view-list` → `PAGE_LIST_URI`로 이동
   - `.btn-view-edit` → `PAGE_EDIT_URI`가 있으면 `getUrlWithIdentifiers(PAGE_EDIT_URI, KEY)`로 이동
   - `.btn-view-delete` → `deleteData(KEY)`: SweetAlert 확인 → `DELETE {API_URI}/{key}` → `PAGE_LIST_URI`로 이동

**API** (`GET {API_URI}/{key}?_mode=view`)

| 단계 | 위치 | 내용 |
|---|---|---|
| 분기 | `MY_Builder_API::beforeGet()` → `afterGet()` | `idData`가 있으면 `view()`, 없으면 `list()` |
| 존재 확인 | `beforeView()` | 데이터가 없으면 `DATA_NOT_EXIST` |
| 조회 | `executeView()` | `Model->getDataWhere([], idData)` → `transformView()` |
| 변환(core) | `MY_Builder_API::transformView()` | `_mode=view`이면 `transTargetKeys['view']`의 필드 중 viewConfig에 `option_attributes`가 있는 것을 옵션 라벨로 바꿈. 이어서 `fileList` 필드를 파일 DTO 배열로 바꿈(`'0'`이면 null) |
| 변환(공통) | `api/Common::transformView()` | `'0000-00-00'` → `''`, `recent_dt = updated_dt ?: created_dt`, `created_id`/`updated_id`를 `Model_User`의 `id`(로그인 아이디)로 바꿈 |
| 모드별 | `api/Common::transformViewByMode()` | `form` 모드에서만 select2 multiple 값을 `explode(',')`. `view`는 아무것도 안 함(확장 지점) |
| 응답 | `afterView()`, `MY_Controller::setResponseData()` | 단건 객체를 `data:[obj]`로 감싸 반환. JS는 `response.data[0]` 사용 |

**목록에서 상세로 이동** (`app-page-list.js`)

- 행 action `view`(`.view-record` 클릭 핸들러) → `PAGE_VIEW_URI`가 있으면 그 페이지로, 없으면 `openViewModal()` 호출.
- 컬럼 `onclick.kind='view'`(`getColumnOnclick()`) → `attrs.href` 또는 `PAGE_VIEW_URI` + identifier. `attrs.target`이 `_blank`/`_self`가 아니면 `openViewModal(id, target)`.
- `openViewModal()`은 **미구현** — `getData()` 후 `console.log`만 한다. `view_modal.php`를 포함하는 곳도 없다.
- `PAGE_VIEW_URI`는 route `methods`에 `view` 키가 있고(`allows`) list 메소드의 `actions.view`가 true일 때 만들어진다(`method_list_base_config`의 기본값은 `false`). list `properties.viewConfig`가 있으면 만들지 않는다(이 키는 `method_list_base_config`에 정의되어 있지 않음). 자세한 것은 [list-filter.md](list-filter.md).

### 7. 설정 예시 (가상 예시)

저장소에는 실제 `view_*_config`가 없고, `category => 'view'`를 쓰는 route는 admin `dashboard`(`type=>'dashboard'`) 하나뿐이다. 아래는 코드가 읽는 키만으로 만든 **가상 예시**이다.

```php
// application/config/extra/admin/admin_view_config.php
$config['view_partners_config'] = [
    ['field' => 'id', 'subtype' => 'identifier'],                 // → hidden + IDENTIFIER
    ['field' => 'name', 'label' => 'lang:partners.name'],         // type 기본 'view' → <span id="name">
    ['field' => 'status', 'label' => 'lang:partners.status',
     'option_attributes' => ['option_type' => 'yn']],             // API transTargetKeys['view'] 지정 시 라벨 변환
    ['field' => 'memo', 'type' => 'textarea', 'colspan' => 12,    // 입력형 → setFormColumn() 경유, form 위젯 출력
     'help_block' => ['tag' => 'span', 'text' => '메모', 'attr' => ['class' => 'text-muted']]],
];

// application/config/extra/admin/admin_route_config.php (partners)
'methods' => [
    'list' => ['category' => 'list', 'type' => 'table', 'subtype' => 'datatable',
               'actions' => ['view' => true]],
    'view' => ['category' => 'view', 'subtype' => 'base', 'resourceConfig' => 'partners'],
],
```

> **주의** — 현재 코드에서는 `subtype => 'base'`라서 VIEW_COLUMNS가 만들어지지 않는다. API는 `resourceConfig`와 상관없이 `view_partners_config`(소문자 클래스명)를 읽는다.

---

## Part 2. Excel 가져오기 설정

> **현재 상태** — `MY_Builder_WEB::getExcelHeaders()`가 정의되지 않은 `$config`를 검사해 항상 `show_error`로 끝나므로, excel 화면은 그대로는 열리지 않는다. 자세한 항목은 [TODO.md](../../TODO.md#6-빌더-설정-조사-2026-10-23)를 참고한다.

이 부분은 **가져오기(업로드)** 화면만 다룬다. Excel/CSV **내보내기(export)**(`exports.csv/excel`, `MY_Builder_API::getExportsConfig()`, `prepareExcel()`, `setExcelValue()`)는 [list-filter.md](list-filter.md)를 참고한다.

### 1. 메소드 설정 (`method_excel_base_config`)

`builder_method_config.php`에 정의된다.

| 키 | 기본값 | 설명 | 사용 위치 |
|---|---|---|---|
| `category` | `'excel'` | `setJSVariables()`에 excel 분기가 없음(공통 URI와 IDENTIFIER만 설정됨) | `MY_Builder_WEB::setJSVariables()` |
| `mode` | `'import'` | baseMethod가 list인 클래스에서 메뉴 권한의 I(6번째 자리) 검사 | `MY_Builder_WEB::setupPlatformPage()`, `builder_auth_helper.php` `auth_char_map()` |
| `resourceConfig` | `''` | `excel_{resourceConfig}_config` 후보 | `getExcelHeaders()` |
| `actions.list` | `true` | `PAGE_LIST_URI` 생성 여부(`excel.php`에는 목록 버튼 없음) | `setJSVariables()` |
| `properties.templateDownload`, `preview`, `validateBeforeImport` | `true` | **미구현** — 읽는 코드 없음. 샘플 다운로드·미리보기·사전검증은 항상 켜져 있음 | – |

진입 경로: list 메소드의 `buttons.excel = true`(기본값 true) + 메뉴 권한 import + route `methods`에 `excel` 키가 있어야 한다.

- `setJSVariables()`가 `PAGE_EXCEL_URI = {href}/excel`을 만들고, `setListButtons()`가 import 권한으로 버튼을 거른다.
- 목록 툴바의 "Upload Excel" 버튼은 `app-page-list.js` `getListButtons()`가 출력한다.
- list의 `actions.excel`은 `setJSVariables()`에서 `buttons`와 병합될 때 `buttons.excel`에 덮어써지므로 사실상 효과가 없다.

### 2. 헤더 설정 (`excel_{name}_config`)

이름 후보는 `setConfigList('excel', resourceConfig)`로 만든다. URL 메소드가 `excel`이므로 `{method}`는 `excel`이다.

1. `excel_{resourceConfig}_config` (resourceConfig가 있을 때)
2. `excel_{class}_excel_config`
3. `excel_{class}_config`
4. `excel_excel_config`

항목 키(`getExcelHeaders()`가 정리하고 `excel.php`, `app-page-excel.js`가 사용):

| 키 | 타입 | 기본값 | 설명 | 사용 위치 |
|---|---|---|---|---|
| `field` | string | 필수(없으면 항목을 건너뜀) | 업로드 JSON의 키(DB 컬럼) | `getExcelHeaders()`, `excel.php`, `#excelFormSubmit` 클릭 핸들러 |
| `label` | string | `field` | `lang()`으로 번역. **엑셀 1행 헤더 텍스트와 정확히 같아야 함** | `getExcelHeaders()`, `#excelFile` change 핸들러 |
| `required` | bool | `false` | 샘플 헤더를 빨간 굵은 글씨로 표시. 빈 셀이면 `edit-required` 표시 후 저장 비활성화 | `getExcelHeaders()`, `getExcelSample()`, `#excelFile` change 핸들러 |
| 그 밖의 키 | – | – | 그대로 `excelHeaders` JSON에 포함(사용하지 않음) | `excel.php` |

설정이 없을 때 쓰도록 의도된 대체 경로(`getExcelHeaders()`의 else 분기): form 컬럼 중 `form=true`, hidden 아님, 시스템 컬럼(created/updated/del_yn/use_yn/recent_dt) 아님, `matches[...]` 규칙 없음 조건을 만족하는 것을 고르고, `required`는 rules에 `required`가 있는지로 정한다. **미구현** — 현재는 도달할 수 없고, 고쳐도 excel category에서는 `formColumns`가 비어 있다.

> **주의** — 저장소에 `excel` 메소드를 가진 route도 `excel_*_config`도 없다. 대부분의 admin route는 `buttons.excel=false`로 꺼 두었다.

### 3. 샘플 파일 생성

`MY_Builder_WEB::getExcelSample()`

- 경로: `public/sample/{class}_upload_sample.xlsx`. 파일이 이미 있으면 다시 만들지 않는다(캐시).
- PHPExcel(`application/libraries/Excel_lib.php`)로 1행에 라벨을 쓴다. required는 빨간 굵은 글씨, 열 너비 24, 헤더는 노란 배경 + 가운데 정렬, `A1:{마지막열}5`에 얇은 테두리. 열 문자는 `excel_helper.php` `number_to_alphabet()`로 만든다.
- 폴더가 없으면 `make_directory()`로 생성한다.
- 헤더 설정을 바꾼 뒤에는 화면의 "샘플 파일 리셋"을 눌러야 새로 만들어진다. `resetExcelFile()`이 `PATCH {API_URI}/deleteExcelFile`(`class=body[data-class]`)을 보내고 → `MY_Builder_API::deleteExcelFile_patch()`가 `unlink` → 새로고침한다. **주의** — `class` 값을 검증 없이 경로에 붙인다.

### 4. 화면과 업로드 흐름

**WEB** `MY_Builder_WEB::excel()`: head에 `jquery.tabledit.js`, `app-page-excel.js`, `xlsx.full.min.js@0.18.5`(cdnjs)를 넣고 → `excelHeaders`와 `sampleFile`을 뷰에 전달 → `builder/layout/excel.php`. 이 뷰는 `<script>const excelHeaders = JSON.parse('…')</script>`로 헤더를 JS에 넘긴다.

**브라우저** (`app-page-excel.js`, 문서 ready 블록의 핸들러들)

1. 파일 선택(`#excelFile` change 핸들러): `.xls`/`.xlsx`만 허용. SheetJS로 **첫 시트**를 `header:1` 배열로 읽는다.
2. 헤더 검사: 1행의 각 셀이 `excelHeaders[].label`에 있는지만 확인한다(없는 컬럼이면 오류).
3. 2행부터 빈 행을 빼고 테이블로 출력한다. required 빈 셀 수를 세고, 오류가 있으면 저장을 막고 "오류 바로가기"를 켠다.
4. Tabledit로 셀을 바로 수정한다(AJAX 저장은 막아 둠). 수정할 때마다 required를 다시 검사한다.
5. 저장(`#excelFormSubmit` 클릭 핸들러): 각 행을 `{excelHeaders[i].field: 셀 텍스트}`로 만든 뒤
   - `validateData()`: 50행씩 `POST {API_URI}/validateExcel`(JSON body)
   - 모두 통과하면 `submitData()`: 50행씩 `POST {API_URI}/uploadExcel` → 끝나면 성공 알림과 새로고침
   - 응답 코드가 `floor(code/10)` 기준 404x/409x이면 `emitErrorMessage()`가 `response.data`의 첫 키/값과 같은 셀을 강조

**API** (`MY_Builder_API`)

| 엔드포인트/함수 | 동작 |
|---|---|
| `validateExcel_post()` | `beforeExcelUpload()` → `DATA_AVAILABLE` |
| `uploadExcel_post()` | `beforeExcelUpload()` → `afterExcelUpload()` → `Model->addList($data)`(`Model_Common::addList()`: `getValidSetList` 후 `addListPDO`) → `DATA_CREATED`(201). 예외면 `WRITE_FILEDB_FAIL`(500) |
| `beforeExcelUpload()` | `raw_input_stream`을 JSON으로 디코드 → `validateExcelData()` |
| `validateExcelData()` | 기본은 그대로 반환(**검증 없음**). 하위 API 컨트롤러에서 오버라이드하는 확장 지점(현재 오버라이드한 곳 없음) |
| `deleteExcelFile_patch()` | 샘플 파일 삭제 |

> **주의**
> - 서버 검증·권한 검사 없이 `addList()`로 바로 INSERT된다(`validateExcel`/`uploadExcel` AJAX에는 `Authorization` 헤더도 없음).
> - 열 매핑이 위치 기반이다. 데이터는 엑셀 헤더가 아니라 설정 순서(`excelHeaders[k-1].field`)로 매핑되므로 엑셀 열 순서는 설정과 같아야 한다.
> - 행에 `id`가 없어 `submit-completed` 표시가 안 되므로, 중간 청크 실패 후 다시 저장하면 이미 들어간 행이 중복 INSERT된다.

### 5. form 규칙과의 관계

업로드 경로는 `validate()`, `checkUniqueExist()`, `defaultList`, `fileList`를 **거치지 않는다**. `index_post`의 `beforePost()`와 달리 form 설정의 `rules`([form.md](form.md))가 서버에서 적용되지 않는다.

form 설정과는 헤더 대체 경로(§2)와 `required` 판단에서만 연결되는데, 이 경로도 현재는 실행되지 않는다.

서버 검증이 필요하면 하위 API 컨트롤러에서 `validateExcelData()`를 오버라이드한다. 오류는 `emitErrorMessage()`가 읽을 수 있도록 `data: {field: value}`와 404x/409x 코드로 응답해야 한다.
