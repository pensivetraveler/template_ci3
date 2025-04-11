### packages

설치 완료

- "symfony/dotenv": "^5.4"
- "ext-gettext": "*"
- "chriskacerguis/codeigniter-restserver": "^3.1"
- "nyholm/psr7": "^1.8"
- "sentry/sentry": "^4.9"

설치 미완료

- "symfony/http-client": "^7.1"

symfony/http-client 는 php 8.1 이상을 원하는데 반해, 현재 서버 php는 7.4 버젼이라 설치하지 않음.


---

### form_config

##### common
- field : (string) db field 명
- label : (string) label에 노출시킬 텍스트

##### form
- form : (bool) form에 노출시킬지 여부
- rules : (string) ci3 혹은 custom rules, | 로 구분
- category : input category
	- identifier : db primary key.
	- unique : unique 값. duplicate check button이 함께 노출.
	- common : created_dt, updated_dt 등 table별 공통 column.
	- hidden : hidden 처리할 것들
	- text : text type. type opt 에서 분기
	- select : select type. type opt 에서 분기
	- tel : tel type. type opt 에서 분기
	- date : date type. type opt 에서 분기
	- number : number type. type opt 에서 분기
	- file : file type. type opt 에서 분기
	- textarea : textarea type. type opt 에서 분기
	- custom : 임의로 설정할 type들. ex) youtube 등. custom인 경우, form_side_{custom} 과 같이 layout 호출
	- addr : 주소
- type : (string) category 에 따라 type이 분기됨
	- tel : cleave
	- date : flatpickr
	- select : selectpicker, select2
	- textarea : textarea, autosize, quill
	- addr : zipcode, addr1, addr2
	- file : file
	- custom : youtube, version
- icon : (string) form field에 노출시킬 icon
- group : (string) group으로 묶을 경우, group 명. group이 있을 경우, form_side_{group} 과 같이 layout 호출
- default : (mixed) default value

##### list
- 'list' : (bool) list에 노출시킬지 여부
- list_attributes : list 관련 attrs (field, label은 form의 그것을 가져오게 된다.)
	- field : (string) form의 field와 동일
	- label : (string) form의 label과 동일
	- format : (string) 노출시킬 형태
		- text (default)
		- icon
		- img
		- button
		- select
	- icon : (string) ico class name. 있다면 ```<i>``` tag와 함께 노출
	- text : (string) 노출될 텍스트. 해당 값이 없을 시 api 중 field에 해당하는 값 노출.
	- class_name : (string or list) 추가할 className
	- onclick : (dict) onclick 시 실행시킬 프론트 메소드(js) 정의
		- kind : (string) onclick 시 실행할 동작 유형.
			- popup : bootstrap full page modal popup 이며, data 변수로 전달된 link를 iframe에 넣어 실행.
			- redirect : 특정 링크로 이동
			- download : downloader url redirect
			- bs : onclick 시 부트스트랩 이벤트 실행
			- 이외 : callback js function name
		- attrs : (dict) list element의 attributes 정의
			- target : (string)
				- kind 가 redirect 인 경우, target 이 _self 라면 location.href, target 이 _blank 라면 window.open
				- kind 가 bs 라면, data-bs-target
			- toggle : (string) 부트스트랩 이벤트 타입 (data-bs-toggle)
			- attrs : (dict) data-* 형태로 전달할 attrs
		- params : (dict) callback function에 전달할 params.
	- onchange : (dict) onchange 시 실행시킬 프론트 메소드(js) 정의
		- kind : (string) onchange 시 실행할 동작 유형.
			- 이외 : callback js function name
		- attrs : (dict) list element의 attributes 정의
		- params : (dict) callback function에 전달할 params.
	- render : (mixed) render 시 실행시킬 커스텀 프론트 메소드(js) 정의
		- callback : (string) js function name
		- params : (mixed) function에 전달할 param. 없다면 this 전달

