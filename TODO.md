# TODO

빌더 완성 전까지 보류한 작업 목록. (작성: 2026-10-06)

---

## 1. 비밀번호 저장 방식 운영 전환

현재 개발 환경은 `PASSWORD_DECRYPTABLE=TRUE`(복호화 가능한 암호화)로 저장한다.
운영 전환 시 기존 암호화 데이터를 해시로 일괄 변환하고 플래그를 `FALSE`로 바꿔야 한다.

- [ ] `custom_password_verify`가 플래그가 아닌 **저장값 형태**로 판별하도록 변경
    - `password_get_info($hash)['algo']`가 있으면 `password_verify`, 없으면 `decrypt` 비교
    - 플래그는 "새로 저장할 때의 방식"만 결정 → 전환 도중에도 로그인이 깨지지 않음
    - 위치: `application/helpers/encryption_helper.php`
- [ ] CLI 일괄 전환 명령 구현: `php index.php module/migration migrateEncryptedData`
    - bizsoft 운영은 이 명령 대신 `deploy/password-hash.php`로 전환함 (2026-10-17)
    - 위치: `application/controllers/module/Migration.php` (CLI 전용, 현재 뼈대만 있음)
    - 사용자별 `decrypt` → `password_hash` → 저장 (복호화 실패/이미 해시인 행은 건너뜀)
    - 전환 완료 후 `.env.{ENVIRONMENT}`의 `PASSWORD_DECRYPTABLE=FALSE`로 수정
        - 쓰기 권한 확인, 백업(`.bak.날짜`) 후 임시 파일 → `rename`으로 교체
        - 권한이 없으면 수동 변경 안내만 출력
    - 되돌릴 수 없는 작업이므로 실행 전 확인 프롬프트, 재실행 시 안전(멱등)하게
- [x] 순서: 비밀번호 일괄 전환 → `.env` 변경 (bizsoft 운영 2026-10-17: bcrypt 전환 후 `PASSWORD_DECRYPTABLE=FALSE`)
- [x] `.env.production`이 git 추적 중이라 서버에서 수정하면 `git pull` 시 충돌/덮어쓰기 위험 → 4번 항목과 함께 정리 (`57b0483`)
- [ ] 해시 모드의 `findPassword_post`: 현재 "관리자에게 문의" 응답 → 이메일 재설정 링크(토큰) 방식 검토 (`Auth.php` TODO 주석)
- [ ] `composer.json`에 `"ext-mbstring": "*"` 추가 (`get_starred_*`가 `mb_*` 사용)

## 2. API 권한(인가) 검사

로그인만 하면 일반 계정도 관리자 API(`api/administrators`, `api/sysCfg` 등)를 직접 호출할 수 있다.
웹 화면의 메뉴 권한(`menu_auth`)은 메뉴 표시에만 쓰이고 API에는 적용되지 않는다.

- [ ] 검사 위치: `api/Common::auth()` (현재 빈 메서드), `/admin/api/*` 요청에만 적용, `is_sys_admin`은 통과 (추가: `MY_Builder_API::prepareExports_get` 내보내기, `uploadExcel_post` 가져오기 권한도 서버에서 검사 안 함 — 버튼 노출만 제어)
- [ ] API → 메뉴 대응표를 config로 명시 (예: `sysCfg` → `system/sysCfg`), 이름이 같으면 자동 매칭
- [ ] 결정 필요
    - 검사 수준: 요청 방식별 6자리 권한(생성·조회·수정·삭제·내보내기·가져오기) vs 메뉴 접근만
    - 메뉴에 연결되지 않은 API: 로그인만 확인 vs 시스템 관리자만
    - `CACHING_MENU=false`일 때: 웹처럼 건너뜀 vs 항상 검사
- [ ] `isSuper` 메뉴(시스템 설정, 에러 로그 등)는 시스템 관리자만

## 3. 운영 배포 전 설정

- [x] `.htaccess`의 `CI_ENV=development` → 운영 시 `production`으로 변경 (서버 설정에서 주입 권장)
    - bizsoft: 운영 이미지 빌드에서 바꿈 (`deploy/php/Dockerfile`), 저장소의 `.htaccess`는 development 유지
- [ ] `.env`의 `APP_DEBUG`는 코드에서 읽지 않음 → 정리 (에러 노출은 `CI_ENV`가 결정)
- [x] `config/production/hooks.php`의 존재하지 않는 `setPearPath` hook 제거 (`f49aaa6`)
- [ ] 세션·보안 설정: `cookie_httponly`, `csrf_protection`, `allow_any_cors_domain`
- [x] 암호화 키를 `.env`의 `ENCRYPTION_KEY`로 이동 (`MY_Hooks::loadEnv`에서 `encryption_key` 설정, `hex:` 접두사 지원)
- [x] 운영 `ENCRYPTION_KEY` 결정 (현재 의도적으로 비워 둠 → 비어 있으면 `show_error`로 모든 요청 중단)
    - bizsoft 운영: 개발 데이터를 옮긴 뒤 비밀번호 전환을 마치고 무작위 키로 교체 (2026-10-17, 개발 키는 `ENCRYPTION_KEY_PREVIOUS`)
    - 개발 데이터를 운영으로 옮길 경우 개발과 같은 키 사용
    - 새로 시작하면 무작위 키 사용: `php -r "echo 'hex:' . bin2hex(random_bytes(32)), PHP_EOL;"`
- [x] 키 교체 시 기존 암호화 데이터 복호화 불가 → 영향 범위 확인 후 진행
    - bizsoft 확인 결과: 비밀번호는 bcrypt 전환 후 교체, `Match_lib` 링크·`module/Iframe` `v`는 경기 목록 응답마다 새로 만들어 영향 없음, 엑셀 파일명은 일회성
    - 비밀번호(복호화 모드 저장값) → 해시 전환 이후에 교체
    - 엑셀 내보내기 파일명(`MY_Builder_API`), 링크(`Match_lib`)
    - 파생 프로젝트의 외부 배포 URL (예: bizsoft의 `module/Iframe` `v` 파라미터) → 이전 키 fallback 복호화 또는 재배포 필요

## 4. `.env` 관리

- [x] `.env.*`를 git에서 제외하고 `.env.example`만 커밋할지 결정 → 제외함 (`57b0483`, 저장소가 공개라 이전 커밋의 값은 교체 필요)
- [x] JWT 키를 `jwt_config.php`에서 `.env`의 `JWT_KEY`로 이동 (`57b0483`)

## 5. 코드 검토에서 나온 남은 항목

### 보안
- [ ] XSS: 목록 셀 렌더링(`app-page-list.js` `innerHTML`, inline `onclick`), 뷰 출력 `html_escape` 미사용 (추가: `renderColumn` text 셀, 반응형 `responsive.details.renderer`, `getColumnOnclick` redirect/download/view의 `location.href`/`window.open` 문자열(`javascript:` URL 포함), checkbox·select 셀 inline `onchange`의 식별자 값, `app-page-view.js::applyViewData` `SPAN.innerHTML`)
- [ ] XSS: `views/module/player.php`의 `$poster`, `$source` → `json_encode(..., JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT)`
- [ ] 인증 토큰이 JS 전역(`common.HOOK_PHPTOJS_VAR_TOKEN`)에 노출, 32곳 이상에서 개별 주입
- [ ] `module/File.php`: `basePath`를 템플릿 디렉토리로 제한, svg 업로드 제외·MIME 검사, 백업 폴더를 웹 루트 밖으로
- [ ] `api/Setup.php`: 파일 캐시(`init_done`, TTL 1일)만으로 보호 → DB 상태로 판정, SQL 실행은 CLI 마이그레이션으로

