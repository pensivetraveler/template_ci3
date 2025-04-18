function makeEllipsis(input, length) {
	if (input.length > length) {
		return input.substring(0, length) + '...';
	}
	return input;
}

function dateAfterCertainPeriod(startDate, period) {
	const newDate = new Date(startDate);
	newDate.setDate(newDate.getDate() + period);
	return newDate.getFullYear()+'-'+(newDate.getMonth()+1).toString().padStart(2, '0')+'-'+newDate.getDate()
}

function numberWithCommas(x) {
	return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

function maxLengthCheck(object){
	if (object.value.length > object.maxLength){
		object.value = object.value.slice(0, object.maxLength);
	}
}

function getNodeIndexInParent(node) {
	if(node === null) return -1;
	var children = node.parentNode.childNodes;
	var num = 0;
	for (var i=0; i<children.length; i++) {
		if (children[i]==node) return num;
		if (children[i].nodeType==1) num++;
	}
	return -1;
}

function getTextLength(str) {
	var len = 0;
	for (var i = 0; i < str.length; i++) {
		if (escape(str.charAt(i)).length == 6) {
			len++;
		}
		len++;
	}
	return len;
}

function getJSON(url) {
	let obj;
	$.ajax({
		async: false,
		url: url,
		type: 'get',
		dataType: 'json',
		success: function(json){
			obj = json
		},
	})
	return obj;
}

function getParam(sname) {
	let params = location.search.substr(location.search.indexOf("?") + 1);
	let sval = "";
	params = params.split("&");
	for (let i = 0; i < params.length; i++) {
		let temp = params[i].split("=");
		if ([temp[0]] == sname) { sval = temp[1]; }
	}
	return sval;
}

function fireEvent(eventTargetId, eventName) {
	const obj = new Event(eventName)
	document.querySelector(`#${eventTargetId}`).dispatchEvent(obj);
}

function resize() {
	const width = window.innerWidth;
	const height = window.innerHeight;
	const screenRatio = width/height;
	let ratio = 1;
	if(screenRatio > 1.6){
		ratio = height/1200;
	}else{
		ratio = width/1920;
	}
	// document.body.setAttribute('style', `zoom:${ratio}`)
	document.querySelector('body > div').setAttribute('style', `zoom:${ratio}`)
}

function initScale()   {
	var ress = navigator.userAgent;
	if (ress.indexOf("Android 1.", 0) > -1 ){
		if (ress.indexOf("480", 0) > -1 ) { // 480x800
			var per = 0.5226824457593688;
		} else if(ress.indexOf("600", 0) > -1 ) { // 600 x 1024
			var per = 0.681
		} else if(ress.indexOf("1280", 0) > -1 ) { // 800 x 1280
			var per = 0.631
		}
	} else {
		var dh = window.innerHeight;
		var dw = window.innerWidth;
		var cw = parseInt( $('#contentsArea').css('width') );
		var ch = parseInt( $('#contentsArea').css('height') );
		var per = dw/cw;
		var per2 =dh/ch;
		if(per > per2 ){
			per = per2;
		}
		var gapH = ( dh - (ch*per) )/2;
		var gapW = ( dw - (cw*per) )/2
	}
	$("body > div").css('transform', 'scale('+per+','+per+')');
	$('body').css('margin-top', gapH );
	$('body').css('margin-left', gapW );
}

function resizeScale() {
	var dh = $(window).height();
	var dw = $(window).width();
	var cw = 1010; // 내가 표시하고자 하는 컨텐츠, 예를 들어 동영상의 폭
	var ch = 613;   // 내가 표시하고자 하는 컨텐츠, 예를 들어 동영상의 넓이
	var per = dw/cw; // 내가 표시하고자 하는 컨텐츠의 현재 윈도우 대비 비율
	var per2 =dh/ch;
	if(per > per2 ){
		per = per2;
	}
	var gapH = ( dh - (ch*per) )/2;
	var gapW = ( dw - (cw*per) )/2
	$('body').css('margin-top', gapH ); // 세로방향의 중앙에 위치하게 할때 사용합니다.
	$('body').css('margin-left', gapW );
	$('meta[name=viewport]').attr('content','"width=device-width, initial-scale='+per+', maximum-scale=2.0, user-scalable=yes"');
}

/****************************************************/

var setCookie = function(name, value, exp = 1) {
	var date = new Date();
	date.setTime(date.getTime() + exp*24*60*60*1000);
	document.cookie = name + '=' + value + ';expires=' + date.toUTCString() + ';path=/';
};

var getCookieList = function() {
	const obj = {};
	if(document.cookie.indexOf('=') !== -1){
		if(document.cookie.indexOf(';') !== -1){
			for(const cookie of document.cookie.split(';')){
				let pair = cookie.split('=');
				const key = pair[0].trim();
				const val = pair[1].trim();
				obj[key] = val;
			}
		}else{
			let list = document.cookie.split('=');
			obj[list[0]] = list[1];
		}
	}
	return obj;
}

var getCookie = function(name) {
	var value = document.cookie.match('(^|;) ?' + name + '=([^;]*)(;|$)');
	return value? value[2] : null;
};

var delCookie = function(name) {
	document.cookie = name + '=; expires=Thu, 01 Jan 1999 00:00:10 GMT;';
}

var delCookieList = function() {
	const list = getCookieList();
	for(const key of Object.keys(list)){
		delCookie(key);
	}
}

var getURLQueryList = function() {
	const obj = {}
	if(location.search !== ''){
		const queryList = location.search.substring(1, location.search.length).split('&');
		for(const query of queryList) {
			const splitted = query.split('=');
			obj[splitted[0]] = splitted[1];
		}
	}
	return obj;
}

function loadHTML(target, url) {
	$.get(url,{},function(data, status) {$(target).html(data);});
}

function listAllEventListeners() {
	const allElements = Array.prototype.slice.call(document.querySelectorAll('*'));
	allElements.push(document);
	allElements.push(window);

	const types = [];

	for (let ev in window) {
		if (/^on/.test(ev)) types[types.length] = ev;
	}

	let elements = [];
	for (let i = 0; i < allElements.length; i++) {
		const currentElement = allElements[i];
		for (let j = 0; j < types.length; j++) {
			if (typeof currentElement[types[j]] === 'function') {
				elements.push({
					"node": currentElement,
					"type": types[j],
					"func": currentElement[types[j]].toString(),
				});
			}
		}
	}

	return elements.sort(function(a,b) {
		return a.type.localeCompare(b.type);
	});
}

function absorbEvent_(event) {
	var e = event || window.event;
	e.preventDefault && e.preventDefault();
	e.stopPropagation && e.stopPropagation();
	e.cancelBubble = true;
	e.returnValue = false;
	return false;
}

function preventLongPress(node) {
	node.ontouchstart = absorbEvent_;
	node.ontouchmove = absorbEvent_;
	node.ontouchend = absorbEvent_;
	node.ontouchcancel = absorbEvent_;
}

function basename(path) {
	return path.split('/').reverse()[0];
}

function formatPercent(float) {
	const res = Math.round(float*1000)/10;
	console.log(res)
	return res;
}

function replaceCssFile(selector, replace) {
	const filename = basename(document.querySelector(selector).href);
	const path = document.querySelector(selector).href.replace(basename(document.querySelector(selector).href), '');
	document.querySelector(selector).href = path+`${replace}.css`;
}

// 함수 존재 여부를 확인하는 함수
function isFunctionExists(functionName) {
	return typeof functionName === 'function';
}

/**
 * 사용자의 입력에 따라 높이가 조절되는 textarea
 * @param textarea
 * https://joyful-development.tistory.com/36
 */
function handleResizeHeight(textarea) {
	textarea.style.height = 'auto'; //height 초기화
	textarea.style.height = textarea.scrollHeight + 'px';
}

/**
 *
 * @param dataUrl
 * @returns {Blob}
 */
function getImgFileBlob(dataUrl) {
	// base64 데이터 디코딩
	const blob = atob(dataUrl.split(',')[1]);
	const array = [];
	for (var i = 0; i < blob.length; i++) array.push(blob.charCodeAt(i));
	// Blob 생성
	return new Blob([new Uint8Array(array)], {type: 'image/png'});
}

function addFileInput(form, name, value) {
	const input = document.createElement('input');
	input.classList.add('added');
	input.name = name;
	input.value = value;
	form.appendChild(input);
}

function checkFormData(formData) {
	for (let key of formData.keys()) {
		console.log(key, ":", formData.get(key));
	}
}

function addHyphenToPhoneNumber(number) {
	if(number.length >= 9) {
		return number.replace(/[^0-9]/g, "")
			.replace(/^(\d{2,3})(\d{3,4})(\d{4})$/, `$1-$2-$3`);
	}
}

// Restricts input for the given textbox to the given inputFilter function.
function setInputFilter(textbox, inputFilter, errMsg) {
	[ "input", "keydown", "keyup", "mousedown", "mouseup", "select", "contextmenu", "drop", "focusout" ].forEach(function(event) {
		textbox.addEventListener(event, function(e) {
			if (inputFilter(this.value)) {
				// Accepted value.
				if ([ "keydown", "mousedown", "focusout" ].indexOf(e.type) >= 0){
					this.classList.remove("input-error");
					this.setCustomValidity("");
				}

				this.oldValue = this.value;
				this.oldSelectionStart = this.selectionStart;
				this.oldSelectionEnd = this.selectionEnd;
			}
			else if (this.hasOwnProperty("oldValue")) {
				// Rejected value: restore the previous one.
				this.classList.add("input-error");
				this.setCustomValidity(errMsg);
				this.reportValidity();
				this.value = this.oldValue;
				this.setSelectionRange(this.oldSelectionStart, this.oldSelectionEnd);
			}
			else {
				// Rejected value: nothing to restore.
				this.value = "";
			}
		});
	});
}

function getYuotueId(youtubeLink) {
	const pattern = /^(?:https?:\/\/|\/\/)?(?:www\.|m\.)?(?:youtu\.be\/|youtube\.com\/(?:embed\/|v\/|watch\?v=|watch\?.+&v=))([\w-]{11})(?![\w-])/;
	const matches = url.match(pattern);
	return matches ? matches[1] : '';
}

function elementToSelector(element) {
	// 태그 이름을 소문자로 가져오기
	let selector = element.tagName.toLowerCase();

	// id가 존재하면 추가
	if (element.id) {
		selector += `#${element.id}`;
	}

	// 클래스가 존재하면 클래스 리스트 추가
	if (element.classList.length > 0) {
		selector += '.' + Array.from(element.classList).join('.');
	}

	return selector;
}

function redirect(uri = null) {
	if(uri === null) {
		back();
	}else{
		location.href = uri;
	}
}

function reload() {
	location.reload();
}

function back() {
	history.back();
}

function logFormData(formData) {
	if(!formData instanceof FormData) {
		console.warn(`logFormData : 'obj' is not instance of FormData. Please check the 'obj'.`);
	}else{
		const json = {};
		for (const [key, value] of formData.entries()) {
			if(value instanceof File){
				json[key] = value.name;
			}else{
				json[key] = value;
			}
		};
		console.table(json);
	}
}

function isValidSelector(selector) {
	try {
		return document.querySelector(selector) !== null;
	} catch (e) {
		return false; // 유효하지 않은 선택자
	}
}

function isAttributeValueTrue(node, attr) {
	let val = node.getAttribute(attr);
	if(isNumeric(val)) val = parseInt(val);
	return Boolean(val);
}

function arrayToBrackets(arr) {
	let result = arr[0];
	for (let i = 1; i < arr.length; i++) {
		result += `[${arr[i]}]`;
	}
	return result;
}

function arrayToHyphens(arr) {
	return arr.join('-');
}

function dynamicForm(data, action, method = 'get', target = '_blank', submit = false, formAttrs = {}) {
	const form = document.createElement('form');
	form.method = method;
	form.action = action;
	form.target = target;

	for (const [name, value] of Object.entries(data)) {
		const input = document.createElement('input');
		input.name = name;
		input.value = value;
		form.appendChild(input);
	}

	if(submit) {
		document.body.appendChild(form);
		form.submit();
		form.remove();
	}else{
		return form;
	}
}

// MIME 타입으로 확장자 찾기 함수
async function getExtensionsForMimeType(mimeType) {
	const url = 'https://cdn.jsdelivr.net/gh/jshttp/mime-db@master/db.json';
	try {
		const response = await fetch(url);
		const mimeDb = await response.json();

		if (mimeDb[mimeType] && mimeDb[mimeType].extensions) {
			return mimeDb[mimeType].extensions;
		} else {
			console.log(`No extensions found for ${mimeType}`);
			return null;
		}

	} catch (error) {
		console.error('Error fetching MIME DB:', error);
		return null;
	}
}

function nl2br(str) {
	return str.replace(/\n/g, '<br>');
}