##### select
- option_attributes : category가 select일 경우, select 관련 attrs
	- option_type : (string) option 을 불러올 type 정의
		- yn : 하드코딩된 option. option_data 값이 없을 경우, ```['Y', 'N']```을 옵션으로 반환.
		- static : 하드코딩된 option. option_static의 내용을 그대로 사용.
		- field : form_options_by_field 함수에 정의된 option 반환.
		- code : 하드코딩된 option. option_static의 내용을 그대로 사용.
		- api : api 로 프론트에서 호출. 'onload' 가 정의되어있지 않다면 공통 url(ex /api/common/options ) 등의 url로 option을 호출.
		- model : 콘트롤러에서 model 및 model 내 method로 호출.
		- db : 콘트롤러에서 db obj를 이용해 호출.
		- custom : 콘트롤러에서 임의 처리
		- script : view 에서 onload 시 임의 처리
	- option_data
		- type 이 static 인 경우
			- return할 option dict or list
		- type 이 code 인 경우
			- params : (dict) api 호출 시 사용할 param 정의
		- type 이 api 인 경우
			- url : (string) api 호출 시 사용할 url
			- method : (string) api 호출 method. 값이 없다면 get
			- params : (dict) api 호출 시 사용할 param 정의
		- type 이 model 인 경우
			- model : (string) 실행할 model
			- method : (string) 실행할 method
			- params : (string) method에 전달할 파라미터
			- select : (dict) id, text 에 해당하는 field
				- id : (string) option value
				- text : (string) option label
				- extra : (array) extra values. 해당 값이 있을 경우 option에 data-{extra_name} 과 같이 html 작성.
		- type 이 db 인 경우
			- table : (string) 조회 테이블
			- params : (string) 조회 조건
			- select : (dict) id, text 에 해당하는 field
				- id : (string) option value
				- text : (string) option label
				- extra : (array) extra values. 해당 값이 있을 경우 option에 data-{extra_name} 과 같이 html 작성.
		- type 이 custom 인 경우
			- method : (string) callback method name
			- params : (array) method 호출 시 전달할 param
		- type 이 script 인 경우
			- method : (string) callback method name
			- params : (array) method 호출 시 전달할 param
	- onload : (dict) onload 시 실행시킬 프론트 메소드(js) 정의
		- callback : (string) js function name
		- params : (mixed) function에 전달할 param. 없다면 this 전달
	- onchange : (dict) onchange 시 실행시킬 프론트 메소드(js) 정의
		- callback : (string) js function name
		- params : (mixed) function에 전달할 param. 없다면 this 전달
	- render : (dict) option render 할 때마다 실행할 프론트 메소드(js) 정의
		- callback : (string) js function name
		- params : (mixed) function에 전달할 param. 없다면 this 전달

##### group_attributes
- option_attributes : category가 group일 경우, group 관련 attrs
	- envelope_name : (bool) group 내 field name 을 group_name 으로 감쌀 것인지 여부
		- values
			- true : name 을 group_name 으로 감싼다. ex) `group_name[field_name]`
			- false : name 을 group_name 으로 감싸지 않는다. ex) `field_name`
		- default : false
	- group_repeater : (bool) group 내 field name 를 array 구조로 가져갈 것인지 여부
		- values
			- true : name 을 array 구조로 한다. ex) `group_name[0][field_name]`
			- false : name 을 array 구조로 하지 않는다. ex) `group_name[field_name]`
		- default : false
	- repeater_type : (string) group_repeater true 일 경우, repeater type
		- values
			- manual : 직접 repeater 구현
			- jquery : jquery repeater 이용
		- default : 'manual'
	- repeater_id : (string) repeater의 identifer에 해당하는 field. 삭제 등에 기준 값이 됨.
		- values : group 내 field name 중 하나와 동일해야 함.
		- default : '' (repeater의 identifier 없음)
	- label : (string) group 을 대표하는 라벨 값
		- default : ''
	- form_text : (string) group 을 대표하는 form text
		- default : ''
	- type : (string) group 의 view type
		- values : 직접 view 를 만들 경우, 해당 view file 명을 입력.
		- default : 'base'
	- key : (string) view 내에서 해당 field 를 대표하는 이름
		- values : field 를 가리키는 변수명.
		- default : '' (해당 값이 없을 경우, field 값을 계승.)