### 버그
- [ ] `api/SysCode.php:53` `category_srt` → `cd_srt` (추가: `SysCode::executeModify`의 `$original->category_srt` 비교 때문에 정렬 변경 여부와 무관하게 reorder 실행)
- [ ] `api/Comments.php` `in_array($id, $adminList)` 객체 배열 비교로 `is_admin` 항상 false, 댓글 N+1 쿼리
- [ ] `app-page-common.js:166` 선언되지 않은 `identifier` (추가: `deleteRepeater`에서 ReferenceError → 리피터 행 삭제 요청 실패)
- [ ] `app-page-utils.js` `replaceValidationMessage` 중복 정의
- [ ] `MY_Model::orderBy` 정의되지 않은 `$defaultOrderBy`, `querySql`의 `strpos(...) !== -1` (추가: orderBy는 재귀 호출 인자(`$table`) 누락으로 `defaultOrderBy` 프로퍼티가 전혀 적용되지 않음, querySql은 UPDATE/DELETE도 `insert_id()` 반환)

### 구조
- [ ] `MY_Builder_API`(약 2000줄), `MY_Builder_WEB`(약 1300줄) 기능별 분리 (Validator, Exporter, 권한 서비스)
- [ ] 환경별 config 중복 제거 (development/production 차이 3줄뿐, 세션 테이블명 차이 의도 확인)
- [ ] 빌더 config: `method.layout` 미구현, `normalize_route_config` 미사용(병합 로직 2벌) (추가: `method_base_config.widgets`도 읽는 코드 없음, `get_route_method_base_config()`가 없는 `route_list_datatable_config`·`route_view_base_config`·`route_excel_base_config` 참조)
- [ ] AJAX 기본 동기(`app-page-ajax.js:30`) → 비동기 전환, 비JSON 에러 응답 처리 (추가: DataTable 조회·export 준비, `app-page-form.js::fetchFrmValues`가 동기로 UI 멈춤)
- [ ] `RestController` 부모 클래스 직접 수정 → 어댑터로 분리

### 정리
- [ ] 미사용 파일: `player.bak.php`, `player_original.php`, `player_swarmcloud.php`, 로드되지 않는 JS, 빈 `admin/js` stub (추가: 저장소 루트 `app-page-list.js`·`bakj.app-page-list.js` 사본, 정의되지 않은 `classed`를 참조하는 미사용 `app-page-list.js::renderButtonColumn`)
- [ ] vendor: jQuery 3벌(1.11/2.1/3.7), SweetAlert 1·2 공존 → 정리, `package.json` 도입
- [ ] PHPExcel → PhpSpreadsheet (추가: `MY_Builder_WEB::getExcelSample`의 `Excel_lib`)
- [ ] 기본 컨트롤러 `Welcome` 교체
- [ ] 프로젝트 테스트(PHPUnit), ESLint 도입

## 6. 빌더 설정 조사 (2026-10-23)

README·docs/builder 작성 중 코드 조사에서 나온 항목. 문서에는 짧게 표시하고 전체 목록은 여기에 둔다.

### 보안
- [x] `builder_api_helper.php::reformat_get_data` + `MY_Model::select` GET `select[]` 값이 그대로 `getList()` select로 들어가고, `parse_select_column()`의 `alias_expr` 분기가 `db->select("{expr} as {alias}", false)`(escape 없음)로 실행된다. `?select[]=(SELECT password FROM user LIMIT 1) as x` 같은 서브쿼리 주입 가능 (모든 builder GET 목록·`prepareExports_get`, 로그인 사용자 누구나) → `reformat_request_select()`로 요청 select는 단순 컬럼명만 허용 (식·별칭·테이블 접두어 제거)
- [x] `MY_Builder_API::executeModify`/`setModData` 기본 흐름이 where를 `$this->idData`가 아니라 바디에서 계산한다. non-AI 모델은 바디에 PK가 없으면 where `[]` → `UPDATE` 테이블 전체 (PATCH 인라인 수정, query string으로만 PK를 준 PUT/POST). AI 모델은 identifier가 없으면 Notice + `IS NULL`로 무변경 (MenuAuth/PartnerAuth가 우회 오버라이드 중) → 메인 Model은 `$this->idData`로 수정, `checkModifyIdData()`가 식별자 누락 시 400, `Model_Common::validateWhereCondition()`이 빈 WHERE의 수정·삭제를 거부
- [ ] `MY_Builder_API` 전반 API 인가가 rest_config `session`(user_id 존재)뿐이다. admin WEB의 `user_cd`/`IS_ADMIN` 검사가 API엔 없고, `flag !== 'admin'` 검사(SysCode, MenuAuth, MenuList, PartnerAuth, SysCfg)는 URL 첫 세그먼트라 `/admin/api/...`로 호출하면 통과 (일반 로그인 사용자가 관리자·권한·코드 CRUD 가능)
- [ ] `Model_Common::getSelectList` / `Model_Administrator::getList`·`Model_Partner::getList`(join select `'*'`) 기본 select에 `password` 해시, `user_api_key`가 포함되어 Users/Administrators/Partners 목록·상세 응답으로 그대로 나간다 (자격증명 노출)
- [ ] `MY_Builder_API::deleteFile_patch` body `file_id`만으로 `delFileData()` 실행, 엔티티 소속·작성자 확인 없음 (임의 파일 삭제). `type` 파라미터는 필수인데 쓰이지 않음
- [ ] `MY_Builder_API::duplicate_get` 권한 확인 없이 모든 엔티티에서 GET 한 번으로 INSERT 실행 (아래 버그와 결합)
- [ ] `api/Common::sendEmail` bcc `pensive_lee@naver.com`, 발신자명 `'이광우'` 하드코딩 → 파생 프로젝트의 모든 발송 메일이 개인 주소로 복사됨
- [ ] `MY_Model::addSqlSet`, `MY_Model::addSqlWhere` 값을 문자열 보간해 SQL 생성 (현재 호출부 없음, 사용 시 주입)
- [ ] `api/MenuList::reset_get` GET 요청 하나로 `menu`, `menu_auth`, `partner_auth`를 truncate하고 캐시 삭제 (CSRF·링크 프리페치로 전체 메뉴/권한 유실 가능 — POST/DELETE + 확인 절차 필요) (추가: `MenuAuth::reset_get`, `PartnerAuth::reset_get`, `MenuList::generateMenuCode_get`도 GET으로 truncate/일괄 수정)
- [ ] `MY_Builder_WEB::afterViewApp` 요청 class/method 이름으로 `public/assets/{platform}/js/*_preset.js`·`_onload.js`, `style.css`, `common.js`를 없으면 자동 생성, view 메서드도 제외 안 됨 (운영 웹 요청이 공개 디렉터리에 파일 생성 — devMode 한정 필요)
- [x] `MY_Builder_API::setProperties` GET/POST 외(PUT/PATCH/DELETE)는 설정을 읽기 전에 return → `beforePut`의 `validateFormRules`가 빈 설정으로 돌고 `setData`의 체크박스/onoff 처리도 빠짐 (API 직접 호출로 rules 검증 우회) → PUT·PATCH도 설정을 읽고, PATCH는 본문에 있는 필드만 검증 (`validatePatchFormRules()`). bizsoft-mig에서 재현 확인(2026-10-24)
- [ ] `MY_Builder_API::checkUniqueExist` `$this->mode`를 GET `_mode`로만 읽어 폼 POST의 add를 add로 인식 못함 → `editable=true`가 아닌 고유키는 add에서 중복검사 생략 (중복 데이터 저장)
- [ ] `MY_Form_validation::required_mod` 등 CI3는 빈 값에 `required/isset/matches` 외 규칙을 실행하지 않아 `required_mod`/`required_if_value`/`required_if_empty_data`/`password_matches`가 서버에서 무력 (클라이언트 우회 시 필수값 누락 저장)
- [ ] `app-page-edit.js` FormValidation `core.validator.validating` 핸들러가 `event.element.value`를 마스킹 없이 console.log (edit 화면 비밀번호 평문 노출, add/list는 `32f07d5`에서 마스킹)
- [ ] `MY_Builder_API::getExportsConfig` form config 전체로 컬럼을 정해 `password`, `user_api_key`, hidden 필드까지 CSV/XLSX로 내보냄 (비밀번호 해시·API 키 유출)
- [ ] `MY_Builder_API::prepareExportsPath` 임시 파일을 웹 공개 경로 `public/temps/`에 저장하고 다운로드 안 된 파일은 남음 (파일명 유출 시 직접 접근, 잔존 파일 누적)
- [ ] `builder_api_helper.php::reformat_get_data` 필터·top-level 파라미터의 필드명 화이트리스트 없음 → `filters[where|like][임의컬럼]`이 그대로 조건 (password LIKE로 해시 탐색 가능) (추가: `exceptValidateKeys` 외 모든 top-level GET 키도 where가 되어 `?password=...`로 값 추측(불리언 오라클) 가능)
- [ ] `MY_Builder_WEB::setListActions` `actions.duplicate`는 권한 필터 대상이 아님 (create 권한 없이 복제 아이콘 노출)
- [ ] `MY_Builder_API::uploadExcel_post` 기본 `validateExcelData`가 데이터를 그대로 반환하고 form rules·`checkUniqueExist`·`defaultList`·`fileList`를 거치지 않고 `Model->addList()`로 INSERT (검증 없는 대량 INSERT, POST 경로와 규칙 이원화 — form rules 재사용 검토)
- [ ] `app-page-excel.js::validateData`/`submitData` `validateExcel`·`uploadExcel` AJAX에 `Authorization` 헤더 없음 (다른 호출과 인증 방식 불일치, 서버 측 우회 여부 확인 필요)
- [ ] `MY_Builder_WEB::setupPlatformPage` mode 검사를 원본 `routeConfig['methods'][baseMethod]['category']`가 list일 때만 수행 (category 없는 baseMethod면 notice, list가 아닌 baseMethod route는 add/edit/excel 등 mode 권한 검사 전무)
- [ ] `MY_Builder_API::deleteExcelFile_patch` `class` 입력을 검증 없이 파일 경로에 붙여 `unlink` (경로 조작으로 임의 `*_upload_sample.xlsx` 삭제 가능) (추가: admin 확인도 없음, `public/sample` 밖 경로 삭제 가능)

