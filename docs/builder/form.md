# Form 설정 (form_config)

엔티티의 입력 폼은 `form_{class}_config` 배열 하나로 정의된다. 이 문서는 그 배열의 한 항목(field item)에 들어가는 각 키가 PHP 렌더링, JS 동작, API 검증에서 실제로 어떻게 쓰이는지를 코드 기준(v3 브랜치)으로 정리한 레퍼런스다.
리스트 컬럼(`list_attributes`)과 필터 폼은 [list-filter.md](list-filter.md), 메서드 설정과 `FORM_TYPE` 의 출처는 [route-method-nav.md](route-method-nav.md), 상세 보기·엑셀은 [view-excel.md](view-excel.md) 를 참고한다.

> **`category` 는 CSS 클래스만 붙인다.** 예전 문서는 `category`(identifier/unique/hidden/select/addr …)로 렌더링이 갈린다고 적었지만, 실제 분기는 전부 `type`/`subtype` 으로 한다. `category` 는 `form-input_{category}` 클래스로만 출력된다(`builder_form_helper.php` `get_admin_form_attributes()`). 그룹 판정은 `group` 키로 한다.

**목차**

1. [전체 흐름](#1-전체-흐름)
2. [설정 파일과 기본값](#2-설정-파일과-기본값)
3. [Field item 키](#3-field-item-키)
4. [type / subtype 매트릭스](#4-type--subtype-매트릭스)
5. [attributes 와 form_attributes](#5-attributes-와-form_attributes)
6. [option_attributes](#6-option_attributes)
7. [group / group_attributes](#7-group--group_attributes)
8. [폼 레이아웃](#8-폼-레이아웃)
9. [rules 와 검증](#9-rules-와-검증)
10. [파일 필드](#10-파일-필드)
11. [JS 라이프사이클](#11-js-라이프사이클)
12. [예시: form_administrators_config](#12-예시-form_administrators_config)
13. [이전 문서와 다른 점](#13-이전-문서와-다른-점)

---

## 1. 전체 흐름

```
[config]  builder_form_config.php (기본값)  +  {admin,web}_form_config.php (form_{class}_config)
   │
   ▼ BuilderColumnsTrait::setFormColumns / setFormColumn / setColumnErrors   (WEB)
   │    - builder_form_base 병합, label/errors/required 처리, 옵션 로딩(getOptions), 파일 업로드 설정
   ▼ MY_Builder_WEB::setFormData
   │    - form=false 제외, id/name/view 생성, group_attributes 누적 병합, group_* form_attributes 추가
   │    → JS 변수 common.FORM_DATA / FORM_REGEXP / FORM_TYPE / (FORM_STYLE, FORM_EXIST)
   ▼ restructure_form_data_by_type()  (builder_form_helper.php)
   │    - get_admin_form_attributes(): form_attributes → data-*, type/subtype → class/플러그인, rules → HTML 속성
   │    - 그룹 묶기, hidden 분리, grid 행(12 col) 분할, tabs(tab_index) 분할
   ▼ views/builder/layout/form_{type}.php → form_{x}_group_{view} / form_{x}_custom_{view}
   ▼ JS: app-page-form.js 라이프사이클 (preparePlugins → resetFrmInputs → readyFrmInputs → fetch/apply → refreshPlugins)
   ▼ 제출: FormValidation(reformatFormData) → getFormData/checkInputSubmittable → submitAjax(POST)
   ▼ API: MY_Builder_API::validateFormRules(form_{class}_config) → validateManually → checkUniqueExist → setData
```

| 단계 | 위치 |
|---|---|
| 설정 탐색·기본값 병합 | `BuilderColumnsTrait::setConfigList()`, `setFormColumns()`, `setFormColumn()`, `setColumnErrors()` |
| 프론트 JS 변수 생성 | `MY_Builder_WEB::setJSVariables()`(list+사이드폼, form 페이지), `MY_Builder_WEB::setFormData()` |
| 뷰 데이터 가공 | `MY_Builder_WEB::prepareFormData()`, `MY_Builder_WEB::prepareListData()` |
| HTML 속성 생성 | `builder_form_helper.php` `get_admin_form_attributes()` |
| 그룹/행/탭 재구성 | `builder_form_helper.php` `restructure_admin_form_data()`, `restructure_form_data_by_type()`, `reformat_form_data_by_type()`, `set_form_rows()` |
| 서버 검증 | `MY_Builder_API::validate()`, `validateFormRules()`, `validateManually()`, `checkUniqueExist()` |

---

## 2. 설정 파일과 기본값

### 2-1. 설정 이름 탐색 (WEB)

`BuilderColumnsTrait::setFormColumns($configData)`

| 입력 | 사용되는 설정 |
|---|---|
| 배열 | 그대로 사용 |
| 문자열 `xxx` | `form_xxx_config`. 메서드 설정의 `resourceConfig`(form 페이지) 또는 `properties.formConfig`(list + 사이드폼)가 여기로 들어온다 ([route-method-nav.md](route-method-nav.md)) |
| 비어있음 | `setConfigList('form', $method)` 순서로 처음 존재하는 설정: `form_{method}_config`(index 면 `baseMethod`) → `form_{class}_{method}_config` → `form_{class}_config` → `form_{method}_config` |

- `field` 가 있거나 `type === 'common'` 인 항목만 남는다.
- API 는 `form_{builder_config_name(class)}_config` 하나만 쓴다(`MY_Builder_API::__construct()`, `setProperties()`). 이름 규칙은 WEB 과 같지만 `resourceConfig`·method 후보는 보지 않으므로, WEB 에서 다른 이름의 설정을 빌려 쓰면 API 컨트롤러의 `$formConfigName` 도 맞춰야 한다.

### 2-2. 기본값 (`application/config/extra/builder/builder_form_config.php`)

| 설정 | 내용 |
|---|---|
| `form_side_prefix` / `form_page_prefix` | 요소 id 접두사 `form_side-` / `form_page-` |
| `builder_form_base` | field item 기본값 (3장 표의 "기본값" 열) |
| `builder_form_base_form_attributes` | `form_sync=true, reset_value=true, detect_changed=true, with_btn=false, with_list=false, list_sorter=false, list_onclick='download', list_delete=false, form_validation=true` |
| `builder_form_base_option_attributes` | `option_type='field', option_data=[], render=[], option_stack='vertical'` |
| `builder_form_base_group_attributes` | `label='', form_text='', type='base', key='', envelope_name=false, group_repeater=false, repeater_type='base', repeater_id='', repeater_count=1` |
| `builder_form_filter_base` | `builder_form_base` + `filter_attributes.type='where'` (필터 폼용, [list-filter.md](list-filter.md)) |

병합 방식 (`BuilderColumnsTrait::setFormColumn()`):
- field item 은 `array_merge(builder_form_base, $item)` — **얕은 병합**.
- `form_attributes` 는 항상 기본값과 병합한다. `option_attributes` 는 **비어있지 않을 때만** 병합하고 옵션을 로딩한다. `list_attributes` 는 `builder_list_base` 와 병합한다.
- API 의 `formConfig` 는 `builder_form_base` 와만 얕게 병합하고 form_attributes/group_attributes 기본값은 병합하지 않는다(`MY_Builder_API::setProperties()`).

### 2-3. `admin_form_constants.php`

`FORM_ROW_SELECTOR='.form-row'`, `FORM_VALIDATION_UNIT_SELECTOR='.form-validation-unit'`, `FILTER_BASE_COLSPAN=3`. 폼 렌더링에서 실제로 쓰이는 것은 필터 행 분할용 `FILTER_BASE_COLSPAN`(`reformat_filter_data()`, `MY_Builder_WEB::setFilterColumns()`)뿐이다.

---

## 3. Field item 키

| 키 | 타입 | 기본값 | 설명 | 사용 위치 |
|---|---|---|---|---|
| `field` | string | `''` | DB 컬럼명 = 입력 `name`(그룹 제외) = 데이터 키. id 생성 시 `.` 은 `_` 로 치환 | `setFormColumns()`, `MY_Builder_WEB::setFormData()`, `get_form_item_id()` |
| `label` | string | `''` | `lang:xxx` 이면 `xxx` 로 저장 후 `lang()` 처리. placeholder/aria-label 기본값. **주의** — 생략해도 자동 라벨(`lang:{class}.{field}`)이 붙지 않고 빈 문자열이 된다 | `setFormColumn()`, `get_admin_form_attributes()`, `get_builder_form_label()` |
| `form` | bool | `true` | `false` 면 `FORM_DATA` 에서 제외(리스트 전용 컬럼 등) | `setFormData()` |
| `rules` | string | `''` | CI 검증 규칙(`\|` 구분, `[]` 안의 `\|` 는 분리 안 함). HTML 속성·클라이언트·서버 검증 모두의 원천 (9장) | `setColumnErrors()`, `get_admin_form_attributes()`, `reformatFormData()`, `validateFormRules()` |
| `errors` | array | `[]` | 규칙별 사용자 메시지 `['required' => '...']`. 최종 errors 는 규칙 목록으로 다시 만든다(언어파일에 `form_validation_{rule}` 이 있는 규칙만) | `setColumnErrors()`, `MY_Form_validation::get_error_msg()` |
| `category` | string | `'base'` | **분기 없음.** `form-input_{category}` 클래스만 출력 | `get_admin_form_attributes()` |
| `type` | string | `'text'` | 렌더링·플러그인·값 적용 분기의 핵심 (4장). `type === subtype` 이면 subtype 을 `base` 로 바꾼다 | `setFormColumn()`, `get_form_input_by_type()` |
| `subtype` | string | `'base'` | 플러그인과 커스텀 뷰 선택 (4장). `identifier` 는 식별자 지정 | `MY_Builder_WEB::setIdentifier()`, `setFormData()` |
| `default` | mixed | `''` | 초기값(`set_value`). 비어있지 않으면 `data-default-value` 출력 | `get_admin_form_attributes()`, `get_form_input_by_type()` |
| `icon` | string\|null | `null` | `null`: 규칙/타입으로 자동(`integer`/`numeric`→`ri-number-2`, select→없음, 그 외 `get_icon_classname_by_type()`). `'none'`: 아이콘 없음. `'svg:ri-xxx'`: SVG 아이콘 | `get_admin_form_icon()`, `create_admin_form_icon()`, `get_admin_form_icon_classname()`, `builder_web_helper.php` `get_icon_classname_by_type()` |
| `form_text` | string\|array | `''` | 입력 아래 도움말 (3-2) | `get_admin_form_text()` |
| `attributes` | array | `[]` | 네이티브 HTML 속성 (5-1) | `get_admin_form_attributes()` |
| `form_attributes` | array | 2-2 기본값 | 커스텀 동작 속성 → `data-*` (5-2) | `get_admin_form_attributes()` |
| `option_attributes` | array | `[]` | 옵션 로딩 설정 (6장). 비어있으면 옵션을 로딩하지 않음 | `setFormColumn()` |
| `option_attributes.option_field` | string | field | 옵션 조회 키를 field 대신 지정 | `setFormColumn()` |
| `option_attributes.validate` | bool | `true` | false 면 저장할 때 선택지 값 검사를 하지 않음 (6-3) | `MY_Builder_API::getValidatableOptionValues()` |
| `group` | string | `'base'` | `'base'` 가 아니면 같은 이름끼리 하나의 그룹 블록으로 렌더링 (7장) | `setFormData()`, `restructure_admin_form_data()` |
| `group_attributes` | array | `[]` | 그룹 설정 (7장). 같은 그룹 안에서 뒤 항목 값이 앞 항목 값에 누적 병합 | `setFormData()` |
| `list` | bool | `false` | 리스트 컬럼 노출 → [list-filter.md](list-filter.md) | `MY_Builder_API::setProperties()` |
| `list_attributes` | array | `[]` | 리스트 렌더링 설정 → [list-filter.md](list-filter.md). 폼 렌더링 전에 제거됨 | `restructure_admin_form_data()` |
| `tab_index` | int | `0` | `tabs` 레이아웃의 탭 번호. **정수 strict 비교**(`'1'` 문자열 불가) | `reformat_form_data_by_type()` |
| `colspan` | int | grid `6`, 그 외 `12` | grid 행 분할(12 컬럼)과 `col-md-{colspan}` | `reformat_form_data_by_type()`, `set_form_rows()` |

### 3-1. 자동 생성 키 (설정에 쓰지 않음)

| 키 | 생성 규칙 | 위치 |
|---|---|---|
| `id` | 일반: `form_side-{field}`(list+사이드폼) / `form_page-{field}`(페이지 폼). 그룹: 7-2 | `setFormData()`, `MY_form_helper.php` `get_group_field_id()` |
| `name` | 일반: `field`. 그룹: `get_group_field_name()` (7-2) | `setFormData()`, `MY_form_helper.php` `get_group_field_name()` |
| `view` | `subtype` 값 (그룹 블록은 `group_attributes.type`) | `setFormData()`, `restructure_admin_form_data()` |
| `options` | `getOptions()` 결과 `[value => text]` | `setFormColumn()` |
| `form_attributes.form_field` | `field` 값 → `data-form-field` | `get_admin_form_attributes()` |
| `form_attributes.group_name/group_field/group_key/group_view` | 그룹 항목에 추가 → `data-group-*` | `setFormData()` |

> **주의** — 샘플 설정(`builder_form_config.php` `form_sample_config`)의 최상위 `group_key` 키는 어디에서도 읽지 않는다. `group_attributes.key` 를 쓴다.

### 3-2. `form_text`

`builder_form_helper.php` `get_admin_form_text()`

| 형태 | 동작 |
|---|---|
| `''` | `div.form-text.d-none` (숨김) |
| string | `lang($text)` → 없으면 `lang('form_text.'.$text)` → 없으면 원문 |
| array `['line'=>..., 'replace'=>..., 'icon'=>..., 'icon_size'=>...]` | `line` 번역 후 `%` 가 있으면 `vsprintf(line, (array)replace)`. `icon` 이 있으면 앞에 아이콘(`icon_size` 크기) |
| `form_attributes.sample_file` 있음 | 문구 뒤에 `(Sample)` 다운로드 링크 추가 |

JS 는 `[max]` 속성이 있는 input/textarea 의 입력 길이를 `max` 로 자르고 형제 `.form-text` 에 경고를 덮어쓴다(`app-page-form-event.js` 의 `input, textarea` input 핸들러).

---

## 4. type / subtype 매트릭스

### 4-1. 렌더링 경로

| 폼 위치 | 그룹 | `type === 'custom'` | file + dropzone | 일반 |
|---|---|---|---|---|
| 페이지 base (`form_base.php`) | `form_base_group_{view}` | `form_base_custom_{view}` | (일반 경로) | `get_page_form_input_by_type()` |
| 페이지 grid (`form_base.php`, `form_inputs.php`) | `form_grid_group_{view}` | `form_grid_custom_{view}` | (일반 경로) | 동일 |
| 사이드 (`form_side.php`) | `form_side_group_{view}` | `form_side_custom_{view}` | `form_side_file_{view}` | `get_side_form_input_by_type()` |

- 뷰는 `builder_base_helper.php` `builder_view()` 로 로드한다. `{platformName}/layout/...` 이 없으면 `builder/layout/...` 을 쓴다. 즉 **플랫폼 폴더에 같은 파일명을 만들면 오버라이드**된다.
- hidden 타입은 폼 상단에 따로 출력된다(`form_base.php`, `form_side.php`). 이때 `name` 은 `field` 다.
- 일반 경로의 HTML 은 `get_form_input_by_type()` 가 만든다: `password`→`form_password`, `checkbox`→onoff/choice, `radio`→choice, `select`→`form_dropdown`, `textarea`→`form_textarea`, `file`→`form_upload`, `number`(unit 처리), 그 외 → `<input type="{type}">`.
- 페이지 폼은 `with_btn && btn_type === 'dup_check'` 이면 중복확인 버튼을 덧붙인다(`get_page_form_input_by_type()`). 사이드 폼은 라벨을 input 뒤에 붙인다(floating label, 체크/라디오 single 제외, `get_side_form_input_by_type()`).

### 4-2. 매트릭스

PHP 클래스는 공통으로 `form-control dt-{field} form-input_{category} form-input_{type}-{subtype}` 가 붙는다(`get_admin_form_attributes()`). 아래 표는 그 외 추가분이다. "값 적용"은 `app-page-form.js` `applyFrmValuesByCategory()` 의 분기다.

| type | subtype | 렌더링 HTML / PHP 처리 | JS 플러그인 (preparePlugins / refreshPlugins) | 값 적용 | reset | 제출 |
|---|---|---|---|---|---|---|
| `text` (기본) | `base` | `<input type=text>` | - | `inputs`: 값이 truthy 이고 `'0000-00-00'` 이 아닐 때 대입 | value='' | 변경/필수일 때 `name=value` |
| `text` | `readonly` | `readonly` 속성 + `detect_changed=0` 강제 | - | 동일 | 동일 | detect_changed=0 → 항상 전송 |
| `text` | `identifier` | 일반 input. `IDENTIFIER` 목록에 포함(`MY_Builder_WEB::setIdentifier()`), 페이지 폼에서 URI 마지막 세그먼트를 default 로(`setFormData()`) | - | 동일 | 동일 | 동일 |
| `text` | `cleave-bizno` | class `cleave cleave-bizno`, placeholder `123-45-67890` | `app-page-utils.js` `setCleave()` (blocks 3-2-5) | inputs | 동일 | 동일 |
| `text` | `cleave-version` | `.cleave.form-input_text-cleave-version` + cleave 에셋, placeholder 기본 `1.0.0` | `setCleave()` 버전 형식(1.2.3, 숫자만) | inputs | | |
| `text` | `zipcode` | 일반 input (addr_daum 그룹 뷰에서 사용) | - | inputs | | |
| `hidden` | `base`/`identifier` | 폼 상단 hidden. `detect_changed=0` 강제 | - | inputs | value='' (default 있으면 유지) | 항상 전송 (`checkInputSubmittable()`) |
| `password` | `base`/`password_confirm` | `form_password` | - | **적용하지 않음**(`excepts`) | value='' | 변경 시 전송. 서버에서 빈 값이면 제거, 있으면 `create_hash` (`MY_Builder_API::setData()`) |
| `email` | - | `<input type=email>` | - | inputs | | |
| `number` | `base` | `<input type=number>` | - | inputs (0 은 falsy 라 미적용) | value='' | |
| `number` | `readonly` | readonly | - | | | |
| `number` | `unit` | 페이지: 아이콘 + input + `form_attributes.unit` 텍스트(`get_form_input_by_type()`), 사이드: `get_side_form_input_adds()` | - | inputs | | |
| `tel` | `cleave-hp` | class `cleave cleave-hp`, placeholder `010-1234-5678` | Cleave phone KR | inputs | | |
| `tel` | `cleave-fulldate` | class `cleave cleave-fulldate`, `YYYY-MM-DD` | Cleave date Y-m-d | inputs | | |
| `date` | `flatpickr` | class `flatpickr flatpickr-date`, `<input type=date>` | `setFlatpickr()` (Y-m-d). **주의** — add/edit 페이지의 변경 시 재검증은 클래스명 오타로 동작하지 않음(리스트는 정상) | inputs | | FV `date` validator 자동 추가 |
| `date` | `cleave-year`/`cleave-month`/`cleave-date` | class `cleave cleave-*`, placeholder `YYYY`/`MM`/`DD` | Cleave | inputs | | |
| `time` | `flatpickr` | class `flatpickr flatpickr-time` | flatpickr time (h:i K) | inputs | | |
| `time` | `cleave-time`/`cleave-hour`/`cleave-minute` | class `cleave cleave-*` | Cleave time | inputs | | |
| `year_month` | `flatpickr` | class `flatpickr flatpickr-year-month` (`<input type=year_month>` 로 출력) | monthSelectPlugin | inputs | | |
| `color` | `coloris` | **type 을 text 로 변경**, class `coloris`, `data-coloris=1`, coloris 에셋 | 클릭 시 `Coloris()`(`preparePlugins()`), refresh 때 `setColorisValue()` | inputs | | |
| `select` | `base` | `form_dropdown(name, options, default)` + class `form-select`, `data-style=btn-default` | - | `app-page-utils.js` `bindSelectValue()` | `bindSelectValue(null)` | `$(node).val()` |
| `select` | `selectpicker` | class `w-100 selectpicker` (form-control 제거), 에셋 | `initSelectpicker()`. refresh 때 `editable=0` 이고 edit 모드면 disabled | 동일 | 동일 | 동일 |
| `select` | `select2` | class `select2` | `initSelect2()` — `appPlugins.form.select2[name]` 로 옵션/핸들러 확장, `change_after` 콜백 | `appPlugins.form.select2[name].beforeHand()` 후 bindSelectValue | 동일 | multiple 은 배열 → FormData 문자열(`a,b`) |
| `select` | `select2-repeater` | class `select2-repeater` | 리피터용 select2 (`preparePlugins()`, `addRepeaterItem` 이벤트 핸들러) | 동일 | | |
| `textarea` | `base` | `form_textarea`, class `h-px-100`, rows 사이드 3 / 페이지 5 | - | `textareas` | value='' | |
| `textarea` | `autosize` | class `textarea-autosize`, rows 2. **사이드 폼에서는 모든 textarea 가 autosize 로 강제**(`setFormData()`) | `autosize()`, refresh 때 높이 재계산 | textareas | | |
| `textarea` | `quill` | 페이지 폼이면 `custom/quill` 뷰로 그림(그 폼 type 의 `form_{type}_custom_quill.php` 가 있을 때). 목록 사이드 폼은 autosize | 아래 `custom/quill` 과 같음 | 아래와 같음 | | |
| `checkbox` | `base`(다중) | `get_admin_form_choice()`: options 수만큼 `name[]` 체크박스 | - | `name[]` 중 값이 data(문자열이면 `,` split, 배열이면 includes)에 포함되면 checked | checked=false | 체크된 것만 `name[]` 반복 append. 서버에서 `join(',')`. **주의** — 모두 해제하면 아무것도 전송되지 않아 "전부 해제" 저장 불가 |
| `checkbox` | `single` | options 있으면 각 옵션을 단일 `name`(배열 아님) 체크박스로, 없으면 value=1 하나 | - | value 일치 시 checked | | 체크 시 `name=value`. **주의** — 서버 `setData()` 의 `join()` 이 스칼라를 받아 PHP 8 에서 TypeError |
| `checkbox` | `onoff` | `builder/custom/onoff` 뷰: `<input type=checkbox name={field} value=Y>` 토글. **주의** — attributes/data-* 를 출력하지 않음 | custom-toggle CSS | data 가 truthy 면 checked (`'N'` 도 checked) | reset 안 됨 | 체크+변경 시 `Y`. 서버: 미전송이면 `N` 으로 저장 |
| `radio` | `base` | 페이지: choice 그룹. **주의** — 사이드 폼은 `get_admin_form_radio()` 를 인자 부족으로 호출해 렌더링 시 오류 | - | value 일치 시 checked | checked=false | 체크된 것만 |
| `radio` | `single` | choice single | | | | |
| `file` | `base`/`single`/`thumbnail` | `form_upload`. `accept`/`max_size` 자동 (10장) | - | 값 대입 없음. `with_list` 면 목록 렌더링 | 노드 clone 교체 + value='' | 파일 선택 시만. 1개면 `name`, 여러 개면 `name[]` |
| `file` | `multiple` | `multiple` 속성 | | 동일 | | 동일 |
| `file` | `readonly` | class `d-none` + 목록 라벨 (`get_admin_form_list_item()`) | | 목록 있으면 unit 표시, 없으면 숨김 (`setFormListItem()`) | | |
| `file` | `dropzone` (사이드) | `form_side_file_dropzone.php`: `div.dropzone-wrapper` + `dz-*` 속성(`set_dropzone_attributes()`) | `setDropzone()` | 기본 분기: `form[name].value = data` | | Dropzone 인스턴스 파일을 `field` / `field[i]` 로 append (`getFormData()`) |
| `file` | `dropzone-full` | **부분 구현** — class `input-dropzone d-none` 만 붙고 페이지에선 일반 경로로 렌더링. dropzone 에셋은 subtype 이 정확히 `dropzone` 일 때만 로드(`MY_Builder_WEB::beforeViewApp()`) | - | 기본 분기(file input 에 문자열 대입) | | |
| `custom` | `unique` | `form_{base,grid,side}_custom_unique.php`: hidden `{field}_unique` + input(`type="custom"`) + 중복확인 버튼 | `[data-btn-type=dup_check]` input 핸들러로 버튼 활성화(`app-page-form-event.js`), `checkDuplicate()` | `customs` 기본 분기 `form[name].value=data` + `data-original-value` 기록 | | 값 + `{field}_unique`(hidden, 서버에서 컬럼이 아니라 제거) |
| `custom` | `youtube` | `form_{base,side}_custom_youtube.php`: `<input type=url>` + 링크 목록 | - | `setFormListItem()` (youtube) | | |
| `custom` | `version` | `form_{base,side}_custom_version.php`: 일반 input | - | 기본 분기 | | |
| `custom` | `quill` | `form_base_custom_quill.php`: hidden(`data-textarea-id`) + `div#{id}-quill.textarea-quill` (**페이지 base 전용**) | refresh 때 `new Quill`, 내용이 바뀔 때마다(`text-change`)와 검증 직전 hidden 에 HTML 복사. **저장 때 서버가 HTML 을 정화**(`sanitize_rich_html`, HTMLPurifier, 허용 태그·속성만) | div innerHTML + hidden value | | hidden |
| `custom` | `tag-base` | `form_side_custom_tag-base.php`, class `tagify h-auto` (**사이드 전용**) | `new Tagify` (`appPlugins.form.tagify[name]` 로 옵션 지정, 기본은 값 배열) | 기본 분기 | | |
| `custom` | `dropzone-full` | `form_base_custom_dropzone-full.php`: 넓은 끌어놓기 영역. id `{field}-dropzone-wrapper`, `data-field={field}` (2026-10-29 필드 기준으로) | `setDropzone()` (자동 업로드 없음) | 저장된 파일을 미리보기로(`setDropzoneExistingFile`), 미리보기 삭제 = `deleteFile/{행 id}?type={field}` | | Dropzone 파일을 `field` / `field[i]` 로 append |
| `custom` | `dropzone-single` | `form_base_custom_dropzone-single.php`: 파일 하나(썸네일 등), `max` 1 고정. 새 파일을 고르면 이전 파일·기존 미리보기를 바꿈 (2026-10-29) | 같음 | 같음 | | 같음 (`field`) |
| `custom` | `domain_register`/`ip_register`/`domain_list` | grid 전용 뷰, 에셋 등록(`get_admin_form_attributes()`) | 각 lib common.js | | | |
| `custom` | `reply_list` | `*_group_reply_list` 그룹 뷰와 연동 | | 아무것도 안 함 | | |
| `common` | `space`/`text`/`divider` | 필드가 아닌 레이아웃 요소. grid: `form_inputs.php` 에서 공백 / `<h6>{content}</h6>` / 구분선. `form_base.php` grid 에선 공백만. base subtype 에선 제외(`reformat_form_data_by_type()`) | - | - | - | - |

---

## 5. attributes 와 form_attributes

### 5-1. `attributes` (네이티브 HTML 속성)

`get_admin_form_attributes()` 의 마지막에 `array_merge($item['attributes'], $자동속성)` 을 하므로 **자동 속성이 이긴다.**

| 자동 속성 | 규칙 | 위치 |
|---|---|---|
| `placeholder` | `attributes.placeholder` 에 `.` 이 있으면 lang 키, 없으면 `placeholder.{값}` → 실패 시 `lang(값)`. 지정이 없으면 label. cleave/flatpickr subtype 은 고정값으로 덮어씀 | `get_admin_form_attributes()` |
| `aria-label`, `aria-describedby={field}-ico` | 항상 | 동일 |
| `class` | **주의** — 사용자 `attributes.class` 는 무시되고 자동 classList 로 덮어써짐 | 동일 |
| `autocomplete="false"` | name 이 `name/email/tel` | 동일 |
| `required` | rules 에 `required` | `get_admin_form_attributes()`, `setColumnErrors()` |
| `required-mod` | `required_mod[x]` 의 파라미터. 현재 router method 가 x 에 포함되면 규칙을 `required` 로 바꾸고 `required` 속성 추가 | `setColumnErrors()` |
| `minlength`/`maxlength` | `exact_length[n]`, `min_length[n]`, `max_length[n]` | `get_admin_form_attributes()` |
| `min`/`max` | `min[n]`, `max[n]` (정수만) | 동일 |
| `data-default-value` | default 가 비어있지 않을 때. `set_default_first` 면 첫 옵션 키 | 동일 |
| `data-*` | form_attributes 변환 (5-2) | 동일 |
| `max_size`, `accept` | file 타입 (10장). 사용자 attributes 가 우선 | `setFormColumn()` |

JS 가 직접 읽는 attributes:

| 속성 | 효과 | 위치 |
|---|---|---|
| `clipboard` | ClipboardJS 로 클릭 시 복사 | `preparePlugins()` |
| `max` | input/textarea 입력 길이 제한. file 은 FV maxFiles | `app-page-form-event.js` input 핸들러, `reformatFormData()` |
| `data-add-hypen` | 9자 이상이면 전화번호 하이픈 | `app-page-form-event.js` input 핸들러 |
| `accept`, `extension` | FV `file` validator | `reformatFormData()` |
| `style`/`max`/`max_size`/`accept`/`multiple` | dropzone `dz-*` 속성 | `set_dropzone_attributes()` |
| `rows` | textarea 행 수 (자동값이 우선) | `get_admin_form_attributes()` |
| `.form-maxlength` 클래스 | bootstrap-maxlength. **주의** — class 가 덮어써지므로 설정으로 붙일 방법이 없음 | `preparePlugins()` |

### 5-2. `form_attributes` → `data-*`

변환 규칙(`get_admin_form_attributes()`): 키의 `_` → `-` 후 `data-` 접두사, `false` → `0`, `true` → `1`, 배열 → JSON(쌍따옴표를 `'` 로 치환). hidden/readonly 항목은 `detect_changed` 가 `0` 으로 강제된다.

`common.FORM_DATA` 의 `form_attributes` 는 **변환 전 원본 객체**라서 JS 는 `field.form_attributes.xxx` 와 `data-xxx` 두 경로로 읽는다.

| 키 | data 속성 | 기본값 | 효과 / 소비자 | 상태 |
|---|---|---|---|---|
| `form_sync` | `data-form-sync` | `true` | 읽는 코드 없음 | **미구현** |
| `reset_value` | `data-reset-value` | `true` | 0 이면 `resetFrmInputs()` 가 해당 노드를 건너뜀(값·input-changed·original-value 모두 유지) | 구현 |
| `detect_changed` | `data-detect-changed` | `true` | 0 이면 변경 여부와 무관하게 항상 제출(`checkInputSubmittable()`), reset 시 `data-input-changed=false`(`resetFrmInputs()`) | 구현 |
| `view_mod` | `data-view-mod` | (없음) | `\|` 로 나눈 목록에 현재 mode 가 있으면 `.form-validation-unit` 표시, 없으면 `d-none` (`readyFrmInputs()`) | `''`이거나 생략하면 모든 mode 에서 표시, 배열(`['add','edit']`)도 지원 |
| `writable` | `data-writable` | (없음) | 0 이면 항상 readonly (`readyFrmInputs()`) | 구현 |
| `editable` | `data-editable` | (없음) | 0 이면 edit 모드에서 readonly, 그 외 모드에선 **readonly 제거**(subtype readonly 여도). selectpicker 는 edit 시 disabled(`refreshPlugins()`). API 고유값 중복검사 대상 여부(`checkUniqueExist()`) | 구현 |
| `text_type` | `data-text-type` | (없음) | input 이벤트에서 `eng`/`num`/`eng\|num` 외 문자 제거 (`app-page-form-event.js` input 핸들러) | **부분 구현** — `email` 등 다른 값은 무시 |
| `with_btn` | `data-with-btn` | `false` | class `form-input_with-button`, 페이지 폼에서 dup_check 버튼 렌더(`get_page_form_input_by_type()`), 값 적용 시 `data-original-value` 기록 | 구현 |
| `btn_type` | `data-btn-type` | (없음) | `dup_check` 만 동작(버튼 + 입력 이벤트). addr_daum 버튼은 그룹 뷰에 하드코딩되어 이 값과 무관 | **부분 구현** |
| `btn_params` | `data-btn-params` | (없음) | 읽는 코드 없음(주석 처리된 코드만) | **미구현** |
| `dup_check` | `data-dup-check` | - | 주석 처리된 코드에서만 참조 | **미구현** |
| `with_list` | `data-with-list` | `false` | 입력 아래 `ul#{id}-list` 렌더(`get_admin_form_list_item()`), 값 적용 시 `setFormListItem()` | 구현 |
| `list_type` | `data-list-type` | (subtype) | 목록 아이템 템플릿: `thumbnail` / `youtube` / `reply_list` / 그 외 = 파일 (`setFormListItem()`) | 구현 |
| `list_field` | `data-list-field` | field | 목록 데이터를 읽을 키 (`setFormListItem()`) | 구현 |
| `list_delete` | `data-list-delete` | `false` | 값이 있는 목록 아이템에 삭제 버튼 → `deleteFile(this, type)` → `PATCH {API}/deleteFile/{id}?type=` (`setFormListItemFile()`, `deleteFile()`) | 구현 |
| `list_sorter` | `data-list-sorter` | `false` | `form-list-item-wrap_sorter` → Sortable, 종료 시 `PATCH /reorder` (body 에 new_index/article_id/file_id) (`preparePlugins()`) | **부분 구현** — 서버 `reorder_patch()` 가 스텁 |
| `list_sorter_params` | - | - | 읽는 코드 없음 (JS 는 `data-article-id`/`data-file-id` 사용) | **미구현** |
| `list_onclick` / `list_click` / `list_click_params` | - | `'download'` | 읽는 코드 없음 | **미구현** |
| `max_file_uploads` / `onchange_upload` / `onchange_preview` | - | - | 읽는 코드 없음 | **미구현** |
| `form_validation` | `data-form-validation` | `true` | `false` 면 FormValidation 필드에서 제외 (`reformatFormData()`) | 구현 |
| `show_if_field` / `show_if_value` | `data-show-if-field` / `data-show-if-value` | - | 이 필드 값이 `show_if_value` 와 같을 때만 `show_if_field` 필드의 unit 표시(초기 숨김) (`preparePlugins()`, `applyFrmValuesByCategory()`, `app-page-form-event.js` `[data-show-if-field]` change 핸들러) | 구현 |
| `change_after` | `data-change-after` | - | `{callback, params}`. **select2 에서만** change 시 `window[callback](node, params)` (`initSelect2()`). 제공 콜백: `onChangeTrigger`, `onChangeTriggerSelect`(옵션 재조회), `onChangeTriggerAPIParams` | 구현 |
| `set_default_first` | `data-set-default-first` | - | 첫 옵션을 default 로. reset 대상에서 제외, 옵션 재구성 시 첫 값 선택 (`get_admin_form_attributes()`, `resetFrmInputs()`, `prepareSelectOptions()`) | 구현 |
| `option_stack` | `data-option-stack` | `'horizon'` | 체크박스/라디오 배치. `vertical` 이면 세로, 그 외 가로 (`get_admin_form_choice()`). **주의** — `option_attributes` 의 기본값 `option_stack='vertical'` 은 쓰이지 않음 | 구현 |
| `wrap_with_border` | `data-wrap-with-border` | `false` | 선택지 래퍼에 테두리 (`get_admin_form_choice()`) | 구현 |
| `unit` | `data-unit` | - | `number/unit` 단위 텍스트 (`get_form_input_by_type()`, `get_side_form_input_adds()`) | 구현 |
| `sample_file` | `data-sample-file` | - | form_text 뒤 샘플 다운로드 링크 (`get_admin_form_text()`) | 구현 |
| `check_delete` | `data-check-delete` | - | API 고유값 중복검사 시 삭제 데이터 포함 여부 (`checkUniqueExist()`) | 구현 |
| `form_field` | `data-form-field` | 자동 | `readyFrmInputs()` 의 default 키, `onChangeTrigger*` 대상 탐색 | 자동 |
| `group_name` / `group_field` / `group_key` / `group_view` | `data-group-*` | 자동(그룹) | 리피터 id 재작성(`preparePlugins()`), `deleteRepeater()` 데이터 키, `findAddress()` 입력 탐색 | 자동 |

### 5-3. JS 가 런타임에 만드는 속성

| 속성 | 생성/변경 | 소비 |
|---|---|---|
| `data-input-changed` | `#formRecord` 의 non-hidden 입력 `change` 시 `refreshPlugins` 단계가 끝난 뒤에만 `true`(`app-page-form-event.js` change 핸들러), select2 select/unselect 시 true/false, reset 시 detect_changed=0 노드만 `false` | `checkInputSubmittable()` |
| `data-original-value` | select: `bindSelectValue()` 가 항상 기록. dup_check 입력: 값 적용 시. reset 시 제거 | 중복확인 버튼 활성화(`app-page-form-event.js` dup_check 핸들러), `checkDuplicate()`, 옵션 재구성 시 선택 복원(`prepareSelectOptions()`) |
| `data-default-value` (dataset) | `prepareSelectOptions()` 에서 set_default_first 일 때 | select2 `allowClear` 여부 (`initSelect2()`) |
| `data-repeater-count`, `data-row-index` | jQuery repeater show / 값 적용 | `preparePlugins()`, `applyFrmValues()` |

---

## 6. option_attributes

### 6-1. 로딩 시점

- 폼: `option_attributes` 가 **비어있지 않을 때만** `getOptions(option_field ?? field, option_attributes)` 를 호출한다(`setFormColumn()`). 비어있는 select 는 옵션 없이 렌더링된다.
- 필터: select 는 항상 로딩하고 `default/bool/yn/gender` 타입이면 `'*' => 'All'` 을 앞에 붙인다(`MY_Builder_WEB::setFilterColumns()`, [list-filter.md](list-filter.md)).
- API GET `_mode=list|view` 에서는 `transTargetKeys` 의 값을 옵션 텍스트로 바꾼다(`MY_Builder_API::transformView()`).

### 6-2. option_type (`MY_Controller::getOptions()`)

| option_type | 동작 | option_data | 상태 |
|---|---|---|---|
| (attr 비어있음) | `field` 로 처리 | - | |
| `''` / `none` | `options.default` (`Option 1/2`) | - | |
| `blank` | `[]` | - | |
| `static` | option_data 그대로 (비면 default) | `[value => text]` (text 는 렌더 시 `lang()`) | |
| `field` (기본) | `config['options'][field]`. **주의** — 없으면 `options.default`(Option 1/2)로 대체 (`MY_form_helper.php` `form_options_by_field()`) | - | |
| `config['options']` 의 키 (`bool`, `yn`, `gender`, `cfg_type`, `target`, `menu_auth`, `access_type`, `log_threshold`, `date_range`, …) | 해당 옵션 세트 (`application/config/extra/custom_options.php`) | - | |
| `method` | `$this->{option_data.method}(option_data.params)` (컨트롤러 메서드) | `method`, `params` | |
| `code` | `getCodeList(params)` (sml_cd≠000, use_yn=Y) → render 기본 `cmb_cd`/`cd_name` | `params` | |
| `db` | `$this->db->where(params)->get(table)` | `table`, `params` | |
| `model` | 모델 로드 후 `call_user_func_array([model, method], ['select'=>array_values(render), 'data'=>params])` | `model`, `method`, `params` | **주의** — PHP 8 에서 문자열 키가 named argument 로 해석되어, 파라미터 이름이 다른 메서드는 Error |
| `custom` | `call_user_func_array(option_data.method, option_data.params)` (전역 함수) | `method`, `params` | |
| `api` / `script` | 분기 없음 → `[]` | - | **미구현** |

`render` (`MY_Controller::getOptionsFromDBList()`):

| 키 | 의미 |
|---|---|
| `id` | option value 컬럼 |
| `text` | option text 컬럼 (`a.b` 면 `b`) |
| `add` | 값이 있으면 text 뒤에 ` (add값)` |

> **주의**
> - 컬럼 매핑은 `render.{id,text,add}`. 예전 문서의 `option_data.select.{id,text,extra}`도 받는다(render 가 비었을 때). `extra`(컬럼 목록, `render.extra`도 됨): db·model·code 옵션에서 그 컬럼 값을 `<option data-{컬럼}>`으로 붙인다(`collectOptionExtras` → `option_extras` → `data-option-extra` → `decorateOptions`).
> - `option_type => 'api'`: `option_data.url`(BASE_URI 기준 경로, 예: `api/frames/options`. 없으면 화면 API의 `/options`)·`method`(get|post)·`params`로 브라우저가 요청(`_mode=option`, `render`의 id·text·add)해서 채운다. `option_type => 'script'`: `window[option_data.method](node, option_data.params)`(`function` 키도 됨)가 돌려준 목록(`{값: 라벨}` 또는 `[{id, text}]`). 둘 다 서버 쪽 옵션은 비어 있다(`getOptions`가 `[]`).
> - 콜백(2026-10-28 구현, `app-page-form.js loadOptionSources`, `preparePlugins` 처음): 값은 `'함수명'` 또는 `['callback' => 함수명, 'params' => 값]`.
>   - `onload`: 옵션을 채운 뒤 `window[이름](params ?? node)`
>   - `onchange`: 값이 바뀔 때 `window[이름](params ?? node, event)`
>   - `render.callback`: 옵션마다 `window[이름](option, params ?? node)` — `false`면 그 옵션을 빼고, 문자열이면 라벨로 씀
> - 체크박스 구분자 `option_attributes.seperator` 는 읽히지 않아 항상 `,` 다(`MY_Builder_API::setData()`, `applyFrmValuesByCategory()`).

### 6-3. 저장할 때 선택지 값 검사 (`MY_Builder_API::validateOptionValues()`)

등록·수정·부분 수정(POST·PUT·PATCH)은 form 규칙 검사 뒤에 선택형 필드 값이 선택지에 있는지 확인한다. 없으면 422(`type: in_list`, "{label}은 선택할 수 있는 값이 아닙니다."). 엑셀 가져오기도 같은 기준(`getValidatableOptionValues()`).

- 대상: `type`이 `select`·`radio`·`checkbox`이고, 요청에 값이 있고, 기본 그룹(`group`이 없거나 `base`)인 필드. 빈 값은 `required` 규칙이 맡는다. 다중 값은 배열이나 `seperator` 문자열을 하나씩 본다.
- 제외(서버가 선택지를 알 수 없거나 믿을 수 없음): `option_attributes`가 비었거나 `option_type`이 `''`·`none`·`blank`·`api`·`script`, 옵션 설정이 없어 더미(Option 1/2)가 되는 `field`·`static`, 선택지가 빈 필드.
- `option_attributes.validate => false`: 그 필드는 검사하지 않는다(직접 입력도 받는 select 등).
- 수정(PUT·PATCH)은 지금 저장된 값이면 통과한다 — 선택지가 "사용 중인 것만"이라 그 사이 빠진 값 때문에 저장이 막히지 않게.
- 선택지에 없는 값만 막는다. 그 밖의 엔티티 규칙(예: 게시판의 BRD000 제외, 게시글의 게시판 존재)은 엔티티 API 훅에서 따로 확인한다.

### 6-4. 동적 옵션 (JS, `app-page-form.js`)

- `setDynamicSelectOptions(selector, params)`: `GET {url | BASE_URI/add_uri | API_URI/options}?_mode=option&{form_field}={value}[&render=]` → `prepareSelectOptions(target, json.data)`.
- `prepareSelectOptions(node, data)`: 객체 `{value:text}` 와 배열 `[{id,text}]` 를 모두 받는다. `data-original-value` 가 옵션에 있으면 선택을 복원하고 selectpicker 를 리빌드한다.
- 서버 `MY_Builder_API::options_get()` 은 일반 GET 이고, `_mode=option` 이면 `beforeResponse()` 에서 `getOptionsFromDBList($data['list'], $data['render'])` 로 변환한다.
- 보조 함수: `reloadSelect2()`, `resetSelect2()`, `setSelect2Readonly()`.

---

## 7. group / group_attributes

### 7-1. 키

| 키 | 타입 | 기본값 | 설명 | 위치 |
|---|---|---|---|---|
| `label` | string | `''` | 그룹 라벨 | `restructure_admin_form_data()` |
| `form_text` | string\|array | `''` | 그룹 도움말 | 동일 |
| `type` | string | `'base'` | 그룹 뷰 이름 → `form_{x}_group_{type}`. `base` 이고 `group_repeater` 면 `repeater_{repeater_type}` 로 바뀜 | `setFormData()`, `restructure_admin_form_data()` |
| `key` | string | `''` | 그룹 `data` 배열의 키(뷰에서 `extract()` 되는 변수명). 비면 `{group}{순번}` | `restructure_admin_form_data()` |
| `envelope_name` | bool | `false` | `true`: name `group[field]` | `get_group_field_name()` |
| `group_repeater` | bool | `false` | 반복 그룹. name 에 index 삽입 | 동일 |
| `repeater_type` | string | `'base'` | `base`(뷰 `repeater_base`) / `jquery`(jquery-repeater) / `manual`(플러그인 없이 직접 추가·삭제, `form_side_group_repeater_manual.php` — `<template>`의 `__INDEX__`를 다음 행 번호로 바꿔 붙임, 저장된 행은 확인 후 `deleteRepeater`) | `setFormData()`, `addManualRepeaterRow()` |
| `repeater_id` | string | `''` | 행 식별 필드. 비어있는 행은 서버 호출 없이 삭제 | `preparePlugins()` 리피터 hide 콜백 |
| `repeater_count` | int | `1` | repeater_base 초기 행 수 | `form_side_group_repeater_base.php` |
| `colspan` | int | `12` | grid 열 너비 | `restructure_admin_form_data()` |
| `tab_index` | int | `0` | tabs 레이아웃의 탭 (그룹 블록에는 항목이 아니라 이 값이 쓰임) | 동일 |

같은 그룹의 `group_attributes` 는 첫 항목 값에 이후 항목 값이 누적 병합되므로(`setFormData()`) **공통 설정은 첫 항목에** 두고 뒤 항목에는 `key` 만 둔다.

> **주의** — 서버 `validateFormRules()` 는 반대로 그룹의 **마지막 항목** `group_attributes` 만 본다. `envelope_name`/`group_repeater` 를 첫 항목에만 두면 서버에서는 non-envelope 로 처리되므로, 검증이 필요한 그룹은 이 값을 마지막 항목에도 둔다.

### 7-2. name / id

`MY_form_helper.php` `get_group_field_name()` / `get_group_field_id()` (렌더 시 index=0)

| envelope_name | group_repeater | name | id (사이드) |
|---|---|---|---|
| false | false | `field` | `form_side-field` |
| true | false | `group[field]` | `form_side-group-field` |
| true | true | `group[0][field]` | `form_side-group-0-field` |
| false | true | `field[0]` | `form_side-field-0` |

이 name 을 실제로 쓰는 뷰는 `form_side_group_base.php`(`get_side_form_input_by_type()` 경유)뿐이다. `addr_daum`, `new_password`, `reply_list`, `repeater_base`, `repeater_jquery` 의 일반 입력과 모든 custom 뷰는 `$item['field']` 를 name 으로 쓴다. jquery 리피터는 `data-repeater-list="{group}"` 로 플러그인이 name 을 `group[i][field]` 로 바꾼다.

**주의** — `repeater_base` 뷰는 모든 행이 같은 name(`field`)으로 전송되고, `form_side_group_base`/`repeater_*` 의 hidden 필드 name 은 정의되지 않은 값으로 만들어진다.

### 7-3. 렌더링

`restructure_admin_form_data()` 가 같은 group 의 항목들을 첫 등장 위치에 하나의 항목으로 합친다:
`['id'=>group, 'category'=>'group', 'group'=>group, 'label', 'form_text', 'type', 'attr'=>group_attributes, 'data'=>[key=>item...], 'colspan', 'tab_index', 'view'=>type]`.

| 그룹 뷰 | 사이드 | 페이지 base | grid | 요구 key |
|---|---|---|---|---|
| `base` | `form_side_group_base.php` | **미구현** (`form_group_base.php` 는 빈 파일) | 없음 | 자유 |
| `repeater_base` | `form_side_group_repeater_base.php` | 없음 | 없음 | 자유 |
| `repeater_jquery` | `form_side_group_repeater_jquery.php` | 없음 | 없음 | 자유 |
| `addr_daum` | `form_side_group_addr_daum.php` | `form_base_group_addr_daum.php` | 없음 | `zipcode`, `addr1`, `addr2` |
| `new_password` | `form_side_group_new_password.php` | `form_base_group_new_password.php` | `form_grid_group_new_password.php` | `password`, `password_confirm` |
| `reply_list` | `form_side_group_reply_list.php` | `form_base_group_reply_list.php` | 없음 | `reply_content` 등 |
| `carousel_simple` | 없음 | 없음 | `form_grid_group_carousel_simple.php` (에셋 자동 등록) | - |

주소 입력은 별도 type 이 아니라 `group_attributes.type = 'addr_daum'` 그룹이다. 검색 버튼이 `findAddress()`(`app-page-utils.js`)를 호출해 `[data-group-name][data-group-key=zipcode|addr1]` 에 값을 넣는다. 프로그램으로 넣은 값이라 `data-input-changed` 는 바뀌지 않지만, readonly 라서 `detect_changed=0` → 항상 제출된다.

### 7-4. JS 값 적용

`app-page-form.js` `applyFrmValues()`:
1. 필드를 그룹별·카테고리별(inputs/selects/checkboxes/radios/textareas/excepts/files/customs)로 분류한다.
2. jquery 리피터면 `data[group].length` 만큼 `[data-repeater-create]` 를 클릭한다.
3. `window['applyFrmValues' + Pascal(group)]` 함수가 있으면 거기에 위임한다: `(form, data[group], groupAttr, fields, dto)`.
4. 없으면: base → `data`, envelope 그룹 → `data[group]`(리피터면 행마다 `getFrmInputDto()` 로 index 치환), non-envelope 그룹 → `data` 에서 직접. **주의** — non-envelope 리피터의 index 치환은 ReferenceError 로 실패한다.

### 7-5. 서버 검증과 행 삭제

`MY_Builder_API::validateFormRules()`:

| 경우 | 검증 데이터 | 422 param |
|---|---|---|
| base | 최상위 필드 | `field` |
| envelope, non-repeater | `data[group]` | `group[field]` |
| envelope, repeater | `data[group][i]` — **값이 하나라도 비어있는 행은 검증에서 제외** | `group[i][field]` |
| non-envelope, repeater | 최상위 `field[i]` 들을 행으로 재조립. **주의** — 루프 범위가 한 행 더 많음 | `field[i]` |
| non-envelope, non-repeater | 최상위 필드 | `field` |

`deleteRepeater()`(`app-page-common.js`)는 행의 `data-group-field` 값들을 `PATCH {API}/deleteRepeater/{identifier}` 로 보내지만 서버 `deleteRepeater_patch()` 는 `DATA_DELETED` 만 응답하는 스텁이다. 실제 삭제는 컨트롤러에서 오버라이드해야 한다.

---

## 8. 폼 레이아웃

메서드 설정(`type`/`subtype`/`properties.*`)의 구조는 [route-method-nav.md](route-method-nav.md) 참고.

### 8-1. 페이지 폼 (method category `form`)

`MY_Builder_WEB::prepareFormData()` 가 `formType = methodConfig.type`, `formSubType = methodConfig.subtype` 를 넘기고 `form.php` 가 `form_{formType}.php` 를 포함한다.

| methodConfig.type | 뷰 | 설명 |
|---|---|---|
| `base` | `form_base.php` | subtype `base`: 한 줄에 라벨(col-2) + 입력(col-10). subtype `grid`: 12 컬럼 행(`set_form_rows()`) |
| `tabs` | `form_tabs.php` | `fields[0..3]` 을 각 탭에서 `form_inputs.php` 로 렌더. **주의** — 탭 4개(이름 포함)가 하드코딩 |
| `modal` | `form_modal.php` | `form_base` 를 `formType='base'` 로 포함 |
| `side` | `form_side.php` | 사이드 레이아웃 |
| `auth`, `article` 등 | 각 전용 뷰 | `custom` 타입은 `trans_formdata_dit_type()` 으로 `[field\|group => item]` 맵을 받는다 |

설정 예: `builder_method_config.php` 의 `method_form_base_config`(type base / subtype base), `method_form_base_grid_config`(subtype grid).

폼 공통 hidden: `_mode`(router method), `_event`(`submit`/`dup_check`)(`form_base.php`). 사이드 폼은 `_mode=''` 로 시작하고 `readyFrmInputs()` 가 채운다.

### 8-2. 리스트 + 사이드 폼 (method category `list`)

`properties.formExist=true` 이면 `properties.formConfig/formType/formSubType/formStyle` 를 쓴다(`MY_Builder_WEB::setJSVariables()`, `prepareListData()`). 리스트 쪽 설정은 [list-filter.md](list-filter.md).
- 렌더링은 항상 `restructure_form_data_by_type(FORM_DATA)` 기본 인자(`side`, `base`)로 처리되고, `list.php` 가 `form_side` 를 `formType='side'` 로 고정 포함한다. **`properties.formType` 은 화면 레이아웃에 영향이 없다.**
- `formStyle` 은 offcanvas 의 `data-form-style` 로만 출력되며 CSS 는 `[data-form-style="wide"]` 만 정의돼 있다(`public/assets/builder/css/custom.css`).

### 8-3. JS 변수 (`common.*`)

| 변수 | 값 | 사용처 |
|---|---|---|
| `FORM_DATA` | `setFormData()` 결과 (form_attributes 원본, options 포함, data-* 미포함) | 모든 라이프사이클 함수 |
| `FORM_REGEXP` | `config['regexp']` (`application/config/extra/regexp_config.php`) | `reformatFormData()` |
| `FORM_TYPE` | 페이지: `methodConfig.subtype`, 리스트: `properties.formType` | **미사용** |
| `FORM_STYLE` | 리스트만 | **미사용** |
| `FORM_EXIST` | 리스트만 true | `app-page-list.js` 초기화 |

### 8-4. grid / tabs

- grid: colspan 합이 12 를 넘으면 다음 행으로 넘긴다(`set_form_rows()`). 그룹 블록의 colspan 은 `group_attributes.colspan`.
- tabs: `min(tab_index)..max(tab_index)` 범위로 `fields[i]` 를 만든다(`reformat_form_data_by_type()`). `form_inputs.php` 가 **행 배열**을 기대하므로 `subtype=grid` 와 함께 써야 한다. tab_index 종류가 4개 미만이면 `form_tabs.php` 에서 undefined index 가 난다.

---

## 9. rules 와 검증

### 9-1. 클라이언트 (FormValidation)

`app-page-utils.js` `reformatFormData(form, FORM_DATA, FORM_REGEXP)` 가 필드별 validators 를 만든다.

- 제외: `type=hidden`, `form_attributes.form_validation === false`.
- selector 탐색 순서: `[name=field]` → `[name=field[]]` → 그룹(`[name^=group][name$=[field]]` / `[name^=field]`) → `#id`.
- `errors.required` 가 있으면 `notEmpty`. `errors` 는 서버(`setColumnErrors()`)에서 규칙별 메시지로 채워진다.
- `type=date` → `date(YYYY-MM-DD)`, `type=file` → `file(extension, maxFiles=max, type=accept)`.
- 나머지 규칙(`required`, `trim` 제외)은 `customValidatorsPreset.rules` 또는 `FORM_REGEXP` 에서 찾는다. 없으면 console.warn 후 무시한다.

| CI 규칙 | FV validator (`app-page-validators.js` `customValidatorsPreset`) | 비고 |
|---|---|---|
| `required` | `notEmpty` | errors.required 필요 |
| `exact_length[n]` / `min_length[n]` / `max_length[n]` | `stringLength` | 같은 키라 옵션이 합쳐짐 |
| `min[n]` / `max[n]` | `greaterThan` / `lessThan` | 정수만 |
| `matches[f]` | `identical` | |
| `password_matches[f]` | `identical` | **주의** — compare 함수의 변수 참조 오류로 실행 시 ReferenceError |
| `required_if_value[f\|v]` | `requiredIfValue` | |
| `required_if_empty_data[...]` / `required_if_empty_file[...]` / `required_if_article_file_empty` | `requiredIfEmpty` | `#{id}-list` 에 항목이 있으면 통과 |
| `required_mod[m]` | `requiredMod` | 현재 `_mode` 가 m 에 포함되면 필수. **주의** — 정규식이 `\w+` 라 `required_mod[add\|edit]` 는 매칭 안 됨 |
| `is_numeric` | `isNumeric` | |
| `is_unique[...]` | `isUnique` | submit 이벤트일 때 `{name}_unique` hidden 이 `1` 이어야 통과. **주의** — edit 분기의 원래값 비교가 오류 |
| `max_files[n\|...]` | `checkFileCounts` | 목록 개수 < n |
| `dropzone_required` | `dropzoneRequired` | |
| `concat[a,b\|sep]` | `concatFields` | 값을 조합해 대상 필드에 기록 |
| `numeric`, `alpha*`, `decimal`, `integer`, `is_natural*`, `valid_date`, `version_callable` | `regexp` (FORM_REGEXP) | 여러 개면 마지막 것만 남음 |

페이지 add/edit 에서는 `required_mod[x]` 가 현재 method 와 맞으면 PHP 가 `required` 로 바꾸므로(`setColumnErrors()`) `notEmpty` 로 처리된다. 리스트 사이드 폼(method=`index`)에서는 바뀌지 않아 `requiredMod` 로 처리된다.

### 9-2. 서버

`MY_Builder_API::validate()` → `validateFormRules()` → `validateManually()`. `beforePost()`/`beforePut()` 에서 모델 파이프라인마다 호출한다.

1. `validateFormRules()`: rules 가 없으면 `do_nothing`, `type=common` 제외, 그룹별로 `set_rules/set_data/run`(7-5). 이어서 `file_rules`(`required_if_empty_file`, `max_files`, `regexp_config.php`)를 정규식으로 찾아 직접 호출한다.
2. 실패하면 HTTP **422** (`setValidateFormErrors()`, `MY_Controller_API::responseError()`):
   ```json
   {"code":null,"msg":null,"data":{...post},"errors":[{"location":"MY_Builder_API::validateFormRules","param":"group[0][field]","value":"...","type":"min_length","msg":"..."}]}
   ```
3. `validateManually()`: POST 일 때 모델 `notNullList` 중 식별자/PK/FK 생성키/`validateCallback` 제외 키가 없거나 비면 `EMPTY_REQUIRED_DATA`. 모델 `fileList` 키는 `$_FILES` 로 검사한다.
4. `checkUniqueExist()`: 모델 `uniqueKeyList` 기준으로 중복이면 409 `DATA_ALREADY_EXIST`.

클라이언트의 422 처리(`app-page-add.js`, `app-page-edit.js`, `app-page-list.js` 의 submitAjax `error` 콜백): `fv.fields[error.param]` 이 있을 때만 표시한다. `password_matches`→`identical`, `required`→`notEmpty` 는 해당 validator 메시지로, 그 외는 `server` validator 로 표시한다.

> **주의**
> - 그룹 param(`group[field]`)의 서버 에러는 FV 필드 키(`field`)와 달라 화면에 표시되지 않는다. `server` validator 도 `reformatFormData()` 에서 등록되지 않는다.
> - PUT 은 POST 와 같이 전체 form rules 로 검증한다. PATCH 는 본문에 있는 필드의 rules 만 검증한다(`validatePatchFormRules()`). 프론트는 add/edit 모두 POST 로 보낸다(11-3).
> - `checkUniqueExist()` 는 `_mode` 를 GET 에서만 읽어, 폼 POST(add)에서도 add 로 인식하지 않는다 → `editable=true` 가 아닌 고유키는 중복검사가 생략된다.
> - CI3 는 빈 값에 `required/isset/matches` 외 규칙을 실행하지 않는다. 서버의 `required_mod`, `required_if_value`, `required_if_empty_data`, `password_matches` 는 빈 값에서 동작하지 않으므로 필수 검사는 클라이언트와 `validateManually()`(notNullList)에 의존한다.

### 9-3. 커스텀 규칙 (`application/libraries/MY_Form_validation.php`)

| 규칙 (메서드) | 동작 |
|---|---|
| `strong_password` | 공백 불가, 숫자 + 영문 필수 |
| `required_if` | 비어있으면 실패 |
| `check_conditions[t.c\|cols\|vals]` | 조건부 중복 |
| `edit_unique[t.f.id]` | id 제외 중복 |
| `required_mod[m\|m]` | `_mode`(GET → POST) 가 m 에 있으면 필수 |
| `required_if_value[f\|v]` | f 값이 v 면 필수 |
| `required_if_empty_data[t.f.id]` | add 면 필수, 아니면 DB 값도 비었을 때 필수 |
| `required_if_empty_file[name\|t.f.id]` | add 면 파일 필수, 아니면 DB 값이 없을 때 파일 필수 |
| `is_numeric` | PHP is_numeric |
| `is_unique[t.f.key]` | key 값 제외, `del_yn='N'` 만 대상 |
| `valid_youtube` | Youtube 라이브러리 검증 |
| `max_files[n\|t.f.id]` | add: 파일 존재, 그 외: DB 개수 < n |
| `do_nothing` | 항상 통과 |
| `password_matches[f]` | add 면 matches, 그 외엔 양쪽 값이 있을 때만 |
| `concat[a,b\|sep]` | 필드 조합 == 값 (sep 에 `&#95;` 같은 엔티티 허용) |
| `addMethod(name, regexp)` | 정규식 기반 규칙을 동적으로 등록 |

---

## 10. 파일 필드

| 단계 | 내용 | 위치 |
|---|---|---|
| WEB 업로드 설정 탐색 | `{web router class}_{field}_upload_config` → `{field}_upload_config` → `base_upload_config` | `setFormColumn()` |
| 자동 속성 | `max_size = get_file_size_to_kb(max_size)` (CI 의 KB 값을 1024 로 나눠 MB 숫자가 됨, 예: 5120 → `5`, dropzone `dz-max-filesize` 용), `accept = .ext,...` (allowed_types 변환). 사용자 attributes 가 우선 | `setFormColumn()`, `MY_file_helper.php` `get_file_size_to_kb()`, `convert_allowed_types_to_accept()` |
| 설정 파일 | `base_upload_config`(allowed_types 없음), `thumbnail_` / `uploads_` / `category_icon_upload_config` | `application/config/extra/custom_upload_config.php` |
| 클라이언트 검증 | FV `file(extension, maxFiles=max, type=accept)` | `reformatFormData()` |
| 제출 | 선택된 파일만. 1개면 `name`, 여러 개면 `name[]`. **주의** — `max` 속성이 있으면 다중 파일이 0개 전송됨 | `getFormData()` |
| 서버 업로드 | 폼 설정이 아니라 모델 `fileList` 기준. `{api router class}_{key}_upload_config` → `{key}_upload_config` → `base_upload_config`. **allowed_types 가 없으면 예외** → base 설정으로는 업로드 불가 | `MY_Builder_API::uploadFileInList()` |
| 서버 규칙 | `file_rules` 정규식(`required_if_empty_file`, `max_files`) 별도 실행 | `validateFormRules()` |
| 목록/삭제 | `with_list` + `list_delete` → `deleteFile_patch()` (`type`, `file_id`) | `deleteFile()`, `MY_Builder_API::deleteFile_patch()` |
| 응답 변환 | GET 시 fileList 값(file_id)을 파일 DTO 배열로 교체 | `MY_Builder_API::transformView()` |

---

## 11. JS 라이프사이클

### 11-1. 단계

함수는 모두 `public/assets/builder/js/app-page-form.js`.

| 단계 | 함수 | 하는 일 | 이벤트 detail |
|---|---|---|---|
| onLoadedLayout | `onLoadedLayout(form)` | 시작 표시 | `{formSelector}` |
| preparePlugins | `preparePlugins(form)` | selectpicker, select2, maxlength, autosize, select2-repeater, jQuery repeater, Sortable, show_if 초기 숨김, flatpickr, cleave, tagify, dropzone, coloris, clipboard | `{formSelector}` |
| resetFrmInputs | `resetFrmInputs(form, fields)` | reset_value/default/set_default_first 가 아닌 노드 값 비움, detect_changed=0 노드 input-changed=false, original-value 제거, file 초기화, `#{id}-list` 비움, 리피터 1행으로, 라이프사이클 플래그 초기화(onLoadedLayout 제외), `safeReset(fv)`, `record=null` | `{formSelector}` |
| readyFrmInputs | `readyFrmInputs(form, mode, fields)` | view_mod / writable / editable 처리, `_mode` 설정, add·filter 모드면 default 값 적용(`applyFrmValues(..., triggerEvent = mode==='filter')`) | `{formSelector, mode}` |
| fetchFrmValues | `fetchFrmValues(form, key, params)` | **동기** AJAX `GET {API_URI}/{key}?_mode=form` → 전역 `record` | `{formSelector, record}` |
| applyFrmValues | `applyFrmValues(form, data, fields, triggerEvent=true)` | 7-4 참조 | `{formSelector, record}` (triggerEvent 일 때만) |
| refreshPlugins | `refreshPlugins(form, data)` | selectpicker disabled 동기화, autosize 높이, Quill 생성, 빈 목록 숨김, coloris 값 | `{formSelector, record}` |
| checkFrmValues | (FV `core.form.valid` 핸들러) | 검증 통과 표시 | `{formSelector}` |
| transFrmValues | (submitAjax success 콜백) | 제출 완료 | `{formSelector}` |

`updateFormLifeCycle(state, form, detail)` 이 `common.FORM_LIFECYCLE[form.name][state]=true` 로 기록한 뒤 **form 요소에 `CustomEvent(state)`**(bubbles=false)를 디스패치한다.

> **주의** — 모든 폼이 같은 `FORM_LIFECYCLE` 객체를 공유해서(복사 아님) 필터 폼과 레코드 폼의 단계 플래그가 섞인다.

### 11-2. 모드별 흐름

| 모드 | 흐름 | 위치 |
|---|---|---|
| 페이지 add | onLoadedLayout → preparePlugins → resetFrmInputs → readyFrmInputs('add') → refreshPlugins → (submit) checkFrmValues → POST → transFrmValues | `app-page-add.js` ready 핸들러 |
| 페이지 edit | onLoadedLayout → preparePlugins → resetFrmInputs → readyFrmInputs('edit') → fetchFrmValues(KEY) → applyFrmValues → refreshPlugins(record) → … | `app-page-edit.js` ready 핸들러 |
| 리스트 사이드 초기화 | onLoadedLayout → preparePlugins → resetFrmInputs | `app-page-list.js` (`common.FORM_EXIST` 블록) |
| 리스트 add 버튼 | readyFrmInputs('add') → (`readyFrmInputs` 이벤트) offcanvas.show → (`show.bs.offcanvas`) refreshPlugins(record) | `app-page-list.js` `getListButtons()`, offcanvas 이벤트 핸들러 |
| 리스트 edit 버튼 | readyFrmInputs('edit') → fetchFrmValues → (`fetchFrmValues` 이벤트) applyFrmValues + show → refreshPlugins | `app-page-list.js` `.edit-record` click 핸들러 |
| 리스트 닫기 | `hidden.bs.offcanvas` → resetFrmInputs | `app-page-list.js` |
| 리스트 제출 성공 | dt reload + transFrmValues → offcanvas.hide | `app-page-list.js` `core.form.valid` 핸들러 |
| filter | onLoadedLayout → preparePlugins → resetFrmInputs(FILTER_COLUMNS) → readyFrmInputs('filter') → refreshPlugins. 초기화 버튼 시 reset → ready → refresh 반복, `filterSearchClicked`/`filterResetClicked` window 이벤트 ([list-filter.md](list-filter.md)) | `app-page-form.js` `activateFilterForm()` |
| view | preparePlugins → `fetchViewData` 이벤트 → refreshPlugins ([view-excel.md](view-excel.md)) | `app-page-view.js` ready 핸들러 |

### 11-3. 제출 데이터 (`getFormData` / `checkInputSubmittable`)

`app-page-ajax.js` `submitAjax()` 는 항상 `POST {API_URI}[/{API_URI_ADD}][/{identifier}]?{API_PARAMS}` 로 보낸다(add 모드는 식별자 생략). `app-page-common.js` `getFormData()` 가 name 이 있는 모든 input/textarea/select 를 돌며 `checkInputSubmittable()` 이 참일 때만 append 한다.

1. file: 파일이 선택된 경우
2. checkbox/radio: **체크된 경우에만** (아래 조건과 AND)
3. `data-detect-changed` 가 있고 false → 전송
4. hidden → 전송
5. `required` 속성 → 전송
6. `data-input-changed` true → 전송
7. `required-mod` 에 현재 `_mode` 포함 → 전송

즉 기본(detect_changed=true) 상태에서 **필수가 아니고 변경되지 않은 필드는 전송되지 않고**, 서버는 그 컬럼을 갱신하지 않는다. 체크 해제된 체크박스는 보내지 않는다.

### 11-4. 확장 포인트

| 훅 | 사용법 | 위치 |
|---|---|---|
| 라이프사이클 이벤트 | `document.querySelector('#formRecord').addEventListener('refreshPlugins', e => e.detail.record ...)` | 예시: `public/assets/admin/js/system_onload.js` |
| 클래스별 JS | `{class}[_{method}]_preset.js`(head) / `_onload.js`(tail). `PLATFORM_ASSET_JS_PATH` 에 없으면 빈 파일을 자동 생성. index/list/add/edit/excel 은 method 접미사 없음 | `MY_Builder_WEB::afterViewApp()` |
| `appPlugins.form.select2[name]` | select2 옵션 덮어쓰기, `onHandler.{change,'select2:select'}`, `beforeHand()` | `initSelect2()`, `applyFrmValuesByCategory()`, `builder-common.js` |
| `appPlugins.form.tagify[name]` | Tagify 옵션 | `preparePlugins()` |
| `window.applyFrmValues{Group}` | 그룹 값 적용 대체 | `applyFrmValues()` |
| `window.applyFrmValuesCustom{Field}` | custom 필드 값 적용 대체 | `applyFrmValuesByCategory()` |
| `addRepeaterItem` (window 이벤트) | 리피터 행 추가 후 | `preparePlugins()`, `app-page-form-event.js` |
| `form_attributes.change_after` | select2 change 콜백 | 5-2 |

---

## 12. 예시: form_administrators_config

`application/config/extra/admin/admin_form_config.php` 에서 발췌.

```php
$config['form_administrators_config'] = [
    [   // 식별자: hidden + subtype identifier → IDENTIFIER 목록, edit 에서만 required
        'field' => 'administrator_id', 'label' => 'lang:administrator.administrator_id',
        'rules' => 'trim|required_mod[edit]',
        'type' => 'hidden', 'subtype' => 'identifier',
    ],
    [   // 고유값: custom/unique 뷰 + 중복확인 버튼, edit 에서 readonly
        'field' => 'id', 'label' => 'lang:user.id',
        'rules' => 'trim|required|min_length[4]|is_unique[user.id.user_id]',
        'type' => 'custom', 'subtype' => 'unique', 'icon' => 'ri-user-line',
        'form_text' => 'Please enter at least 4 characters',
        'attributes' => ['autocomplete' => 'off', 'placeholder' => 'Enter The User ID'],
        'form_attributes' => [
            'editable' => false, 'with_btn' => true, 'btn_type' => 'dup_check',
            'text_type' => 'eng|num',
        ],
    ],
    [   // 그룹: new_password 뷰, key 로 뷰 변수명 지정
        'field' => 'password', 'rules' => 'trim|required_mod[add]|min_length[4]|max_length[15]',
        'type' => 'password', 'icon' => 'svg:ri-lock-password-line',
        'group' => 'user_password',
        'group_attributes' => ['envelope_name' => false, 'label' => 'lang:user.password',
                               'type' => 'new_password', 'key' => 'password'],
    ],
    [
        'field' => 'password_confirm', 'rules' => 'trim|required_mod[add]|password_matches[password]',
        'type' => 'password', 'subtype' => 'password_confirm',
        'group' => 'user_password', 'group_attributes' => ['key' => 'password_confirm'],
    ],
    [   // cleave 전화번호
        'field' => 'tel', 'rules' => 'trim|required', 'type' => 'tel', 'subtype' => 'cleave-hp',
    ],
    [   // edit 모드에서만 보이는 readonly
        'field' => 'recent_dt', 'type' => 'text', 'subtype' => 'readonly',
        'form_attributes' => ['view_mod' => 'edit'],
    ],
];
```

`id` 필드가 리스트 사이드 폼에서 렌더링된 결과(요약, `form_side_custom_unique.php`):

```html
<input type="hidden" name="id_unique" id="form_side-id_unique">
<input type="custom" name="id" id="form_side-id" value=""
  autocomplete="off" placeholder="(lang 처리된 placeholder)" aria-label="..." aria-describedby="id-ico"
  required="required" minlength="4"
  data-form-sync="1" data-reset-value="1" data-detect-changed="1" data-with-btn="1" data-with-list="0"
  data-list-sorter="0" data-list-onclick="download" data-list-delete="0" data-form-validation="1"
  data-editable="0" data-btn-type="dup_check" data-text-type="eng|num" data-form-field="id"
  class="form-control dt-id form-input_base form-input_with-button form-input_custom-unique ...">
<button type="button" data-rel-field="id" class="btn ... btn-dup-check" onclick="checkDuplicate(this)" disabled>Check</button>
```

- `category` 를 쓰지 않았으므로 `form-input_base` 클래스가 붙는다. 렌더링은 `type=custom`, `subtype=unique` 로 결정된다.
- FV: `notEmpty`, `stringLength{min:4}`, `isUnique{hiddenId}` (submit 시 `id_unique=1` 필요).
- 서버: `validateFormRules()` 에서 `is_unique[user.id.user_id]`(user_id 값 제외) → `checkUniqueExist()`.
- `password` 그룹은 `envelope_name=false` 이므로 name 은 `password`, `password_confirm` 그대로다.

---

## 13. 이전 문서와 다른 점

| 이전 기술 | 실제 |
|---|---|
| `category` 로 identifier/unique/common/hidden/text/select/tel/date/number/file/textarea/custom/addr 분기 | `category` 는 CSS 클래스뿐. 분기는 `type`/`subtype`. identifier = **subtype**, unique = **custom 의 subtype**, common/hidden = **type** |
| `type: addr` (zipcode, addr1, addr2) | 없음. 주소는 `group_attributes.type = 'addr_daum'` + `key` 가 `zipcode/addr1/addr2` 인 그룹 (7-3) |
| `textarea: quill` | 페이지 base 폼에서 `custom/quill`과 같게 동작(2026-10-28). 저장 때 HTML 정화 |
| `custom` 은 `form_side_{custom}` 호출 | `form_{formType 또는 formSubType}_custom_{subtype}` |
| `group` 이 있으면 `form_side_{group}` 호출 | `form_{...}_group_{group_attributes.type}` (group 이름이 아님) |
| `view_mod: ''` = 전체 노출 | 2026-10-25 수정으로 문서대로 전체 노출 (배열 값도 지원) |
| `list_type` 값 dup_check/addr_daum | 실제 값은 thumbnail/youtube/reply_list/그 외(파일) |
| `repeater_type: manual` | 사이드 폼 뷰 구현(2026-10-28). 페이지 base 폼의 그룹 뷰는 아직 없음 |

이전 문서에 없던 구현: `writable`, `editable`, `form_validation`, `show_if_field/show_if_value`, `change_after`, `set_default_first`, `option_stack`(form_attributes), `wrap_with_border`, `unit`, `sample_file`, `check_delete`, `list_field`, `option_field`, `colspan`, `tab_index`, `group_attributes.repeater_count/colspan/tab_index`, option_type `none/blank/method/설정키`, `render.add`, `attributes.clipboard`, `data-add-hypen`, `appPlugins.form.*`, `applyFrmValues{Group}` / `applyFrmValuesCustom{Field}` 훅, `common` 타입(space/text/divider).
