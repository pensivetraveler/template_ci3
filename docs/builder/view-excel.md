# View · Excel 가져오기 설정 (view_config, excel_config)

엔티티의 상세보기(view) 화면과 엑셀 가져오기(import) 화면을 설정하는 방법을 정리한 참고 문서이다.
설정 이름을 찾는 순서, 메서드 설정, 요청 흐름을 코드 기준으로 설명한다. 아직 정리되지 않은 부분도 함께 적었다.
경로는 저장소 루트 기준이다. 버그와 미구현 항목의 전체 목록은 [TODO.md](../../TODO.md#6-빌더-설정-조사-2026-10-23)에 있다.

**목차**

- [Part 1. View 설정과 상세보기 화면](#part-1-view-설정과-상세보기-화면)
  - [1. 관련 파일](#1-관련-파일) · [2. 켜는 방법](#2-켜는-방법) · [3. 상세 화면 판단과 화면 틀](#3-상세-화면-판단과-화면-틀) · [4. 표시할 컬럼](#4-표시할-컬럼-setviewcolumns) · [5. 메서드 설정](#5-메서드-설정-method_view_base_config) · [6. 값 표시](#6-값-표시-app-page-viewjs)
- [Part 2. Excel 가져오기 설정](#part-2-excel-가져오기-설정)
  - [1. 메소드 설정](#1-메소드-설정-method_excel_base_config) · [2. 헤더 설정](#2-헤더-설정-excel_name_config) · [3. 샘플 파일 생성](#3-샘플-파일-생성) · [4. 화면과 업로드 흐름](#4-화면과-업로드-흐름) · [5. form 규칙과의 관계](#5-form-규칙과의-관계)

관련 문서: 메소드 변형(category/type/subtype)과 route 설정은 [route-method-nav.md](route-method-nav.md), form 항목과 rules는 [form.md](form.md), 목록·필터와 Excel/CSV 내보내기(export)는 [list-filter.md](list-filter.md)를 참고한다.

---

## Part 1. View 설정과 상세보기 화면

목록에서 행을 눌러 들어가는 **상세 보기 페이지**를 설정만으로 만든다. 모달형 상세(`openViewModal()`)와 게시판형 상세(`view_article`, `view_comments`)는 아직 정리되지 않았다([TODO.md](../../TODO.md#6-빌더-설정-조사-2026-10-23)).

### 1. 관련 파일

| 구분 | 파일 | 역할 |
|---|---|---|
| WEB | `MY_Builder_WEB::view()`, `isDetailView()`, `prepareViewData()`, `setViewColumns()`, `setJSVariables()` | 상세 화면 여부 판단, 표시할 컬럼(`VIEW_COLUMNS`) 준비, 버튼 권한 |
| 뷰 | `views/builder/layout/view.php` + `view_{subtype}.php` (`base`, `grid`) | 화면 틀. 값 자리는 `<span id="{필드}">` |
| 모달 뷰 | `views/builder/layout/view_modal.php` | 목록 화면의 모달 상세(아래 "목록에서 모달로 보기"). 값 자리는 `<span id="view-{필드}">` |
| JS | `public/assets/builder/js/app-page-view.js` | API에서 단건을 받아 값 채우기, 목록·수정·삭제 버튼 |
| API | `MY_Builder_API::view()`, `transformView()`, `api/Common::transformView()` | `GET {API_URI}/{식별자}?_mode=view` 응답 |

### 2. 켜는 방법

1. route `methods`에 `view` 메서드를 추가한다. `category => 'view'`만 적으면 `method_view_base_config`(type `base`, subtype `base`)가 채워진다.
2. 목록 메서드에서 행 action `view`를 켠다(`'actions' => ['view' => true]`). `PAGE_VIEW_URI`가 만들어져 행의 보기 아이콘이 `/{flag}/{class}/view/{식별자}`로 이동한다.
3. (선택) `view_{class}_config`로 표시할 필드를 따로 정한다. 없으면 form 설정의 필드를 읽기 전용으로 보여 준다.

```php
// admin_route_config.php
'partners' => [
    'properties' => ['baseMethod' => 'list'],
    'methods' => [
        'list' => ['category' => 'list', 'type' => 'table', 'subtype' => 'datatable',
                   'actions' => ['view' => true]],
        'view' => ['category' => 'view'],                        // type·subtype 기본 base
    ],
],
```

### 3. 상세 화면 판단과 화면 틀

| 조건 | 동작 |
|---|---|
| `category => 'view'`, `type => 'base'` | 설정 기반 상세 화면 (`isDetailView()`). `VIEW_COLUMNS`를 만들고 `app-page-view.js`를 붙인다 |
| `category => 'view'`, 그 밖의 type (`dashboard`, `statistics` 등) | 전용 화면. 컨트롤러와 전용 뷰(`{flag}/{class}/{method}.php` 또는 route 기본 메서드면 `{class}/index.php`)가 그린다 |

- `subtype`은 화면 틀이다: `base`(라벨·값 한 줄씩), `grid`(12칸 행, 항목 `colspan`). `view_modal.php`는 Bootstrap 모달이라 상세 페이지 틀로 쓰지 않는다(목록의 모달 상세 전용).
- `{flag}/{class}/view.php` 같은 전용 뷰 파일이 있으면 공통 틀(`builder/layout/view.php`)보다 먼저 쓴다.

### 4. 표시할 컬럼 (`setViewColumns()`)

찾는 순서:
1. `view_{resourceConfig}_config` → `view_{class}_view_config` → `view_{class}_config` → `view_view_config` (`Resource_loader::entityConfigNames('view', …)`)
2. 없으면 form 설정(`properties.formConfig`가 있으면 그 이름, 없으면 form 찾는 순서)에서 `form => false`가 아닌 필드를 가져와, hidden·common이 아닌 필드를 `type => 'view'`로 바꾼다.

항목 처리:
- `subtype => 'identifier'`는 hidden(식별자)으로 바뀐다.
- `type`이 `view`가 아니면(예: `textarea`) `setFormColumn()`을 거쳐 입력 위젯으로 그린다. 이때만 폼 자산을 붙인다.
- `label`의 `lang:` 접두어는 떼고 뷰에서 `lang()`으로 번역한다.
- 키 기본값은 `builder_view_base`: `field`, `label`, `category`(`base`), `type`(`text` → 위 규칙으로 `view`), `subtype`(`base`), `attributes`, `help_block`, `colspan`(6).

### 5. 메서드 설정 (`method_view_base_config`)

| 키 | 기본값 | 의미 |
|---|---|---|
| `type` | `base` | `base`면 설정 기반 상세 화면(3장) |
| `subtype` | `base` | 화면 틀 `view_{subtype}.php` |
| `mode` | `read` | 메뉴 권한 검사에 쓰는 자리 |
| `resourceConfig` | `''` | view 설정을 다른 이름에서 빌려 올 때 |
| `actions` | `list`·`edit`·`delete` true | 화면 아래 버튼. `edit`은 route에 `edit` 메서드가 있고 수정 권한이 있을 때, `delete`는 삭제 권한이 있을 때만 표시 |
| `properties.formConfig` | `''` | view 설정이 없을 때 필드를 가져올 form 설정 이름 |
| `properties.checkOwner` | (없음, false) | true면 작성자(`isMyData`)일 때만 수정·삭제 버튼 표시 — 게시판처럼 본인 글만 고치는 화면용 |
| `properties.isComments` | `false` | 댓글 영역(`view_comments`)과 `app-page-comment.js` |

### 6. 값 표시 (`app-page-view.js`)

1. `GET {API_URI}/{식별자}?_mode=view`로 단건을 받는다. 응답에서 `password`는 항상 빠진다([api-hooks.md](api-hooks.md)).
2. `VIEW_COLUMNS`의 필드마다 `#{필드}` 요소에 값을 넣는다. 그다음 `VIEW_COLUMNS`에 없지만 같은 id 요소가 있는 응답 값도 넣는다(전용 뷰 호환).
   - 값은 `textContent`로 넣는다(HTML로 해석하지 않음). 빈 값은 `-`.
   - 컬럼에 `options`(form의 `option_attributes`로 만든 선택지)가 있으면 값 대신 라벨을 보여 준다. 다중 값은 `option_attributes.seperator`(기본 `,`)로 나눠 각각 바꾼다.
   - 값이 파일 목록(`file_id`가 있는 객체)이면 `{CURRENT_URI}/downloader/{file_id}` 링크 목록으로 보여 준다. `thumbnail` 필드는 배경 이미지.
   - 입력 위젯(span이 아닌 요소)은 `.value`에 넣는다.
3. 필드별로 표시 방식을 바꾸려면 `appPlugins.view[필드] = function (element, value, data, column) { … }`를 화면별 `_preset.js`에 둔다.
4. 버튼: 목록 → `PAGE_LIST_URI`, 수정 → `PAGE_EDIT_URI`(없으면 버튼 숨김), 삭제 → 확인 후 `DELETE {API_URI}/{식별자}` → 목록.

> **주의**
> - 수정을 목록의 사이드 폼으로 하는 화면은 수정 페이지(`edit` 메서드)가 없으므로 상세 화면의 수정 버튼이 나오지 않는다.

### 목록에서 모달로 보기

상세 페이지 없이 목록에서 행을 모달로 본다. 목록 메서드에 두 가지를 켠다.

```php
'list' => [
    'actions' => ['view' => true],
    'properties' => [
        'formExist' => true,      // 수정은 옆 폼으로 (없으면 수정 페이지 PAGE_EDIT_URI)
        'viewConfig' => true,     // 또는 문자열: view_{값}_config 를 먼저 찾음
        'checkOwner' => false,    // true 면 내가 만든 데이터일 때만 수정·삭제 버튼
    ],
],
```

- 필드: `view_{값}_config` → `view_{class}_list_config` → `view_{class}_config` → `view_list_config`, 모두 없으면 폼 설정(`formConfig`)의 필드를 읽기 전용으로 보여 준다(상세 페이지와 같은 규칙).
- 옆 폼과 같은 페이지에 그리므로 요소 id 는 `view-{필드}`이다. `appPlugins.view[필드]`는 그대로 필드 이름으로 쓴다.
- `viewConfig`가 있으면 `PAGE_VIEW_URI`는 비고, 보기 버튼·`onclick.kind: view` 셀은 `openViewModal()`(app-page-list.js)을 부른다. 값 채우기는 상세 페이지와 같은 `applyViewData()`(app-page-view.js).
- 모달 버튼: 수정(목록 `edit` 액션이 있고 옆 폼 또는 수정 페이지가 있을 때), 삭제(목록 `delete` 액션), 닫기. 수정·삭제는 목록의 `.edit-record`·`.delete-record` 처리를 그대로 쓰고, 누르면 모달을 먼저 닫는다.
- 댓글(`isComments`)은 모달에서 지원하지 않는다. 게시글처럼 댓글이 있는 상세는 상세 페이지로 본다.
- 예: 게시판 관리(`admin/boards`).

## Part 2. Excel 가져오기 설정

> **현재 상태** — 화면 진입, 헤더 대체 경로, 서버 행 검증, 헤더 이름 기준 열 매핑, 재저장 시 중복 방지가 동작한다. 서버는 route 설정에 `excel` 메서드가 있는 클래스만 받는다. 메뉴 권한(import) 검사는 아직 서버에서 하지 않는다(`checkExcelImportPermission()`, TODO 2장).

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

진입 경로: list 메소드의 `buttons.excel = true`(기본값 true) + route `methods`에 excel method(키 `excel` 또는 category `excel`) + 메뉴 권한 import. excel method가 없으면 버튼과 base_auth의 I 비트가 꺼진다(`route_has_excel_method()`).

- `setJSVariables()`가 `PAGE_EXCEL_URI = {href}/excel`을 만들고, `setListButtons()`가 import 권한으로 버튼을 거른다.
- 목록 툴바의 "Upload Excel" 버튼은 `app-page-list.js` `getListButtons()`가 출력한다.
- 엑셀 버튼은 `buttons.excel`이 정한다. list의 `actions.excel`은 목록 액션이 아니며(`setJSVariables()`에서 `buttons`와 병합될 때 `buttons.excel`에 덮어써짐) 효과가 없다.

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

설정이 없으면(`getExcelHeaders()`의 else 분기) form 설정(`properties.formConfig` 또는 form 찾는 순서)에서 고른다: `form=true`, hidden·common·file·password 아님, 시스템 컬럼(created/updated/del_yn/use_yn/recent_dt) 아님, `matches[...]` 규칙 없음. `required`는 rules에 `required` 또는 `required_mod[...add...]`가 있으면 true. 서버(`MY_Builder_API::getExcelImportFields()`)도 같은 규칙으로 받을 필드를 정한다.

> **주의** — 저장소에 `excel` 메소드를 가진 route도 `excel_*_config`도 없다. 대부분의 admin route는 `buttons.excel=false`로 꺼 두었다.

### 3. 샘플 파일 생성

`MY_Builder_WEB::getExcelSample()`

- 경로: `public/sample/{class}_upload_sample.xlsx`. 파일이 이미 있으면 다시 만들지 않는다(캐시).
- PHPExcel(`application/libraries/Excel_lib.php`)로 1행에 라벨을 쓴다. required는 빨간 굵은 글씨, 열 너비 24, 헤더는 노란 배경 + 가운데 정렬, `A1:{마지막열}5`에 얇은 테두리. 열 문자는 `excel_helper.php` `number_to_alphabet()`로 만든다.
- 폴더가 없으면 `make_directory()`로 생성한다.
- 헤더 설정을 바꾼 뒤에는 화면의 "샘플 파일 리셋"을 눌러야 새로 만들어진다. `resetExcelFile()`이 `PATCH {API_URI}/deleteExcelFile`(`class=body[data-class]`)을 보내고 → `MY_Builder_API::deleteExcelFile_patch()`가 `unlink` → 새로고침한다. `class` 값은 클래스 이름 형식(`^[A-Za-z][A-Za-z0-9_]*$`)만 받는다.

### 4. 화면과 업로드 흐름

**WEB** `MY_Builder_WEB::excel()`: head에 `jquery.tabledit.js`, `app-page-excel.js`, `xlsx.full.min.js@0.18.5`(cdnjs)를 넣고 → `excelHeaders`와 `sampleFile`을 뷰에 전달 → `builder/layout/excel.php`. 이 뷰는 `<script>const excelHeaders = {JSON};</script>`(JSON_HEX_* 처리)로 헤더를 JS에 넘긴다.

**브라우저** (`app-page-excel.js`, 문서 ready 블록의 핸들러들)

1. 파일 선택(`#excelFile` change 핸들러): `.xls`/`.xlsx`만 허용. SheetJS로 **첫 시트**를 `header:1` 배열로 읽는다.
2. 헤더 검사: 1행의 헤더 이름으로 열을 찾는다(열 순서는 샘플과 달라도 됨). 모르는 컬럼이 있거나 필수 컬럼이 없으면 업로드 전에 알린다.
3. 2행부터 빈 행을 빼고 표(thead = `excelHeaders` 순서)로 출력한다. 행마다 `data-row`(엑셀 행 번호)를 붙인다. required 빈 셀 수를 세고, 오류가 있으면 저장을 막고 "오류 바로가기"를 켠다.
4. Tabledit로 셀을 바로 수정한다(AJAX 저장은 막아 둠). 수정할 때마다 required를 다시 검사한다.
5. 저장(`#excelFormSubmit` 클릭 핸들러): 저장되지 않은 행(`submit-completed` 아님)을 `{_row: 엑셀 행 번호, {th data-field}: 셀 텍스트}`로 만든 뒤
   - `validateData()`: 50행씩 `POST {API_URI}/validateExcel` (JSON, `Authorization` 헤더)
   - 모두 통과하면 `submitData()`: 50행씩 `POST {API_URI}/uploadExcel`. 저장된 청크의 행에 `submit-completed`를 붙여, 중간에 실패해도 다시 저장할 때 보내지 않는다. 끝나면 성공 알림과 새로고침
   - 422 응답이면 `emitExcelErrors()`가 `errors[]`의 행(`value`)·필드(`param`) 셀을 강조하고 앞의 5건을 알림에 보여 준다

**API** (`MY_Builder_API`)

| 엔드포인트/함수 | 동작 |
|---|---|
| `validateExcel_post()` | `beforeExcelUpload()` → `DATA_AVAILABLE` |
| `uploadExcel_post()` | `beforeExcelUpload()`(검증) → `afterExcelUpload()`: 행마다 `setData()`(형 변환·비밀번호 해시 등) → `Model->addList()`(한 번에, 트랜잭션) → `DATA_CREATED`(201, `data.count`). 실패하면 `WRITE_FILEDB_FAIL`(500) |
| `beforeExcelUpload()` | `checkExcelImportPermission()` → Model 확인 → `raw_input_stream` JSON 디코드 → `validateExcelData()` |
| `validateExcelData()` | 행마다 검사하고 오류가 있으면 422(아래). 통과한 값만 돌려준다 |
| `getExcelImportFields()` | 받을 필드: `excel_{class}_config`의 field, 없으면 form 설정에서 화면과 같은 규칙 |
| `checkExcelImportPermission()` | route 설정에 `excel` 메서드가 없으면 403. 메뉴 import 권한 검사는 TODO 2장 |
| `deleteExcelFile_patch()` | 샘플 파일 삭제 |

`validateExcelData()` 검사:
- 받는 필드 외 키는 버리고, 값은 문자열로 trim, `defaultList`로 빈 값을 채운다. 한 요청 최대 `$excelChunkLimit`(500)행.
- 받는 필드의 form `rules`로 검증한다. 등록이므로 `required_mod[...add...]`는 `required`로 바꿔 적용한다.
- 선택형 필드(form `option_attributes`)는 선택지 값만 받는다(다중 값은 `seperator`로 나눠 각각 검사).
- 모델 `notNullList` 중 받는 필드(또는 defaultList)가 비면 필수 오류.
- `uniqueKeyList`는 DB 중복과 같은 요청 안 중복을 모두 오류로 본다.
- 같은 행·필드의 오류는 한 번만 넣는다.
- 오류 응답: 422, `errors: [{param: 필드, value: 엑셀 행 번호, type: 규칙, msg: 메시지}]`.
- 부모·자식 모델(`Model_Parent`·`Model_Child`)이 있는 엔티티는 한 행이 여러 테이블에 저장되므로 400(지원 안 함).

### 5. form 규칙과의 관계

업로드는 일반 등록(`index_post`)과 같은 form `rules`, `notNullList`, 고유키, `defaultList`, `setData()`를 거친다. 그룹 필드·파일 필드(`fileList`)는 엑셀로 받지 않는다.

추가 검사나 값 변환이 필요하면 하위 API 컨트롤러에서 `validateExcelData()`를 오버라이드하고 `parent::validateExcelData($data)`의 결과를 이어서 가공한다.