### 버그

**공통·route/method/nav**
- [ ] `MY_Builder_WEB::setupPlatformMenu` `CACHING_MENU=false`이고 비시스템 관리자(USR001/USR002) 로그인 시 권한 필터 전에 return해 `setCurrentMenu()` 미호출 → `setupPlatformPage()`가 매번 `loggedInRedirect`로 보냄 (무한 redirect, `.env.example` 기본값이 false)
- [ ] `BuilderCommonTrait::saveMenuList` 기존 DB 행을 지우지 않고 `addData()`만 함 (`menu_done` 캐시 유실 + `CACHING_MENU=true`면 메뉴 중복 insert)
- [ ] `BuilderCommonTrait::saveMenuList` `route_config` 전체에 `fillRouteConfigProperties()` 적용 (`methods` 없는 route 하나만 있어도 E_USER_ERROR로 메뉴 저장 전체 실패)
- [ ] `BuilderCommonTrait::getMenuBaseAuth` 메뉴 `method`가 route `methods`에 없으면 undefined index, `class`를 대소문자 구분 매칭 (`myInfo` 메뉴 ↔ `myinfo` route → base_auth `000000`)
- [ ] `MY_Builder_WEB::setupPlatformMenuAuth` `array_reduce` 초기값 없음 (허용 메뉴 0개면 `null` → `array` 타입 속성 대입 오류)
- [ ] `MY_Builder_WEB::setupPlatformRedirect` `menuList[0]` 직접 접근 (`isUse=false`로 첫 항목이 빠지면 `array_filter`가 키를 유지해 notice, 잘못된 redirect)
- [ ] `MY_Builder_WEB::setupPlatformPage` `pageAuth === '000000'`이면 mode 검사를 건너뜀 (list 버튼/액션은 전부 false인데 add/edit 직접 URL 접근은 허용)
- [ ] `MY_Builder_WEB::setRouteConfig` route가 없는 클래스면 `trigger_error` 후 `$config['']`로 계속 진행 (에러 연쇄 — `EmailLogs`, `Company` 컨트롤러가 해당)
- [ ] `MY_Builder_WEB::setMethodConfig` methods에 없는 method면 생성자 안에서 `$this->index()` 호출, 이 시점 `isLoggedIn`은 항상 false (로그인 상태여도 `noLoginRedirect`, allowNoLogin route면 `_remap` 전에 baseMethod 렌더링)
- [x] `MY_Builder_WEB::viewApp` 뷰를 못 찾으면 `$view['subPage']`에 blank 지정 (`$data['subPage']` 미설정으로 빈 페이지 + notice, `builder/layout/blank` 미사용)
- [ ] `builder_method_config.php` `method_list_log_config`의 `actions=[]`/`buttons=[]`가 `fill_config_properties()`에서 list_base 값으로 다시 채워짐 (logs.errors에 add/excel 버튼·edit/delete 액션 노출, base_auth `101101`)
- [ ] `builder_method_config.php` `method_form_auth_config` `actions=[]`가 `array_replace_recursive`로 `{list, delete}`를 못 지움 (auth form에 불필요한 액션 잔존)
- [ ] `BuilderCommonTrait::getMethodList`, `api/MenuList::options_get` noIndex route에서 원본 `properties.baseMethod`를 `??` 없이 읽음 (`visitors`, `logs`에서 undefined index)
- [ ] `builder_route_helper.php::fill_config_properties` `$base`는 연관배열인데 `$config` 같은 키가 스칼라(예: `'includes' => false`)면 TypeError (설정 실수가 fatal)
- [ ] `BuilderCommonTrait::fillMenuConf` `array_merge($item['params'], navDefaultParams)` 순서로 플랫폼 값이 항목 값을 덮어씀 (항목별 `layout` 변경 불가)
- [ ] `MY_html_helper.php::is_current_uri` params 전부 일치를 요구 (`?layout=side-menu` 없이 진입하면 메뉴 active 미표시)
- [x] `MY_Builder_API::setProperties` `$this->input->method === 'get'/'post'`를 메서드 호출이 아닌 없는 프로퍼티로 비교해 항상 false (form `list => true` listConfig fallback과 "form config 비어있음" 에러 블록 미실행, PHP 8 undefined property 경고) → `method()` 호출로 수정. POST 빈 설정 차단은 form 설정 없는 엔티티(MenuAuth 등)를 깨므로 로그만 남김