### Form Life Cycle

##### Life Cycle 각 stage 소개

- preparePlugins : 플러그인 초기화
- resetFrmInputs : form input/select/textarea 에 대해 value 제거. radio/checkbox의 경우 checked 해제. default 값이 있을 경우 적용.
- readyFrmInputs : mode 값에 따라 display 여부가 변경되는 것들 등을 조정.
- fetchFrmValues : ajax 로 api 로부터 데이터 가져오기
- applyFrmValues : 가져온 데이터를 form 내 각 input 에 value 대입
- refreshPlugins : input 값이 변경된 이후, 플러그인 refresh
- checkFrmValues : submit 하기 전 formvalidation 진행. validation 완료 하면 true
- transFrmValues : form data 객체를 생성 후, api 로 submit

##### mode 별 Life Cycle 흐름

- mode add 인 경우
	- preparePlugins -> resetFrmInputs -> readyFrmInputs -> refreshPlugins -> checkFrmValues -> transFrmValues
- mode edit 인 경우
	- preparePlugins -> resetFrmInputs -> readyFrmInputs -> fetchFrmValues -> applyFrmValues -> refreshPlugins -> checkFrmValues -> transFrmValues

### Attributes

##### 자동 생성되는 attr
- data-input-changed
	- default : false
		- resetFrmInputs, applyFrmValues 에서 input 값 변경 이후, 해당 node 의 type 이 hidden 이 아니면서 data-detect-changed 값이 false인 경우에 false 적용
	- 'true' or '1' 인 경우, FormData 객체에 append
	- 'false' or '' 인 경우, FormData 객체에 append 하지 않음

##### 현재 정의된 attr
- data-reset-value
	- default : 1
		- form_config 에서 조정
	- 'true' or '1' 인 경우, resetFrmInputs 시 값 초기화.
	- 'false' or '' 인 경우, resetFrmInputs 시 값 초기화 하지 않음.\
- data-detect-changed
	- default : 1
		- form_config 에서 조정
	- 'true' or '1' 인 경우, data-input-changed 의 값에 따라 Form Data 객체에 append
	- 'false' or '' 인 경우, data-input-changed 값 관계 없이 Form Data 객체에 append
- data-dup-check
	- default : {key: "", value, ""}
		- 중복 체크를 할 대상 input 에만 적용
- data-original-value
	- default : ""
	- duplicationCheck 시 버튼을 활성/비활성하는 근거. 해당 값과 현재 값이 다르면 버튼 활성. 같다면 비활성
- data-text-type
- data-with-btn
- data-view-mod

### extra attributes
- data-addr-id


### Form Attributes

attributes 와 form attributes 간의 차이는
attributes 의 값들은 input들의 native attr 이나,
form attributes 는 커스텀으로 정의된 attr 로,
그 값에 따라 script 혹은 php 에서 커스텀 로직이 발생됨.

- form_sync : bool|string
	- desc : form data 전달받을 시, 데이터 연동 여부 조정.
	- values
		- 1, "true" : applyFrmValues(js) 시 값 적용
		- 0, "false" : applyFrmValues(js) 시 값 적용 하지 않음.
	- default : 1
- reset_value : bool|string
	- desc : resetFrmInput(js) 에서 값 초기화 여부 조정.
	- values
		- 1, "true" : resetFrmInputs(js) 시 값 초기화
		- 0, "false" : resetFrmInputs(js) 시 값 초기화 하지 않음.
	- default : 1
- detect_changed : bool
	- desc : getFormData(js) 에서 FormData 객체에 append 할지 말지에 대한 여부 조정.
	- values
		- 1, "true" : data-input-changed 값에 따라 FormData 객체에 append
		- 0, "false" : data-input-changed 값에 관계없이 FormData 객체에 append
	- default : 1
