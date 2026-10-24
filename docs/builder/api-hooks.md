# API 확장 지점 (hooks)

builder API 컨트롤러(`application/controllers/api/*.php`)는 설정(form/list/view config)과 모델 정의만으로 목록·상세·등록·수정·삭제를 처리한다. 커스텀 동작은 부모 클래스의 공통 메서드 몇 개("hook")를 오버라이드해서 넣는다.
이 문서는 "X 동작이 필요할 때 어떤 메서드를 오버라이드하고, 언제 불리며, 무엇을 받고 무엇을 돌려줘야 하며, `parent::`를 불러야 하는가"에 답하기 위한 레퍼런스다. 기준은 `v3` 브랜치 코드다.
관련 문서: 검증 규칙은 [form.md](form.md), 목록·필터·내보내기는 [list-filter.md](list-filter.md), 상세보기·엑셀 가져오기는 [view-excel.md](view-excel.md), 라우트·메서드 설정은 [route-method-nav.md](route-method-nav.md).

**목차**

1. [최소 컨트롤러와 모델](#1-최소-컨트롤러와-모델)
2. [클래스 계층과 3개 층](#2-클래스-계층과-3개-층)
3. [요청별 호출 순서](#3-요청별-호출-순서)
4. [Hook 레퍼런스](#4-hook-레퍼런스)
5. [자주 쓰는 패턴](#5-자주-쓰는-패턴)
6. [공통 모델 메서드](#6-공통-모델-메서드)
7. [상태 변수](#7-상태-변수)
8. [엔드포인트 기능 설정 (api_method_config)](#8-엔드포인트-기능-설정-api_method_config)

---

## 1. 최소 컨트롤러와 모델

### 1-1. API 컨트롤러

엔티티 하나에 필요한 최소 코드다(`application/controllers/api/Companies.php`).

```php
require_once __DIR__.'/Common.php';

class Companies extends Common
{
    function __construct()
    {
        parent::__construct();                          // 1. 반드시 먼저

        $this->routeTitle = '업체';                      // 2. 사용자 로그 문구에 쓰임

        $this->validateModel('Model_Company', 'Model'); // 3. 주 모델은 반드시 별칭 'Model'

        $this->setProperties($this->Model);             // 4. 식별자·파일 목록·config 로드
    }
}
```

| 단계 | 역할 | 빠뜨리면 |
|---|---|---|
| `parent::__construct()` | builder config 로드, `flag` 결정, `Model_User_Logs` 로드 | 아무것도 동작하지 않음 |
| `routeTitle` | `loggingUserlog()` 메시지의 대상 이름("업체 데이터를 등록했습니다") | 로그 문구에 대상 이름이 빠짐 |
| `validateModel($model, $name)` | `load->model()` 후 `Model_Common::validateModelDefinition()`으로 모델 정의를 검사한다. 어긋나면 500 `MODEL_DATA_NOT_COINCIDENCE`(`diffList` 포함)로 응답 | `load->model()`을 써도 동작은 하지만 정의 오류를 못 잡는다(Users, MenuAuth 등은 `load->model()` 사용) |
| `setProperties($this->Model)` | `identifier`, `fileList`, `primaryKeyList`, `uniqueKeyList`를 복사하고, GET/POST이면 `list_/form_/view_{class}_config`를 읽어 `listConfig/formConfig/viewConfig`를 채운다 | 검증 규칙, 중복검사, 파일 처리가 모두 빠짐 |

선택 항목:

- `$this->defaultList = ['field' => 값, ...]`: POST/PUT에서 **요청에 없는 필드**(`isset` 기준)를 채우는 기본값이다. 목록 조건이 아니다. `Model_Parent`의 필수 컬럼(`user_cd`, `del_yn` 등)을 채우는 데 주로 쓴다(`Administrators`, `Partners`).
- 보조 모델: `$this->validateModel('Model_Company')`처럼 별칭 없이 로드하면 `$this->Model_Company`로 쓴다. `Model_Common`, `Model_User`, `Model_File`, `Model_Sys_Code`, `Model_Menu`, `Model_Menu_Auth` 등은 autoload(`config/{env}/autoload.php`)되어 있다.
- 접근 제한: `if($this->flag !== 'admin') show_404();`(SysCode, MenuAuth). `flag`는 URL 첫 세그먼트라서 보안 장치가 아니다(4-주의 참고).

bizsoft의 `Domains.php`(18줄)도 같은 모양이다. 단 거기서는 `validateModel('Model_Company', 'Model')`로 도메인 컨트롤러에 업체 모델을 연결하고 있어 복사 시 모델명을 확인해야 한다.

### 1-2. admin 화면 컨트롤러 짝

API만 있으면 화면이 없다. 같은 클래스명으로 `application/controllers/admin/{Class}.php`를 두고 API URI를 넘긴다(`admin/Companies.php`).

```php
class Companies extends Common   // admin/Common → MY_Builder_WEB
{
    public function __construct()
    {
        parent::__construct();
        $this->titleList[] = 'Companies Management';
        $this->addJsVars([
            'API_URI' => $this->apiUri.'companies',   // → /admin/api/companies
            'API_PARAMS' => [],                       // GET 목록 요청에 붙어 where 조건이 됨
        ]);
    }
}
```

- 라우트·메서드·메뉴 등록은 [route-method-nav.md](route-method-nav.md).
- config 이름: WEB·API 모두 `builder_config_name(class)`(snake 형태)를 쓴다. 예: `partnerAuth` → `form_partner_auth_config`. API는 `resourceConfig`를 모르므로 WEB에서 다른 이름을 빌려 쓰면 `$formConfigName` 등을 지정한다([form.md](form.md) 1-1).
- URL: `custom_routes.php`가 `/{platform}/api/{class}/{num}` → `api/{class}/index/{num}`, `/{platform}/api/{class}/{seg}` → `api/{class}/{seg}`로 보낸다. `{seg}_get` 같은 메서드가 없으면 `RestController::_remap()`이 `index_get($seg)`로 넘기므로 문자열 키(`USR001`)도 key로 들어온다.

### 1-3. 모델 프로퍼티

모델은 `Model_Common`(→ `MY_Model`)을 상속하고 프로퍼티만 선언한다(`Model_User.php`, `Model_Administrator.php`).

| 프로퍼티 | 타입 / 기본값 | 의미 | builder에서 쓰는 곳 |
|---|---|---|---|
| `table` | string | 테이블명(prefix 제외) | 모든 쿼리 |
| `identifier` | string `''` | 단일 식별 컬럼. `isAutoIncrement`인데 비어 있으면 생성자가 `primaryKeyList[0]`으로 채움 | `checkIdentifierExist()`, `addData()`(insert id 기록), `setModData()`, `makeDeleteIdData()`, `checkDuplicate()` |
| `primaryKeyList` | array `[]` | PK 컬럼. AI 모델은 정확히 1개여야 함 | non-AI 모델의 식별(GET 파라미터), `beforeAdd()` 중복 확인 |
| `uniqueKeyList` | array `[]` | 고유값 컬럼 | `checkUniqueExist()`(409), non-AI 수정 시 where |
| `foreignKeyList` | array `[]` | `['col' => ['table'=>, 'column'=>, 'model'=>]]` | `applyForeignKeyValues()`, `getGeneratedForeignKeyFields()`(부모-자식 저장) |
| `notNullList` | array | 필수 컬럼 | `validateManually()`(POST만), `setData()` 허용 컬럼, int 기본값 0 |
| `nullList` | array | 선택 컬럼 | `setData()` 허용 컬럼 |
| `strList` | array | 문자열 컬럼 | `setData()`에서 빈 값 → `''`, 검색 `like`(필드 미지정 시 전체 strList OR) |
| `intList` | array | 정수 컬럼 | `setData()`에서 `(int)` 변환 |
| `fileList` | array | 파일 id 컬럼 | `uploadFileInList()`(POST), `transformView()`가 파일 DTO 배열로 치환 |
| `isAutoIncrement` | bool `false` | AI 여부 | 식별 방식, insert id 반환 |
| `isDelYn` | bool `false` | `del_yn` 사용 | 조회 시 `del_yn='N'` 자동 조건, `delData()` soft delete |
| `isUseYn` | bool `false` | `use_yn` 사용 | 조회 시 where에 `use_yn` 키가 없으면 `use_yn='Y'` 자동 조건 |
| `useDelYnAsBase` / `useUseYnAsBase` | bool `true` | 위 자동 조건을 끌 때 `false` | `MY_Model::where()` |
| `isCreatedDt` / `isCreatedId` | bool `false` | insert 시 `created_dt` / `created_id`(세션 `user_id`, 없으면 1) 자동 기록 | `Model_Common::getValidSetData()` |
| `isUpdatedDt` | bool `false` | update 시 `updated_dt` 자동 기록. **`isUpdatedId`는 없다** — `updated_id`는 `isCreatedId`를 따른다 | 같음 |
| `filteringDateColumn` | string | 날짜 필터 대상. `isCreatedDt`면 기본 `created_dt` | `setFilterDate()` |
| `defaultOrderBy` | array | 선언만 있고 **읽히지 않는다**(6장 주의) | - |

`validateModelDefinition()` 규칙: `notNullList ∩ nullList = ∅`, (notNull+null+del_yn/use_yn) 컬럼 집합 = (str+int+file+del_yn/use_yn) 집합, identifier/PK/unique/FK 컬럼이 모두 컬럼 목록에 있을 것, AI면 PK 1개이고 identifier가 그 안에 있을 것. `created_*`, `updated_*`, `del_yn`, `use_yn`은 플래그로 처리하므로 목록에 넣지 않아도 된다(`Model_User`처럼 `del_yn`을 notNull에 넣으면 strList에도 넣어야 한다).

조회에 join이 필요하면 모델에서 `getList/getData/getCnt`를 오버라이드해 `$dto['join'][]`을 붙인다(`Model_Administrator`). 이때 셋 모두 같은 join을 넣어야 count와 목록 조건이 맞는다.

> **주의**
> - 주 모델 별칭은 반드시 `'Model'`이다. 부모·자식 모델은 `'Model_Parent'`, `'Model_Child'`(5-7).
> - `setProperties()`는 모델을 로드한 **뒤**에 부른다. GET/POST/PUT/PATCH에서 config를 읽는다(DELETE에서는 `formConfig`가 빈 배열). PATCH는 본문에 있는 필드의 rules만 검증한다(`validatePatchFormRules()`).
> - non-AI 모델(복합 PK)은 URL의 key를 쓰지 않고 **PK 컬럼을 GET 파라미터로만** 식별한다(3-6).
> - join select `'*'`나 기본 select에는 `password`, `user_api_key`도 포함되어 그대로 응답된다. 응답에서 빼려면 `transformView()`에서 `unset`한다.

---

## 2. 클래스 계층과 3개 층

```
CI_Controller
 └ MY_Controller                 response()/setResponseData(), getOptions(), getCodeName(), uploader(), delFileData()
   └ RestController              (libraries) _remap(): {method}_{http} 디스패치, 인증, response() 원형
     └ MY_Controller_API         index_{get,post,put,patch,delete} → handleX → beforeX/afterX, response()(exit), responseError(), validateModel()
       └ MY_Builder_API          builder CRUD 파이프라인, 검증, 업로드, 응답 가공, 내보내기/엑셀, 공통 엔드포인트
         └ api/Common            apiRoute, beforeList(페이징), transformView(공통 필드), transformViewByMode, sendEmail
           └ api/{Entity}        엔티티 컨트롤러 (hook 오버라이드)
```

hook은 성격에 따라 세 층으로 나뉜다.

| 층 | 메서드 | 하는 일 | 오버라이드 시점 |
|---|---|---|---|
| HTTP 층 | `beforeGet/Post/Put/Patch/Delete`, `afterGet/Post/Put/Patch/Delete` | 요청 바디·쿼리 파싱, 식별자(`idData`) 결정, 기본값·검증·중복검사·업로드, list/view/add/modify/remove 분기 | 요청 데이터를 검증 **전후로** 바꾸거나, 요청 단계에서 거절할 때 |
| CRUD 층 | `list`/`view`/`add`/`modify`/`remove` 각각의 `beforeX → executeX → afterX` | 조회 조건 조립, DB 실행, 후처리와 응답 | 조회 조건·저장 흐름·연관 데이터 처리 |
| 응답 가공 층 | `transformList`, `transformView`, `transformViewByMode`, `beforeResponse`, `loggingUserlog` | 행 단위 가공, 옵션 변환, 사용자 로그 | 응답에 계산 필드 추가, 로그 정책 변경 |

`MY_Controller_API`의 기본 `beforeX/afterX`는 바디를 읽어 그대로 돌려주는 echo 동작이다. builder 엔티티는 `MY_Builder_API`가 이를 덮어쓴 버전을 쓴다.

---

## 3. 요청별 호출 순서

표기: `★` = 엔티티에서 오버라이드할 수 있는 hook. `→ exit`는 `response()`가 출력 후 `exit`한다는 뜻이다(이후 코드는 실행되지 않는다).

### 3-1. 공통 진입

```
HTTP 요청 /admin/api/{class}[/{key}|/{action}]
MY_Builder_API::_remap()          $this->mode ← GET _mode, $this->submode ← GET _submode, apiBaseUri/apiUri 설정
 └ RestController::_remap()       {seg}_{http} 메서드가 있으면 그것(예: options_get, checkDuplicate_get)
                                  없으면 index_{http}($seg). 세션 user_id가 없으면 401 → exit
```

`$this->mode`는 `_remap()`에서 채워지므로 **생성자 안에서는 항상 `''`**다. 또 GET 쿼리에서만 읽으므로 폼 POST 바디의 `_mode`는 반영되지 않는다.

### 3-2. GET 목록

```
index_get($key=0) → handleGet($key)
 ├ ★beforeGet($key, $strict=false)
 │    MY_Controller_API: $data = input->get()
 │    checkIdentifierExist($key) → $this->idData           (3-6)
 │    reformat_get_data($data, exceptValidateKeys) → ['where'=>…, 'filter'=>…, 'select'=>…, 'render'=>…]
 │    return [$key, $data]
 └ afterGet($key, $data)          Model 프로퍼티 없으면 500. idData가 비어 있으면 list(), 있으면 view()
    └ list($data, $isResponse=true)
       ├ ★beforeList($data)       api/Common: format=datatable|log 이면 extraFields(draw, recordsTotal, recordsFiltered)
       │                          계산 후 data['limit']=['limit'=>n,'offset'=>pageNo*n]. data['extraFields'] 설정
       ├ ★executeList($data, $model=null)
       │    $list = Model->getList($data['select'] ?? [], $data)
       │    $data['list'] = ★transformList($list)  → 행마다 ★transformView($row)   (3-7)
       └ afterList($data, $isResponse)
            $isResponse=false 이면 $data 반환(응답 없음)
            ★beforeResponse($data, true)   _mode=option 이면 list → 옵션 맵, 아니면 loggingUserlog()
            response(DATA_RETRIEVED, data=$data['list'], extra=$data['extraFields']) → exit
```

### 3-3. GET 상세

```
handleGet($key) → ★beforeGet (idData 채워짐) → afterGet → view($key, $data)
 ├ ★beforeView($key, $data)       checkDataExist(['where'=>idData]) 없으면 DATA_NOT_EXIST(404) → exit
 ├ ★executeView($key, $data)      $row = Model->getDataWhere([], $this->idData)   ($data의 where는 쓰지 않음)
 │                                $data['view'] = ★transformView($row)
 └ afterView($key, $data)         ★beforeResponse($data, true)
                                  response(view ? DATA_RETRIEVED : DATA_NOT_EXIST, data=view) → exit
```

응답의 `data`는 `setResponseData()`가 객체를 배열로 감싸므로 `[ {row} ]` 형태다.

### 3-4. POST (등록 / 수정)

프론트 폼은 등록·수정 모두 POST로 보낸다. 수정이면 URL에 식별자가 붙는다([form.md](form.md) 11-3).

```
index_post($key=0) → handlePost($key)
 ├ ★beforePost($key, $model=null)
 │    MY_Controller_API: $data = input->post()
 │    checkIdentifierExist($key) → idData
 │    defaultList: isset 아닌 필드에 기본값
 │    getModelPipeline() 순서(Model_Parent → Model → Model_Child) 중 존재하는 모델마다:
 │       validate($data, $model)   = validateFormRules(formConfig) 실패 시 422
 │                                 + validateManually(notNullList) 실패 시 400 EMPTY_REQUIRED_DATA
 │                                   (앞 모델이 생성할 FK 컬럼은 필수 검사 제외)
 │       checkUniqueExist($data, $model)  중복이면 409 DATA_ALREADY_EXIST
 │    count($this->fileList) > 0 이면 uploadFileInList($data)  → $data[field] = file_id (실패 시 500)
 │    return [$key, $data]
 └ afterPost($key, $data)         idData 있으면 modify($key, $data, true), 없으면 add($data, false)

add($dto, $bool=false)
 ├ ★beforeAdd($dto, $model=null)  non-AI + PK 모델: PK+unique 값으로 idData 구성, 이미 있으면 DATA_ALREADY_EXIST(409) → exit
 ├ ★executeAdd($dto, $bool, $model=null)
 │    $model=null 이면 Model_Parent → Model → Model_Child 순서로 존재하는 모델마다:
 │       applyForeignKeyValues($dto, $m)   빈 FK 컬럼을 dto[fk.column] 값으로 채움
 │       $params = setData($dto, $m)       컬럼만 남김, trim, int 변환, password 해시, checkbox join
 │       $params = addData($params, $bool, $m)   INSERT. AI면 insert id → params[identifier], idData[identifier]
 │       $dto = array_merge($dto, $params)
 │    $model 지정 시 그 모델 하나만 (반환값은 setData를 거친 컬럼 배열)
 └ ★afterAdd($dto)                beforeResponse($dto) (반환값 무시, 로그만)
                                  response(DATA_CREATED, data=idData, 201) → exit

modify($key, $dto, $bool=true)
 ├ ★beforeModify($key, $dto)      기본 동작 없음(그대로 반환)
 ├ ★executeModify($key, $dto, $bool, $model=null)
 │    $model=null 이면 각 모델마다 setModData($dto, $m) → [$set, $where]
 │      메인 Model은 $where를 $this->idData(URL·쿼리로 확인한 식별자)로 바꿈
 │      checkModifyIdData($where, $m): 식별자가 빠지면 400 → $set에서 식별자 컬럼 제거 → modData($set, $where, $bool, $m)
 │       AI 모델:     where = [identifier => $dto[identifier]]       ← $this->idData가 아니라 바디 값
 │       non-AI 모델: where = 바디의 PK+unique 값 (set에서는 제거)
 │    $model 지정 시 setData 후 modData($dto, $this->idData, …)
 │    반환: $model=null 이면 입력 $dto 그대로(폼의 비컬럼 필드 포함)
 └ ★afterModify($key, $dto)       beforeResponse($dto) (반환값 무시)
                                  response(DATA_EDITED, data=idData) → exit
```

### 3-5. PUT / PATCH / DELETE

```
PUT    index_put → handlePut
        ★beforePut($key)   beforePost와 같다. 단 바디는 input->put()(urlencoded만 파싱), 업로드 없음
        afterPut           idData 있으면 modify(), 없으면 add()

PATCH  index_patch → handlePatch
        ★beforePatch($key) checkIdentifierExist($key) 후 input->patch(). 기본값·검증·중복검사·업로드 모두 없음
        afterPatch         modify($key, $data, true)  (목록 인라인 토글 등)

DELETE index_delete → handleDelete
        ★beforeDelete($key) checkIdentifierExist($key). $data = input->put() → DELETE에서는 항상 []
        afterDelete         remove($key, $data, true)

remove($key, $data, $bool=true)
 ├ ★beforeRemove($key, $data)     기본 동작 없음
 ├ ★executeRemove($key, $data, $bool)
 │    $base = getDeleteBaseRow()   idData 비면 show_error(500 HTML), 행 없으면 DATA_NOT_EXIST → exit
 │                                 = Model->getDataWhere([], idData) (모델의 join 포함)
 │    getModelPipeline() 역순(Model_Child → Model → Model_Parent)마다:
 │       where = makeDeleteIdData($m, $base)   AI면 [identifier => base->identifier], 아니면 PK 값들
 │       $m->delData(where, $bool)              isDelYn이면 del_yn='Y'(soft), 아니면 DELETE
 └ ★afterRemove($key, $data)      beforeResponse($data) → response(DATA_DELETED) → exit
```

### 3-6. 식별자 결정 (`checkIdentifierExist`)

| 모델 | idData | 비고 |
|---|---|---|
| `isAutoIncrement` | `[identifier => $key ?: GET[identifier]]` | key가 0/빈 값이면 GET 파라미터를 본다 |
| non-AI + `primaryKeyList` | PK 컬럼을 **모두** GET에서 찾으면 `[pk => 값, …]`, 일부만 있으면 `[]` | URL key는 쓰지 않는다. `$strict=true`면 일부만 있을 때 500 |
| 둘 다 아님 | `[]` | |

`idData`는 GET에서 list/view 분기, POST/PUT에서 add/modify 분기, 응답 `data`, 삭제 대상 조회에 쓰인다.

### 3-7. 행 가공 순서 (`transformView`)

엔티티 → `api/Common` → `MY_Builder_API` 순으로 호출되지만, 실제 처리 순서는 각 클래스가 `parent::transformView()`를 앞에서 부르느냐 뒤에서 부르느냐로 정해진다. 부모 쪽 처리 내용:

1. `MY_Builder_API::transformView()`: GET `_mode`가 `list`/`view`면 `transTargetKeys`의 값을 옵션 텍스트로 치환. `fileList` 컬럼을 `Model_File` 행 배열로 치환(`'0'`이나 없는 파일은 `null`).
2. `api/Common::transformView()`: `0000-00-00` → `''`, `recent_dt`(updated_dt 우선) 추가, `created_id`/`updated_id`를 **사용자 로그인 id 문자열로 덮어씀**, 마지막에 `transformViewByMode()`.
3. `api/Common::transformViewByMode()`: `$this->mode === 'form'`이면 select2 multiple 필드를 `explode(',')`.

> **주의**
> - `beforePost()`에서 parent 이후에 바꾼 값은 검증·중복검사를 거치지 않는다. 검증 대상이 되어야 하면 parent 호출 전에 `$_POST`를 바꾸거나 `beforeAdd/beforeModify`에서 직접 검사한다.
> - 기본 `executeModify()`는 메인 `Model`의 수정 대상을 `$this->idData`(URL·쿼리로 확인한 식별자)로 정한다. 부모·자식 모델은 바디의 식별자/PK를 쓰므로 폼에 해당 hidden 필드가 있어야 한다. 식별자가 하나라도 빠지면 `checkModifyIdData()`가 400으로 멈추고, 모델 단에서도 `Model_Common::validateWhereCondition()`이 빈 WHERE의 수정·삭제를 거부한다.
> - `afterAdd/afterModify/afterRemove`가 부르는 `beforeResponse()`의 반환값은 버려진다. 응답 data는 `idData`로 고정이다.
> - JSON 바디는 읽히지 않는다(`input->post()`가 빈 배열을 돌려 `?? json()`이 동작하지 않음). DELETE의 `$data`는 항상 `[]`이다.
> - 각 INSERT/UPDATE는 개별 커밋된다. `afterAdd/afterModify`에서 오류 응답을 보내도 앞서 저장된 행은 남는다.

---

## 4. Hook 레퍼런스

"parent" 열: **필수** = 부르지 않으면 기본 기능이 빠진다. **선택** = 기본 동작이 없거나 대체가 목적일 때만 생략. **마지막** = 자기 처리 후 마지막 줄에서 호출(부모가 응답하고 exit하므로).
"빈도" 열: 엔티티 컨트롤러에서 오버라이드한 파일 수(템플릿 / bizsoft_dev).

### 4-1. HTTP 층

| 메서드 | 호출 시점 | 인자 | 반환 / 효과 | parent | 빈도 |
|---|---|---|---|---|---|
| `beforeGet($key = 0, $strict = false)` | GET 진입 직후 | URL key | `[$key, $data]`. `$data`는 `reformat_get_data()` 결과(`where`, `filter`, `select`, `render`) | **필수**(앞에서). idData 설정과 조건 변환을 함 | 2 / 2 |
| `afterGet($key, $data)` | beforeGet 직후 | | idData 유무로 `view()`/`list()` 분기 | 대체 목적일 때만 생략 | 0 / 0 |
| `beforePost($key = 0, $model = null)` | POST 진입 직후 | URL key (`$model`은 무시됨) | `[$key, $data]`. 기본값·검증·중복검사·업로드 완료된 바디 | **필수**(앞에서) | 2 / 3 |
| `afterPost($key, $data)` | beforePost 직후 | | idData 있으면 `modify(…, true)`, 없으면 `add(…, false)` | 거의 오버라이드 안 함 | 0 / 0 |
| `beforePut($key = 0, $model = null)` | PUT 진입 직후 | | beforePost와 같음(업로드 없음) | **필수** | 1 / 1 |
| `beforePatch($key = 0, $model = null)` | PATCH 진입 직후 | | `[$key, $data]`. idData만 설정, 검증 없음 | **필수** | 0 / 0 |
| `beforeDelete($key = 0)` | DELETE 진입 직후 | | `[$key, []]`. idData 설정 | **필수** | 0 / 0 |
| `afterPut` / `afterPatch` / `afterDelete` | 각 before 직후 | | modify/add/remove로 분기 | 거의 오버라이드 안 함 | 0 / 0 |
| `index_get($key = 0)` 등 | `_remap()` 디스패치 | | 파이프라인 전체를 버리고 직접 응답 | 생략(완전 대체) | 0 / 2 |

### 4-2. CRUD 층

| 메서드 | 호출 시점 | 인자 | 반환 / 효과 | parent | 빈도 |
|---|---|---|---|---|---|
| `beforeList($data)` | `list()` 첫 단계 | beforeGet의 `$data` | 조건이 추가된 `$data`(array). `where`, `whereIn`, `whereNot`, `like`, `orderBy`, `join`, `filter`, `limit` 키를 쓸 수 있다(6-2) | **필수**. `api/Common` 쪽이 페이징·count를 함. 조건은 parent **호출 전에** 넣어야 count에도 반영됨 | 7 / 9 |
| `executeList($data, $model = null)` | beforeList 다음 | | `$data`에 `'list'`(가공된 행 배열)를 넣어 반환 | 선택. 대체하면 `$data['list'] = $this->transformList($rows)`를 직접 넣어야 함 | 3 / 4 |
| `afterList($data, $isResponse = true)` | executeList 다음 | | `$isResponse=false`면 `$data` 반환, 아니면 응답 후 exit | 거의 오버라이드 안 함 | 0 / 1 |
| `beforeView($key, $data)` | `view()` 첫 단계 | | `[$key, $data]`. 기본: 행 없으면 404 | **필수**(존재 확인) | 0 / 0 |
| `executeView($key, $data)` | beforeView 다음 | | `[$key, $data]`, `$data['view']`에 가공된 행 | 선택 | 0 / 0 |
| `afterView($key, $data, $isResponse = true)` | executeView 다음 | | 응답 후 exit | 거의 오버라이드 안 함 | 0 / 0 |
| `beforeAdd($dto, $model = null)` | `add()` 첫 단계(검증 완료 후) | 바디 dto | 수정된 `$dto`. 기본: non-AI PK 중복 시 409 | **필수**. `$dto = parent::beforeAdd($dto, $model);` 형태 권장 | 2 / 4 |
| `executeAdd($dto, $bool, $model = null)` | beforeAdd 다음 | `$bool=false`(insert id를 받음) | 생성 id가 병합된 `$dto` | 선택. 보통 parent 호출 후 추가 작업 | 2 / 3 |
| `afterAdd($dto)` | executeAdd 다음 | 생성 id가 들어간 dto(비컬럼 필드 포함) | 응답 후 exit | **마지막** | 0 / 3 |
| `beforeModify($key, $dto)` | `modify()` 첫 단계 | URL key, 바디 dto | 수정된 `$dto` | 선택(기본 동작 없음). 관례상 `return parent::beforeModify($key, $dto);` | 2 / 3 |
| `executeModify($key, $dto, $bool, $model = null)` | beforeModify 다음 | `$bool=true` | `$dto` | 선택. 오버라이드할 때도 `checkModifyIdData()`로 식별자를 확인한다(3-4) | 4 / 4 |
| `afterModify($key, $dto)` | executeModify 다음 | 입력 dto | 응답 후 exit | **마지막** | 4 / 6 |
| `beforeRemove($key, $data = [])` | `remove()` 첫 단계 | `$data`는 항상 `[]` | `$data` | 선택. 삭제 거부는 여기서 `response()` | 1 / 3 |
| `executeRemove($key, $data, $bool)` | beforeRemove 다음 | | `$data` | 선택. 보통 parent 후 정렬 재계산 등 | 2 / 2 |
| `afterRemove($key, $data = [])` | executeRemove 다음 | | 응답 후 exit | **마지막** | 0 / 3 |

### 4-3. 응답 가공 층

| 메서드 | 호출 시점 | 인자 | 반환 / 효과 | parent | 빈도 |
|---|---|---|---|---|---|
| `transformList($list)` | executeList 안 | 행 객체 배열 | 배열. 기본: 행마다 `transformView()` | 선택. 목록 전체를 보고 가공할 때(예: 한 번에 조회) | 1 / 1 |
| `transformView($data)` | 목록 행마다, 상세 1회, 내보내기 행마다 | stdClass 행 | stdClass(`: object` 반환 타입) | **필수**. 앞/뒤 위치에 따라 보는 값이 다름(3-7) | 7 / 13 |
| `transformViewByMode($data)` | `api/Common::transformView()` 마지막 | | stdClass | 선택. `$this->mode`별 분기를 따로 두고 싶을 때 | 0 / 0 |
| `beforeResponse($data, $isFetch = false)` | list/view 응답 직전(`$isFetch=true`), add/modify/remove 응답 직전(`false`) | | list/view에서만 반환값이 응답에 쓰임 | **필수**. 생략하면 사용자 로그와 `_mode=option` 변환이 빠짐 | 1 / 1 |
| `loggingUserlog()` | beforeResponse 안 | | `Model_User_Logs`에 insert. 운영 환경에서 sys admin은 건너뜀 | 로그 정책을 바꿀 때 대체 | 0 / 0 |

### 4-4. 보조 메서드 (필요할 때 오버라이드하거나 hook 안에서 호출)

| 메서드 | 용도 | 비고 |
|---|---|---|
| `setData($data, $model = null)` | 저장 직전 정규화: 모델 컬럼만 남김, 공백 압축·trim, `strList` 빈 값 `''`, `intList` `(int)`, `gender` 대문자, `password` 해시(빈 값이면 제외), checkbox 배열 join, onoff 미전송 시 `'N'` | 필드별 규칙을 추가하려면 parent 결과에 덧붙인다 |
| `setModData($data, $model = null)` | `setData` + 바디 기준 수정 where 계산 | 3-4 참고 |
| `checkModifyIdData($idData, $model)` | 수정 where가 식별자(AI면 identifier, 아니면 primaryKeyList 전체)를 모두 갖췄는지 확인, 아니면 400 | 3-4 참고 |
| `validate($data, $model, $validate, $configName, $exceptRequiredKeys)` | 폼 규칙 + 필수값 검사. 다른 config로 검사할 때 직접 호출 | 실패 시 응답 후 exit |
| `validateFormRules($configName = '', $data = [])` | `form_*_config` 규칙 검사(422) | [form.md](form.md) 9-2 |
| `checkUniqueExist($dto, $model)` / `checkDuplicate($unique, $model, $dto, $isIncludeDeleted)` | 고유값 검사 | 409로 응답 |
| `checkDataExist($data, $model, $exit)` / `checkCnt()` | `getCnt() > 0` | `$data`는 `['where'=>…]` 형태 |
| `uploadFileInList($dto, $model)` | `model->fileList` 기준 업로드 → file_id | 업로드 설정 탐색은 [form.md](form.md) 10장 |
| `getModelPipeline()` | 부모-자식 모델 순서(`['Model_Parent','Model','Model_Child']`) | `executeAdd/executeModify`는 이 메서드를 쓰지 않는다(주의) |
| `reorderList($sortColumn, $idColumn, $cateColumn, $addCondition, $target, $model)` | 정렬 컬럼 재번호 (BuilderCommonTrait) | Categories, SysCode |
| `validateExcelData($data)` | 엑셀 업로드 행 검증 | [view-excel.md](view-excel.md) Part 2 |
| `getExportsConfig/Header/DataTypes/DataSource/Dataset`, `addExtraDataset`, `prepareExcel`, `setExcelSheetStyle` | `prepareExports_get` 내보내기 | [list-filter.md](list-filter.md) 4-6 |

### 4-5. 공통 엔드포인트 (`MY_Builder_API`)

| 엔드포인트 | 동작 |
|---|---|
| `GET {api}/options?_mode=option&render[id]=&render[text]=` | `options_get()` → GET 목록 파이프라인 → `beforeResponse()`가 `[id => text]` 맵으로 변환. `MenuList`는 완전히 대체 |
| `GET {api}/checkDuplicate?field=&value=` | `checkDuplicate()` → `DATA_ALREADY_EXIST` / `DATA_AVAILABLE` (둘 다 HTTP 409/200) |
| `GET {api}/duplicate/{id}` | 행 복제. 현재 조건이 무시되는 버그가 있다(주의) |
| `GET {api}/isMyData/{key}` | `created_id`가 세션 사용자인지 확인 |
| `GET {api}/getMethodList?class=` | admin flag에서만. route config의 메서드 목록 |
| `GET {api}/prepareExports?exportType=csv\|xlsx`, `GET {api}/downloadExports?filename=` | 내보내기 → [list-filter.md](list-filter.md) |
| `POST {api}/validateExcel`, `POST {api}/uploadExcel` | 엑셀 가져오기 → [view-excel.md](view-excel.md) |
| `PATCH {api}/deleteFile?type=` (body `file_id`) | `delFileData()`로 파일 행과 스토리지 삭제 |
| `PATCH {api}/reorder`, `PATCH {api}/deleteRepeater/{id}` | 스텁(동작 없음). 필요하면 엔티티에서 구현 |

엔티티 전용 엔드포인트는 `{name}_{http}` 메서드를 추가하면 된다(`MenuList::saveAll_post`, `Logs::userAccess_get`).

> **주의**
> - `beforeList()`에서 parent **이후**에 넣은 where는 `recordsTotal/recordsFiltered` count에 반영되지 않는다(`orderBy`는 상관없음).
> - `beforeResponse()`를 오버라이드하고 parent를 부르지 않으면 사용자 로그가 남지 않는다(`MenuList`).
> - `executeAdd/executeModify`는 모델 순서를 하드코딩하므로 `getModelPipeline()`만 바꾸면 검증·삭제 순서와 저장 순서가 어긋난다.
> - API 인증은 세션 로그인 여부뿐이다. `flag`(URL 첫 세그먼트) 검사나 메뉴 권한은 API에서 확인하지 않으므로 민감한 엔드포인트는 hook에서 `session->userdata('user_cd')` 등을 직접 확인한다.
> - `Comments::afterAddData()`처럼 존재하지 않는 hook 이름은 호출되지 않는다. 이름과 시그니처를 이 표와 맞춘다.

---

## 5. 자주 쓰는 패턴

### 5-1. 목록 정렬 지정

`executeList()`에서 `orderBy`를 넣고 parent를 부른다. 클라이언트 요청으로는 orderBy를 바꿀 수 없으므로(GET 키는 모두 where가 됨) 서버에서 정한다.

```php
// bizsoft_dev api/Themes.php
protected function executeList($data, $model = null): array
{
    $data['orderBy'] = ['theme_id' => 'asc'];
    return parent::executeList($data, $model);
}
```

`beforeList()`에서 넣어도 같다(`Categories::beforeList`: parent 후 `$data['orderBy'] = ['category_srt' => 'ASC']`). 지정하지 않으면 `MY_Model::orderBy()`가 AI identifier DESC → `created_dt` DESC 순으로 기본 정렬한다.

### 5-2. 목록 조건 추가

`beforeList()`에서 parent **전에** 조건을 넣는다.

```php
// api/MenuList.php
protected function beforeList($data): array
{
    $data['where']['depth'] = 1;
    $data['orderBy'] = ['srt' => 'asc'];
    return parent::beforeList($data);
}

// api/SysCode.php
protected function beforeList($data): array
{
    $data['whereNot'] = ['sml_cd' => '000'];
    return parent::beforeList($data);
}
```

- 화면에서 고정 조건을 주려면 admin 컨트롤러의 `API_PARAMS`(GET 파라미터 → `where`)를 써도 된다.
- 로그인 사용자 기준 제한: bizsoft `Frames::beforeList`처럼 세션 값을 보고 `$data['whereIn'][...]`를 넣는다.
- join이 필요하면 `beforeGet()`에서 `$data['join']`을 넣거나(`MenuAuth::beforeGet`) 모델의 `getList/getData/getCnt`에서 붙인다.
- `defaultList`는 목록 조건이 아니라 저장 기본값이다.

### 5-3. 응답에 계산 필드 추가

```php
// bizsoft_dev api/Themes.php
protected function transformView($data): object
{
    $data->theme_preview = base_url('/module/iframe/preview/'.$data->theme_path.'?isTheme=1');
    return parent::transformView($data);
}
```

화면 모드별로 다르게 가공하려면 `$this->mode`(GET `_mode`: 목록 `list`, 폼 `form`, 상세 `view`, 옵션 `option`)로 분기한다. 목록에서만 무거운 조회를 하면 폼 로드가 가벼워진다.

```php
// bizsoft_dev api/Apis.php
protected function transformView($data): object
{
    $data = parent::transformView($data);

    switch ($this->mode) {
        case 'list' :
            $data->api_cd = $this->getCodeName(['cmb_cd' => $data->api_cd]);
            break;
        case 'form' :
            $data->api_fields = $this->Model_Api_Fields->getList(
                ['field_name','field_nick','field_feature','use_yn'],
                ['where' => ['api_id' => $data->api_id], 'orderBy' => ['field_id' => 'asc']]
            );
            break;
    }
    return $data;
}
```

- parent를 **먼저** 부르면 `created_id`는 이미 로그인 id 문자열로 바뀌어 있고 파일 컬럼은 배열이다. 원래 값(user_id 숫자, file_id)이 필요하면 parent 전에 읽는다.
- `transformView()`는 행마다 불리므로 안에서 쿼리를 하면 행 수만큼 실행된다. 목록이 크면 `transformList()`를 오버라이드해 한 번에 조회한다.
- 모든 엔티티 공통으로 바꿀 것은 `api/Common::transformViewByMode()`에 둔다.

### 5-4. 저장 전 검증·값 보정

형식 검증은 form config `rules`로 한다([form.md](form.md) 9장). 여러 필드에 걸친 업무 규칙은 `beforeAdd`/`beforeModify`에서 검사하고 `response()`로 끊는다.

```php
// bizsoft_dev api/Frames.php
protected function beforeAdd($dto, $model = null): array
{
    $this->validateCategoryDisplay($dto);   // 위반 시 $this->response(['code' => BAD_REQUEST, 'msg' => …]) → exit
    $this->validateLiveQtime($dto);

    parent::beforeAdd($dto, $model);
    $dto['frame_uuid'] = generate_uuid_v4(); // 서버 생성 값
    return $dto;
}

protected function beforeModify($key, $dto): array
{
    $this->validateCategoryDisplay($dto);
    $this->validateLiveQtime($dto, $key);
    return parent::beforeModify($key, $dto);
}
```

검증 전에 값을 보정하려면 `beforePost()`에서 parent 결과를 고친다(이 경우 고친 값은 검증되지 않는다).

```php
// api/Categories.php
protected function beforePost($key = 0, $model = null): array
{
    list($key, $data) = parent::beforePost($key);
    if($data['use_yn'] === 'N') $data['category_srt'] = 0;
    return [$key, $data];
}
```

등록·수정 공통 처리는 `beforePost`, 한쪽만이면 `beforeAdd`/`beforeModify`를 쓴다. `beforeAdd`에 들어올 때 `$this->idData`는 비어 있고(non-AI 모델은 `parent::beforeAdd()`가 PK 값으로 채움), `beforeModify`에서는 URL key/GET 식별자로 채워져 있다.

### 5-5. 저장 후 연관 데이터 처리

`afterAdd`/`afterModify`의 `$dto`에는 폼의 비컬럼 필드(반복 그룹 등)가 그대로 남아 있고, 등록이면 생성된 identifier도 들어 있다. 처리 후 **마지막 줄**에서 parent를 불러 응답한다.

```php
// bizsoft_dev api/Apis.php
protected function afterAdd($dto)
{
    if($dto['active_yn'] === 'Y') $this->processAPIActiveYN($dto['api_id'], $dto['api_cd']);
    $this->processAPIFields($dto['api_id'], $dto['api_fields']);   // 자식 테이블 delData + addList
    parent::afterAdd($dto);
}

protected function afterModify($key, $dto)
{
    if($dto['active_yn'] === 'Y') $this->processAPIActiveYN($dto['api_id'], $dto['api_cd']);
    $this->processAPIFields($key, $dto['api_fields']);
    parent::afterModify($key, $dto);
}
```

자식 테이블이 여러 개면 bizsoft `Frames`처럼 `processXData($dto)`를 나열하고, 삭제 시 `afterRemove($key)`에서 `frame_id`로 자식 행을 `delData()`한다. 삭제 전에 경로 등 값을 보관해야 하면 `beforeRemove`에서 프로퍼티에 저장해 두고 `afterRemove`에서 쓴다(bizsoft `Templates`).

### 5-6. 저장 흐름 교체

기본 파이프라인이 맞지 않으면 `executeX`를 바꾼다.

```php
// api/MenuAuth.php — 복합 PK 모델에서 PATCH 바디에 PK가 없으므로 idData로 갱신
protected function executeModify($key, $dto, $bool, $model = null): array
{
    $dto = $this->setData($dto, $model);
    $this->modData($dto, $this->idData, $bool, $model);
    return $dto;
}

// api/Categories.php — 저장 후 정렬 재계산
protected function executeAdd($dto, $bool, $model = null): array
{
    $dto = parent::executeAdd($dto, $bool, $model);
    $this->reorderList('category_srt', 'category_id', 'parent_id', ['where' => ['use_yn' => 'Y']], $dto);
    return $dto;
}
```

- 다른 모델에 한 행을 넣을 때는 `parent::executeAdd($dto, $bool, $this->Model_X)`처럼 `$model`을 넘긴다. 이때 반환값은 그 모델 컬럼만 남은 배열이다.
- 목록 파이프라인을 다른 엔드포인트에서 재사용하려면 `$this->list([], false)['list']`(응답 없이 반환, `MenuList::cachingMenu`)나 `$this->handleGet()`(`Logs::userAccess_get`)를 부른다.
- 아예 응답 형식이 다르면 `index_get()`을 오버라이드한다(bizsoft `FrameTraffics`). 이때 `reformat_get_data($this->input->get(), $this->exceptValidateKeys)`로 같은 조건 형식을 얻을 수 있다.

### 5-7. 부모-자식 모델 함께 저장

한 폼이 두 테이블에 걸치면 생성자에서 부모를 `Model_Parent`, 주 모델을 `Model`로 로드하고, 자식 모델에 `foreignKeyList`를 선언한다.

```php
// api/Administrators.php
$this->validateModel('Model_User', 'Model_Parent');
$this->validateModel('Model_Administrator', 'Model');
$this->setProperties($this->Model);
$this->defaultList = ['user_cd' => 'USR001', 'use_yn' => 'Y', 'del_yn' => 'N', 'approve_yn' => 'Y', 'withdraw_yn' => 'N'];

// models/Model_Administrator.php
public array $foreignKeyList = [
    'user_id' => ['table' => 'user', 'column' => 'user_id', 'model' => 'Model_User'],
];
```

동작:

1. 검증(`beforePost`): 각 모델의 `notNullList`를 검사한다. `Model`의 `user_id`는 앞 모델(`Model_Parent`, AI)이 생성할 FK로 판단되어 필수 검사에서 빠진다(`fk.model == get_class(앞 모델)` 또는 `fk.table == 앞 모델 table`, 그리고 `fk.column == 앞 모델 identifier`). 부모의 필수 컬럼은 `defaultList`로 채운다.
2. 등록(`executeAdd`): `Model_Parent` INSERT → `dto['user_id']` 채워짐 → `Model`의 `applyForeignKeyValues()`가 빈 `user_id`를 `dto[fk.column]`로 채움 → `Model` INSERT. 응답 `data`는 두 identifier가 모두 든 `idData`.
3. 수정(`executeModify`): 메인 `Model`은 URL 식별자(`$this->idData`), 부모·자식 모델은 **바디의 identifier**로 where를 만든다. 폼에 `user_id` 같은 부모 식별자 hidden 필드가 있어야 하며, 없으면 400이 난다.
4. 삭제(`executeRemove`): `Model->getDataWhere(idData)`로 기준 행을 읽고(join으로 `user_id` 포함) `Model_Child → Model → Model_Parent` 순서로 각 모델의 identifier 값으로 `delData()`. 기준 행에 부모 identifier 컬럼이 같은 이름으로 있어야 부모까지 삭제된다.

조회는 모델 쪽 join이 담당한다(`Model_Administrator::getList/getData/getCnt`가 `user`를 join).

### 5-8. 에러 응답 보내기

hook 어디서든 `$this->response([...])`를 부르면 출력 후 **exit**한다. 별도 return이 필요 없다.

```php
// api/SysCode.php
if ($this->Model->getCntWhere(['big_cd' => $this->bigCd]) > 1) {
    $this->response(['code' => BAD_REQUEST, 'msg' => 'You Should Delete Codes In The Category']);
}
```

HTTP 상태와 응답 형태(`MY_Controller_API::response()` → `MY_Controller::setResponseData()`):

- `$http_code`를 생략하면 `floor(code / 10)`이 HTTP 상태다. 코드는 `config/extra/custom_status_constants.php`에 있다: `DATA_RETRIEVED 2001→200`, `DATA_CREATED 2010→201`, `DATA_EDITED 2005`, `DATA_DELETED 2006`, `BAD_REQUEST 4000→400`, `EMPTY_REQUIRED_DATA 4002→400`, `NO_PERMISSION 4031→403`, `DATA_NOT_EXIST 4041→404`, `DATA_ALREADY_EXIST 4090→409`, `INTERNAL_SERVER_ERROR 5000→500`.
- 본문: `{status, code, msg, data: [], errors: []}`. `msg`를 생략하면 `status_code_lang.php`의 문구. `data`가 리스트가 아니면 배열로 감싼다. 4xx/5xx에서 `errors`를 안 주면 기본 오류 1건이 들어간다. `extra`의 키는 최상위로 펼쳐진다.
- 필드 단위 오류는 `responseError($error, $response, $http_code)`를 쓴다. `$error`는 `['location','param','value','type','msg']` 한 건 또는 그 리스트. `$response['code']`가 있으면 HTTP 상태는 코드에서 계산되고, 없으면 `$http_code`(기본 500)를 쓴다.

```php
$this->responseError(
    ['location' => __METHOD__, 'param' => 'end_date', 'type' => 'range', 'msg' => '종료일이 시작일보다 빠릅니다.'],
    ['code' => BAD_REQUEST, 'msg' => '기간을 확인하세요.']
);
```

> **주의**
> - `code` 없이 `response(['msg' => …])`만 보내면 HTTP 200, `status:false`, `code:0`이 된다. 항상 `code`를 넣는다.
> - `afterX`에서 오류로 끊어도 저장은 이미 커밋되었다. 되돌려야 하는 검사는 `beforeX`에서 한다.
> - `executeRemove()` 앞에서 삭제 거부 여부를 정하려면 `beforeRemove()`에서 응답한다. `$data`가 비어 있으므로 필요한 값은 `$this->idData`로 다시 조회한다.
> - 디버그용 `show_error()`는 JSON이 아닌 HTML 500을 낸다. API hook에서는 `response()`/`responseError()`를 쓴다.

---

## 6. 공통 모델 메서드

### 6-1. 메서드

`Model_Common`(조회·저장)과 `MY_Model`(쿼리 빌더 조각). `$dto`는 6-2의 조건 배열이다.

| 메서드 | 인자 | 반환 | 비고 |
|---|---|---|---|
| `getList($select = [], $dto = [], $filter = [])` | select 컬럼 목록, 조건, 별도 필터 | stdClass 배열. **select가 1개면 값 배열** | select가 비면 모델 전체 컬럼(+del/use/created/updated) |
| `getListWhere($select, $where)` | | 위와 같음 | `getList($select, ['where' => $where])` |
| `getData($select = [], $dto = [])` | | stdClass 또는 null. **select가 1개면 스칼라** | `limit/orderBy/groupBy` 키는 무시. 조건 키 없이 `['col' => v]`를 주면 조건이 적용되지 않는다 |
| `getDataWhere($select, $where)` | | 위와 같음 | `getData($select, ['where' => $where])` |
| `getCnt($dto = [], $filter = [])` | | int | `limit/orderBy/groupBy` 무시 |
| `getCntWhere($where)` | | int | |
| `addData($set, $bool = false)` | 컬럼 배열, 반환 형식 | `$bool=false`: insert id(non-AI는 0), `true`: 트랜잭션 성공 bool | 모델 컬럼 외 키는 버림. `created_dt/created_id` 자동. non-AI면 `$bool`은 강제로 false |
| `addList($set)` | 행 배열 | bool | `insert_batch`. created 컬럼 자동 |
| `modData($set, $where, $bool = false)` | | `false`: affected rows, `true`: bool | `updated_dt/updated_id` 자동. where 값에 배열 금지(`show_error`). **where가 비면 전체 행 갱신** |
| `modNumb($field, $count, $where)` | | 위와 같음 | `field = field + count` |
| `delData($where, $bool = false, $isSoftDelete = true, $set = [])` | | | `isDelYn`이면 `del_yn='Y'` UPDATE(+`$set`), 아니면 DELETE |
| `checkDuplicate($where, $whereNot = [], $isIncludeDeleted = true)` | | 행 수 | `uniqueKeyList`가 비면 false |
| `reorder($where, $sortField, $sortItem, $newIndex)` | | | 정렬 번호 재부여 |
| `truncate()` | | | |
| `getColumnList()` | | 컬럼명 배열 | notNull+null(+del_yn/use_yn) |
| `getDataQuery($sql, $binds)` / `getListQuery` / `getCntQuery` | 바인딩 SQL | row / result / cnt | 복잡한 쿼리는 이것과 바인딩을 쓴다 |

### 6-2. 조건 배열(`$dto`) 형식

`Model_Common::setCondition()`이 아래 키만 처리한다. 다른 키는 무시되므로 `$data`에 임시 값(`extraFields`, `list` 등)을 함께 넣어도 된다.

| 키 | 형식 | 처리 |
|---|---|---|
| `where` | `['col' => v]` 또는 그 리스트 | 값이 배열이면 `where_in`. 컬럼명에 연산자 가능(`'created_dt >=' => '2024-01-01'`). `'other.col'`처럼 테이블 지정 가능 |
| `whereIn` | `['col' => [v1, v2]]` | `where_in` (스칼라는 `[v]`로) |
| `whereNot` | `['col' => v 또는 [..]]` | `where_not_in` |
| `like` | `['col' => v]`, 값이 배열이면 OR 그룹 | `%v%` |
| `orLike` | - | 현재 setCondition 경유로는 쓸 수 없음(주의) |
| `limit` | `['limit' => n, 'offset' => m]` 또는 `[n, m]` | 목록만 |
| `orderBy` | `['col' => 'asc']` 또는 그 리스트 | 목록만. 없으면 기본 정렬 |
| `groupBy` | `'col'` 또는 `['a','b']` | 목록만 |
| `join` | `['table'=>, 'matches'=>[joinCol => baseCol], 'select'=>'*'\|'a,b'\|[..], 'direction'=>'left']` 또는 그 리스트 | 같은 테이블 join은 한 번만 |
| `filter` | 아래 filter 형식 | `setFilter()` |

`filter` 형식(`reformat_get_data()`가 GET `filters`로 만든다, [list-filter.md](list-filter.md) 5-6):

```php
'filter' => [
    'where'    => ['col' => v],
    'whereIn'  => ['col' => [v1, v2]],
    'whereNot' => ['col' => [..]],
    'like'     => [['field' => 'col', 'value' => 'kw'], ['field' => '', 'value' => 'kw']],  // field 비면 strList 전체 OR
    'date'     => ['on_date' => 'Y-m-d'] 또는 ['start_date' => …, 'end_date' => …],          // filteringDateColumn 기준
]
```

모든 조회에 `isDelYn`이면 `del_yn='N'`, `isUseYn`이면(where에 `use_yn` 키가 없을 때) `use_yn='Y'`가 자동으로 붙는다. 비활성 행까지 보려면 where에 `use_yn`을 명시하거나 모델의 `useUseYnAsBase`를 끈다.

GET 요청이 조건으로 바뀌는 규칙(`reformat_get_data()` → `sanitizeRequestConditions()`): `exceptValidateKeys`(`_mode, _submode, _event, _, select, format, draw, pageNo, limit, searchWord, searchCategory, filters`)를 뺀 파라미터 중 **빈 값(null·''·[])이 아닌 것**은 `where[key] = value`(`'0'`도 조건으로 씀), `filters`는 `filter`, `render`는 그대로 전달된다. `select`는 `reformat_request_select()`가 단순 컬럼명만 남긴다. 그다음 `sanitizeRequestConditions()`가 허용 목록에 없는 `where`·`filters[where|whereIn|whereNot|like]` 키를 버리고, `filters[date]`는 `start_date`·`end_date`·`on_date`만 남긴다. 허용 목록은 `getAllowedRequestFields()`: 메인 `Model` 컬럼(`col`, `{table}.col`) + `Model_Parent`·`Model_Child` 컬럼(`{table}.col`만, 조건이 메인 테이블에 붙기 때문) + form/list/view 설정 필드(테이블 접두어가 있거나 메인 컬럼일 때) + filter 설정 필드(`filter_{class}_{method}_config` 등) + 컨트롤러의 `$allowedRequestFields`. `$deniedRequestFields`(`password`, `user_api_key`, `api_key`, `token`, `secret`, `salt`)와 form `type => 'password'` 필드는 접두어가 붙어도 항상 뺀다. hook에서 직접 넣는 조건은 거르지 않는다.

> **주의**
> - `MY_Model::orderBy()`가 `$this->defaultOrderBy`가 아닌 미정의 지역변수를 읽어 `defaultOrderBy`는 효과가 없다. 정렬은 `executeList/beforeList`에서 지정한다.
> - `orLike` 키는 `setCondition()`에서 인자가 모자라 오류가 난다. OR 검색은 `like`에 배열 값을 쓰거나 `filter.like`(field 빈 값)를 쓴다.
> - `getData()`/`getList()`의 두 번째 인자에 `['col' => v]`를 바로 넣으면 조건이 무시되고 첫 행이 나온다. 반드시 `['where' => …]` 또는 `*Where()` 메서드를 쓴다.
> - `isUseYn` 모델에서 `checkDuplicate()`는 `use_yn='Y'`와 `use_yn='N'`이 동시에 걸려 항상 0을 돌려준다. `check_delete`(isIncludeDeleted)도 기본 `del_yn='N'` 조건 때문에 효과가 없다.
> - GET의 `'0'` 값은 truthy가 아니어서 where 조건이 되지 않는다.

---

## 7. 상태 변수

hook 안에서 읽을 수 있는 프로퍼티. "설정 시점"이 생성자보다 늦은 값은 생성자에서 읽으면 비어 있다.

| 프로퍼티 | 설정 위치 / 시점 | 의미 |
|---|---|---|
| `$this->Model`, `Model_Parent`, `Model_Child` | 엔티티 생성자 | 주 모델·부모·자식. 이름이 고정이다 |
| `$this->mode` | `_remap()` (생성자 이후) | GET `_mode`: `list`, `view`, `form`, `option`, `''`. POST 바디의 `_mode`는 반영 안 됨 |
| `$this->submode` | `_remap()` | GET `_submode`. 코어에서 읽는 곳은 없다 |
| `$this->flag` | `MY_Builder_API::__construct()` | URL 첫 세그먼트. `api`면 `'web'`, `/admin/api/...`면 `'admin'` |
| `$this->routeTitle` | 엔티티 생성자 또는 메서드 | 사용자 로그 문구의 대상 이름 |
| `$this->idData` | `checkIdentifierExist()`(before*), `beforeAdd()`(non-AI), `addData()`(AI insert id) | 현재 대상 행의 식별 조건. 등록·수정 응답의 `data` |
| `$this->identifier`, `primaryKeyList`, `uniqueKeyList`, `fileList` | `setProperties()` | 주 모델에서 복사. `fileList`를 바꾸면 `transformView()`와 업로드 실행 여부에만 영향(실제 업로드 대상은 `model->fileList`) |
| `$this->defaultList` | 엔티티 생성자 | POST/PUT 기본값 |
| `$this->listConfig`, `formConfig`, `viewConfig` | `setProperties()`, GET/POST/PUT/PATCH | `builder_*_base`와 병합된 config. DELETE에서는 `[]` |
| `$this->listConfigName`, `formConfigName`, `viewConfigName` | 부모 생성자가 `''`일 때만 `list_{builder_config_name(class)}_config` 등으로 채움 | 다른 config를 쓰려면 프로퍼티 기본값으로 선언하거나 `setProperties()` 전에 대입 |
| `$this->setConfig` | 프로퍼티 기본값 `true` | `false`면 `setProperties()`가 config를 읽지 않음(`Auth`는 인자로 `false`) |
| `$this->exceptValidateKeys` | 부모 생성자에서 대입 | GET에서 where로 바꾸지 않을 키. 추가하려면 parent 생성자 **이후**에 `[] =` |
| `$this->validateMessages`, `validateCallback` | 부모 생성자에서 `[]`로 초기화 | 필수값 메시지 / 필수 검사를 대신할 메서드명(`[col => 'methodName']`, 인자 없이 호출). parent 이후에 설정 |
| `$this->transTargetKeys` | 부모 생성자에서 `[]` | `['list' => [col…], 'view' => [col…]]`: 해당 모드에서 옵션 값을 텍스트로 치환. parent 이후에 설정 |
| `$this->noIndexMethod` | 부모 생성자 **안에서** 검사 | `true`면 `index` 경로 404. 프로퍼티 기본값으로 선언해야 효과가 있다 |
| `$this->apiRoute`, `apiBaseUri`, `apiUri` | `api/Common` 생성자, `_remap()` | `apiUri` = `base_url({flag}/api/{class})` |
| `$this->methods['{name}_{http}']` | 엔티티 생성자 | RestController 메서드별 `key`(API key 필요 여부), `log`(요청 로그) |
| `$this->devMode` | `MY_Controller` | `ENVIRONMENT !== 'production'` |
| `$this->session->userdata(...)` | 세션 | `user_id`, `user_cd`, `name`, `is_admin`, `is_sys_admin` 등 |

> **주의**
> - 생성자에서 `$this->mode`로 분기하면 항상 `''`이다. 모드 분기는 hook 안에서 한다.
> - `noIndexMethod`를 생성자에서 parent 이후에 `true`로 바꾸면 이미 검사가 끝난 뒤라 무시된다(`Logs`, `Auth`가 이 상태).
> - `validateMessages`, `validateCallback`, `transTargetKeys`, `exceptValidateKeys`를 프로퍼티 기본값으로 선언하면 부모 생성자가 덮어쓴다.
> - `MY_Builder_API`의 `table`, `notNullList`, `nullList`, `strList`, `intList` 프로퍼티는 선언만 있고 채워지지 않는다. 모델 값은 `$this->Model->…`로 읽는다.

## 8. 엔드포인트 기능 설정 (api_method_config)

RestController가 요청마다 제어하는 기능(인증 방식, API 키, 로그, 요청 제한)은 `config/extra/api/api_method_config.php` 한 곳에서 엔드포인트별로 정한다. 컨트롤러 생성자에 `$this->methods`를 쓰거나 `rest_config.php`에 `auth_override_class_method_http`를 추가하지 않는다.

```php
$config['api_method_config'] = [
    'auth' => [                                   // API 클래스: URL 세그먼트 그대로
        'login'  => ['post' => ['auth' => 'none', 'key' => false, 'log' => false]],
        'logout' => ['post' => ['key' => false, 'log' => false]],   // auth 생략 → rest_auth(session)
    ],
    'sysCfg' => [
        '*' => ['*' => ['log' => false]],         // 클래스 전체·모든 HTTP 메서드
    ],
];
```

키 구조: API 클래스 → 메서드(URL 세그먼트 그대로, 기본 메서드는 `index`, 전체는 `*`) → HTTP 메서드(`get`·`post`·`put`·`patch`·`delete`, 전체는 `*`). 한 요청에 여러 항목이 맞으면 `[*][*]` → `[*][http]` → `[메서드][*]` → `[메서드][http]` 순으로 덮어쓴다.

| 키 | 값 | 효과 | 적용 위치 |
|---|---|---|---|
| `auth` | `none`·`session`·`basic`·`digest`·`whitelist` | 인증 방식 덮어쓰기. 생략하면 `rest_auth` | 부모 생성자 전에 `auth_override_class_method_http`로 넣음 → `RestController::_auth_override_check()` |
| `key` | bool | API 키 검사 여부(`rest_enable_keys`가 켜져 있을 때) | `$this->methods['{메서드}_{http}']` → `RestController::_remap()` |
| `log` | bool | `rest_logs` 기록 여부 | 〃 |
| `limit` / `time` | int / 초(기본 3600) | `time` 초 동안 허용할 요청 수(`rest_enable_limits`) | 〃 → `_check_limit()` |
| `level` | int | 필요한 API 키 레벨 | 〃 |
| `permission` | `menu`·`mode`·`super`·`check` | **예정** — API 권한 검사(TODO.md 2장). 지금은 `$this->apiMethodFeature['permission']`에 담기기만 한다 | — |

- 파일 위치: `custom_routes.php`의 `api_folders`에 있는 폴더마다 `config/extra/{폴더}/{폴더}_method_config.php`(`$config['{폴더}_method_config']`)를 둔다. 기본 `api` 폴더는 `config/extra/api/api_method_config.php`. API 폴더의 컨트롤러는 모두 `MY_Controller_API`를 상속하므로(빌더 기능이 필요하면 `MY_Builder_API`) 빌더를 쓰지 않는 API에도 적용된다.
- 읽는 곳: `MY_Controller_API::applyApiMethodAuth()`(부모 생성자 전), `applyApiMethodFeatures()`(부모 생성자 후). 현재 요청에 적용된 값은 `$this->apiMethodFeature`에 있다.
- 없는 메서드로 들어온 요청(`/api/companies/6` 같은 식별자 경로)은 RestController가 `index_{http}`로 처리하므로 설정도 `index` 기준으로 찾는다.

> **주의**
> - 우선순위: 컨트롤러가 `$this->methods`를 직접 지정하면 그 값 > `api_method_config` > `rest_config`.
> - `auth`는 HTTP 메서드를 `$this->input->method()`(실제 요청 메서드)로 판단한다. `X-HTTP-Method-Override`로 메서드를 바꾸는 요청은 RestController와 판단이 다를 수 있다.