**form**
- [x] `MY_Builder_API::setData` `checkbox/single`의 스칼라 값에 `join($seperator, $value)` 호출 → PHP 8 TypeError (single 체크박스 저장 시 500)
- [x] `MY_Builder_API::setData` `$checkboxes['option_attributes']['seperator']`를 목록 배열에서 찾아 항상 `,` (구분자 설정 불가)
- [ ] `MY_Controller::getOptions` `model` 분기에서 `call_user_func_array`에 문자열 키(`select`,`data`) 배열 전달 → PHP 8 named argument로 해석돼 `Model_Sys_Code::getBigCodeList()`/`Model_Menu::getParentList()` 등에서 `Unknown named parameter` (option_type model 폼 렌더링 실패)
- [ ] `builder_form_helper.php::get_form_input_by_type` 사이드 폼 radio에서 `get_admin_form_radio($item, $formType)` 인자 부족 호출 → ArgumentCountError (사이드 폼 radio 렌더링 중단)
- [ ] `MY_Builder_API::checkUniqueExist` `if(!array_search(...))`가 index 0을 false로 취급 (폼 설정 첫 필드가 고유키면 중복검사 생략)
- [ ] `MY_Builder_API::validateFormRules` `array_flip`으로 그룹 마지막 항목의 `group_attributes`만 사용 (첫 항목에만 `envelope_name/group_repeater`를 둔 그룹, 예: `form_menu_list_config`의 `attr`가 non-envelope로 검증, `common` 제외 후 index 어긋남 가능)
- [ ] `MY_Builder_API::validateFormRules` non-envelope 리피터 루프 `$i <= $cnt` off-by-one (없는 행 index 경고, 빈 행 검증으로 오탐 422)
- [ ] `BuilderColumnsTrait::setFormColumn`, `MY_Builder_WEB::setListColumns` 기본값(`builder_form_base`/`builder_list_base`)의 `label: ''` 병합 후 존재 검사 → 자동 라벨 `lang:{class}.{field}` fallback이 실행 안 됨 (label 생략 시 폼·목록 헤더 빈 라벨)
- [ ] `builder_form_helper.php::get_admin_form_attributes` 자동 `class`가 사용자 `attributes.class`를 덮어씀 (설정으로 `.form-maxlength` 등 클래스 추가 불가)
- [ ] `views/builder/custom/onoff.php` (`get_admin_form_onoff`) attributes/data-*를 출력하지 않음 → reset 안 됨, `detect_changed` 없어 다른 필드만 수정해 저장하면 onoff 미전송 → `setData`가 `N`으로 덮어씀 (기존 `Y`가 꺼짐)
- [ ] `app-page-form.js::applyFrmValuesByCategory` onoff에서 data가 truthy면 checked (값 `'N'`도 켜짐으로 표시)
- [ ] `app-page-form.js::applyFrmValuesByCategory` `type=file, subtype=dropzone-full`이 기본 분기로 file input에 문자열 대입 (브라우저 InvalidStateError 가능)
- [ ] `app-page-common.js::checkInputSubmittable` 체크 해제된 체크박스는 전송 안 함 (다중 체크박스 "모두 해제" 저장 불가)
- [ ] `app-page-form.js::getFrmInputDto` non-envelope 리피터 분기에서 정의되지 않은 `regex`, `listName` 사용 → ReferenceError (해당 그룹 값 적용 실패)
- [ ] `app-page-validators.js::customValidatorsPreset.rules.password_matches` 정의되지 않은 `password_matches[1]` 참조(`matches[1]`이어야 함) → ReferenceError
- [ ] `app-page-validators.js::customValidatorsPreset.validators.isUnique` edit 분기에서 FV input 객체에 `getAttribute` 호출 → TypeError (edit에서 원래값 유지 시 통과 처리 불가)
- [ ] `app-page-validators.js::customValidatorsPreset.rules.required_mod` 정규식 `\w+`가 `required_mod[add|edit]`와 매칭 안 됨 (클라이언트 검증 누락)
- [ ] `app-page-common.js::getFormData` 다중 파일에서 `parseInt(node.hasAttribute('max'))` → NaN (`max` 속성이 있으면 파일 0개 전송)
- [ ] `app-page-form.js::preparePlugins` 리피터 show 콜백에서 문자열 `cbName`에 `typeof === 'function'` 검사 (`after_{group}_repeater_show` 콜백이 절대 호출 안 됨, `window[cbName]`으로 검사해야 함)
- [ ] `app-page-form.js::updateFormLifeCycle` 모든 폼이 같은 `FORM_LIFECYCLE` 객체를 참조 (필터 폼과 레코드 폼의 단계 플래그가 섞여 `data-input-changed` 판정 오류 가능)
- [ ] `app-page-add.js`/`app-page-edit.js` FV `core.field.init` 핸들러 `classList.contains('.form-input_date-flatpickr')` 점 포함 (flatpickr 변경 시 재검증 안 됨, list는 정상)
- [ ] `app-page-add.js`/`app-page-edit.js`/`app-page-list.js` submitAjax `error` 422 처리: FV 필드 키(`field`)와 서버 param(`group[field]`)이 달라 그룹 필드 서버 에러 미표시, `server` validator가 `reformatFormData`에서 등록 안 됨
- [ ] `app-page-utils.js::reformatFormData` regexp형 규칙이 여러 개면 `regexp` 키가 덮어써짐 (마지막 규칙만 검증)
- [ ] `app-page-form.js::readyFrmInputs` `view_mod: ''`이면 항상 숨김, 배열 지정 시 JSON 문자열이 되어 매칭 불가 (문서상 "전체 노출"과 반대)
- [ ] `views/builder/layout/form_side_group_base.php`, `form_side_group_repeater_base.php`, `form_side_group_repeater_jquery.php` hidden 필드 name을 없는 `$fieldData['attr']`로 생성 (undefined index, 잘못된 name)
- [ ] `views/builder/layout/form_side_group_repeater_base.php` 일반 입력 name이 `field` 고정 (여러 행이 같은 name으로 전송돼 마지막 행만 남음)
- [ ] `builder_form_helper.php::reformat_form_data_by_type` tabs 분기에 디버그 `print_data($item)` 잔존 (tab_index 없는 항목이 있으면 화면에 덤프 출력)
- [ ] `views/builder/layout/form_tabs.php` `fields[0]~[3]`과 탭 이름 4개(`기본`/`배너관리`/`색상 및 폰트`/`기능`) 하드코딩 (tab_index 종류가 4개 미만이면 undefined index — 탭 정의를 설정으로 이동)
- [ ] `config/extra/admin/admin_form_config.php` `form_categories_config`의 `category_srt`가 `type => 'base', subtype => 'number'` (`<input type="base">` 출력, 의도는 `type => 'number'`)

