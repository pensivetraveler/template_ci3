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

if(!window[appName].hasOwnProperty('ERRORS'))
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
	if(error !== undefined && error.hasOwnProperty('stack')) stack = error.stack;

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