- view_mod : string|array
	- desc : readyFrmInputs(js) 에서 해당 input을 노출할지 말지 여부 조정
	- values
		- string type : add|edit|view
		- array : [add, edit, view]
	- default : '' => 모두 선택을 의미
- text_type : string|array
	- desc : onkeydown event를 감지하여 키보드 입력 방지
	- values
		- string type : eng|num|email ...
		- array type : [eng, num, email, ...]
	- default : '' => 감지 안함.
- with_btn : bool|string
	- desc : input 에 button 이 함께 존재하는지 아닌지 여부를 조정
	- values
		- 1, "true" : button layout 함께 노출
		- 0, "false" : button layout 없음
	- default : 0
- btn_type : string
	- desc : input과 함께 렌더링될 btn type 정의
	- values
		- dup_check : 중복 검사 버튼. 실행 시 duplicationCheck(js) 실행
		- addr_daum : 카카오 주소찾기 iframe 활성화. 클릭 시 findAddress(js) 실횅
	- default : '' => 해당 값 없을 시, button 레이아웃 보여주지 않음.
- dup_check :
- btn_params : string|array
	- desc : btn 클릭 시, js function에 전달될 function
	- values : ex) json string {"key":"id", "title":"아이디"}
- max_file_uploads : string
	- desc : 파일 업로드의 최대 개수를 제한
	- values
		- `#` : 값이 있을 경우, 리스트의 개수를 동적으로 체크하여 개수 제한 alert을 노출.
	- default : '' -> 제한 없음
- onchange_upload : bool|string
	- desc : file input에 파일 로드 시, 바로 업로드할지 말지 여부 조정
	- values
		- 1, "true" : file input에 onchange event 발생 시, 바로 업로드 후 file_id 를 form에 append 하며 input file 객체를 동적 생성
		- 0, "false" : onchange evnet 발생 관계 없이, 해당 input node 가 files 객체를 유지함
	- default : 0
- onchange_preview : bool|string
	- desc : file input에 파일 로드 시, 프리뷰 섹션이 생성됨과 함께 이미지 보여줄지에 대한 여부 조정
	- values
		- 1, "true" : file input에 onchange event 발생 시, preview 섹션이 활성화.
		- 0, "false" : preview 섹션 비활성
	- default : 0
- with_list : bool|string
	- desc : input 에 list 가 함께 존재하는지 아닌지 여부를 조정
	- values
		- 1, "true" : list layout 함께 노출
		- 0, "false" : list layout 없음
	- default : 0
- list_type : string
	- desc : input과 함께 렌더링될 list type 정의
	- values
		- dup_check : 중복 검사 버튼. 실행 시 duplicationCheck(js) 실행
		- addr_daum : 카카오 주소찾기 iframe 활성화. 클릭 시 findAddress(js) 실횅
	- default : '' => 해당 값 없을 시, button 레이아웃 보여주지 않음.
- list_click : string
	- desc : list 내 요소 클릭 시의 실행 type
	- values
		- download : 파일 다운로드
		- youtube : youtube modal 실행
		- player : audio player modal 실행
		- video : video player modal 실행
	- default : '' => click 요소 없음.
- list_click_params : string
	- desc : list_onclick 에서 활용될 param 정의
	- values
		- download 시, {key: file_id}
		- youtube 시, {key: link}
		- player 시, {key: file_id}
	- default : '' => click 시 param 전달 없음.
- list_delete : string
	- desc : 삭제 유형. 해당 값이 있을 경우 delete btn 노출.
	- values
		- "file" : file 삭제. deleteFile callback
		- "attachment" : attachment 삭제. deleteAttachment callback
	- default : '' => 미노출
- list_sorter : bool|string
	- desc : sorter 기능을 추가할지 말지 결정. sort 변경 시, sorterChange(js) api 동작
	- values
		- 1, "true" : sorter 추가
		- 0, "false" : sorter 추가 X
	- default : false
- list_sorter_params : string
	- desc : list_sorter 에서 활용될 param 정의
	- values
		- {key : article_id (sort 의 기준이 되는 identifier 필드명 기재)}