**list/filter·export**
- [ ] `api/Common::beforeList` `recordsTotal`을 필터가 적용된 `$data`로 계산 (항상 `recordsFiltered`와 같아 "전체 N건 중" 부정확)
- [ ] `builder_api_helper.php::reformat_get_data` `date_range` 프리셋이 `start_date`, `end_date`, `on_date`가 모두 채워졌을 때만 적용되는 반전 조건 (date_range 필터 사실상 미동작)
- [ ] `builder_api_helper.php::reformat_get_data` searchWord 경로가 `filter.like[category] = word` 연관배열을 넣지만 `Model_Common::setFilterLike`는 `{field, value}` 목록을 기대 (검색창 검색 무시)
- [ ] `app-page-form.js::getFilterData` 첫 조회(isFirstDraw)에서 배열에 문자열 키(`filters.like['field']`)를 넣어 jQuery 직렬화에서 사라지고 multiple select 기본값도 `where`로 들어감 (첫 화면 like/whereIn 기본값 미적용)
- [ ] `app-page-list.js::getColumnOnclick` redirect `_blank`가 `params` 키가 있을 때만 판정되고 `params`만 있고 `attrs`가 없으면 `Object.hasOwn(undefined)` TypeError (`target: '_blank'`만 지정하면 새 창 안 열림)
- [ ] `app-page-list.js::afterSelectColumnChange` `callback: $(...).DataTable().ajax.reload()`로 함수 대신 즉시 실행 (알림 전 reload, 콜백 없음)
- [ ] `app-page-list.js` `.duplicate-record` 핸들러가 정의되지 않은 `reload`를 콜백으로 넘기고 `FORM_EXIST` 분기 안에만 바인딩 (복제 후 갱신 안 됨/ReferenceError, formExist=false면 클릭 무반응)
- [ ] `app-page-list.js::getListButtons` add 버튼이 `FORM_EXIST` false이고 `PAGE_ADD_URI`가 비면 없는 `formRecord`로 `readyFrmInputs` 호출 (ReferenceError)
- [ ] `app-page-list.js::getListButtons` 커스텀 버튼에 `classes`가 없으면 `btnObj.classes.length` TypeError, 배열을 그대로 `className`에 전달 (버튼 생성 실패/클래스 문자열 오염)
- [ ] `app-page-list.js::renderColumn` `img` 셀이 파일 배열일 때 inner를 한 번만 계산 (첫 이미지만 원소 수만큼 반복)
- [ ] `app-page-list.js::renderColumn` `checkbox` 셀이 `subtype !== 'boolean'`이면 `undefined` 출력, `data === 1` 엄격 비교로 `'1'`은 미체크
- [ ] `app-page-list.js::renderSelectColumn` `data === value` 엄격 비교(옵션 키는 문자열) (숫자 값이면 현재 값 미선택)
- [ ] `MY_Builder_API::getExportsDataset` by-reference 행 번호 `$i`를 checkbox 처리 `foreach ($exploded as $i=>$v)`가 덮어씀 (`No.` 번호 틀어짐)
- [ ] `MY_Builder_API::prepareCSV` BOM 없이 `fputcsv` (Excel에서 한글 깨짐)
- [ ] `app-page-list.js::getListExports` print/pdf/copy `exportOptions.columns`가 `[3..7]`/`[1..5]`로 하드코딩된 템플릿 잔재 (엉뚱한 컬럼 출력)
- [ ] `builder_form_helper.php::reformat_filter_data`, `MY_Builder_WEB::setFilterColumns` `FILTER_BASE_COLSPAN`이 `admin_form_constants.php`에만 정의 (web 플랫폼에 filter config 추가 시 상수 미정의 에러)
- [ ] `builder_form_helper.php::get_help_block` `$data['attr']`를 기본값 없이 접근 (help_block에 attr 없으면 Notice)

