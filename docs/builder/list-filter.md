# List · Filter 설정 (list_config, filter_config)

엔티티의 목록 화면(DataTable)과 그 위의 검색 필터를 구성하는 `list_{name}_config`, `filter_{name}_config`의 동작을 설명한다.
설정 키가 어떤 코드에서 어떻게 해석되는지, 서버와 어떤 형식으로 통신하는지를 실제 코드(`v3` 브랜치) 기준으로 정리했다.
라우트·메소드 설정(버튼/액션/익스포트 플래그의 출처)은 [route-method-nav.md](route-method-nav.md), 필드 키 공통 규칙은 [form.md](form.md)를 참고한다.

**목차**

1. [전체 흐름](#1-전체-흐름)
2. [List 컬럼 설정](#2-list-컬럼-설정-list_name_config)
3. [onclick / onchange](#3-onclick--onchange)
4. [DataTable과 서버 통신](#4-datatable과-서버-통신)
5. [Filter 설정](#5-filter-설정-filter_name_config)
6. [API 쪽 list config](#6-api-쪽-list-config)
7. [예시](#7-예시)

---

## 1. 전체 흐름

```
[WEB] MY_Builder_WEB::_remap() → setupPlatform() → setJSVariables()
        ├─ LIST_BUTTONS / LIST_ACTIONS / LIST_EXPORTS   ← setListButtons() / setListActions() / setListExports() (권한 문자로 필터)
        ├─ LIST_COLUMNS                                  ← setListColumns() ← getListColumns()
        ├─ FILTER_COLUMNS                                ← setFilterColumns()
        └─ FORM_EXIST / FORM_DATA …                      (properties.formExist = true 일 때 사이드 폼)
      list() → prepareListData() → 뷰 builder/layout/list.php (+ filter.php)
      prepareListData() → addListScripts('datatable') → DataTables JS/CSS + app-page-list.js

[JS]  app-page-list.js : DataTable(serverSide) → GET {API_URI}?_mode=list&format=datatable&draw&pageNo&limit&filters…
[API] MY_Builder_API::beforeGet() → reformat_get_data()
      → api/Common::beforeList() (draw / recordsTotal / recordsFiltered / limit)
      → MY_Builder_API::executeList() → Model_Common::getList() → setCondition() → setFilter()
      → 응답 {status, code, msg, data:[…], errors, draw, recordsTotal, recordsFiltered}
```

실제로 로드되는 리스트 JS는 `public/assets/builder/js/app-page-list.js` 하나다(`MY_Builder_WEB::addListScripts()`). 저장소 루트의 `app-page-list.js`, `bakj.app-page-list.js`는 사용되지 않는다.

리스트 화면이 동작하려면 세 가지가 함께 필요하다.

1. route config의 method 설정: `category => 'list'`, `type => 'table'`, `subtype => 'datatable'` ([route-method-nav.md](route-method-nav.md)).
   - `subtype`이 `datatable`이 아니면 `addListScripts()`가 아무 스크립트도 붙이지 않는다.
   - `type`이 `table`이 아니면 `setJSVariables()`가 `LIST_*` JS 변수를 만들지 않는다(`FILTER_COLUMNS`는 생성).
2. list config: `list_{…}_config` (2장).
3. WEB 컨트롤러에서 `API_URI`(필요 시 `API_PARAMS`)를 `addJsVars`로 지정 (예: `controllers/admin/Administrators.php`).

---

## 2. List 컬럼 설정 (`list_{name}_config`)

### 2-1. 설정 파일 위치

| 구분 | 위치 |
|---|---|
| 기본값 | `config/extra/builder/builder_list_config.php`의 `builder_list_base` |
| 플랫폼별 | `config/extra/{flag}/*_config.php` 전부 자동 로드(`BuilderCommonTrait::loadConfigs()`) → `admin_list_config.php`, `web_list_config.php` |
| 공용 샘플 | `builder_list_config.php`의 `list_visitors_config`, `list_syscode_config`, `list_menu_auth_config` |

`$config['list_xxx_config']`는 **컬럼 배열(list type)** 이다. 원소 하나가 컬럼 하나다.

### 2-2. config 이름 탐색 순서 (WEB)

`MY_Builder_WEB::getListColumns()`가 `BuilderColumnsTrait::setConfigList('list', methodConfig.resourceConfig)`로 후보 목록을 만들고, `MY_Config::get()`이 **앞에서부터** 처음 존재하는 것을 사용한다.

| 순서 | 이름 | 비고 |
|---|---|---|
| 1 | `list_{resourceConfig}_config` | method config에 `resourceConfig`가 있을 때만 |
| 2 | `list_{class}_{method}_config` | |
| 3 | `list_{class}_config` | 일반적으로 이것을 사용 |
| 4 | `list_{method}_config` | |

- `{class}` = `snakeize(router->class)` (`partnerAuth` → `partner_auth`).
- `{method}` = `snakeize(router->method)`. 단 **method가 `index`이면 `list`로 치환**된다(filter는 치환하지 않음, 5-1).
  예: `/admin/administrators/list` → `list_administrators_list_config` → `list_administrators_config` → `list_list_config`.
- 아무것도 없으면 `show_error("… List Config is Empty For …")`.
- list 설정이 없으면 WEB·API 모두 form 설정의 `list => true` 항목으로 목록을 만든다(`BuilderColumnsTrait::getListConfigFromForm()`, 2026-10-26). 목록 표시는 `list_attributes`로 따로 줄 수 있다.

### 2-3. 컬럼 가공 과정 (WEB)

`MY_Builder_WEB::getListColumns()`:

1. 각 item에 `builder_list_base`를 `array_merge` → base의 모든 키가 item에 항상 존재한다.
2. `option_attributes`가 비어 있지 않으면 `options = getOptions(option_field ?? field, option_attributes)` (폼·필터와 같음).
3. `type === 'hidden'`이면 `list = false` → 화면 컬럼에서 빠지지만 `$this->listColumns`(identifier 수집용)에는 남는다.

`MY_Builder_WEB::setListColumns()`:

4. `list`가 truthy인 컬럼만 최종 컬럼으로 만든다.
   - `$attributes = builder_list_base + (item.list_attributes ?? [])`
   - **label 결정**: ① `list_attributes.label`이 있으면 그것, 없으면 item `label` → ② `lang:` 접두어 제거 → ③ 언어 파일에 `{label}_list` 키가 있으면 그것으로 교체(리스트 전용 라벨).
   - 최종 컬럼 = `array_merge($attributes, $item, ['field' => …, 'label' => …])`
5. `properties.isRowNumber`(기본 true)면 맨 앞에 `{label: 'lang:common.row_num', type: 'row_num'}` 컬럼 추가.
6. 활성 row action이 1개 이상이면 맨 뒤에 `{label: 'lang:common.actions', type: 'actions'}` 컬럼 추가.

### 2-4. Identifier 컬럼

- `subtype => 'identifier'`인 컬럼의 `field`가 JS `common.IDENTIFIER`가 된다(`MY_Builder_WEB::setIdentifier()`).
  - 우선순위: route `properties.identifier` → method `properties.identifier` → `listColumns`(hidden 포함) 중 subtype이 `identifier`인 컬럼 자동 수집.
- 관례: `type => 'hidden'` + `subtype => 'identifier'` → 화면에서는 빠지고 row action, 인라인 PATCH 등에 쓰인다.
- API 응답 row에 해당 필드가 반드시 있어야 한다(JS `getIdentifiersData()`가 `full[field]`로 읽음).
- `IDENTIFIER`가 비어 있으면 view/edit/delete/duplicate 클릭 시 `Identifier is not defined` 예외.

### 2-5. 컬럼 item 키

> **우선순위: 기본값 < 항목(top-level) 값 < `list_attributes`.** 최종 컬럼은 `array_merge($item, $item['list_attributes'], …)`이다(item에는 이미 base 기본값이 채워져 있음). form 설정 항목을 목록에도 쓸 때 `list_attributes`로 목록 표시(type·onclick·render·classes 등)를 따로 줄 수 있다. (2026-10-26 전에는 항목 값이 `list_attributes`를 덮어써 label 외에는 무시됐음)
>
> **렌더링은 `type`이 결정한다(`format` 아님). class 키는 `classes`(배열)이다(`class_name` 아님).** 이전 문서의 `list_attributes.format`, `list_attributes.class_name` 설명은 실제 코드와 다르다.

| 키 | 타입 | 기본값 | 설명 | 사용 위치 |
|---|---|---|---|---|
| `field` | string | `''` | API row의 키. DataTable `columns[].data` | `setListColumns()`, DataTable 초기화 |
| `label` | string | `''` | `lang:xxx.yyy` 권장. `{label}_list` 언어 키 우선. **주의** — 미지정 시 빈 라벨 | `setListColumns()`, `list.php` |
| `list` | bool | `true` | false면 컬럼 제외. `type=hidden`이면 강제 false | `getListColumns()` |
| `type` | string | `'text'` | **셀 렌더링 방식**(2-6). `hidden`, `text`, `button`, `icon`, `img`, `checkbox`, `select` (+ 내부용 `row_num`, `actions`) | DataTable `columnDefs`, `renderColumn()` |
| `subtype` | string | `'base'` | `identifier`(식별자), `boolean`(checkbox 전용) | `setIdentifier()`, `renderColumn()` |
| `format` | string | `'text'` | **미구현** — 어디에서도 읽지 않음. 렌더링은 `type` | – |
| `icon` | string | `''` | `type=icon`일 때 `<i class="{icon}">` | `renderColumn()` |
| `text` | string | `''` | `type=text/button`일 때 데이터 대신 표시할 고정 텍스트(locale 키). `onclick.kind=download`이고 비어 있으면 `'Download'` | `renderColumn()` |
| `classes` | array | `[]` | 셀 wrapper에 추가할 class 목록 | `renderColumn()` |
| `onclick` (`onClick`) | dict | `[]` | 셀 클릭 동작 (3장) | `renderColumnHTML()`, `getColumnClickAction()` |
| `onchange` (`onChange`) | **string** | 없음 | `type=select` 전용. change 시 호출할 **전역 함수명**. 기본 `afterSelectColumnChange` | `renderSelectColumn()` |
| `render` | dict | `[]` | `{callback: '함수명', params: mixed}` 커스텀 렌더러 (2-7) | DataTable `columnDefs` |
| `option_attributes` | dict | `[]` | `type=select` 셀의 옵션 소스. form과 같은 규칙(`MY_Controller::getOptions()`, `option_field` 포함) | `getListColumns()` |
| `options` | dict | `[]` | (자동 생성) `{value: text}` | `renderSelectColumn()` |
| `list_attributes` | dict | 없음 | 목록 전용 값. 항목 값보다 우선 | `setListColumns()` |

DataTable 컬럼 정의의 `width`, `orderable`, `searchable`, `visible`, `className` 등을 설정으로 조절하는 키는 **없다**. 데이터 컬럼은 모두 `searchable: true, orderable: false`로 고정된다.

### 2-6. `type`별 셀 렌더링 (`app-page-list.js`)

`row_num`, `actions`, `select`가 아닌 컬럼의 렌더 우선순위(DataTable 초기화의 `columnDefs` default 분기):

1. `render.callback`이 전역 함수로 존재 → `window[callback](data, type, full, meta, column, render.params ?? null)` 반환값 사용. 함수가 없으면 `console.warn` 후 다음 단계.
2. `window['renderColumn' + pascalize(field)]` 함수가 있으면 → `fn(data, type, full, meta, column)`. (페이지별 JS에서 필드명 규칙으로 렌더러를 정의하는 확장점)
3. 기본 `renderColumn(data, type, full, meta, column)`.

| `type` | wrapper | 내용(inner) | 비고 |
|---|---|---|---|
| `row_num` | – | `recordsDisplay - (row + displayStart)` (내림차순 번호) | `searchable/orderable: false` |
| `actions` | – | `getListActions(LIST_ACTIONS, full, IDENTIFIER)` | 마지막 컬럼 (4-4) |
| `select` | `div.bootstrap-select > select.datatable-selectpicker` | `options`로 `<option>` 생성, `data === value`면 selected | `renderSelectColumn()`. change 시 `{onchange}(idData, field, this.value)`. `drawCallback`에서 selectpicker 초기화. **주의** — 엄격 비교라 숫자 값은 선택 안 됨 |
| `text` (기본) | `span.d-inline-block` | `text`가 있으면 `getLocale(text)`, 없으면 `data` | 값은 텍스트 노드로 넣는다(HTML로 해석하지 않음) |
| `button` | `button.btn.btn-sm.btn-info…` | `text` 또는 `getLocale(field)` | `data === undefined`이거나, onclick이 popup/redirect/download인데 data가 비면 `'-'` |
| `icon` | `span` | `<i class="{icon}"></i>` | 값은 `data-value` 속성에만 |
| `img` | `span` | `<img class="img-thumbnail…" src="{data[0].file_link}">`, `data-bs-content`에도 동일 HTML | data가 `[{file_link…}]` 배열이어야 함(API `fileList` 변환 결과) |
| `checkbox` | `label` | `subtype=boolean`일 때만 `<input type=checkbox value=1>`(`data === 1`이면 checked). change 시 `afterCheckboxColumnChange(idData, field, 0/1)` | **주의** — 다른 subtype이면 `undefined` 출력 |
| 그 외 | `span` | `'-'` | |

공통 처리(`renderColumn()`, `renderColumnHTML()`):

- `field`가 `recent_dt`, `created_dt`, `updated_dt`이면 type과 무관하게 `YYYY-MM-DD`로 포맷, 값이 없으면 `'-'`.
- `full[field]`가 배열이면 원소마다 `renderColumnHTML()`을 반복해 `<div>…</div>`로 감싼다(파일 목록 → 파일별 다운로드 링크).
- wrapper에 `data-identifiers`(IDENTIFIER JSON), `data-value`(값, 객체면 JSON) 속성을 붙인다.
- 최종적으로 `wrap.innerHTML = inner` — `text` 타입은 API 값을 HTML로 그대로 삽입한다.

### 2-7. 커스텀 렌더러 시그니처

```js
// list config: 'render' => ['callback' => 'renderMenuAuthCheckbox', 'params' => [...]]
function renderMenuAuthCheckbox(data, type, full, meta, column, params) {
  // data   : 셀 값 (full[column.field])
  // type   : DataTables render type ('display' 등)
  // full   : row 전체 객체
  // meta   : {row, col, settings}
  // column : LIST_COLUMNS 원소(가공된 컬럼 설정)
  // params : render.params, 없으면 null
  return renderColumnHTML(data, full, column, wrap, inner); // 공용 래퍼 재사용 가능
}
```

실제 예: `public/assets/builder/js/app-page-menu-auth.js` `renderMenuAuthCheckbox()`.
`renderColumn{PascalField}(data, type, full, meta, column)`는 `params` 인자가 없다.

---

## 3. onclick / onchange

### 3-1. `onclick` (`getColumnClickAction()`)

`onclick`(또는 `onClick`)이 비어 있지 않으면 `renderColumnHTML()`이 wrapper에 `cursor-pointer` class와 클릭 동작을 담은 `data-cell-click`(JSON) 속성을 붙인다. 실행은 `bindListCellEvents()`의 위임 이벤트가 한다(inline `onclick` 문자열을 만들지 않음). 이동 주소가 `javascript:`·`data:`·`vbscript:`면 실행하지 않는다. `kind`가 없으면 예외.

| 키 | 타입 | 기본값 | 설명 |
|---|---|---|---|
| `kind` | string | (필수) | `view`, `popup`, `redirect`, `download`, `bs`, 그 외(전역 함수명) |
| `attrs` | dict | – | kind별 의미가 다름(아래) |
| `params` | mixed | – | view: identifier 필드 목록 대체 / custom: 콜백 2번째 인자 / redirect: `_blank` 판정 조건 |
| `noValue` | bool | `false` | false면 값이 빈 셀은 inner를 비운다. `bs`에서는 값이 없어도 속성을 붙일지 여부 |

| kind | 동작 |
|---|---|
| `view` | URL 이동: `attrs.href` → 없으면 `PAGE_VIEW_URI`. URL = `getUrlWithIdentifiers(uri, ids)` (ids = `params` 필드 목록 또는 `IDENTIFIER`). `attrs.target`: `_blank` → 새 창, `_self`(기본) → 현재 창, 그 밖의 값 → `openViewModal(ids, target)`. URL이 없으면 `openViewModal(ids)`. `type ≠ button`이면 `text-primary text-decoration-underline` 추가 |
| `popup` | **미구현** — 빈 onclick만 붙는다 |
| `redirect` | 셀 값 주소로 이동. `attrs.target === '_blank'`면 새 창 |
| `download` | 값이 `{file_id}` 객체여야 함 → `location.href = "{CURRENT_URI}/downloader/{file_id}"` (`MY_Controller::downloader()`). 파일 배열이면 파일별 링크. `text` 미지정 시 `'Download'` |
| `bs` | onclick 없음. `attrs`의 1단계 키를 `data-bs-{key}`로 부여 (`{toggle: 'modal', target: '#m'}` → `data-bs-toggle="modal" data-bs-target="#m"`). 값이 비면(noValue=false) 부여 안 함. 중첩 `attrs.attrs`는 지원 안 함 |
| 그 외(custom) | `window[kind]`가 함수가 아니면 아무것도 안 함. 함수면 `kind(element, params)` 호출. `element` = 클릭된 셀 wrapper 요소, `params` = 설정 값 그대로(없거나 빈 값이면 `null`) |

`openViewModal(dataId, modalId)`는 **미구현** 스텁이다(`getData()`로 조회 후 `console.log`만 함).

```php
[
    'field' => 'subject',
    'label' => 'lang:article.subject',
    'onclick' => ['kind' => 'view'],   // PAGE_VIEW_URI 가 있으면 상세 페이지로, 없으면 openViewModal()
],
// 새 창으로 열기
'onclick' => ['kind' => 'view', 'attrs' => ['target' => '_blank']],
```

### 3-2. `onchange` (select 셀 전용)

- `type => 'select'` 컬럼에서만 동작하며 값은 **함수명 문자열**이다: `'onchange' => 'myChangeHandler'`. dict(`{kind, attrs, params}`)를 넣으면 `[object Object](…)`가 되어 동작하지 않는다.
- 호출 시그니처: `fn(idData, field, value)` — `idData`는 IDENTIFIER 객체. select에 `data-onchange`(함수명)·`data-identifiers`·`data-field`를 붙이고 위임 이벤트가 부른다(inline `onchange` 없음).
- 기본 `afterSelectColumnChange()`: `PATCH {API_URI}/{id}`(복수 식별자면 query string)에 `{[field]: value}` 전송 후 성공 알림.
- `type=checkbox, subtype=boolean`의 기본 핸들러 `afterCheckboxColumnChange(idData, field, 0|1)`도 같은 방식으로 PATCH한다.

> **주의**
> - 화면별 커스텀 렌더러가 `renderColumnHTML(data, full, column, wrap, inner)`에 `inner`를 HTML 문자열로 넘기면 그대로 HTML로 들어간다(호환용). 서버 값은 DOM 노드(`document.createTextNode` 등)로 만들어 넘긴다. 체크박스는 `createListCheckbox(full, column, checked, disabled, funcName)`를 쓴다.
> - `afterSelectColumnChange()`는 성공 후 테이블 reload가 콜백이 아닌 즉시 실행된다.

---

## 4. DataTable과 서버 통신

### 4-1. 화면 구조 (`views/builder/layout/list.php`)

- `{platformName}/layout/filter` 포함.
- `<thead>`: 반응형 컨트롤용 빈 `<th>` 1개 + (`isCheckbox`면) 빈 `<th>` 1개 + `LIST_COLUMNS` 라벨(`lang()`).
- JS는 `th 개수 === 1 + LIST_CHEKBOX + LIST_COLUMNS.length`가 아니면 예외를 던진다. 커스텀 뷰를 만들 때 반드시 맞춰야 한다.
- `formExist`면 `#offcanvasRecord` 오프캔버스 + `form_side` 렌더 (4-7).

### 4-2. JS 전역 변수 (`MY_Builder_WEB::setJSVariables()`)

| 변수 | 출처 | 내용 |
|---|---|---|
| `LIST_COLUMNS` | `setListColumns()` | 2장 |
| `LIST_CHEKBOX` (철자 그대로) | `properties.isCheckbox` (기본 false) | true면 두 번째 컬럼에 `dt-checkboxes` 체크박스. **미구현** — 선택값을 읽는 코드 없음 |
| `LIST_PAGING` | 항상 `true` 하드코딩 | DataTable `paging`. 설정으로 끌 수 없음 |
| `LIST_ACTIONS` | `setListActions(actions)` → true인 키 목록 | row action (4-4) |
| `LIST_BUTTONS` | `setListButtons(buttons)` | 상단 버튼 (4-5) |
| `LIST_EXPORTS` | `setListExports(exports)` | Export 드롭다운 (4-6) |
| `LIST_OPTIONS` | method `properties` 전체 | JS 사용처 없음 |
| `LIST_PLUGIN` | `properties.plugin` | JS 사용처 없음 |
| `FILTER_COLUMNS` | `setFilterColumns()` | 5장 |
| `FORM_EXIST`, `FORM_DATA`, `FORM_TYPE`, `FORM_STYLE`, `FORM_REGEXP` | `properties.formExist === true`일 때 | 사이드 폼 (4-7) |
| `IDENTIFIER` | `setIdentifier()` | 2-4 |
| `PAGE_{LIST,ADD,EDIT,VIEW,EXCEL}_URI` | `setJSVariables()` | 해당 action/button이 false이거나 route `allows`에 없으면 `''`. list 메소드에 `formConfig`가 지정되면 ADD/EDIT URI, `viewConfig`가 지정되면 VIEW URI도 `''` |
| `API_URI`, `API_PARAMS` | 각 WEB 컨트롤러 `addJsVars` | ajax 대상 / 추가 파라미터 |

method config 기본값은 `method_list_base_config` + `method_list_table_datatable_config`(`builder_method_config.php`)가 `BuilderCommonTrait::fillMethodConfigProperties()`로 채운다([route-method-nav.md](route-method-nav.md)).

### 4-3. Ajax 요청 / 응답

요청(DataTable 초기화의 `ajax`, `app-page-ajax.js` `getAjaxOptions()`): `GET {API_URI}`, 헤더 `Authorization: HOOK_PHPTOJS_VAR_TOKEN`, **동기 요청**(`async` 미지정 시 false).

| 파라미터 | 값 |
|---|---|
| `_mode` | `'list'` |
| `...API_PARAMS` | 컨트롤러가 준 값(예: `user_cd=USR001`) — 서버에서 `where`로 변환 |
| `format` | `'datatable'` |
| `draw` | DataTables draw 카운터 |
| `pageNo` | `floor(start / length)` (0부터) |
| `limit` | `length` (페이지 크기, 기본 10) |
| `searchWord`, `searchCategory` | 항상 `''` (dom에서 검색창 `f`가 주석 처리됨) |
| `filters` | `getFilterData('#formFilter', isFirstDraw)` 결과 (5-6) |

`ajax.data`가 새 객체를 반환하므로 DataTables 기본 `order/columns/search` 파라미터는 **전송되지 않는다**. `order: [[1+LIST_CHEKBOX, 'desc']]`는 의미가 없고, 정렬은 모델 기본값(`MY_Model::orderBy()`: auto-increment identifier DESC, 없으면 `created_dt` DESC)을 따른다.

서버 처리:

1. `MY_Builder_API::beforeGet()` → `reformat_get_data($data, exceptValidateKeys)` (`builder_api_helper.php`)
   - `exceptValidateKeys` = `_mode, _event, _, select, format, draw, pageNo, limit, searchWord, searchCategory, filters`를 제외한 **truthy 파라미터는 전부 `where[field] = value`**.
   - `filters` → `filter.{where|whereIn|like|date}` (5-6). `render`, `select`는 그대로 전달.
2. `api/Common::beforeList()`: `format`이 `datatable` 또는 `log`일 때
   - `extraFields.draw = (int)draw`
   - `extraFields.recordsTotal = Model->getCnt($data)`, `extraFields.recordsFiltered = Model->getCnt($data)`
   - `limit !== -1`이면 `data.limit = {limit: limit ?: 10, offset: pageNo * limit}`
3. `MY_Builder_API::executeList()` → `Model->getList(select, $data)` → `Model_Common::setCondition()`이 `where, whereIn, whereNot, like, orLike, limit, orderBy, groupBy, filter, join` 키를 처리.
4. `MY_Builder_API::transformList()` → row마다 `transformView()`: 모델 `fileList` 필드는 파일 DTO 배열로 치환(img/download 셀용). `api/Common::transformView()`에서 `0000-00-00` → `''`, `recent_dt` 계산, `created_id/updated_id` → 사용자 id.
5. `MY_Builder_API::afterList()` → `MY_Controller::response()` / `setResponseData()`:

```json
{
  "status": true, "code": 2000, "msg": "...",
  "data": [ { "administrator_id": 1, "id": "admin", "name": "...", "...": "..." } ],
  "errors": [],
  "draw": 1, "recordsTotal": 12, "recordsFiltered": 12
}
```

`extra`의 키가 최상위로 펼쳐지므로 DataTables가 그대로 읽는다. `code/10`이 200이 아니면 ajax `complete`에서 경고 알림을 띄운다.

> **주의**
> - `recordsTotal`도 필터가 적용된 조건으로 계산되어 항상 `recordsFiltered`와 같다.
> - ajax가 동기로 실행되어 조회 중 UI가 멈춘다.

### 4-4. Row actions (`LIST_ACTIONS`)

플래그의 출처는 method config `actions`([route-method-nav.md](route-method-nav.md)). 아이콘/핸들러는 `getListActions()`와 DataTable 초기화 블록의 click 바인딩.

| 키 | 기본값 | 권한 문자 | 클래스 | 클릭 동작 |
|---|---|---|---|---|
| `view` | false | read | `.view-record` | `PAGE_VIEW_URI`가 있으면 이동, 없으면 `openViewModal()`(**미구현**) |
| `edit` | true | update | `.edit-record` | FORM_EXIST: 사이드 폼 edit 모드 + `fetchFrmValues()` / 아니면 `PAGE_EDIT_URI`로 이동 |
| `delete` | true | delete | `.delete-record` | `deleteData()` → 확인창 → `DELETE {API_URI}/{id}` → `dt.ajax.reload(null, false)` |
| `duplicate` | false | **주의** — 권한 체크 없음 | `.duplicate-record` | **FORM_EXIST일 때만 바인딩**: `GET {API_URI}/duplicate/{id}` 후 알림 |

- 권한: `setListActions()` → `is_auth_avaliable(mode, pageAuth)` (`builder_auth_helper.php`). `pageAuth`는 6자리 문자열 `C R U D E I`(create/read/update/delete/export/import, `auth_char_map()`), 초기값 `INIT_AUTH_CHAR = '111111'`.
- `setListActions()`가 true인 키만 남기므로 false 키(예: route의 `'excel' => false`)는 JS에 전달되지 않는다. true인데 아이콘이 정의되지 않은 키는 `getListActions()`가 경고 후 건너뛴다.
- 식별자가 1개면 `data-id`, 여러 개면 `data-{field}` 속성 → `getRedirectActionData()`가 다시 읽는다. `getUrlWithIdentifiers()`는 식별자 1개 + 숫자면 `/{id}`, 그 외엔 query string을 만든다.
- `prepareListData()`가 formExist=false일 때 뷰 변수 `$actions`에서 `allows`에 없는 action을 제거하지만, list.php/JS는 이 값을 쓰지 않고 `LIST_ACTIONS`를 사용하므로 효과가 없다.

### 4-5. 상단 버튼 (`LIST_BUTTONS`)

`getListButtons()`가 만든다. 순서: Export 드롭다운 → 커스텀 버튼들 → excel → add.

| 키 | 기본값 | 권한 | 동작 |
|---|---|---|---|
| `add` | true | create (`setListButtons()`) | `FORM_EXIST`가 아니고 `PAGE_ADD_URI`가 있으면 이동, 아니면 `readyFrmInputs(formRecord, 'add')` → 오프캔버스 열림 |
| `excel` | true | import (`setListButtons()`) | "Upload Excel" → `PAGE_EXCEL_URI`(엑셀 업로드 화면, [view-excel.md](view-excel.md)) |
| 그 외(커스텀) | – | 없음 | dict `{text, classes, action, params}` → 클릭 시 `window[action](this, params ?? null)` |

```php
'buttons' => [
    'resetDB' => [
        'text' => 'DB 초기화',                                                 // getLocale 처리
        'classes' => ['btn','btn-warning','waves-effect','waves-light','my-5'], // 필수(없으면 TypeError)
        'action' => 'resetMenuAuthDB',                                          // 전역 함수명
        // 'params' => [...],
    ],
],
```

### 4-6. Exports (`LIST_EXPORTS`)

- 키: `print, csv, excel, pdf, copy` (기본 전부 false). `setListExports()`는 `export` 권한이 없으면 전부 false로 덮는다.
- 하나라도 true면 `getListExports()`가 "Export" collection 드롭다운을 만든다.
- 별도의 `export_{class}_config` 같은 export 전용 설정은 **없다**.

| kind | 구현 |
|---|---|
| `print`, `pdf`, `copy` | DataTables 내장 버튼(클라이언트, **현재 페이지 데이터만**). **주의** — `exportOptions.columns`가 템플릿 값으로 하드코딩 |
| `csv`, `excel` | 서버 생성: `prepareExports(kind, getFilterData('#formFilter'))` |

서버 Export 흐름:

1. JS `prepareExports()` (`app-page-common.js`): `GET {API_URI}/prepareExports?{API_PARAMS}` + `exportType = (kind === 'csv' ? 'csv' : 'xlsx')`, `filters`.
2. `MY_Builder_API::prepareExports_get()`
   - `exportType`이 `csv|xlsx`가 아니면 BAD_REQUEST.
   - `reformat_get_data()`로 where/filter 구성 — **limit 없음 → 필터된 전체 데이터**.
   - **컬럼 결정 `getExportsConfig()`**: `form_{snakeize(class)}_config`가 있으면 그 form 컬럼 전체(`setFormColumns()`), 없으면 `list_{snakeize(class)}_config`. 이후 `type=common`, `type=file`, `subtype=identifier`만 제외한다. list 설정이나 `list` 플래그와 무관하며 hidden/password 필드도 포함된다.
   - 헤더 `getExportsHeader()`: `num => 'No.'` + `field => lang(label)`.
   - 데이터 타입 `getExportsDataTypes()`: `type`이 `int`/`float`/`date`인 필드는 숫자/날짜 셀, `option_attributes`가 있으면 옵션 텍스트로 치환, `type=checkbox`면 구분자(`option_attributes.seperator`, 기본 `,`)로 나눠 각각 치환.
   - 데이터 `getExportsDataset()` → `addExtraDataset()`(하위 컨트롤러 확장점).
   - 파일 경로 `prepareExportsPath()`: `public/temps/` + `encryption->encrypt("{APP_NAME}_{class}_{YmdHis}.{ext}")` (`+`→`-`, `/`→`_`).
   - xlsx: `prepareExcel()`(PHPExcel `Excel2007`, 헤더 굵게/회색, 열 너비 20, 테두리). csv: `prepareCSV()`(`fputcsv`, 콤마, BOM 없음).
   - 응답 `{code: FILE_CREATED, data: [{filename: "<암호화된 파일명>"}]}`.
3. JS가 숨김 iframe `#downloadFrame`(`views/builder/includes/tail.php`)의 `src`를 `{API_URI}/downloadExports?filename=…`로 지정.
4. `MY_Builder_API::downloadExports_get()`: 파일 존재 확인 → 복호화해서 원래 파일명 획득(실패 시 `show_alert` 후 종료) → 내용 읽고 **즉시 삭제** → `force_download(원래 이름)`.

> **주의**
> - 서버(`prepareExports_get()`)에 export 권한 검사가 없다. 버튼 노출만 `setListExports()`로 제어된다.
> - form config 전체가 기준이라 password·API key 같은 필드도 내보내질 수 있다.
> - 임시 파일이 웹 공개 경로 `public/temps/`에 저장된다.
> - CSV에 BOM이 없어 Excel에서 한글이 깨질 수 있다.

### 4-7. 사이드 폼 연동 (`properties.formExist = true`)

- `setJSVariables()`: `formColumns = setFormColumns(properties.formConfig)`. `formConfig`가 비면 form config 이름 규칙으로 탐색(`form_{class}_config` 등, [form.md](form.md)).
- `prepareListData()`: `formData/formType/formSubType/formStyle` 뷰 변수 + `addFormScripts()`.
- JS(DataTable 초기화 블록의 `if (common.FORM_EXIST)` 분기):
  - 초기화: `onLoadedLayout → preparePlugins → resetFrmInputs`, `bootstrap.Offcanvas` 생성.
  - add 버튼: `readyFrmInputs(form, 'add')` → `readyFrmInputs` 이벤트에서 mode가 edit이 아니면 오프캔버스 show.
  - edit 아이콘: `readyFrmInputs(form, 'edit')` + `fetchFrmValues()` → `fetchFrmValues` 이벤트에서 `applyFrmValues()` 후 show.
  - 오프캔버스 제목 `"{Mode} Record"`. 닫히면(`hidden.bs.offcanvas`) `resetFrmInputs()`.
  - FormValidation 통과 → `submitAjax()` → 성공 시 `dt.ajax.reload(null, false)` + `transFrmValues` 이벤트로 닫힘. 422면 필드별 서버 에러 표시.

### 4-8. 기타 DataTable 옵션 / 확장점

- `scrollX, scrollCollapse, processing, serverSide: true`, `responsive`(모달 상세, 제목이 빈 컬럼 제외).
- 정보 문구 `검색결과 총 _TOTAL_ 개 데이터 중 _START_ ~ _END_ 표시` 하드코딩.
- 로딩: `preXhr.dt` → `#loading` show, `xhr.dt` → hide.
- **훅**: `appPlugins.list.datatable.{preDrawCallback, initComplete, drawCallback, rowCallback}`(`builder-common.js`)에 함수를 지정하면 해당 시점에 호출된다.
- 필터 Enter: `#filter-search_word`에서 Enter → `#filter-search_category` 값이 있을 때만 검색 버튼 클릭.

---

## 5. Filter 설정 (`filter_{name}_config`)

### 5-1. 위치와 이름 탐색

- 기본값: `builder_filter_config`(`builder_filter_config.php`). 필드 기본값: `builder_form_filter_base`(`builder_form_config.php`, = `builder_form_base` + `icon: null` + `filter_attributes.type: 'where'`).
- 플랫폼별: `admin_filter_config.php` 등. 샘플: `builder_filter_config.php`의 `filter_logs_errors_config`, `filter_logs_user_access_config`, `filter_menu_auth_config`.
- 탐색(`MY_Builder_WEB::setFilterColumns()` → `setConfigList('filter', properties.filterConfig)`):
  1. `filter_{properties.filterConfig}_config`
  2. `filter_{class}_{method}_config`
  3. `filter_{class}_config`
  4. `filter_{method}_config`
  - filter는 method가 `index`여도 `list`로 치환되지 **않는다**.
- 없으면 `FILTER_COLUMNS = []`, 필터 영역을 출력하지 않는다.

### 5-2. 최상위 키

`fill_config_properties($filterConfig, builder_filter_config)`로 누락 키만 채운다.

| 키 | 타입 | 기본값 | 설명 |
|---|---|---|---|
| `filters` | list | `[]` | 필터 필드 목록 (5-3) |
| `help_block` | dict | `[]` | `{tag: 'span', text: '…', attr: […]}` → 필터 제목 아래 출력(`get_help_block()`). **주의** — `attr` 없으면 Notice |
| `actions.submit` | bool | `true` | 검색 버튼 출력 |
| `actions.reset` | bool | `true` | 초기화 버튼 출력 |
| `buttons` | dict | `[]` | **미구현** — 사용처 없음 |

### 5-3. 필터 필드 키

각 필드는 `builder_form_filter_base`와 `array_merge`된 뒤 form 필드처럼 `get_admin_form_attributes($item, 'common')`으로 HTML 속성이 만들어진다(`setFilterColumns()`). 즉 **form config의 type/subtype/attributes/form_attributes/option_attributes 규칙을 그대로 따른다** — 공통 키는 [form.md](form.md) 참고. 아래는 필터에서 의미가 있는 키만 정리했다.

| 키 | 타입 | 기본값 | 설명 |
|---|---|---|---|
| `field` | string | `''` | 필드명(테이블 컬럼). `user.user_id`처럼 테이블 지정 가능 |
| `label` | string | `''` | `lang:` 형식 가능 |
| `type` | string | `'text'` | `select`, `text`, `date`, `password`, `checkbox`, `radio`, `textarea`, `file`, 기타 input type. `common`이면 가공 생략 |
| `subtype` | string | `'base'` | `selectpicker`, `select2`, `flatpickr` 등(플러그인 결정) |
| `id` | string | `filter-{field의 .을 _로}` | `get_form_item_id()` |
| `colspan` | int | `FILTER_BASE_COLSPAN`(=3) | bootstrap `col-md-{n}`. **주의** — 상수가 `admin_form_constants.php`에만 정의됨 |
| `default` | mixed | `''` | `data-default-value` → 초기/초기화 시 적용 + 첫 조회에 포함 |
| `attributes` | dict | `[]` | `placeholder`(점 포함 시 lang 키), `multiple` 등 |
| `form_attributes` | dict | base form attrs | `set_default_first`(첫 옵션을 기본값으로), `change_after: {callback, params}`(변경 시 전역 함수 호출) 등 → `data-*` |
| `option_attributes` | dict | `[]` | select 옵션 소스(`option_type`, `option_field`, `option_data`, `render`). 필터에서는 `option_field`가 사용된다. `option_type`이 `default/bool/yn/gender`면 맨 앞에 `'*' => 'All'` 추가 |
| `filter_attributes.type` | string | `'where'` | 서버 조건 종류: `where`, `like`, `date` (`whereIn`도 `where`와 동일하게 처리) |
| `filter_attributes.subtype` | string | – | input `name`의 대괄호 키. 없으면 `field` |

**input name 규칙**: `{filter_attributes.type}[{subtype ?: field}]`

| 설정 | name | 의미 |
|---|---|---|
| `type: 'where'` | `where[log_threshold]` | `field = 값`. `select[multiple]`이면 자동으로 `whereIn` |
| `type: 'like', subtype: 'field'` | `like[field]` | 검색 대상 컬럼을 고르는 select(값 = 컬럼명) |
| `type: 'like', subtype: 'value'` | `like[value]` | 검색어. `like[field]`와 짝 |
| `type: 'like'` (subtype 없음) | `like[{field}]` | 해당 필드 `LIKE '%값%'` |
| `type: 'date'` + field `on_date` / `start_date` / `end_date` / `date_range` | `date[on_date]` 등 | **이 4개 이름만** JS가 읽는다. 비교 컬럼은 필드명이 아니라 모델의 `filteringDateColumn`(기본 `created_dt`) |

### 5-4. 렌더링 (`views/builder/layout/filter.php`)

- `prepareListData()`: `filters = reformat_filter_data(FILTER_COLUMNS, filterConfig)`
  - 누적 `colspan`이 12 이상이 되면 다음 필드부터 새 행.
  - 마지막 행에 검색/초기화 버튼 블록(`col-md-3`)을 붙이고 남는 칸은 `space` 블록으로 채운다. 자리가 없으면 새 행에 `space(9)` + 버튼.
- `<form id="formFilter" class="form-type-filter">` + hidden `_onloaded=0`.
- 필드는 `form-floating` 안에 type별 `form_dropdown/form_input/…` + `form_label(lang(label))`. 버튼 문구 `검색`/`초기화`는 하드코딩.

### 5-5. 필터 폼 생애주기 (JS)

`activateFilterForm('#formFilter', reloadDatatable, dtApi)`(`app-page-form.js`)는 DataTable `preDrawCallback`마다 호출되지만 `_onloaded`가 0일 때만 초기화한다.

1. `preparePlugins` 이벤트에서 `_onloaded = 1` 설정.
2. `onLoadedLayout → preparePlugins → resetFrmInputs(form, FILTER_COLUMNS) → readyFrmInputs(form, 'filter', FILTER_COLUMNS) → refreshPlugins`.
   - `readyFrmInputs()`의 `'filter'` 모드: `[data-default-value], [data-set-default-first]` 값을 모아 `applyFrmValues(form, data, fields, true)` 호출 — filter 모드에서는 `triggerEvent = true`(add 모드는 false).
3. `.btn-search` 클릭: `start_date > end_date`면 경고 후 중단 → `window`에 `filterSearchClicked` 이벤트 → `reloadDatatable(dt)`.
4. `.btn-reset` 클릭: reset → ready('filter') → refresh → `filterResetClicked` 이벤트 → reload.
5. `form_attributes.change_after` → 값 변경 시 `window[callback](node, params)` (`initSelect2()`, `initSelectpicker()`). 예: `onChangeTriggerAPIParams()`는 `common.API_PARAMS[params.target] = node.value`로 다음 요청의 top-level 파라미터(서버에서 `where`)를 바꾼다.

### 5-6. 직렬화(`getFilterData()`)와 서버 적용

`getFilterData(selector, isFirstDraw)`(`app-page-form.js`)가 만드는 객체:

```js
filters = {
  where:   { field: value, ... },            // where[...] 단일값
  whereIn: { field: [v1, v2], ... },         // where[...] 이면서 select[multiple]
  like:    [ {field, value}, ... ],
  date:    { start_date, end_date, on_date, date_range },
  text:    {}                                // 사용처 없음
}
```

- **첫 조회(isFirstDraw = true)**: 폼 값 대신 `data-default-value` 값만 `name`을 `group[key]`로 파싱해 넣는다. 숫자 문자열은 `parseInt`. **주의** — like 기본값은 첫 조회에서 유실된다.
- **이후**: `_onloaded === '1'`일 때만 실제 폼 값을 읽는다.
  - `like[value]`가 있으면 `{field: like[field] 값, value: like[value] 값}` 하나를 push, `like[x]`(x ≠ field/value)는 `{field: x, value}`.
  - `where[...]`(`whereIn[...]`도 접두어가 같아 여기서 처리): multiple이면 빈 값 제거 후 `whereIn`, 아니면 `where`.
- jQuery GET 직렬화: `filters[where][grade_cd]=USR001&filters[like][0][field]=id&…`

서버 `reformat_get_data()` → `$data['filter']` → `Model_Common::setFilter()`:

| 그룹 | 서버 규칙 | SQL |
|---|---|---|
| `where` | `null`, `''`, `'*'`(All) 값 제외 | `col = v` (배열이면 `IN`) — `MY_Model::where()` |
| `whereIn` | 빈 값 제외(`'*'`는 제외 안 됨) | `col IN (…)` — `MY_Model::whereIn()` |
| `like` | `value`가 빈 항목 제외 | `field`가 있으면 `col LIKE '%v%'`(`MY_Model::like()`), 없으면 모델 `strList` 전 컬럼 `OR LIKE` 그룹(`MY_Model::orLike()`) — `setFilterLike()` |
| `date` | `date_range` 프리셋(`today, yesterday, last7days, last30days, currentMonth, lastMonth, thisYear, lastYear`) 처리 후 falsy 값 제외. **주의** — 프리셋 적용 조건이 반전되어 사실상 동작 안 함 | `DATE_FORMAT(filteringDateColumn, '%Y-%m-%d')`에 대해 `on_date`면 `=`, 아니면 `>= start_date`, `<= end_date` — `setFilterDate()` |
| `whereNot` | JS가 만들지 않음 | `NOT IN` (서버만 지원) |

filter 밖의 top-level 파라미터(`API_PARAMS` 등)는 `where`로 들어간다. `format=datatable`이고 `searchWord && searchCategory`면 `filter.like[searchCategory] = searchWord`를 넣는다(현재 JS는 항상 빈 값을 보냄).

> **주의**
> - 필터 필드는 허용 목록으로 거른다(`MY_Builder_API::sanitizeRequestConditions()`). 모델 컬럼·설정 필드가 아닌 키는 버려지므로, hook에서만 읽는 필터 키는 컨트롤러의 `$allowedRequestFields`에 추가한다.

---

## 6. API 쪽 list config

- 이름: `MY_Builder_API::__construct()`에서 `listConfigName = 'list_' . builder_config_name(class) . '_config'`(WEB과 같은 규칙, `partnerAuth` → `list_partner_auth_config`). resourceConfig/method 후보 탐색은 없다.
- `MY_Builder_API::setProperties()`: GET/POST/PUT/PATCH일 때 list/form/view config를 base와 merge한다. GET에서 list config가 없으면 form config 중 `list => true` 항목으로 listConfig를 만든다.
- API의 `listConfig`는 `MY_Builder_API::transformView()`에서 `_mode=list`이고 `transTargetKeys['list']`에 있는 필드 값을 옵션 텍스트로 바꾸는 데만 쓰인다. 현재 `transTargetKeys`를 설정하는 컨트롤러는 없다.
- 결론: **화면 컬럼은 WEB의 list config만으로 결정**되고, API는 모델 select 결과 전체를 반환한다.

---

## 7. 예시

### 7-1. list (`admin_list_config.php`)

```php
$config['list_administrators_config'] = [
    [
        'field' => 'administrator_id',
        'type' => 'hidden',          // 화면 컬럼에서 제외
        'subtype' => 'identifier',   // common.IDENTIFIER = ['administrator_id']
    ],
    ['field' => 'id',    'label' => 'lang:user.id'],
    ['field' => 'name',  'label' => 'lang:user.name'],
    ['field' => 'email', 'label' => 'lang:user.email'],
    ['field' => 'tel',   'label' => 'lang:user.tel'],
];
```

route(`admin_route_config.php`의 `administrators.methods.list`): `buttons.excel = false`, `actions.view = false`, `properties.formExist = true` →
최종 화면 컬럼 `[No.] [id] [name] [email] [tel] [actions(edit, delete)]`, 상단 `Add New Record` 버튼(사이드 폼), Export 없음.
요청: `GET /admin/api/administrators?_mode=list&user_cd=USR001&format=datatable&draw=1&pageNo=0&limit=10&searchWord=&searchCategory=&filters[…]`

### 7-2. 인라인 편집 · 커스텀 렌더러 (`list_menu_auth_config`)

```php
[
    'field' => 'is_show',
    'label' => 'lang:menu.is_show',
    'type' => 'checkbox',
    'subtype' => 'boolean',                       // 변경 시 afterCheckboxColumnChange() → PATCH
],
[
    'field' => 'create',
    'label' => 'lang:menu.create',
    'type' => 'checkbox',
    'subtype' => 'boolean',
    'render' => ['callback' => 'renderMenuAuthCheckbox'],   // app-page-menu-auth.js
],
```

select·download 셀의 형식(키 이름만 실제, 필드명은 자리표시자):

```php
['field' => '{code_field}', 'type' => 'select', 'option_attributes' => ['option_type' => 'yn'], 'onchange' => 'myChangeHandler'],
['field' => '{file_field}', 'type' => 'button', 'onclick' => ['kind' => 'download']],   // text 미지정 → 'Download'
```

### 7-3. filter (`admin_filter_config.php`)

```php
$config['filter_administrators_config'] = [
    'filters' => [
        [
            'field' => 'search_category',
            'label' => 'lang:filter.search_category',
            'type' => 'select',
            'subtype' => 'selectpicker',
            'attributes' => ['placeholder' => 'filter.Select The Search Category'],
            'option_attributes' => [
                'option_type' => 'field',
                'option_field' => 'search_category.users',              // form_options_by_field('search_category.users')
            ],
            'filter_attributes' => ['type' => 'like', 'subtype' => 'field'], // name="like[field]"
            'form_attributes'   => ['set_default_first' => true],            // 첫 옵션이 기본값
        ],
        [
            'field' => 'search_word',
            'label' => 'lang:filter.search_word',
            'type' => 'text',
            'attributes' => ['placeholder' => 'filter.Enter The Search Word'],
            'filter_attributes' => ['type' => 'like', 'subtype' => 'value'], // name="like[value]"
        ],
    ],
];
```

→ 한 행: `[검색 카테고리(3)] [검색어(3)] [space(3)] [검색/초기화(3)]`.
검색 시 `filters[like][0][field]={선택 컬럼}&filters[like][0][value]={검색어}` → `WHERE {컬럼} LIKE '%검색어%'`.
검색어 입력란에서 Enter → 카테고리 값이 있으면 검색 실행.

날짜/다중 선택 예는 `builder_filter_config.php`의 `filter_logs_errors_config`, `filter_logs_user_access_config`, API 파라미터 연동 예(`change_after: onChangeTriggerAPIParams`, `help_block`)는 `filter_menu_auth_config`를 참고한다.
