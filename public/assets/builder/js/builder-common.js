const appPlugins = {
	list: {
		datatable: {
			preDrawCallback: null,
			initComplete: null,
			drawCallback: null,
			rowCallback: null,
		}
	},
	view: {},
	form: {},
};

if(!Object.hasOwn(window[appName], 'ERRORS'))
	window[appName].ERRORS = [];

window.onerror = function(event, source, lineno, colno, error) {
	let message, stack = [];

	if(event instanceof jQuery.Event) {
		message = colno;
		source = elementToSelector(event.target);
		lineno = colno = null;
	}else{
		message = event;
	}
	if(error !== undefined && Object.hasOwn(error, 'stack')) stack = error.stack;

	// setJavascriptErrorModal(message, source, lineno, colno, stack);
	window[appName].ERRORS.push(getJavascriptErrorObject(message, source, lineno, colno, stack));

	// 후킹 작업 후 true를 리턴하면 기본 동작을 막을 수 있음
	return false;
};

window.onload = function(){
	setTimeout(function() {
		showErrorModal(document.getElementById('errorModal'), window[appName].ERRORS);
	}, 500)
};

// 순수 JS 버전
document.addEventListener('click', function(e) {
	// 클릭된 요소가 a 태그이면서 target="popup"일 때
	const el = e.target.closest('a[target="_popup"]');
	if (!el) return;

	e.preventDefault();                      // 기본 동작(새 탭/새 창 열기) 막기
	window.open(el.href, '_blank',          // 새 팝업 창 열기
		'width=600,height=400');    // 옵션(크기 등) 지정 가능
});