**view·excel import**
- [x] `MY_Builder_WEB::getExcelHeaders` 설정을 `$this->filterConfig`에 넣고 정의되지 않은 `$config`를 검사해 항상 `show_error`, 대체 경로의 `$this->formColumns`도 excel category에서는 채워지지 않음 (excel 화면이 열리지 않음, 고쳐도 빈 배열)
- [ ] `MY_Builder_WEB::view`/`setJSVariables` view 데이터 준비 조건이 `subtype === 'view'`인데 `method_view_base_config` 기본 subtype은 `'base'`이고 그런 subtype config도 없음, 맞추면 `view_view.php`가 없어 `builder_view(".../view_view")` 실패 (`VIEW_COLUMNS`·`prepareViewData`·`app-page-view.js` 사실상 미사용 — `category === 'view'` 조건 검토)
- [ ] `MY_Builder_WEB::viewApp` subtype base view와 dashboard(`view/dashboard/base`)가 method `view` 기준으로 `builder/layout/view.php`를 잡고 이 파일이 정의되지 않은 `$viewType`·`$viewData` 참조 (notice, `builder/dashboard/index.php`는 매칭 안 됨)
- [ ] `MY_Builder_WEB::prepareViewData` `reformat_form_data_by_type($cols, $viewType)`로 viewType을 form_type 자리에 넘김 (grid여도 행 배열이 안 만들어져 `view_grid.php` `foreach ($row …)` 깨짐, `common` 항목도 걸러짐)
- [ ] `MY_Builder_WEB::prepareViewData` hidden 컬럼도 `type !== 'view'`에 걸려 `addFormScripts()` 로드 (불필요한 form 자산)
- [ ] `views/builder/layout/view_article.php` 평평한 리스트를 기대하는 `trans_formdata_dit_type()`에 `{hiddens, fields}`를 넘기고 `default`·`group` 키도 `builder_view_base`에 없음 (article subtype 렌더 실패)
- [ ] `views/builder/layout/view_comments.php` `$identifier['field']`(단건)를 쓰지만 `prepareViewData()`의 `identifier`는 배열 목록 (댓글 identifier 값 오류)
- [ ] `MY_Builder_WEB::setViewColumns` resourceConfig가 없을 때 `jsVars['FORM_DATA']`를 쓰지만 view category에서는 설정 안 됨 (undefined index), `config->get()`을 `triggerError` 기본값 true로 호출 (설정 없으면 에러 로그)
- [ ] `MY_Builder_API::transformView` 매칭 옵션이 없으면 `$options[$data->{$key}]` undefined index (option_attributes 라벨 변환 시 notice)
- [ ] `app-page-excel.js` `#excelFormSubmit` 열 매핑이 위치 기반(`excelHeaders[k-1].field`)이고 헤더는 허용 목록 포함 여부만 검사 (열 순서가 다르거나 일부만 있으면 값이 엉뚱한 컬럼에 저장)
- [ ] `app-page-excel.js::submitData` `tr.id`로 완료 행을 표시하지만 `#excelFile` 핸들러가 행에 id를 넣지 않음 (`submit-completed` 미표시, 중간 청크 실패 후 재저장 시 중복 INSERT)
- [ ] `app-page-excel.js` `#resetExcelForm` `colspan = th개수 + 2`로 이미 #·삭제 열이 포함된 수에 2를 더함 (빈 행 colspan 과다)
- [ ] `views/builder/layout/excel.php` `JSON.parse('<?=json_encode(...)?>')` 작은따옴표 안에 JSON 출력 (라벨에 `'`나 `\`가 있으면 스크립트 오류)

**API hooks·모델**
- [x] `MY_Model::getListPDO` select가 컬럼 1개면 객체 대신 값 배열을 반환 → 요청 `select[]=col` 하나(또는 식이 걸러져 하나만 남은 경우) 목록·내보내기에서 `transformView()` TypeError 500 (bizsoft 개발 서버에서 확인, 2026-10-24) → `MY_Builder_API::getListSelect()`가 컬럼 1개면 식별자 컬럼을 함께 조회
- [ ] `Model_Common::checkDuplicate` `MY_Model::where()`가 `use_yn='Y'`를 붙인 뒤 `isIncludeDeleted=false`면 `use_yn='N'`을 추가 → isUseYn 모델은 항상 0, 고유값 중복검사 무력화. `isIncludeDeleted=true`여도 `where()`의 `del_yn='N'` 기본 조건 때문에 삭제 행이 포함되지 않아 form `check_delete` 무효
- [ ] `MY_Builder_API::validateManually` `$this->request === 'post'` — RestController의 `$this->request`는 stdClass라 항상 false → 파일 컬럼의 `$_FILES` 검사 분기가 죽고, notNullList에 든 파일 컬럼은 POST 데이터에 없어 항상 `Required`로 실패
- [ ] `MY_Controller_API::beforePost/beforePut/beforePatch/beforeDelete` `input->post() ?? input->json()` — `MY_Input::post()/put()/patch()`는 빈 배열을 반환하므로 json 폴백이 동작하지 않음 (JSON 바디 API 클라이언트 사용 불가). `beforeDelete`는 `put()`을 읽어 DELETE에서 `$data`가 항상 `[]`
- [ ] `MY_Builder_API::duplicate_get` `Model->getData([], [identifier => key])` — getData는 `where` 등 조건 키만 읽으므로 조건 무시 → 요청한 행이 아니라 테이블 첫 행을 복제
- [ ] `Model_Common::setCondition` `orLike` 키를 `$this->orLike($table, $val)`로 호출 → `orLike($table, $keys, $vals)` 인자 부족 ArgumentCountError
- [ ] `MY_Model::addDataPDO/modDataPDO/addListPDO` 실패 시 rollback 없이 `db->error()` 배열(truthy)을 반환 → 호출부(`MY_Builder_API::addData`)가 성공으로 보고 insert id 자리에 배열 기록, 트랜잭션 미종료 (db_debug=FALSE 환경)
- [ ] `api/Common::transformView` `created_id`/`updated_id`를 로그인 id 문자열로 덮어써 이후 user_id로 쓰는 코드가 깨짐: `Comments::transformList`가 parent 호출 뒤 `created_id`로 작성자 조회·`mine_yn`·`is_admin` 계산 → 최상위 댓글은 항상 실패. 사용자가 soft delete되면 `->id` on null Warning
- [ ] `Comments::afterAddData` 존재하지 않는 hook 이름(실제는 `afterAdd`) → 댓글/답글 알림 메시지가 생성되지 않음
- [ ] `Articles::transformView` 없는 메서드 `parent::viewAfter()` 호출 → Articles 목록·상세 Fatal
- [ ] `Articles::afterModify`만 첨부 업로드 처리, `afterAdd` 없음 → 등록 시 `uploads` 첨부 유실
- [ ] `ArticleRead::beforePut` parent 반환값 `[$key, $data]`를 dto로 사용 → where에 숫자 키 0/1이 들어가 SQL 오류, `$dto['article_id']` 미정의
- [ ] `Auth::signup_post` 정의되지 않은 `$this->Model_Child` 사용 + `beforePost()` 반환값 `[$key, $data]`를 dto로 사용 → 회원가입 불가
- [ ] `SysCode::beforeRemove/executeRemove` `$this->bigCd`는 `sml_cd === '000'`일 때만 대입 → 일반 코드 삭제 시 typed property 미초기화 Error (삭제는 이미 커밋된 뒤 500)
- [ ] `Categories::executeRemove` `$data['parent_id']`(DELETE의 `$data`는 항상 `[]`) 미정의 + `whereNot ['sml_cd' => '000']`(category에 없는 컬럼, SysCode 복붙) → SQL 오류
- [ ] `Logs::__construct`, `Auth::__construct` `noIndexMethod = true`를 parent 생성자 이후에 대입 → 검사는 `MY_Builder_API::__construct()` 안에서 이미 끝나 무효 (프로퍼티 기본값으로 선언해야 함)
- [ ] `MY_Builder_API::beforeAdd` 존재 확인이 `del_yn='N'` 기본 조건을 타서 soft delete된 같은 PK가 있으면 409 대신 DB duplicate key 오류
- [ ] `MY_Builder_API::checkDuplicate` `$dto[$model->identifier]` — 수정 시 바디에 identifier가 없거나 `checkDuplicate_get`에 다른 쿼리 키(`_mode` 등)가 있으면 Notice + `where_not_in(identifier, [null])`
- [ ] `reformat_get_data` `exceptValidateKeys`에 `_submode`가 없음 → GET에 `_submode`를 붙이면 where `_submode` 컬럼 SQL 오류 (`_remap()`은 `_submode`를 읽도록 되어 있음)
- [ ] `reformat_get_data` `if(!$value) continue` → `'0'` 값으로 where 불가 (예: `?depth=0`, `?is_show=0`)
- [ ] `MY_Builder_API::prepareExports_get` `is_null($this->Model)` — Model 미정의 컨트롤러에서는 undefined property Warning을 내고 나서야 `MODEL_IS_NOT_DEFINED` (property_exists 필요)
- [ ] `MY_Builder_API::beforeResponse` `_mode=option`으로 상세(view) 조회 시 `$data['list']` 미정의
- [ ] `MY_Builder_API::beforeDelete` 식별자 없이 DELETE하면 `getDeleteBaseRow()`의 `show_error()`로 HTML 500 (JSON 아님)
- [ ] `MY_Controller_API::response` `code` 없이 호출하면 `floor(0/10)=0` → RestController가 200으로 바꿔 HTTP 200 + `status:false, code:0` 응답

### 미구현·문서 불일치

**공통·route/method/nav**
- [ ] `BuilderCommonTrait::saveMenuList` `tbl_menu.base_auth`는 메뉴 최초 생성 때만 계산되고, 다시 계산하려면 `MenuList::reset_get()`(메뉴·권한 truncate, menu_id 재생성)뿐 → route/method 설정을 바꾼 뒤 menu_id를 유지한 채 base_auth만 재계산하는 경로 필요 (bizsoft 다운로드 export 권한 반영 때 DB 직접 수정)
- [ ] `BuilderCommonTrait::fillRouteConfigProperties` `properties.allows`를 `array_keys(methods)`로 무조건 덮어씀 (`projects`, `dashboard`의 `allows` 지정 무효 — 키 제거 또는 교집합 처리 결정)
- [ ] `builder_route_config.php` route `category`(page/api)를 읽는 코드 없음 (구 README의 type `tabs`/`rest`/`cli`, subtype `json` config도 없음)
- [ ] `builder_route_config.php` route `properties.formExist` 기본값이 있지만 route 레벨에서 읽지 않음 (method `properties.formExist`만 유효)
- [ ] `builder_route_config.php` `route_page_auth_config`, `route_modal_base_config` 정의만 있고 사용하는 route 없음 (admin `auth`는 같은 값을 직접 기입)
- [ ] `builder_method_config.php` `method_form_article_config.properties`(editor, thumbnail, attachments, seo, preview) 읽는 코드 없음
- [ ] `builder_method_config.php` 구 README의 type `grid`/`detail`/`process`, subtype `wizard`/`side` 전용 config 없음 (실제 `grid`는 form subtype), mode `import` 누락, `authenticate`/`process`는 권한 맵에 없어 항상 허용
- [ ] `builder_method_config.php` `method_list_base_config` `buttons.excel=true` 기본값 (excel method 없는 list도 base_auth I 비트 1)
- [ ] `MY_Builder_WEB::setJSVariables` list `properties.viewConfig`를 읽지만 `method_list_base_config`에 기본값·문서 없음 (`PAGE_VIEW_URI` 비우기에만 쓰는 숨은 스위치)
- [ ] `MY_Builder_WEB::setJSVariables` `LIST_PLUGIN`, `LIST_OPTIONS`, `FORM_TYPE`, `FORM_STYLE`, `VIEW_TYPE`, `VIEW_COLUMNS`를 builder JS가 읽지 않음 (`properties.plugin`, `formStyle` 무효 — 구현 또는 제거)
- [ ] `MY_Builder_WEB::prepareListData` `properties.formType`을 넘기지만 `layout/list.php`가 `'side'` 하드코딩 (리스트 formType 설정 무효)
- [ ] `MY_Builder_WEB::prepareFormData` `$formType`=type, JS `FORM_TYPE`=subtype으로 같은 이름 다른 의미 (`layout/form_{type}.php`는 base/side/modal/tabs만 있어 auth/menu/article은 subPage 직접 지정 필요)
- [ ] `builder_nav_config.php` `builder_nav_top_*`, `admin_nav_top`, `authParams`, `isLogin` 읽는 코드 없음, `isSubMenu`는 설정해도 재계산
- [ ] `builder_nav_config.php` 로드 플래그 오타 `buider_nav_config_loaded`
- [ ] `MY_Builder_WEB::setupPlatformRedirect` `notAllowAccess`의 `'common'`은 `setRouteConfig()` return + `checkLoggedIn()` "Incorrect Access"가 먼저 처리해 실효 없음
- [ ] `web_base_config.php` `notAllowAccess`, `navDefaultParams` 없음 (web이 builder WEB을 쓰게 되면 `config->get()` 에러 로그)
- [ ] `admin_page_config.php`, `web_page_config.php`, `builder_page_config.php`, `formProperties` 키는 읽는 코드 없는 레거시
- [ ] `MY_Builder_WEB::setMethodConfig` `?? method_config` fallback은 `methods`가 항상 있어 도달 불가
- [ ] `MY_Builder_WEB::beforeViewApp` `$pageConfig`(methodConfig)를 뷰에 넘기지만 읽는 뷰 없음

**form**
- [ ] `form_attributes.form_sync` 읽는 코드 없음 (기본값 true로 모든 입력에 `data-form-sync=1`만 출력)
- [ ] `form_attributes.max_file_uploads`/`onchange_upload`/`onchange_preview` 참조 코드 없음
- [ ] `form_attributes.list_onclick`/`list_click`/`list_click_params`/`list_sorter_params` 참조 코드 없음 (`list_onclick` 기본값 `'download'`만 존재)
- [ ] `form_attributes.dup_check`/`btn_params` 주석 처리된 코드(`app-page-form-event.js`)에서만 참조
- [ ] `app-page-form-event.js` `input, textarea` 핸들러 `text_type`이 `eng`/`num`/`eng|num`만 처리 (`email` 등 무시)
- [ ] `MY_Controller::getOptions` option_type `api`/`script` 분기 없음 → `[]`
- [ ] `option_attributes.onload`/`onchange`/`render(callback)`, `option_data.select.extra`(→`data-*`) 미구현 (`render`는 `{id,text,add}` 컬럼 매핑만)
- [ ] `builder_form_config.php` `builder_form_base_option_attributes.option_stack='vertical'` 미사용 (`get_admin_form_choice`는 `form_attributes.option_stack`, 기본 horizon을 읽음)
- [ ] `group_attributes.repeater_type='manual'` 대응 뷰 `form_side_group_repeater_manual.php` 없음
- [ ] 페이지 base 폼의 `group_attributes.type='base'` 그룹 뷰(`form_base_group_base.php`) 없음, 미추적 `views/builder/layout/form_group_base.php`는 빈 파일
- [ ] `views/builder/layout/form_base_custom_dropzone-single.php` 빈 파일 (custom/dropzone-single 미구현)
- [ ] `type=textarea, subtype=quill` PHP 분기 없음 → 일반 textarea, `builder_form_helper.php::get_admin_form_textarea`는 dead code
- [ ] `type=text, subtype=cleave-version` `get_admin_form_attributes`에 분기 없어 `.cleave` 클래스 미부여 (`setCleave` 분기 미실행)
- [ ] `MY_Builder_API::reorder_patch` GET 파라미터만 검사 후 아무것도 안 함 (`list_sorter` 정렬 저장 안 됨) (추가: 파라미터가 있으면 아무 응답 없이 빈 200)
- [ ] `MY_Builder_API::deleteRepeater_patch` `DATA_DELETED`만 응답하는 스텁 (컨트롤러 오버라이드 필요하다는 점이 코드에 명시 안 됨)
- [ ] `MY_Builder_WEB::beforeViewApp` dropzone 에셋을 subtype이 정확히 `dropzone`일 때만 로드 (`dropzone-full` 사용 시 에셋 누락)

**list/filter·export**
- [ ] `builder_list_config.php::builder_list_base` `format` 키를 JS/PHP 어디서도 읽지 않고 렌더링은 `type`으로 함 (구 README의 `list_attributes.format` 설명과 다름 — 키 제거 결정)
- [ ] `app-page-list.js::renderColumn` class 키는 `classes`(배열)이고 구 README의 `class_name`은 읽지 않음
- [ ] `MY_Builder_WEB::setListColumns` base 키는 item top-level 값이 `list_attributes`를 덮어써 `list_attributes`의 onclick/render/type 등이 무시됨 (merge 순서 변경 검토)
- [ ] `MY_Builder_WEB::getListColumns` WEB은 form config `list => true` fallback이 없고 list config가 없으면 `show_error` (API와 동작 다름)
- [ ] `app-page-list.js::getColumnOnclick` `popup` kind 미구현(빈 onclick) (구 README의 iframe 모달 동작 없음)
- [ ] `app-page-list.js::openViewModal` `getData()` 후 `console.log`만 하는 스텁이고 `view_modal.php`를 포함하는 곳 없음 (onclick view에 `attrs`가 없거나 `PAGE_VIEW_URI`가 없으면 상세 보기 불가)
- [ ] `app-page-list.js::renderColumnHTML` `bs` kind는 `attrs` 1단계 키만 `data-bs-{key}`로 부여 (구 README의 중첩 `attrs.attrs` 미지원)
- [ ] `app-page-list.js::renderSelectColumn` `onchange`는 함수명 문자열만 동작, `{kind, attrs, params}` dict는 `[object Object]`
- [ ] `app-page-list.js` DataTable `columnDefs` `render.params`가 없으면 `null` 전달 (구 README의 "this 전달" 설명과 다름)
- [ ] `MY_Builder_WEB::getListColumns` list 컬럼의 `option_attributes.option_field`가 무시되고 항상 `field`로 옵션 조회 (필터와 동작 불일치)
- [ ] `MY_Builder_WEB::setJSVariables` `LIST_CHEKBOX` 체크박스 선택값을 읽는 코드 없음 (일괄 삭제 등 미구현)
- [ ] `builder_filter_config.php::builder_filter_config` `buttons` 키, `app-page-form.js::getFilterData` `filters.text` 사용처 없음 (제거 또는 구현)
- [ ] `app-page-list.js` DataTable `ajax.data`가 `order/columns/search`를 보내지 않음 (`order` 옵션 무의미, 컬럼 정렬·`orderable` 설정 불가 — 서버 정렬 미구현)
- [ ] `MY_Builder_WEB::setJSVariables` `LIST_PAGING` 항상 `true` 하드코딩 (페이징 끄기 불가)
- [ ] `MY_Builder_API::transformView` 옵션 텍스트 치환용 `transTargetKeys`를 채우는 API 컨트롤러 없음 (list/view config의 `option_attributes` 라벨 변환 사실상 미동작)

**view·excel import**
- [ ] `builder-common.js` `appPlugins.view` 훅이 `app-page-view.js::applyViewData`에서 `console.log`만 함 (필드별 커스텀 렌더 불가)
- [ ] `builder_method_config.php` `method_excel_base_config.properties`(templateDownload, preview, validateBeforeImport) 읽는 코드 없음
- [ ] `builder_method_config.php` `method_view_base_config` `properties.formConfig/formType`을 view에서 읽는 코드 없음
- [ ] `builder_view_config.php` `builder_view_config` 템플릿(`hiddens/fields/actions/buttons`, `actions[].onclick`)을 어디서도 읽지 않음 (예시로만 존재, 실제 구조와 다름)
- [ ] `MY_Builder_WEB::setJSVariables` list `actions.excel`이 `buttons.excel`과 병합되며 덮어써져 효과 없음 (`admin_route_config.php`의 `actions.excel=false` 무의미)
- [ ] `admin_view_config.php`, `web_view_config.php`에 실제 `view_*_config` 예시 없음, 저장소에 `excel` method route와 `excel_*_config`도 없음 (동작 검증 수단 없음)

**API hooks·모델**
- [ ] `MY_Builder_API::beforePut` `uploadFileInList()` 호출 없음, `MY_Input::put()`은 urlencoded만 파싱 → PUT으로 파일·multipart 불가 (POST와 동작 차이)
- [ ] `MY_Builder_API::beforePatch` 기본값·검증·고유값 검사·업로드 모두 없음 (인라인 수정 전용이라는 점이 코드/문서에 명시 안 됨)
- [ ] `MY_Builder_API::afterAdd/afterModify/afterRemove` `beforeResponse()` 반환값을 버림 → 등록·수정·삭제 응답은 가공 불가(list/view만 반영)
- [ ] `MY_Builder_API` 프로퍼티 `table`, `notNullList`, `nullList`, `strList`, `intList` 선언만 있고 어디서도 채우지 않음. `MY_Model`에 `isUpdatedId` 없음 — `updated_id`는 `isCreatedId`를 따름
- [ ] `MY_Builder_API::__construct` `validateMessages`, `validateCallback`, `transTargetKeys`를 `[]`로 덮어씀, 템플릿/bizsoft 어디서도 설정하지 않음. `validateCallback` 메서드는 인자 없이 호출되어 대상 값을 받지 못함
- [ ] `Categories::__construct` `$this->fileList` 재지정은 업로드에 영향 없음(`uploadFileInList()`는 `model->fileList` 사용), `$this->methods['list_post']`는 존재하지 않는 메서드
- [ ] `api/Common::message_read_patch` 공통 클래스에 있어 모든 엔티티 API에 `/message_read/{id}` 노출, `Model_Message` 없는 파생 프로젝트에선 오류

### 구조
- [ ] `MY_Builder_WEB::__construct` `admin_base_config.php`가 builder base와 같은 내용으로 최상위 교체 (base config 깊은 병합 필요 — TODO 주석 존재)
- [ ] `builder_route_helper.php::fill_config_properties` 빈 배열로 상위 기본값을 끌 방법이 없음 (키마다 `false` 필요 — `null`/명시적 리스트 등 끄기 규칙 정의)
- [ ] `MY_Builder_WEB::setMethodConfig` `category` 없는 method 항목은 내용이 통째로 버려지고 `method_base_config`로 대체 (경고 없이 설정 무시)
- [x] `MY_Builder_WEB::setMethodConfig` `category: 'action'`은 레이어링을 생략하는데 `setJSVariables()`가 `actions`/`buttons`/`properties.identifier`를 요구 (action 전용 기본값 config 필요) → `method_base_config`로 빈 키를 채우도록 수정
- [ ] `MY_Builder_API::__construct` method/nav config를 로드하지 않아 `getMethodList()`·`MenuList::options_get()`이 원본 route를 별도 규칙으로 해석 (WEB 레이어링 결과와 불일치)
- [ ] 설정 이름 규칙 이원화: WEB `BuilderColumnsTrait::setConfigList`는 `snakeize(class)`와 탐색 순서, `MY_Builder_API::__construct`는 `form_/list_/view_{strtolower(class)}_config`, `MY_Builder_WEB::setViewColumns`는 `view_{resourceConfig}_config`(둘 다 `setConfigList` 미적용) (camelCase 클래스·resourceConfig 사용 시 WEB/API가 다른 설정을 읽음)
- [ ] `MY_Builder_API::setProperties` API `formConfig`가 `builder_form_base`와만 얕게 병합되고 form/group_attributes 기본값 미병합 (WEB `BuilderColumnsTrait::setFormColumn`과 해석 다름)
- [ ] `MY_Builder_WEB::setFormData`(group_attributes 첫 항목부터 누적) vs `MY_Builder_API::validateFormRules`(마지막 항목만) 그룹 속성 해석 불일치 (헬퍼 하나로 통일)
- [ ] `views/builder/layout/form_base_custom_dropzone-full.php`, `app-page-form.js::applyFrmValuesByCategory` id `thumbnail-dropzone-wrapper`, data-field `thumbnail` 하드코딩 (field 기반으로 일반화)
- [ ] 그룹 뷰(`addr_daum`/`new_password`/`reply_list`/`repeater_*`)와 custom 뷰가 `get_group_field_name` 대신 `$item['field']`를 name으로 사용 (envelope/repeater name 규칙이 `form_side_group_base`에서만 적용)
- [ ] `MY_form_helper.php::form_options_by_field` 설정에 옵션이 없으면 `Option 1/2` 더미로 대체 (설정 누락이 가려짐 — 빈 배열 + 경고)
- [ ] `MY_Builder_WEB::prepareListData` formExist=false일 때 `allows`에 없는 action을 `$actions`에서 제거하지만 뷰/JS는 `LIST_ACTIONS`만 사용 (죽은 코드)
- [ ] `app-page-common.js::prepareExports` `encodeURIComponent`한 `token`을 만들고 쓰지 않고 원본 filename을 URL에 사용 (인코딩 누락)
- [ ] `app-page-list.js` DataTable 정보 문구, `views/builder/layout/filter.php` 필터 버튼 문구(`검색`/`초기화`) 하드코딩 (다국어 미지원)
- [ ] `MY_Builder_API::executeAdd/executeModify` 모델 순서를 `['Model_Parent','Model','Model_Child']`로 하드코딩, `beforePost/beforePut/executeRemove/isForeignKeyGeneratedByPreviousModel`은 `getModelPipeline()` 사용 → 파이프라인 오버라이드 시 검증·삭제와 저장 순서 불일치
- [ ] 파이프라인·hook 전반에 트랜잭션 경계 없음: 모델별 `addData/modData`가 각자 commit → `Model` INSERT 실패 시 `Model_Parent` 행 잔존, `afterAdd/afterModify`에서 오류 응답해도 저장은 유지 (bizsoft `Templates::afterAdd`, `Apis::beforeRemove`의 선삭제 패턴)
- [ ] `MY_Builder_API::beforePost/beforePut` `validateFormRules()`가 파이프라인 모델 수만큼 같은 formConfig로 반복 실행
- [ ] `api/Common::transformView` 행마다 사용자 2회 조회(N+1), `Comments::transformList`(행당 3회+), `Partners::transformView`(회사 수만큼) → 목록 크기에 비례한 쿼리 수
- [ ] `MY_Builder_API::loggingUserlog` 모든 GET(DataTable 재조회·옵션 조회 포함)마다 user_log INSERT, `log_id`는 RestController 요청 로그 id(`rest_enable_logging` 꺼져 있으면 `''`). `MenuList::beforeResponse`는 parent를 부르지 않아 메뉴 변경 로그 누락
- [ ] `MY_Builder_API::checkIdentifierExist` non-AI 모델은 URL key를 무시하고 GET의 PK 파라미터만 사용 → `SysCode::beforeRemove`처럼 `$key`와 `idData`가 서로 다른 출처에서 올 수 있음
